<?php

namespace App\Controladores;

use App\Contratos\GestionUsuariosServicioInterface;
use App\Middleware\AutenticacionMiddleware;
use Throwable;

class AuditoriaControlador {
  private GestionUsuariosServicioInterface $servicio;
  private AutenticacionMiddleware $authMiddleware;

  public function __construct(
    GestionUsuariosServicioInterface $servicio,
    AutenticacionMiddleware $authMiddleware
  ) {
    $this->servicio = $servicio;
    $this->authMiddleware = $authMiddleware;
  }

  public function manejar(): void {
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($metodo !== 'GET') {
      respuestaError('Método HTTP no soportado: ' . $metodo, 405);
    }

    $this->authMiddleware->verificarRol('administrador');

    try {
      $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 100;
      respuestaJson(true, $this->servicio->listarAuditoria($limite), 200);
    } catch (Throwable $e) {
      respuestaError('Error al consultar la auditoría: ' . $e->getMessage(), 500);
    }
  }
}
