<?php

namespace App\Controladores;

use App\Contratos\PerfilServicioInterface;
use App\Middleware\AutenticacionMiddleware;
use DomainException;
use InvalidArgumentException;
use Throwable;

class PerfilControlador {
  private PerfilServicioInterface $perfilServicio;
  private AutenticacionMiddleware $authMiddleware;

  public function __construct(
    PerfilServicioInterface $perfilServicio,
    AutenticacionMiddleware $authMiddleware
  ) {
    $this->perfilServicio = $perfilServicio;
    $this->authMiddleware = $authMiddleware;
  }

  private function obtenerCuerpoJson(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
      return !empty($_POST) ? $_POST : [];
    }

    $datos = json_decode($raw, true);
    if (!is_array($datos)) {
      return !empty($_POST) ? $_POST : [];
    }

    return $datos;
  }

  public function ver(): void {
    $usuario = $this->authMiddleware->autenticar();

    try {
      $perfil = $this->perfilServicio->obtenerPerfil((int)$usuario->getId());
      respuestaJson(true, $perfil, 200);
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 404);
    } catch (DomainException $e) {
      respuestaError($e->getMessage(), 403);
    } catch (Throwable $e) {
      respuestaError('Error al consultar el perfil: ' . $e->getMessage(), 500);
    }
  }

  public function actualizar(): void {
    $usuario = $this->authMiddleware->autenticar();
    $datos = $this->obtenerCuerpoJson();

    try {
      $perfilActualizado = $this->perfilServicio->actualizarPerfil((int)$usuario->getId(), $datos);
      respuestaJson(true, $perfilActualizado, 200, 'Perfil actualizado exitosamente.');
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 400);
    } catch (DomainException $e) {
      respuestaError($e->getMessage(), 403);
    } catch (Throwable $e) {
      respuestaError('Error interno al actualizar perfil: ' . $e->getMessage(), 500);
    }
  }

  public function manejar(): void {
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    switch ($metodo) {
      case 'GET':
        $this->ver();
        break;

      case 'POST':
      case 'PUT':
        $this->actualizar();
        break;

      default:
        respuestaError('Método HTTP no soportado: ' . $metodo, 405);
        break;
    }
  }
}
