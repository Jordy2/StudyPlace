<?php

namespace App\Contratos;

use App\Modelos\Disponibilidad;

interface DisponibilidadServicioInterface {
  public function listarPorMentor(int $mentorId): array;
  public function crear(int $mentorId, array $datos): Disponibilidad;
  public function actualizar(int $mentorId, int $id, array $datos): Disponibilidad;
  public function eliminar(int $mentorId, int $id): bool;
  public function buscarMentores(?int $materiaId, ?string $dia): array;
}
