<?php

namespace App\Servicios;

use App\Contratos\AuditoriaRepositorioInterface;
use App\Contratos\GestionUsuariosServicioInterface;
use App\Contratos\TokenRepositorioInterface;
use App\Contratos\UsuarioRepositorioInterface;
use App\Modelos\Usuario;
use DomainException;
use InvalidArgumentException;
use OutOfBoundsException;

class GestionUsuariosServicio implements GestionUsuariosServicioInterface {
  private const ROLES = ['estudiante', 'mentor', 'administrador'];
  private const ROLES_ASIGNABLES = ['estudiante', 'mentor'];
  private const ESTADOS = ['activo', 'suspendido', 'eliminado'];

  private UsuarioRepositorioInterface $usuarioRepo;
  private TokenRepositorioInterface $tokenRepo;
  private AuditoriaRepositorioInterface $auditoriaRepo;

  public function __construct(
    UsuarioRepositorioInterface $usuarioRepo,
    TokenRepositorioInterface $tokenRepo,
    AuditoriaRepositorioInterface $auditoriaRepo
  ) {
    $this->usuarioRepo = $usuarioRepo;
    $this->tokenRepo = $tokenRepo;
    $this->auditoriaRepo = $auditoriaRepo;
  }

  public function listar(array $filtros = []): array {
    $limpios = [];

    $rol = trim((string)($filtros['rol'] ?? ''));
    if ($rol !== '') {
      if (!in_array($rol, self::ROLES, true)) {
        throw new InvalidArgumentException('El filtro de rol no es válido.');
      }
      $limpios['rol'] = $rol;
    }

    $estado = trim((string)($filtros['estado'] ?? ''));
    if ($estado !== '') {
      if (!in_array($estado, self::ESTADOS, true)) {
        throw new InvalidArgumentException('El filtro de estado no es válido.');
      }
      $limpios['estado'] = $estado;
    }

    $q = trim((string)($filtros['q'] ?? ''));
    if ($q !== '') {
      $limpios['q'] = mb_substr($q, 0, 100);
    }

    return $this->usuarioRepo->listar($limpios);
  }

  public function suspender(Usuario $admin, int $idUsuario): array {
    $objetivo = $this->obtenerObjetivoModificable($admin, $idUsuario);

    if ($objetivo->esEliminado()) {
      throw new DomainException('No se puede suspender una cuenta dada de baja.');
    }

    if ($objetivo->esSuspendido()) {
      throw new InvalidArgumentException('El usuario ya se encuentra suspendido.');
    }

    $this->usuarioRepo->actualizarEstado($idUsuario, 'suspendido');
    // La sesión del usuario suspendido deja de ser válida de inmediato.
    $this->tokenRepo->eliminarTokensDeUsuario($idUsuario);

    $this->auditoriaRepo->registrar(
      (int)$admin->getId(),
      'suspender_usuario',
      $idUsuario,
      'Usuario ' . $objetivo->getCorreo() . ' suspendido.'
    );

    return $this->usuarioActualizado($idUsuario);
  }

  public function reactivar(Usuario $admin, int $idUsuario): array {
    $objetivo = $this->obtenerObjetivoModificable($admin, $idUsuario);

    if ($objetivo->esEliminado()) {
      throw new DomainException('No se puede reactivar una cuenta dada de baja.');
    }

    if ($objetivo->esActivo()) {
      throw new InvalidArgumentException('El usuario ya se encuentra activo.');
    }

    $this->usuarioRepo->actualizarEstado($idUsuario, 'activo');

    $this->auditoriaRepo->registrar(
      (int)$admin->getId(),
      'reactivar_usuario',
      $idUsuario,
      'Usuario ' . $objetivo->getCorreo() . ' reactivado.'
    );

    return $this->usuarioActualizado($idUsuario);
  }

  public function cambiarRol(Usuario $admin, int $idUsuario, string $nuevoRol): array {
    $nuevoRol = trim($nuevoRol);

    if (!in_array($nuevoRol, self::ROLES_ASIGNABLES, true)) {
      throw new InvalidArgumentException('El rol debe ser estudiante o mentor.');
    }

    $objetivo = $this->obtenerObjetivoModificable($admin, $idUsuario);

    if ($objetivo->esEliminado()) {
      throw new DomainException('No se puede cambiar el rol de una cuenta dada de baja.');
    }

    if ($objetivo->getRol() === $nuevoRol) {
      throw new InvalidArgumentException('El usuario ya tiene el rol ' . $nuevoRol . '.');
    }

    $rolAnterior = $objetivo->getRol();
    $this->usuarioRepo->actualizarRol($idUsuario, $nuevoRol);

    // Se cierran las sesiones para que el usuario ingrese de nuevo con su nuevo rol.
    $this->tokenRepo->eliminarTokensDeUsuario($idUsuario);

    $this->auditoriaRepo->registrar(
      (int)$admin->getId(),
      'cambiar_rol',
      $idUsuario,
      'Rol de ' . $objetivo->getCorreo() . ': ' . $rolAnterior . ' -> ' . $nuevoRol . '.'
    );

    return $this->usuarioActualizado($idUsuario);
  }

  public function listarAuditoria(int $limite = 100): array {
    return $this->auditoriaRepo->listar($limite);
  }

  /**
   * RF17: el administrador no puede modificarse a sí mismo ni a otros administradores.
   */
  private function obtenerObjetivoModificable(Usuario $admin, int $idUsuario): Usuario {
    if ($idUsuario <= 0) {
      throw new InvalidArgumentException('El identificador de usuario no es válido.');
    }

    $objetivo = $this->usuarioRepo->buscarPorId($idUsuario);
    if ($objetivo === null) {
      throw new OutOfBoundsException('El usuario solicitado no existe.');
    }

    if ($objetivo->getId() === $admin->getId()) {
      throw new DomainException('No puedes modificar tu propia cuenta.');
    }

    if ($objetivo->tieneRol('administrador')) {
      throw new DomainException('No se puede modificar a otro administrador.');
    }

    return $objetivo;
  }

  private function usuarioActualizado(int $idUsuario): array {
    $usuario = $this->usuarioRepo->buscarPorId($idUsuario);
    return $usuario !== null ? $usuario->toArray() : [];
  }
}
