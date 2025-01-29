<?php
require_once('./../../php/accesoDatos.php');
require_once('./../../php/funciones.php');

$status = 'error';
$mensaje = 'Error desconocido';
$respuesta = ['status' => $status, 'result' => array('mensaje' => $mensaje)];



$oDatos = new AccesoDatos();
$mensConexion = $oDatos->getConexion();
$mensaje = 'Metodo no soportado.';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $arrRes = array();
  if(isset($_GET['ultimoId'])) {
    $id = $_GET['ultimoId'];
    $arrRes = getPagos($oDatos, $id);
    if($id != '0') {
      $listaCheq = getDetalle($oDatos, $id);
      // divido el array en dos pedaso para insertar en el medio el array de productos
      $arr1 = array_slice($arrRes[0], 0, 3, true);
      $arr2 = array_slice($arrRes[0], 3, null, true);

      $lista = [];
      foreach ($listaCheq as $index => $cheq) {
        foreach ($cheq as $key => $value) {
          $nuevaClave = $key . ' ' . ($index + 1);
          $lista[$nuevaClave] = $value;
        }

      }
      $arrRes[0] = $arr1 + $lista + $arr2;
      //var_dump($arrRes[0]);
    }

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

  //$arrPago = getVerificarPago($oDatos, $data['id_nFactSel']);
  $ultimoId = grabarPago($oDatos, $data);
  if($ultimoId > 0 and is_numeric($ultimoId)) {
    $status = 'success';
    $mensaje = 'Compra cargada con exito';
  }
  else $mensaje = 'ocurrio un error: ' .$ultimoId;
  $respuesta = ['tk' => array('tk_valido' => true), 'status' => $status, 'result' => array('mensaje' => $mensaje, 'ultimoId' => $ultimoId)];
  echo json_encode($respuesta);
}


function getFactByProv($oDatos, $idProv) {
  /*$sql = 'SELECT compras.id, factura, compras.importe - SUM(pagos.importe) as saldo FROM pagos
  INNER JOIN compras ON pagos.compra = compras.id INNER JOIN proveedores AS prov ON prov.id = proveedor
  WHERE proveedor = ? GROUP BY compras.id, factura, compra HAVING saldo > 0';*/
  $sql = 'SELECT compras.id, factura, IFNULL(compras.importe - SUM(pagos.importe), compras.importe) as saldo FROM pagos
  RIGHT JOIN compras ON pagos.compra = compras.id INNER JOIN proveedores AS prov ON prov.id = proveedor
  WHERE proveedor = ? GROUP BY compras.id, factura, compra HAVING saldo > 0';
  $oDatos->setearConsulta($sql);
  $oDatos->agregarParametro($idProv);
  $resultado = $oDatos->ejecutarLectura();
  //var_dump($resultado);
  $oDatos->vaciarParametros();
  return $resultado;
}

function grabarPago($oDatos, $data) {
  //$proveedor = $data['id_proveedor'];
  $idUsuario = $data['usuario'];
  $cheques = $data['cheques'];
  $idCompra = $data['id_nFactSel'];
  $idMpago = $data['id_mpago'];
  $filas = 1;

  try {
    $oDatos->iniciarTrans();
    if($idMpago == 'nuevo') {
      $filas++;
      $sql = "INSERT INTO mediosPagos(nombre) VALUES(?)";
      $oDatos->agregarParametro($data['mpago']);
      $oDatos->setearConsulta($sql);
      $idMpago = $oDatos->ejecutarQuery();
      $oDatos->vaciarParametros();
    }
    //verificarPago();

    $sql = 'INSERT INTO pagos(compra, importe, usuario, medioPago) VALUES (?, ?, ?, ?)';
    $parameters = [$idCompra, $data['importe'], $idUsuario, $idMpago];
    $oDatos->setearConsulta($sql);
    $oDatos->agregarTodosParametros($parameters);
    $resultado = $oDatos->ejecutarQuery();
    $oDatos->vaciarParametros();
    $parameters = [];

    if(count($cheques) > 0) {
      $sql = 'INSERT INTO cheques(fechaPago, numero, importe, pago, usuario) VALUES';
      $values = '';

      $total = (float)$data['importe'];
      foreach ($cheques as $cheque) {
        $filas++;
        $total -= (float)$cheque['importeCheq'];
        if($values == '') $values .= '(?, ?, ?, ?, ?)';
        else $values .= ', (?, ?, ?, ?, ?)';
        array_push($parameters, $cheque['fechaPago'], $cheque['numCheq'], $cheque['importeCheq'], $resultado, $idUsuario);
      }

      $sql .= $values;
      $oDatos->setearConsulta($sql);
      $oDatos->agregarTodosParametros($parameters);
      $oDatos->ejecutarQuery();
      $oDatos->vaciarParametros();
      if($total != 0) {
        $oDatos->cancelarTrans();
        return 'El importe total de los cheques no coincide con el total del saldo de la compra';
      }
    }

    $pagoDesborda = getVerificarPago($oDatos, $idCompra); // si devuelve true el pago desborda y no es aceptable.
    if($pagoDesborda) {
      $oDatos->cancelarTrans();
      return 'El importe del pago supera el saldo de lo adeudado.';
    }
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

function getPagos($oDatos, $id) {
  $sql = "SELECT pagos.id AS Id, DATE_FORMAT(pagos.fecha, '%d-%m-%Y') AS Fecha, razon_social AS Proveedor, factura AS 'FACTURA Nº', mp.nombre AS 'Medio de pago', pagos.importe AS 'Importe total del pago' FROM pagos
  INNER JOIN mediosPagos AS mp ON mp.id = medioPago INNER JOIN compras ON compras.id = compra
  INNER JOIN proveedores AS prov ON prov.id = proveedor";
  if($id != '0') {
    $sql .= " WHERE pagos.id = ?";
    $oDatos->agregarParametro($id);
  }
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  //var_dump($resultado);
  return $resultado;
}

function getDetalle($oDatos, $id) {
  $sql = "SELECT numero AS 'Numero de cheque', DATE_FORMAT(fechaPago, '%d-%m-%Y') AS 'Fecha de pago', importe AS 'Importe de cheque' FROM cheques WHERE pago = ?";
  $oDatos->agregarParametro($id);
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  $oDatos->vaciarParametros();
  return $resultado;
}

function getVerificarPago($oDatos, $id) {
  $sql = "SELECT sum(importe) AS Importe, (SELECT importe FROM compras WHERE id = ?) AS Total FROM pagos WHERE compra = ?";
  $oDatos->agregarParametro($id);
  $oDatos->agregarParametro($id);
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  //var_dump($resultado);
  $oDatos->vaciarParametros();
  $importe = (float)$resultado[0]['Importe'];
  $total = (float)$resultado[0]['Total'];
  if($importe > $total) return true;
  return false;
}
