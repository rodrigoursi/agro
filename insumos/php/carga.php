<?php
require_once('./../../php/accesoDatos.php');
require_once('./../../php/funciones.php');

$respuesta = [];
$json = file_get_contents('php://input');
$data = json_decode($json, true);
if($data == null) {
  echo json_encode($respuesta);
  return;
}

// codigo para validar token inicio
$oToken = buscarToken($data['tk']);

if(!$oToken) {
  //var_dump($oToken);
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


$oDatos = new AccesoDatos();
$mensConexion = $oDatos->getConexion();

if($data['proveedores']) {
  $arrRes = getProveedores($oDatos);
  $respuesta['PROVEEDORES'] = $arrRes;
  $arrRes = [];
}
if($data['productos']) {
  $arrRes = getProductos($oDatos);
  $respuesta['PRODUCTOS'] = $arrRes;
  $arrRes = [];
}
if($data['condiciones']) {
  $arrRes = getCondiciones($oDatos);
  $respuesta['CONDICIONES'] = $arrRes;
  $arrRes = [];
}
if($data['destinos']) {
  $arrRes = getDestino($oDatos);
  $respuesta['DESTINOS'] = $arrRes;
  $arrRes = [];
}
if($data['lotes']) {
  $arrRes = getLotes($oDatos);
  $respuesta['LOTES'] = $arrRes;
  $arrRes = [];
}
if($data['facturas']) {
  $arrRes = getFacturas($oDatos);
  $respuesta['FACTURAS'] = $arrRes;
  $arrRes = [];
}

if($data['monedas']) {
  $arrRes = getMonedas($oDatos);
  $respuesta['MONEDAS'] = $arrRes;
  $arrRes = [];
}

if($data['mpago']) {
  $arrRes = getMpago($oDatos);
  $respuesta['MPAGO'] = $arrRes;
  $arrRes = [];
}

echo json_encode($respuesta);

function getProveedores($oDatos) {
  $sql = 'SELECT id, razon_social FROM proveedores';
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  return $resultado;
}

function getProductos($oDatos) {
  $sql = 'SELECT id, descripcion FROM productos';
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  return $resultado;
}

function getCondiciones($oDatos) {
  $sql = 'SELECT * FROM condiciones';
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  return $resultado;
}

function getDestino($oDatos) {
  $sql = 'SELECT * FROM destinos';
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  return $resultado;
}

function getLotes($oDatos) {
  $sql = 'SELECT * FROM lotes';
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  return $resultado;
}

function getFacturas($oDatos) {
  $sql = 'SELECT id, factura FROM compras';
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  return $resultado;
}

function getMonedas($oDatos) {
  $sql = 'SELECT id, nombre FROM monedas';
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  return $resultado;
}

function getMpago($oDatos) {
  $sql = 'SELECT id, nombre FROM mediosPagos';
  $oDatos->setearConsulta($sql);
  $resultado = $oDatos->ejecutarLectura();
  return $resultado;
}
