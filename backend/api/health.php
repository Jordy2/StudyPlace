<?php

require_once __DIR__ . '/../lib/cors.php';
require_once __DIR__ . '/../lib/autoload.php';

use App\Contenedor;

try {
  $contenedor = new Contenedor();
  $pdo = $contenedor->obtenerConexion();

  $stmt = $pdo->query('SELECT VERSION() AS version');
  $versionDb = $stmt->fetchColumn();

  respuestaJson(true, [
    'estado' => 'ok',
    'db' => 'conectada',
    'version_db' => $versionDb,
    'php' => PHP_VERSION,
  ], 200, 'Servicio operativo');
} catch (Throwable $e) {
  respuestaError('Error al comprobar estado del sistema: ' . $e->getMessage(), 500);
}
