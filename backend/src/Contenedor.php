<?php

namespace App;

use App\Config\Conexion;
use PDO;

class Contenedor {
  private ?PDO $pdo = null;

  public function __construct(?PDO $pdo = null) {
    $this->pdo = $pdo;
  }

  public function obtenerConexion(): PDO {
    if ($this->pdo === null) {
      $this->pdo = Conexion::obtenerConexion();
    }

    return $this->pdo;
  }
}
