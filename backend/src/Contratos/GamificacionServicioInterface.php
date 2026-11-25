<?php

namespace App\Contratos;

interface GamificacionServicioInterface {
  /** Retorna los puntos otorgados (0 si ya se habían dado o la regla no existe). */
  public function otorgarPuntos(int $usuarioId, string $accion, string $referencia = ''): int;

  public function obtenerXpTotal(int $usuarioId): int;

  /** Retorna nivel, xp actual y datos para la barra de progreso. */
  public function calcularNivel(int $xp): array;
}