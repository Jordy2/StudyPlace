<?php

namespace App\Contratos;

use App\Modelos\Disponibilidad;

interface DisponibilidadRepositorioInterface {
  public function obtenerPorMentor(int $mentorId): array;
  public function buscarPorId(int $id): ?Disponibilidad;
  public function crear(Disponibilidad $disponibilidad): Disponibilidad;
  public function actualizar(Disponibilidad $disponibilidad): bool;
  public function eliminar(int $id, bool $bajaLogica = true): bool;
  public function existeChoque(int $mentorId, string $dia, string $inicio, string $fin, ?int $excluirId = null): bool;
  public function buscarMentores(?int $materiaId, ?string $dia): array;
}
