<?php

namespace App\Contratos;

use App\Modelos\Espacio;

interface EspacioServicioInterface {
  public function listar(?string $tipo = null, bool $soloActivos = true): array;
  public function obtenerPorId(int $id): Espacio;
  public function crear(array $datos): Espacio;
  public function actualizar(int $id, array $datos): Espacio;
  public function eliminar(int $id): bool;
}
