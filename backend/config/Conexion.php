<?php

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class Conexion {
  private static ?PDO $instancia = null;

  public static function obtenerConexion(?array $config = null): PDO {
    if (self::$instancia === null) {
      if ($config === null) {
        $config = require __DIR__ . '/database.php';
      }

      $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['dbname'],
        $config['charset']
      );

      $opciones = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
      ];

      try {
        self::$instancia = new PDO($dsn, $config['user'], $config['password'], $opciones);
      } catch (PDOException $e) {
        throw new RuntimeException('Error de conexión a la base de datos: ' . $e->getMessage(), (int)$e->getCode(), $e);
      }
    }

    return self::$instancia;
  }

  public static function resetearConexion(): void {
    self::$instancia = null;
  }
}
