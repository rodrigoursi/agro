<?php
require_once('./../../php/accesoDatos.php');
require_once('./../../php/funciones.php');

$status = 'error';
$mensaje = 'Error desconocido';
$respuesta = ['status' => $status, 'result' => array('mensaje' => $mensaje)];



$oDatos = new AccesoDatos();
$mensConexion = $oDatos->getConexion();
$mensaje = 'Metodo no soportado.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // El método de la solicitud es POST

  $json = file_get_contents('php://input');
  $data = json_decode($json, true);
  if($data == null) {
    echo json_encode($respuesta);
    return;
  }

  // codigo para validar token inicio
  $oToken = buscarToken($data['tk']);
  if(!$oToken) {
    $respuesta['tk']['tk_valido'] = false;
    $respuesta['tk']['motivo'] = 'No se encontro token';
    echo json_encode($respuesta);
    return;
  }
  try {
    $tokenVencido = verificarCaducidadToken($oToken);
    if($tokenVencido) {
      $respuesta['tk']['tk_valido'] = false;
      $respuesta['tk']['motivo'] = 'Token vencido. Vuelva a iniciar sesion';
      echo json_encode($respuesta);
      return;
    }
  } catch (\Exception $e) {
    $respuesta['tk']['tk_valido'] = false;
    $respuesta['tk']['motivo'] = $e->getMessage();
    echo json_encode($respuesta);
    return;
  }

  $respuesta['tk']['tk_valido'] = true;

  //codigo para validar token fin

  $data['usuario'] = $oToken['usuario'];

  $ultimoId = grabarRetiro($oDatos, $data);
  if($ultimoId > 0 and is_numeric($ultimoId)) {
    $status = 'success';
    $mensaje = 'Compra cargada con exito';
  }
  else $mensaje = 'ocurrio un error: ' .$ultimoId;
  $respuesta = ['tk' => array('tk_valido' => true), 'status' => $status, 'result' => array('mensaje' => $mensaje, 'ultimoId' => $ultimoId)];
  echo json_encode($respuesta);
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $arrRes = array();
  if(isset($_GET['ultimoId'])) {
    $id = $_GET['ultimoId'];
    $arrRes = getRetiros($oDatos, $id);
    if($id != '0') {
      $listaProd = getDetalle($oDatos, $id);
      foreach ($listaProd as $index => $prod) {
        $arrRes[0][$prod['Producto']] = 'Cantidad ' . $prod['Cantidad'];
      }
    }

    //var_dump($arrRes[0]);
    echo json_encode($arrRes);
  }
  if(isset($_GET['id_prov'])) {
    $idProv = $_GET['id_prov'];
    $arrRes = getFactByProv($oDatos, $idProv);
    echo json_encode($arrRes);
  }
  if(isset($_GET['id_compra'])) {
    $idCompra = $_GET['id_compra'];
    $arrRes = getProdByFact($oDatos, $idCompra);
    echo json_encode($arrRes);
  }

}

function grabarRetiro($oDatos, $data) {
  //$proveedor = $data['id_proveedor'];
  $productos = $data['productos'];
  $destino = $data['id_destino'];
  $lote = $data['id_lote'];
  $idCompra = $data['id_nFactSel'];
  $filas = 1;
  try {
    $oDatos->iniciarTrans();
    /*
    if($data['id_proveedor'] == 'nuevo') {
      $filas++;
      $sql = "INSERT INTO proveedores(razon_social, direccion, localidad) VALUES(?, '', '')";
      $oDatos->agregarParametro($data['proveedor']);
      $oDatos->setearConsulta($sql);
      $proveedor = $oDatos->ejecutarQuery();
      $oDatos->vaciarParametros();
    }

    if($data['id_producto'] == 'nuevo') {
      $filas++;
      $sql = "INSERT INTO productos(descripcion, unidad_medida) VALUES(?, (SELECT id FROM unidades_medida WHERE nombre = 'UNIDAD'))";
      $oDatos->agregarParametro($data['producto']);
      $oDatos->setearConsulta($sql);
      $producto = $oDatos->ejecutarQuery();
      $oDatos->vaciarParametros();
    }*/

    if($data['id_destino'] == 'nuevo') {
      $filas++;
      $sql = "INSERT INTO destinos(nombre) VALUES(?)";
      $oDatos->agregarParametro($data['destino']);
      $oDatos->setearConsulta($sql);
      $destino = $oDatos->ejecutarQuery();
      $oDatos->vaciarParametros();
    }

    if($data['id_lote'] == 'nuevo') {
      $filas++;
      $sql = "INSERT INTO lotes(nombre, destino) VALUES(?, ?)";
      $oDatos->agregarParametro($data['lote']);
      $oDatos->agregarParametro($data['id_destino']);
      $oDatos->setearConsulta($sql);
      $lote = $oDatos->ejecutarQuery();
      $oDatos->vaciarParametros();
    }

    $sql = 'INSERT INTO retiros(remito, lote, usuario, compra) VALUES (?, ?, ?, ?)';
    $parameters = [$data['remito'], $lote, $data['usuario'], $data['id_nFactSel']];
    $oDatos->setearConsulta($sql);
    $oDatos->agregarTodosParametros($parameters);
    $resultado = $oDatos->ejecutarQuery();
    $oDatos->vaciarParametros();
    $parameters = [];
    $productosCantRes = getProdByFact($oDatos, $idCompra);

    $sql = 'INSERT INTO retiroDet(producto, cantidad, retiro) VALUES';
    $values = '';
    foreach ($productos as $producto) {

      $idProd = $producto['id_producto'];
      $cant = $producto['cantidad'];
      $cantPerd = verificarCantidadPermitida($idProd, $productosCantRes, $cant);
      if($cantPerd < 0) {
        $oDatos->cancelarTrans();
        return "La cantidad que deseas retirar del producto {$producto['producto']} supera el pendiente a retirar";
      }
      if($cantPerd == 0) {
        $resulFilasAfect = cambiarEstadoRetirado($oDatos, $data['id_nFactSel'], $idProd);
        if($resulFilasAfect != 1) {
          $oDatos->cancelarTrans();
          return "Las filas afectadas deberian ser 1 y son $resulFilasAfect, CAMPO RETIRADO de la tabla compraDet";
        }
        $filas++;
      }
      $filas++;
      if($values == '') $values .= '(?, ?, ?)';
      else $values .= ', (?, ?, ?)';
      array_push($parameters, $idProd, $cant, $resultado);
    }

    $sql .= $values;
    $oDatos->setearConsulta($sql);
    $oDatos->agregarTodosParametros($parameters);
    $oDatos->ejecutarQuery();

    $oDatos->finalizarTrans($filas);
  } catch (\Exception $e) {
    $resultado = $e->getMessage();
    $oDatos->cancelarTrans();
  }
  finally {
    $oDatos->cerrarConexion();
  }
    return $resultado;
}

