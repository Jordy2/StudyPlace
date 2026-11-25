<?php

namespace App\Repositorios;

use App\Contratos\GamificacionRepositorioInterface;
use PDO;

class GamificacionRepositorio implements GamificacionRepositorioInterface {
  public function __construct(private PDO $pdo) {
  }

  public function obtenerReglaActiva(string $accion): ?array {
    $st = $this->pdo->prepare(
      "SELECT accion, puntos FROM reglas_puntos WHERE accion = ? AND estado = 'activo'"
    );
    $st->execute([$accion]);
    $fila = $st->fetch(PDO::FETCH_ASSOC);

    return $fila ?: null;
  }

  public function registrarXp(int $usuarioId, string $accion, string $referencia, int $puntos): bool {
    // INSERT IGNORE: si ya existe (usuario, acción, referencia) no inserta nada
    $st = $this->pdo->prepare(
      "INSERT IGNORE INTO historial_xp (usuario_id, accion, referencia, puntos)
       VALUES (?, ?, ?, ?)"
    );
    $st->execute([$usuarioId, $accion, $referencia, $puntos]);

    return $st->rowCount() === 1;
  }

  public function obtenerXpTotal(int $usuarioId): int {
    $st = $this->pdo->prepare(
      "SELECT COALESCE(SUM(puntos), 0) FROM historial_xp WHERE usuario_id = ?"
    );
    $st->execute([$usuarioId]);

    return (int) $st->fetchColumn();
  }
}