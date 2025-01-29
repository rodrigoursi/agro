<?php
require_once __DIR__ .'./config.php';

class AccesoDatos {
  private $db;
  private $motor = DB_MOTOR;
  private $host = DB_HOST;
  private $dbname = DB_NAME;
  private $user = DB_USER;
  private $pass = DB_PASS;
  private $ok = '';
  private $error;
  private $parameters = array();
  private $sql = '';
  private $filasAfectadas = 0;
  private $ultimasFilasAfect = 0;

  function __construct() {
    try {
      $this->db = new PDO("$this->motor:host=$this->host;dbname=$this->dbname", $this->user, $this->pass);
      $this->ok = 'ok';
    } catch (PDOException $e) {
      $this->ok = 'error';
      $this->error = $e->getMessage();
      die();
    }
  }

  function getConexion() {
    if($this->ok == 'ok') {
      return 'CONEXION A LA BASE DE DATOS EXITOSA...!';
    }
    return $this->error;
  }

  function getUltFilasAfec() {
    return $this->ultimasFilasAfect;
  }

  function agregarParametro($parametro) {
    $this->parameters[] = $parametro;
  }

  function vaciarParametros() {
    $this->parameters = array();
  }

  function agregarTodosParametros($parametros) {
    $this->parameters = $parametros;
  }

  function setearConsulta($consulta) {
    $this->sql = $this->db->prepare($consulta);
  }

  function cerrarConexion() {
    // Cerrar la conexión PDO
    $this->db = null;

    // Liberar el statement preparado si existe
    if ($this->sql instanceof PDOStatement) {
        $this->sql->closeCursor();
        $this->sql = null;
    }
  }

  function iniciarTrans() {
    $this->db->beginTransaction();
  }

  function finalizarTrans($filas) {
    if($this->filasAfectadas == $filas) {
      $this->db->commit();
      return;
    }
    $this->db->rollBack();
    return;
  }

  function cancelarTrans() {
    $this->db->rollBack();
  }

  function ejecutarLectura() {
    //$preparado = $this->db->prepare($this->sql);
    $this->sql->execute(array_values($this->parameters));
    $this->vaciarParametros();
    return $this->sql->fetchAll(PDO::FETCH_ASSOC);
  }

  function ejecutarQuery($tipo = 'insert') {
    try {
      //var_dump($this->sql);
      //var_dump($this->parameters);
      $this->sql->execute(array_values($this->parameters));
      $this->filasAfectadas += $this->sql->rowCount();
      if($tipo == 'update') {
        $this->ultimasFilasAfect = $this->sql->rowCount();
        return ($this->filasAfectadas > 0) ? $this->filasAfectadas : -1;
      }
      return ($this->filasAfectadas > 0) ? $this->db->lastInsertId() : -1;
    } catch (PDOException $e) {
      throw new PDOException("Error en la consulta: " . $e->getMessage(), (int)$e->getCode(), $e);
    }
  }
}
