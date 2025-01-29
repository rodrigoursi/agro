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


  $ultimoId = grabarCompra($oDatos, $data);
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
  if(!isset($_GET['ultimoId'])) {
    echo json_encode($arrRes);
    return;
  }
  $id = $_GET['ultimoId'];
  $arrRes = getCompras($oDatos, $id);
  if($id != '0') {
    $listaProd = getDetalle($oDatos, $id);
    // divido el array en dos pedaso para insertar en el medio el array de productos
    $arr1 = array_slice($arrRes[0], 0, 5, true);
    $arr2 = array_slice($arrRes[0], 5, null, true);
    // uno los array
    $lista = [];
    foreach ($listaProd as $index => $prod) {
      foreach ($prod as $key => $value) {
        $nuevaClave = $key . ' ' . ($index + 1);
        $listaProd[$index][$nuevaClave] = $value;
        unset($listaProd[$index][$key]);
      }
      $arr1 = $arr1 + $listaProd[$index];
    }

    $arrRes[0] = $arr1 + $arr2;
  }


  echo json_encode($arrRes);
}



function grabarCompra($oDatos, $data) {
  //var_dump(array_column($data['productos']['total']));
  //var_dump($data['productos']);
  $importe = array_sum(array_column($data['productos'], 'total'));
  $proveedor = $data['id_proveedor'];
  $productos = $data['productos'];
  $condicion = $data['id_condicion'];
  $moneda = $data['id_moneda'];
  $filas = 1;
  try {
    $oDatos->iniciarTrans();
    if($data['id_proveedor'] == 'nuevo') {
      $filas++;
      $sql = "INSERT INTO proveedores(razon_social, direccion, localidad) VALUES(?, '', '')";
      $oDatos->agregarParametro($data['proveedor']);
      $oDatos->setearConsulta($sql);
      $proveedor = $oDatos->ejecutarQuery();
      $oDatos->vaciarParametros();
    }

    /*if($data['id_producto'] == 'nuevo') {
      $filas++;
      $sql = "INSERT INTO productos(descripcion, unidad_medida) VALUES(?, (SELECT id FROM unidades_medida WHERE nombre = 'UNIDAD'))";
      $oDatos->agregarParametro($data['producto']);
      $oDatos->setearConsulta($sql);
      $producto = $oDatos->ejecutarQuery();
      $oDatos->vaciarParametros();
    }*/

    if($data['id_condicion'] == 'nuevo') {
      $filas++;
      $sql = "INSERT INTO condiciones(nombre) VALUES(?)";
      $oDatos->agregarParametro($data['condicion']);
      $oDatos->setearConsulta($sql);
      $condicion = $oDatos->ejecutarQuery();
      $oDatos->vaciarParametros();
    }

    if($data['id_moneda'] == 'nuevo') {
      $filas++;
      $sql = "INSERT INTO monedas(nombre) VALUES(?)";
      $oDatos->agregarParametro($data['moneda']);
      $oDatos->setearConsulta($sql);
      $moneda = $oDatos->ejecutarQuery();
      $oDatos->vaciarParametros();
    }

    $sql = 'INSERT INTO compras(factura, importe, moneda, proveedor, condicion, usuario) VALUES (
      ?, ?, ?, ?, ?, ?)';
    $parameters = [$data['nFact'], $importe, $moneda, $proveedor, $condicion, $data['usuario']];
    $oDatos->setearConsulta($sql);
    $oDatos->agregarTodosParametros($parameters);
    $resultado = $oDatos->ejecutarQuery();
    $oDatos->vaciarParametros();
    $parameters = [];

    $sql = 'INSERT INTO compraDet(producto, precio_unit, cantidad, precio_total, compra) VALUES';
    $values = '';
    foreach ($productos as $producto) {

      $idProd = $producto['id_producto'];
      if($idProd == 'nuevo') {
        $filas++;
        $sql2 = "INSERT INTO productos(descripcion, unidad_medida) VALUES(?, (SELECT id FROM unidades_medida WHERE nombre = 'UNIDAD'))";
        $oDatos->agregarParametro($producto['producto']);
        $oDatos->setearConsulta($sql2);
        $idProd = $oDatos->ejecutarQuery();
        $oDatos->vaciarParametros();
      }

      $filas++;
      if($values == '') $values .= '(?, ?, ?, ?, ?)';
      else $values .= ', (?, ?, ?, ?, ?)';
      //if($values == '') $values .= "($producto['id_producto'], $producto['precio'], $producto['cantidad'], $producto['total'], $resultado)";
      //else $values .= ", ($producto['id_producto'], $producto['precio'], $producto['cantidad'], $producto['total'], $resultado)";
      array_push($parameters, $idProd, $producto['precio'], $producto['cantidad'], $producto['total'], $resultado);
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

function getCompras($oDatos, $id) {
  $sql = "SELECT compras.id AS Id, u.nombre_apellido AS Usuario, fecha AS Fecha, p.razon_social AS Proveedor, factura AS 'Nº factura',
  importe AS 'Total compra', mon.nombre AS 'Moneda', con.nombre AS Condicion
  FROM compras INNER JOIN usuarios AS u ON u.id = compras.usuario
  INNER JOIN proveedores AS p ON p.id = compras.proveedor
  INNER JOIN condiciones AS con ON con.id = compras.condicion
  INNER JOIN monedas AS mon ON mon.id = compras.moneda";
  if($id != '0') {
    $sql .= " WHERE compras.id = ?";
    $oDatos->agregarParametro($id);
  }
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  $oDatos->vaciarParametros();
  return $resultado;
}

function getDetalle($oDatos, $id) {
  $sql = "SELECT descripcion AS Producto, cantidad AS Cantidad, precio_total AS 'Precio total del producto' FROM compraDet INNER JOIN productos ON productos.id = producto WHERE compra = ?";
  $oDatos->agregarParametro($id);
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  $oDatos->vaciarParametros();
  return $resultado;
}
