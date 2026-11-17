<?php

namespace App\Contratos;

use App\Modelos\Espacio;

interface EspacioRepositorioInterface {
  public function obtenerTodos(?string $tipo = null, ?string $estado = null): array;
  public function buscarPorId(int $id): ?Espacio;
  public function crear(Espacio $espacio): Espacio;
  public function actualizar(Espacio $espacio): bool;
  public function eliminar(int $id, bool $bajaLogica = true): bool;
}
