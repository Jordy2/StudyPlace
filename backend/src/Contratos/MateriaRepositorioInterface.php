<?php

namespace App\Contratos;

use App\Modelos\Materia;

interface MateriaRepositorioInterface {
  public function obtenerTodas(?string $estado = null): array;
  public function buscarPorId(int $id): ?Materia;
  public function buscarPorCodigo(string $codigo): ?Materia;
  public function crear(Materia $materia): Materia;
  public function actualizar(Materia $materia): bool;
  public function eliminar(int $id, bool $bajaLogica = true): bool;
  public function existeCodigo(string $codigo, ?int $excluirId = null): bool;
}
