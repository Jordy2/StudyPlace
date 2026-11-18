<?php

namespace App\Servicios;

use App\Contratos\MateriaRepositorioInterface;
use App\Contratos\MentorMateriaRepositorioInterface;
use App\Contratos\PerfilServicioInterface;
use App\Contratos\UsuarioRepositorioInterface;
use DomainException;
use InvalidArgumentException;

class PerfilServicio implements PerfilServicioInterface {
  private UsuarioRepositorioInterface $usuarioRepo;
  private MentorMateriaRepositorioInterface $mentorMateriaRepo;
  private MateriaRepositorioInterface $materiaRepo;

  public function __construct(
    UsuarioRepositorioInterface $usuarioRepo,
    MentorMateriaRepositorioInterface $mentorMateriaRepo,
    MateriaRepositorioInterface $materiaRepo
  ) {
    $this->usuarioRepo = $usuarioRepo;
    $this->mentorMateriaRepo = $mentorMateriaRepo;
    $this->materiaRepo = $materiaRepo;
  }

  public function obtenerPerfil(int $usuarioId): array {
    $usuario = $this->usuarioRepo->buscarPorId($usuarioId);

    if ($usuario === null) {
      throw new InvalidArgumentException('El usuario solicitado no existe.');
    }

    if (!$usuario->esActivo()) {
      throw new DomainException('La cuenta del usuario no se encuentra activa.');
    }

    $resultado = [
      'usuario' => $usuario->toArray(),
    ];

    if ($usuario->tieneRol('mentor')) {
      $materiasDominadas = $this->mentorMateriaRepo->obtenerMateriasPorMentor($usuarioId);
      $materiasIds = $this->mentorMateriaRepo->obtenerIdsMateriasPorMentor($usuarioId);
      $catalogoMaterias = $this->materiaRepo->obtenerTodas('activo');

      $resultado['materias_dominadas'] = array_map(fn($m) => $m->toArray(), $materiasDominadas);
      $resultado['materias_ids'] = $materiasIds;
      $resultado['catalogo_materias'] = array_map(fn($m) => $m->toArray(), $catalogoMaterias);
    }

    return $resultado;
  }

  public function actualizarPerfil(int $usuarioId, array $datos): array {
    $usuario = $this->usuarioRepo->buscarPorId($usuarioId);

    if ($usuario === null) {
      throw new InvalidArgumentException('El usuario solicitado no existe.');
    }

    if (!$usuario->esActivo()) {
      throw new DomainException('No se puede actualizar el perfil de una cuenta inactiva o suspendida.');
    }

    // 1. Validación de nombre
    $nombre = isset($datos['nombre']) ? trim($datos['nombre']) : $usuario->getNombre();
    if ($nombre === '') {
      throw new InvalidArgumentException('El nombre completo es obligatorio.');
    }
    if (mb_strlen($nombre) > 100) {
      throw new InvalidArgumentException('El nombre no puede exceder los 100 caracteres.');
    }
    $usuario->setNombre($nombre);

    // 2. Validación de correo
    $correo = isset($datos['correo']) ? trim($datos['correo']) : $usuario->getCorreo();
    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException('El correo electrónico ingresado no tiene un formato válido.');
    }
    if (mb_strlen($correo) > 150) {
      throw new InvalidArgumentException('El correo electrónico no puede exceder los 150 caracteres.');
    }
    if ($correo !== $usuario->getCorreo() && $this->usuarioRepo->existeOtroConCorreo($correo, $usuarioId)) {
      throw new InvalidArgumentException('El correo electrónico ya se encuentra registrado por otro usuario.');
    }
    $usuario->setCorreo($correo);

    // 3. Validación de semestre (exclusivo para estudiantes)
    if ($usuario->tieneRol('estudiante')) {
      if (array_key_exists('semestre', $datos)) {
        if ($datos['semestre'] === '' || $datos['semestre'] === null) {
          throw new InvalidArgumentException('El semestre actual es obligatorio para el perfil de estudiante.');
        }
        $semestreNum = filter_var($datos['semestre'], FILTER_VALIDATE_INT);
        if ($semestreNum === false || $semestreNum < 1 || $semestreNum > 12) {
          throw new InvalidArgumentException('El semestre debe ser un número entero válido entre 1 y 12.');
        }
        $usuario->setSemestre($semestreNum);
      }
    } else {
      $usuario->setSemestre(null);
    }

    // 4. Actualización opcional de contraseña
    $password = $datos['password'] ?? null;
    if ($password !== null && trim($password) !== '') {
      if (strlen($password) < 8) {
        throw new InvalidArgumentException('La nueva contraseña debe tener al menos 8 caracteres.');
      }

      if (isset($datos['password_confirm']) && $datos['password_confirm'] !== $password) {
        throw new InvalidArgumentException('La confirmación de la nueva contraseña no coincide.');
      }

      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
      $usuario->setPasswordHash($passwordHash);
    }

    // 5. Persistencia del usuario
    $this->usuarioRepo->actualizar($usuario);

    // 6. Selección de materias dominadas (RF5: exclusivo para mentores)
    if ($usuario->tieneRol('mentor') && array_key_exists('materias', $datos)) {
      $materiasRecibidas = is_array($datos['materias']) ? $datos['materias'] : [];
      $materiaIdsLimpios = [];

      foreach ($materiasRecibidas as $idRaw) {
        $idInt = filter_var($idRaw, FILTER_VALIDATE_INT);
        if ($idInt !== false && $idInt > 0) {
          $materia = $this->materiaRepo->buscarPorId($idInt);
          if ($materia !== null && $materia->esActivo()) {
            $materiaIdsLimpios[] = $idInt;
          }
        }
      }

      $this->mentorMateriaRepo->sincronizarMateriasMentor($usuarioId, $materiaIdsLimpios);
    }

    return $this->obtenerPerfil($usuarioId);
  }
}
