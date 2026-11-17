<?php

namespace App\Servicios;

use App\Contratos\EspacioRepositorioInterface;
use App\Contratos\EspacioServicioInterface;
use App\Modelos\Espacio;
use InvalidArgumentException;

class EspacioServicio implements EspacioServicioInterface {
  private EspacioRepositorioInterface $espacioRepo;

  public function __construct(EspacioRepositorioInterface $espacioRepo) {
    $this->espacioRepo = $espacioRepo;
  }

  public function listar(?string $tipo = null, bool $soloActivos = true): array {
    $estado = $soloActivos ? 'activo' : null;
    return $this->espacioRepo->obtenerTodos($tipo, $estado);
  }

  public function obtenerPorId(int $id): Espacio {
    $espacio = $this->espacioRepo->buscarPorId($id);
    if ($espacio === null) {
      throw new InvalidArgumentException('El espacio solicitado no existe.');
    }
    return $espacio;
  }

  public function crear(array $datos): Espacio {
    $nombre = trim($datos['nombre'] ?? '');
    $tipo = trim($datos['tipo'] ?? '');
    $estado = isset($datos['estado']) && in_array($datos['estado'], ['activo', 'inactivo'], true)
      ? $datos['estado']
      : 'activo';

    if ($nombre === '' || mb_strlen($nombre) < 3) {
      throw new InvalidArgumentException('El nombre del espacio debe tener al menos 3 caracteres.');
    }

    if (!in_array($tipo, ['fisico', 'virtual'], true)) {
      throw new InvalidArgumentException('El tipo de espacio debe ser "fisico" o "virtual".');
    }

    $capacidad = null;
    $ubicacion = null;
    $enlaceVirtual = null;

    if ($tipo === 'fisico') {
      if (!isset($datos['capacidad']) || !is_numeric($datos['capacidad']) || (int)$datos['capacidad'] <= 0) {
        throw new InvalidArgumentException('Para aulas físicas la capacidad debe ser un número entero mayor a cero.');
      }
      $capacidad = (int)$datos['capacidad'];
      $ubicacion = isset($datos['ubicacion']) && trim($datos['ubicacion']) !== ''
        ? trim($datos['ubicacion'])
        : null;
    } else {
      $enlaceVirtual = trim($datos['enlace_virtual'] ?? '');
      if ($enlaceVirtual === '' || !filter_var($enlaceVirtual, FILTER_VALIDATE_URL)) {
        throw new InvalidArgumentException('Para salas virtuales se debe proporcionar un enlace URL válido.');
      }
    }

    $espacio = new Espacio(
      null,
      $nombre,
      $tipo,
      $capacidad,
      $ubicacion,
      $enlaceVirtual,
      $estado
    );

    return $this->espacioRepo->crear($espacio);
  }

  public function actualizar(int $id, array $datos): Espacio {
    $espacioExistente = $this->obtenerPorId($id);

    $nombre = trim($datos['nombre'] ?? $espacioExistente->getNombre());
    $tipo = trim($datos['tipo'] ?? $espacioExistente->getTipo());
    $estado = isset($datos['estado']) && in_array($datos['estado'], ['activo', 'inactivo'], true)
      ? $datos['estado']
      : $espacioExistente->getEstado();

    if ($nombre === '' || mb_strlen($nombre) < 3) {
      throw new InvalidArgumentException('El nombre del espacio debe tener al menos 3 caracteres.');
    }

    if (!in_array($tipo, ['fisico', 'virtual'], true)) {
      throw new InvalidArgumentException('El tipo de espacio debe ser "fisico" o "virtual".');
    }

    $capacidad = null;
    $ubicacion = null;
    $enlaceVirtual = null;

    if ($tipo === 'fisico') {
      $capInput = $datos['capacidad'] ?? $espacioExistente->getCapacidad();
      if (!is_numeric($capInput) || (int)$capInput <= 0) {
        throw new InvalidArgumentException('Para aulas físicas la capacidad debe ser un número entero mayor a cero.');
      }
      $capacidad = (int)$capInput;
      $ubicacion = array_key_exists('ubicacion', $datos)
        ? (trim($datos['ubicacion'] ?? '') !== '' ? trim($datos['ubicacion']) : null)
        : $espacioExistente->getUbicacion();
    } else {
      $enlaceVirtual = trim($datos['enlace_virtual'] ?? $espacioExistente->getEnlaceVirtual() ?? '');
      if ($enlaceVirtual === '' || !filter_var($enlaceVirtual, FILTER_VALIDATE_URL)) {
        throw new InvalidArgumentException('Para salas virtuales se debe proporcionar un enlace URL válido.');
      }
    }

    $espacioActualizado = new Espacio(
      $id,
      $nombre,
      $tipo,
      $capacidad,
      $ubicacion,
      $enlaceVirtual,
      $estado
    );

    $this->espacioRepo->actualizar($espacioActualizado);
    return $this->obtenerPorId($id);
  }

  public function eliminar(int $id): bool {
    $this->obtenerPorId($id);
    return $this->espacioRepo->eliminar($id, true);
  }
}
