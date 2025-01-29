<?php
require_once('./accesoDatos.php');
require_once('./funciones.php');
$oDatos = new AccesoDatos();
$mensConexion = $oDatos->getConexion();

if($_SERVER['REQUEST_METHOD'] == 'GET') {
  $token;
  $respuesta = [];
  if(isset($_GET['token'])) {
    $token = $_GET['token'];
  }
  if($token == null) {
    echo json_encode($respuesta);
    return;
  }

  // codigo para validar token inicio
  $oToken = buscarToken($token);
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

  $respuesta['data'] = getRol($oDatos, $token);
  echo json_encode($respuesta);
}

/// metodo post
if($_SERVER['REQUEST_METHOD'] == 'POST') {
  $json = file_get_contents('php://input');
  $data = json_decode($json, true);
  $respuesta = [];

  if($data == null) {
    $respuesta[] = ['estado' => 'error', 'mensaje' => 'No llega body al backend'];
    echo json_encode($respuesta);
    return;
  }

  $arrUsuario = getUsuario($oDatos, $data);
  if($arrUsuario == null) {
    $respuesta[] = ['estado' => 'error', 'mensaje' => 'Usuario o contraseña incorrecto.'];
    echo json_encode($respuesta);
    return;
  }
  $idUsuario = $arrUsuario[0]['id'];
  $token = bin2hex(random_bytes(16)) . $idUsuario;
  $idInsertado = grabarToken($oDatos, $token, $idUsuario);
  if($idInsertado == -1) {
    if (empty($respuesta)) {
      $respuesta[] = ['estado' => 'error', 'mensaje' => 'No se pudo obtener el token.'];
    }
    echo json_encode($respuesta);
    return;
  }
  $respuesta[] = ['estado' => 'ok', 'mensaje' => $data['user'], 'token' => $token];
  echo json_encode($respuesta);
}

function getUsuario($oDatos, $data) {
  $sql = 'SELECT id FROM usuarios WHERE nombre_apellido = ? AND contrasenia = ?';
  $oDatos->setearConsulta($sql);
  $oDatos->agregarParametro($data['user']);
  $oDatos->agregarParametro($data['pass']);
  $resultado = $oDatos->ejecutarLectura();
  if(count($resultado) == 1) return $resultado;
  else return null;
}

function grabarToken($oDatos, $token, $idUsuario) {
  try {
    $sql = "INSERT INTO usuarios_token(usuario, token, estado) VALUES(?, ?, 1);";
    $oDatos->setearConsulta($sql);
    $oDatos->agregarParametro($idUsuario);
    $oDatos->agregarParametro($token);
    return $oDatos->ejecutarQuery();
  } catch (DBException $e) {
    // Manejar el error de base de datos
    $respuesta[] = ['estado' => 'error', 'mensaje' => $e->getMessage()];
    return -1;
  } finally {
    $oDatos->cerrarConexion();
    }
}

function getRol($oDatos, $token) {
  $sql = 'SELECT rol.id, nombre FROM usuarios_token tk INNER JOIN usuarios AS u ON u.id = tk.usuario
  INNER JOIN rol ON rol.id = rol WHERE token = ?';
  $oDatos->setearConsulta($sql);
  $oDatos->agregarParametro($token);
  $resultado = $oDatos->ejecutarLectura();
  return $resultado;
}
