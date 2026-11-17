<?php

namespace App\Servicios;

use App\Contratos\MateriaRepositorioInterface;
use App\Contratos\MateriaServicioInterface;
use App\Modelos\Materia;
use InvalidArgumentException;

class MateriaServicio implements MateriaServicioInterface {
  private MateriaRepositorioInterface $materiaRepo;

  public function __construct(MateriaRepositorioInterface $materiaRepo) {
    $this->materiaRepo = $materiaRepo;
  }

  public function listar(bool $soloActivas = true): array {
    $estado = $soloActivas ? 'activo' : null;
    return $this->materiaRepo->obtenerTodas($estado);
  }

  public function obtenerPorId(int $id): Materia {
    $materia = $this->materiaRepo->buscarPorId($id);
    if ($materia === null) {
      throw new InvalidArgumentException('La materia solicitada no existe.');
    }
    return $materia;
  }

  public function crear(array $datos): Materia {
    $codigo = strtoupper(trim($datos['codigo'] ?? ''));
    $nombre = trim($datos['nombre'] ?? '');
    $descripcion = isset($datos['descripcion']) && trim($datos['descripcion']) !== ''
      ? trim($datos['descripcion'])
      : null;
    $estado = isset($datos['estado']) && in_array($datos['estado'], ['activo', 'inactivo'], true)
      ? $datos['estado']
      : 'activo';

    if ($codigo === '') {
      throw new InvalidArgumentException('El código de materia es obligatorio.');
    }

    if (!preg_match('/^[A-Z0-9_\-]{2,30}$/i', $codigo)) {
      throw new InvalidArgumentException('El código debe ser alfanumérico (2-30 caracteres, guiones o guiones bajos).');
    }

    if ($this->materiaRepo->existeCodigo($codigo)) {
      throw new InvalidArgumentException('El código de materia ya se encuentra registrado.');
    }

    if ($nombre === '' || mb_strlen($nombre) < 3) {
      throw new InvalidArgumentException('El nombre de la materia debe tener al menos 3 caracteres.');
    }

    $materia = new Materia(
      null,
      $codigo,
      $nombre,
      $descripcion,
      $estado
    );

    return $this->materiaRepo->crear($materia);
  }

  public function actualizar(int $id, array $datos): Materia {
    $materiaExistente = $this->obtenerPorId($id);

    $codigo = strtoupper(trim($datos['codigo'] ?? $materiaExistente->getCodigo()));
    $nombre = trim($datos['nombre'] ?? $materiaExistente->getNombre());
    $descripcion = array_key_exists('descripcion', $datos)
      ? (trim($datos['descripcion'] ?? '') !== '' ? trim($datos['descripcion']) : null)
      : $materiaExistente->getDescripcion();
    $estado = isset($datos['estado']) && in_array($datos['estado'], ['activo', 'inactivo'], true)
      ? $datos['estado']
      : $materiaExistente->getEstado();

    if ($codigo === '') {
      throw new InvalidArgumentException('El código de materia es obligatorio.');
    }

    if (!preg_match('/^[A-Z0-9_\-]{2,30}$/i', $codigo)) {
      throw new InvalidArgumentException('El código debe ser alfanumérico (2-30 caracteres).');
    }

    if ($this->materiaRepo->existeCodigo($codigo, $id)) {
      throw new InvalidArgumentException('El código de materia ya está en uso por otra materia.');
    }

    if ($nombre === '' || mb_strlen($nombre) < 3) {
      throw new InvalidArgumentException('El nombre de la materia debe tener al menos 3 caracteres.');
    }

    $materiaActualizada = new Materia(
      $id,
      $codigo,
      $nombre,
      $descripcion,
      $estado
    );

    $this->materiaRepo->actualizar($materiaActualizada);
    return $this->obtenerPorId($id);
  }

  public function eliminar(int $id): bool {
    $this->obtenerPorId($id);
    return $this->materiaRepo->eliminar($id, true);
  }
}
