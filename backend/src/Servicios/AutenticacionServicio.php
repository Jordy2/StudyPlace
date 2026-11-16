<?php

namespace App\Servicios;

use App\Contratos\AutenticacionServicioInterface;
use App\Contratos\TokenRepositorioInterface;
use App\Contratos\UsuarioRepositorioInterface;
use App\Modelos\Usuario;
use DomainException;
use InvalidArgumentException;

class AutenticacionServicio implements AutenticacionServicioInterface {
  private UsuarioRepositorioInterface $usuarioRepo;
  private TokenRepositorioInterface $tokenRepo;
  private int $horasExpiracion;

  public function __construct(
    UsuarioRepositorioInterface $usuarioRepo,
    TokenRepositorioInterface $tokenRepo,
    int $horasExpiracion = 24
  ) {
    $this->usuarioRepo = $usuarioRepo;
    $this->tokenRepo = $tokenRepo;
    $this->horasExpiracion = $horasExpiracion;
  }

  public function registrar(array $datos): Usuario {
    $nombre = trim($datos['nombre'] ?? '');
    $correo = trim($datos['correo'] ?? '');
    $password = $datos['password'] ?? '';
    $rol = trim($datos['rol'] ?? '');
    $semestre = isset($datos['semestre']) && $datos['semestre'] !== '' ? (int)$datos['semestre'] : null;

    if ($nombre === '') {
      throw new InvalidArgumentException('El nombre es obligatorio.');
    }

    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException('El correo electrónico no es válido.');
    }

    if ($this->usuarioRepo->existeCorreo($correo)) {
      throw new InvalidArgumentException('El correo electrónico ya se encuentra registrado.');
    }

    $rolesPermitidos = ['estudiante', 'mentor'];
    if (!in_array($rol, $rolesPermitidos, true)) {
      throw new InvalidArgumentException('El rol seleccionado no es válido para registro público.');
    }

    if ($rol === 'estudiante' && ($semestre === null || $semestre < 1 || $semestre > 12)) {
      throw new InvalidArgumentException('El semestre debe ser un número entero entre 1 y 12.');
    }

    if (strlen($password) < 8) {
      throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $nuevoUsuario = new Usuario(
      null,
      $nombre,
      $correo,
      $passwordHash,
      $rol,
      $semestre,
      'activo'
    );

    return $this->usuarioRepo->crear($nuevoUsuario);
  }

  public function autenticar(string $correo, string $password): array {
    $correo = trim($correo);

    if ($correo === '' || $password === '') {
      throw new InvalidArgumentException('El correo y la contraseña son obligatorios.');
    }

    $usuario = $this->usuarioRepo->buscarPorCorreo($correo);

    if (!$usuario || !password_verify($password, $usuario->getPasswordHash())) {
      throw new DomainException('Credenciales incorrectas.');
    }

    if ($usuario->esSuspendido()) {
      throw new DomainException('Su cuenta se encuentra suspendida. Contacte al administrador.');
    }

    if ($usuario->esEliminado()) {
      throw new DomainException('Su cuenta ha sido dada de baja.');
    }

    if (!$usuario->esActivo()) {
      throw new DomainException('Su cuenta no está activa.');
    }

    $token = bin2hex(random_bytes(32));
    $expiraEn = date('Y-m-d H:i:s', time() + ($this->horasExpiracion * 3600));

    $this->tokenRepo->crearToken((int)$usuario->getId(), $token, $expiraEn);

    return [
      'token' => $token,
      'expira_en' => $expiraEn,
      'usuario' => $usuario->toArray(),
    ];
  }

  public function cerrarSesion(string $token): bool {
    $token = trim($token);
    if ($token === '') {
      return false;
    }

    return $this->tokenRepo->eliminarToken($token);
  }

  public function validarToken(string $token): ?Usuario {
    $token = trim($token);
    if ($token === '') {
      return null;
    }

    $tokenSesion = $this->tokenRepo->buscarToken($token);
    if (!$tokenSesion) {
      return null;
    }

    if ($tokenSesion->haExpirado()) {
      $this->tokenRepo->eliminarToken($token);
      return null;
    }

    $usuario = $this->usuarioRepo->buscarPorId($tokenSesion->getUsuarioId());
    if (!$usuario || !$usuario->esActivo()) {
      return null;
    }

    return $usuario;
  }
}
