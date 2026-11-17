<?php

namespace App\Controladores;

use App\Contratos\EspacioServicioInterface;
use App\Middleware\AutenticacionMiddleware;
use InvalidArgumentException;
use Throwable;

class EspacioControlador {
  private EspacioServicioInterface $espacioServicio;
  private AutenticacionMiddleware $authMiddleware;

  public function __construct(
    EspacioServicioInterface $espacioServicio,
    AutenticacionMiddleware $authMiddleware
  ) {
    $this->espacioServicio = $espacioServicio;
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
    $tipo = isset($_GET['tipo']) && in_array($_GET['tipo'], ['fisico', 'virtual'], true)
      ? $_GET['tipo']
      : null;

    $soloActivos = true;
    if (isset($_GET['todos']) && $_GET['todos'] === '1') {
      $soloActivos = false;
    }

    try {
      $espacios = $this->espacioServicio->listar($tipo, $soloActivos);
      $datos = array_map(fn($e) => $e->toArray(), $espacios);
      respuestaJson(true, $datos, 200);
    } catch (Throwable $e) {
      respuestaError('Error al obtener el catálogo de espacios: ' . $e->getMessage(), 500);
    }
  }

  public function ver(int $id): void {
    try {
      $espacio = $this->espacioServicio->obtenerPorId($id);
      respuestaJson(true, $espacio->toArray(), 200);
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 404);
    } catch (Throwable $e) {
      respuestaError('Error al obtener el espacio: ' . $e->getMessage(), 500);
    }
  }

  public function crear(): void {
    $this->authMiddleware->verificarRol('administrador');
    $datos = $this->obtenerCuerpoJson();

    try {
      $espacio = $this->espacioServicio->crear($datos);
      respuestaJson(true, $espacio->toArray(), 201, 'Espacio creado exitosamente.');
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 400);
    } catch (Throwable $e) {
      respuestaError('Error interno al crear espacio: ' . $e->getMessage(), 500);
    }
  }

  public function actualizar(int $id): void {
    $this->authMiddleware->verificarRol('administrador');
    $datos = $this->obtenerCuerpoJson();

    try {
      $espacio = $this->espacioServicio->actualizar($id, $datos);
      respuestaJson(true, $espacio->toArray(), 200, 'Espacio actualizado exitosamente.');
    } catch (InvalidArgumentException $e) {
      $codigo = str_contains($e->getMessage(), 'no existe') ? 404 : 400;
      respuestaError($e->getMessage(), $codigo);
    } catch (Throwable $e) {
      respuestaError('Error interno al actualizar espacio: ' . $e->getMessage(), 500);
    }
  }

  public function eliminar(int $id): void {
    $this->authMiddleware->verificarRol('administrador');

    try {
      $this->espacioServicio->eliminar($id);
      respuestaJson(true, null, 200, 'Espacio eliminado exitosamente.');
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 404);
    } catch (Throwable $e) {
      respuestaError('Error interno al eliminar espacio: ' . $e->getMessage(), 500);
    }
  }

  public function manejar(): void {
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    if ($metodo === 'POST') {
      $body = $this->obtenerCuerpoJson();
      if (isset($body['_method'])) {
        $metodo = strtoupper($body['_method']);
      } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
        $metodo = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
      }
    }

    switch ($metodo) {
      case 'GET':
        if ($id !== null) {
          $this->ver($id);
        } else {
          $this->index();
        }
        break;

      case 'POST':
        $this->crear();
        break;

      case 'PUT':
        if ($id === null) {
          $body = $this->obtenerCuerpoJson();
          $id = isset($body['id']) && is_numeric($body['id']) ? (int)$body['id'] : null;
        }
        if ($id === null) {
          respuestaError('ID de espacio no especificado para actualizar.', 400);
        }
        $this->actualizar($id);
        break;

      case 'DELETE':
        if ($id === null) {
          $body = $this->obtenerCuerpoJson();
          $id = isset($body['id']) && is_numeric($body['id']) ? (int)$body['id'] : null;
        }
        if ($id === null) {
          respuestaError('ID de espacio no especificado para eliminar.', 400);
        }
        $this->eliminar($id);
        break;

      default:
        respuestaError('Método HTTP no soportado: ' . $metodo, 405);
        break;
    }
  }
}
