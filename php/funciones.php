<?php

function buscarToken($token) {
  $aRespuesta = getToken($token);
  if($aRespuesta) {
     return $aRespuesta[0];
  }
  else return false;
}

function getToken($token) {
  //var_dump($token);
  $oDatos = new AccesoDatos();
  $sql = 'SELECT id, usuario, fecha, ADDDATE(fecha, cantidad_dias) AS caducidad FROM usuarios_token WHERE token = ? AND estado = 1';
  $oDatos->setearConsulta($sql);
  $oDatos->agregarParametro($token);
  $resultado = $oDatos->ejecutarLectura();
  $oDatos->cerrarConexion();
  return $resultado;
}

function verificarCaducidadToken($token) {
  $fecha = new DateTime($token['caducidad']);
  $fechaActual = date('Y-m-d H:i:s');
  $fechaActual = new DateTime($fechaActual);
  if($fecha < $fechaActual) {
    try {
      return cambiarEstadoToken($token['id']);
    } catch (\Exception $e) {
      throw $e;
    }
  } else return false;
}

function cambiarEstadoToken($id) {
  try {
    $oDatos = new AccesoDatos();
    $sql = "UPDATE usuarios_token SET estado = 0 WHERE id = ?";
    $oDatos->setearConsulta($sql);
    $oDatos->agregarParametro($id);
    $res = $oDatos->ejecutarQuery('update');
    $oDatos->cerrarConexion();
    if($res > 0 and is_numeric($res)) return true;
    else throw new Exception('No se pudo cambiar el estado del token');
  } catch (\Exception $e) {
    throw $e;
  }
}
