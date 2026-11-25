<?php

namespace App\Controladores;

use App\Contratos\GestionUsuariosServicioInterface;
use App\Middleware\AutenticacionMiddleware;
use DomainException;
use InvalidArgumentException;
use OutOfBoundsException;
use Throwable;

class UsuarioAdminControlador {
  private GestionUsuariosServicioInterface $servicio;
  private AutenticacionMiddleware $authMiddleware;

  public function __construct(
    GestionUsuariosServicioInterface $servicio,
    AutenticacionMiddleware $authMiddleware
  ) {
    $this->servicio = $servicio;
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

  public function index(): void {
    $this->authMiddleware->verificarRol('administrador');

    try {
      $usuarios = $this->servicio->listar([
        'rol' => $_GET['rol'] ?? '',
        'estado' => $_GET['estado'] ?? '',
        'q' => $_GET['q'] ?? '',
      ]);
      respuestaJson(true, $usuarios, 200);
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 400);
    } catch (Throwable $e) {
      respuestaError('Error al listar usuarios: ' . $e->getMessage(), 500);
    }
  }

  public function accion(): void {
    $admin = $this->authMiddleware->verificarRol('administrador');
    $datos = $this->obtenerCuerpoJson();

    $id = (int)($_GET['id'] ?? ($datos['id'] ?? 0));
    $accion = trim((string)($datos['accion'] ?? ''));

    try {
      switch ($accion) {
        case 'suspender':
          $usuario = $this->servicio->suspender($admin, $id);
          $mensaje = 'Usuario suspendido correctamente.';
          break;

        case 'reactivar':
          $usuario = $this->servicio->reactivar($admin, $id);
          $mensaje = 'Usuario reactivado correctamente.';
          break;

        case 'cambiar_rol':
          $usuario = $this->servicio->cambiarRol($admin, $id, (string)($datos['rol'] ?? ''));
          $mensaje = 'Rol actualizado correctamente.';
          break;

        default:
          respuestaError('Acción no válida. Use suspender, reactivar o cambiar_rol.', 400);
          return;
      }

      respuestaJson(true, $usuario, 200, $mensaje);
    } catch (OutOfBoundsException $e) {
      respuestaError($e->getMessage(), 404);
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 400);
    } catch (DomainException $e) {
      respuestaError($e->getMessage(), 403);
    } catch (Throwable $e) {
      respuestaError('Error interno al gestionar el usuario: ' . $e->getMessage(), 500);
    }
  }

  public function manejar(): void {
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    switch ($metodo) {
      case 'GET':
        $this->index();
        break;

      case 'POST':
      case 'PUT':
        $this->accion();
        break;

      default:
        respuestaError('Método HTTP no soportado: ' . $metodo, 405);
        break;
    }
  }
}