function getRetiros($oDatos, $id) {
  //var_dump($id);
  $sql = "SELECT retiros.id AS Id, u.nombre_apellido AS Usuario, retiros.fecha AS Fecha, p.razon_social AS Proveedor, remito AS 'Nº remito',
  des.nombre AS Destino, lot.nombre FROM retiros
  INNER JOIN compras AS c ON c.id = retiros.compra
  INNER JOIN proveedores AS p ON p.id = c.proveedor
  INNER JOIN usuarios AS u ON u.id = retiros.usuario
  INNER JOIN lotes AS lot ON lot.id = retiros.lote
  INNER JOIN destinos AS des ON des.id = lot.destino";
  if($id != '0') {
    $sql .= " WHERE retiros.id = ?";
    $oDatos->agregarParametro($id);
  }
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  return $resultado;
}

function getDetalle($oDatos, $id) {
  $sql = "SELECT descripcion AS Producto, cantidad AS Cantidad FROM retiroDet INNER JOIN productos ON productos.id = producto WHERE retiro = ?";
  $oDatos->agregarParametro($id);
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  $oDatos->vaciarParametros();
  return $resultado;
}

function getFactByProv($oDatos, $idProv) {
  $sql = 'SELECT DISTINCT compras.id, factura FROM compras INNER JOIN compraDet ON compras.id = compra WHERE proveedor = ? AND retirado = 0';
  $oDatos->setearConsulta($sql);
  $oDatos->agregarParametro($idProv);
  $resultado = $oDatos->ejecutarLectura();
  $oDatos->vaciarParametros();
  return $resultado;
}

function getProdByFact($oDatos, $idCompra) {
  $sql = 'SELECT compraDet.producto, (SELECT descripcion FROM productos where id = compraDet.producto) AS descripcion,
  SUM(compraDet.cantidad - ifnull(retiroDet.cantidad, 0)) AS cantidad FROM retiroDet INNER JOIN retiros ON retiros.id = retiro
  RIGHT JOIN compraDet ON compraDet.compra = retiros.compra AND compraDet.producto = retiroDet.producto
  WHERE compraDet.compra = ? AND retirado = 0 GROUP BY compraDet.producto HAVING cantidad > 0';
  $oDatos->setearConsulta($sql);
  $oDatos->agregarParametro($idCompra);
  $resultado = $oDatos->ejecutarLectura();
  $oDatos->vaciarParametros();
  return $resultado;
}

function verificarCantidadPermitida($idProd, $productosCantRes, $cant) {
  // uso array_values para q me reindexe el indice a 0, porq si el indice del encontrado
  $producto = array_values(array_filter($productosCantRes, function($prod) use ($idProd) {
    return $prod['producto'] == $idProd;
  }));
  return $producto[0]['cantidad'] - $cant;
}

function cambiarEstadoRetirado($oDatos, $idCompra, $idProd) {
  $sql = 'UPDATE compraDet SET retirado = 1 WHERE compra = ? AND producto = ?';
  $oDatos->setearConsulta($sql);
  $oDatos->agregarParametro($idCompra);
  $oDatos->agregarParametro($idProd);
  $resultado = $oDatos->ejecutarQuery('update');
  $oDatos->vaciarParametros();
  return $oDatos->getUltFilasAfec();
}
