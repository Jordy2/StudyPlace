<?php
namespace App\Contratos;

use App\Modelos\Reserva;

interface ReservaRepositorioInterface {

  public function iniciarTransaccion(): void;

  public function confirmarTransaccion(): void;

  public function revertirTransaccion(): void;

  public function bloquearDisponibilidad(int $id): ?array;

  /** Bloquea filas de usuarios para serializar validaciones de conflictos. */
  public function bloquearUsuarios(array $ids): void;

  public function crear(Reserva $reserva): Reserva;

  public function buscarPorId(int $id, bool $bloquear = false): ?array;

  public function agregarParticipante(int $reservaId, int $estudianteId): bool;

  public function esParticipanteActivo(int $reservaId, int $estudianteId): bool;

  public function contarParticipantes(int $reservaId): int;

  public function existeChoqueMentor(
    int $mentorId,
    string $fecha,
    string $inicio,
    string $fin,
    ?int $excluirReservaId = null
  ): bool;

  public function existeChoqueEstudiante(
    int $estudianteId,
    string $fecha,
    string $inicio,
    string $fin
  ): bool;

  public function listarPorEstudiante(int $estudianteId): array;

  public function listarPorMentor(int $mentorId): array;

  public function cancelarParticipacion(
    int $reservaId,
    int $estudianteId
  ): bool;

  public function cambiarEstado(
    int $reservaId,
    int $mentorId,
    string $estado
  ): bool;
}
