<?php

if (!function_exists('respuestaJson')) {
  function respuestaJson(bool $exito, mixed $datos = null, int $codigo = 200, ?string $mensaje = null): void {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');

    $cuerpo = ['exito' => $exito];

    if ($mensaje !== null) {
      $cuerpo['mensaje'] = $mensaje;
    }

    if ($datos !== null) {
      $cuerpo['datos'] = $datos;
    }

    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }
}

if (!function_exists('respuestaError')) {
  function respuestaError(string $mensaje, int $codigo = 400, mixed $detalles = null): void {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');

    $cuerpo = [
      'exito' => false,
      'error' => $mensaje,
    ];

    if ($detalles !== null) {
      $cuerpo['detalles'] = $detalles;
    }

    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }
}
