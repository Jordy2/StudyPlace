<?php

namespace App\Controladores;

use App\Contratos\MateriaServicioInterface;
use App\Middleware\AutenticacionMiddleware;
use InvalidArgumentException;
use Throwable;

class MateriaControlador {
  private MateriaServicioInterface $materiaServicio;
  private AutenticacionMiddleware $authMiddleware;

  public function __construct(
    MateriaServicioInterface $materiaServicio,
    AutenticacionMiddleware $authMiddleware
  ) {
    $this->materiaServicio = $materiaServicio;
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
    $soloActivas = true;
    if (isset($_GET['todas']) && $_GET['todas'] === '1') {
      $soloActivas = false;
    }

    try {
      $materias = $this->materiaServicio->listar($soloActivas);
      $datos = array_map(fn($m) => $m->toArray(), $materias);
      respuestaJson(true, $datos, 200);
    } catch (Throwable $e) {
      respuestaError('Error al obtener el catálogo de materias: ' . $e->getMessage(), 500);
    }
  }

  public function ver(int $id): void {
    try {
      $materia = $this->materiaServicio->obtenerPorId($id);
      respuestaJson(true, $materia->toArray(), 200);
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 404);
    } catch (Throwable $e) {
      respuestaError('Error al obtener la materia: ' . $e->getMessage(), 500);
    }
  }

  public function crear(): void {
    $this->authMiddleware->verificarRol('administrador');
    $datos = $this->obtenerCuerpoJson();

    try {
      $materia = $this->materiaServicio->crear($datos);
      respuestaJson(true, $materia->toArray(), 201, 'Materia creada exitosamente.');
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 400);
    } catch (Throwable $e) {
      respuestaError('Error interno al crear materia: ' . $e->getMessage(), 500);
    }
  }

  public function actualizar(int $id): void {
    $this->authMiddleware->verificarRol('administrador');
    $datos = $this->obtenerCuerpoJson();

    try {
      $materia = $this->materiaServicio->actualizar($id, $datos);
      respuestaJson(true, $materia->toArray(), 200, 'Materia actualizada exitosamente.');
    } catch (InvalidArgumentException $e) {
      $codigo = str_contains($e->getMessage(), 'no existe') ? 404 : 400;
      respuestaError($e->getMessage(), $codigo);
    } catch (Throwable $e) {
      respuestaError('Error interno al actualizar materia: ' . $e->getMessage(), 500);
    }
  }

  public function eliminar(int $id): void {
    $this->authMiddleware->verificarRol('administrador');

    try {
      $this->materiaServicio->eliminar($id);
      respuestaJson(true, null, 200, 'Materia eliminada exitosamente.');
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 404);
    } catch (Throwable $e) {
      respuestaError('Error interno al eliminar materia: ' . $e->getMessage(), 500);
    }
  }

  public function manejar(): void {
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    // Permitir emulación de PUT y DELETE vía _method en POST o encabezado
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
          respuestaError('ID de materia no especificado para actualizar.', 400);
        }
        $this->actualizar($id);
        break;

      case 'DELETE':
        if ($id === null) {
          $body = $this->obtenerCuerpoJson();
          $id = isset($body['id']) && is_numeric($body['id']) ? (int)$body['id'] : null;
        }
        if ($id === null) {
          respuestaError('ID de materia no especificado para eliminar.', 400);
        }
        $this->eliminar($id);
        break;

      default:
        respuestaError('Método HTTP no soportado: ' . $metodo, 405);
        break;
    }
  }
}
