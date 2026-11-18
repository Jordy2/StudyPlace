<?php

require_once __DIR__ . '/../../lib/cors.php';
require_once __DIR__ . '/../../lib/autoload.php';

use App\Contenedor;

$contenedor = new Contenedor();
$controlador = $contenedor->obtenerPerfilControlador();
$controlador->manejar();
