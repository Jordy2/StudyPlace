<?php

namespace App\Controladores;

use App\Contratos\DisponibilidadServicioInterface;
use App\Middleware\AutenticacionMiddleware;
use InvalidArgumentException;
use Throwable;

class DisponibilidadControlador {
  public function __construct(
    private DisponibilidadServicioInterface $servicio,
    private AutenticacionMiddleware $auth
  ) {}

  private function cuerpo(): array {
    $raw = file_get_contents('php://input');
    $datos = $raw ? json_decode($raw, true) : null;
    return is_array($datos) ? $datos : (!empty($_POST) ? $_POST : []);
  }

  public function manejar(): void {
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $usuario = $this->auth->verificarRol('mentor');
    $mentorId = (int)$usuario->getId();
    $body = $this->cuerpo();

    try {
      if ($metodo === 'GET') {
        $respuesta = $this->servicio->listarPorMentor($mentorId);
        respuestaJson(true, array_map(fn($d) => $d->toArray(), $respuesta), 200);
        return;
      }
      if ($metodo === 'POST') {
        $creada = $this->servicio->crear($mentorId, $body);
        respuestaJson(true, $creada->toArray(), 201, 'Disponibilidad creada.');
        return;
      }
      if ($metodo === 'PUT') {
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        if ($id <= 0) throw new InvalidArgumentException('ID de disponibilidad requerido.');
        $actualizada = $this->servicio->actualizar($mentorId, $id, $body);
        respuestaJson(true, $actualizada->toArray(), 200, 'Disponibilidad actualizada.');
        return;
      }
      if ($metodo === 'DELETE') {
        $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
        if ($id <= 0) throw new InvalidArgumentException('ID de disponibilidad requerido.');
        $this->servicio->eliminar($mentorId, $id);
        respuestaJson(true, null, 200, 'Disponibilidad eliminada.');
        return;
      }
      respuestaError('Método HTTP no soportado.', 405);
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 400);
    } catch (Throwable $e) {
      respuestaError('Error al procesar disponibilidad: ' . $e->getMessage(), 500);
    }
  }
}
