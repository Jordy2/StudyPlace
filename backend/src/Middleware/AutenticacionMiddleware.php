<?php

namespace App\Middleware;

use App\Contratos\AutenticacionServicioInterface;
use App\Modelos\Usuario;

class AutenticacionMiddleware {
  private AutenticacionServicioInterface $authServicio;

  public function __construct(AutenticacionServicioInterface $authServicio) {
    $this->authServicio = $authServicio;
  }

  public function extraerToken(): ?string {
    $cabecera = null;

    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
      $cabecera = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
      $cabecera = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('getallheaders')) {
      $headers = getallheaders();
      $cabecera = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    }

    if ($cabecera === null) {
      return null;
    }

    if (preg_match('/Bearer\s+(\S+)/i', $cabecera, $coincidencias)) {
      return $coincidencias[1];
    }

    return null;
  }

  public function autenticar(): Usuario {
    $token = $this->extraerToken();

    if ($token === null) {
      respuestaError('No autenticado. Token de sesión requerido.', 401);
    }

    $usuario = $this->authServicio->validarToken($token);

    if ($usuario === null) {
      respuestaError('Sesión inválida o expirada.', 401);
    }

    if (!$usuario->esActivo()) {
      respuestaError('Acceso denegado: cuenta inactiva o suspendida.', 403);
    }

    return $usuario;
  }

  public function verificarRol(array|string $rolesPermitidos): Usuario {
    $usuario = $this->autenticar();
    $roles = is_array($rolesPermitidos) ? $rolesPermitidos : [$rolesPermitidos];

    if (!$usuario->tieneRol($roles)) {
      respuestaError('Acceso denegado: permisos insuficientes para el rol ' . $usuario->getRol() . '.', 403);
    }

    return $usuario;
  }
}
