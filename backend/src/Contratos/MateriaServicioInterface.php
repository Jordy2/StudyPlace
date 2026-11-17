<?php

namespace App\Contratos;

use App\Modelos\Materia;

interface MateriaServicioInterface {
  public function listar(bool $soloActivas = true): array;
  public function obtenerPorId(int $id): Materia;
  public function crear(array $datos): Materia;
  public function actualizar(int $id, array $datos): Materia;
  public function eliminar(int $id): bool;
}
