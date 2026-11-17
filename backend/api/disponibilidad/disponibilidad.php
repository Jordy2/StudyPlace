<?php

require_once __DIR__ . '/../../lib/cors.php';
require_once __DIR__ . '/../../lib/autoload.php';

use App\Contenedor;

$contenedor = new Contenedor();
$contenedor->obtenerDisponibilidadControlador()->manejar();
