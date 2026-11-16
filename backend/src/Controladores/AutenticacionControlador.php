<?php

namespace App\Controladores;

use App\Contratos\AutenticacionServicioInterface;
use App\Middleware\AutenticacionMiddleware;
use DomainException;
use InvalidArgumentException;
use Throwable;

class AutenticacionControlador {
  private AutenticacionServicioInterface $authServicio;
  private AutenticacionMiddleware $authMiddleware;

  public function __construct(
    AutenticacionServicioInterface $authServicio,
    AutenticacionMiddleware $authMiddleware
  ) {
    $this->authServicio = $authServicio;
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

  public function registro(): void {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
      respuestaError('Método no permitido. Se espera POST.', 405);
    }

    $datos = $this->obtenerCuerpoJson();

    try {
      $usuario = $this->authServicio->registrar($datos);
      respuestaJson(true, $usuario->toArray(), 201, 'Usuario registrado exitosamente.');
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 400);
    } catch (Throwable $e) {
      respuestaError('Error interno al registrar usuario: ' . $e->getMessage(), 500);
    }
  }

  public function login(): void {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
      respuestaError('Método no permitido. Se espera POST.', 405);
    }

    $datos = $this->obtenerCuerpoJson();
    $correo = $datos['correo'] ?? '';
    $password = $datos['password'] ?? '';

    try {
      $resultado = $this->authServicio->autenticar($correo, $password);
      respuestaJson(true, $resultado, 200, 'Inicio de sesión exitoso.');
    } catch (InvalidArgumentException $e) {
      respuestaError($e->getMessage(), 400);
    } catch (DomainException $e) {
      $mensaje = $e->getMessage();
      $codigo = (str_contains($mensaje, 'suspendida') || str_contains($mensaje, 'baja')) ? 403 : 401;
      respuestaError($mensaje, $codigo);
    } catch (Throwable $e) {
      respuestaError('Error interno al iniciar sesión: ' . $e->getMessage(), 500);
    }
  }

  public function logout(): void {
    if (!in_array(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['POST', 'GET'], true)) {
      respuestaError('Método no permitido.', 405);
    }

    $token = $this->authMiddleware->extraerToken();

    if ($token === null) {
      $datos = $this->obtenerCuerpoJson();
      $token = $datos['token'] ?? null;
    }

    if ($token === null) {
      respuestaError('Token de sesión no proporcionado.', 400);
    }

    try {
      $this->authServicio->cerrarSesion($token);
      respuestaJson(true, null, 200, 'Sesión cerrada exitosamente.');
    } catch (Throwable $e) {
      respuestaError('Error interno al cerrar sesión: ' . $e->getMessage(), 500);
    }
  }

  public function me(): void {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
      respuestaError('Método no permitido. Se espera GET.', 405);
    }

    $usuario = $this->authMiddleware->autenticar();
    respuestaJson(true, ['usuario' => $usuario->toArray()], 200);
  }
}
