<?php

namespace App\Contratos;

interface GamificacionRepositorioInterface {
  public function obtenerReglaActiva(string $accion): ?array;

  /** Devuelve true si insertó, false si ya existía (puntos ya otorgados). */
  public function registrarXp(int $usuarioId, string $accion, string $referencia, int $puntos): bool;

  public function obtenerXpTotal(int $usuarioId): int;
}