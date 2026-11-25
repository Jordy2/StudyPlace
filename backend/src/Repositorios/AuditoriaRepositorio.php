<?php

namespace App\Repositorios;

use App\Contratos\AuditoriaRepositorioInterface;
use PDO;

class AuditoriaRepositorio implements AuditoriaRepositorioInterface {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function registrar(int $adminId, string $accion, ?int $usuarioAfectadoId, ?string $detalle = null): void {
    $sql = 'INSERT INTO auditoria_admin (admin_id, accion, usuario_afectado_id, detalle)
            VALUES (:admin_id, :accion, :afectado, :detalle)';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      ':admin_id' => $adminId,
      ':accion' => $accion,
      ':afectado' => $usuarioAfectadoId,
      ':detalle' => $detalle,
    ]);
  }

  public function listar(int $limite = 100): array {
    $limite = max(1, min($limite, 500));

    $sql = 'SELECT a.id, a.admin_id, adm.nombre AS admin_nombre,
                   a.accion, a.usuario_afectado_id, af.nombre AS afectado_nombre,
                   a.detalle, a.created_at
            FROM auditoria_admin a
            INNER JOIN usuarios adm ON adm.id = a.admin_id
            LEFT JOIN usuarios af ON af.id = a.usuario_afectado_id
            ORDER BY a.created_at DESC, a.id DESC
            LIMIT ' . $limite;

    return $this->pdo->query($sql)->fetchAll();
  }
}
