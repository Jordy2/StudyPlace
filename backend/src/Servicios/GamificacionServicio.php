<?php

namespace App\Servicios;

use App\Contratos\GamificacionRepositorioInterface;
use App\Contratos\GamificacionServicioInterface;

class GamificacionServicio implements GamificacionServicioInterface {
  private const XP_POR_NIVEL = 100;

  public function __construct(private GamificacionRepositorioInterface $repo) {
  }

  public function otorgarPuntos(int $usuarioId, string $accion, string $referencia = ''): int {
    $regla = $this->repo->obtenerReglaActiva($accion);
    if ($regla === null) {
      return 0;
    }

    $puntos = (int) $regla['puntos'];
    $nuevo = $this->repo->registrarXp($usuarioId, $accion, $referencia, $puntos);

    return $nuevo ? $puntos : 0;
  }

  public function obtenerXpTotal(int $usuarioId): int {
    return $this->repo->obtenerXpTotal($usuarioId);
  }

  public function calcularNivel(int $xp): array {
    $nivel = intdiv($xp, self::XP_POR_NIVEL) + 1;
    $xpInicioNivel = ($nivel - 1) * self::XP_POR_NIVEL;

    return [
      'nivel' => $nivel,
      'xp' => $xp,
      'xp_nivel_actual' => $xp - $xpInicioNivel,
      'xp_para_siguiente' => self::XP_POR_NIVEL,
      'progreso_porcentaje' => (int) round((($xp - $xpInicioNivel) / self::XP_POR_NIVEL) * 100),
    ];
  }
}