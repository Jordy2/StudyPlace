<?php

namespace App\Repositorios;

use App\Contratos\TokenRepositorioInterface;
use App\Modelos\TokenSesion;
use PDO;

class TokenRepositorio implements TokenRepositorioInterface {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function crearToken(int $usuarioId, string $token, string $expiraEn): bool {
    $sql = 'INSERT INTO tokens_sesion (usuario_id, token, expira_en)
            VALUES (:usuario_id, :token, :expira_en)';

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
      ':usuario_id' => $usuarioId,
      ':token' => $token,
      ':expira_en' => $expiraEn,
    ]);
  }

  public function buscarToken(string $token): ?TokenSesion {
    $sql = 'SELECT id, usuario_id, token, expira_en, creado_en
            FROM tokens_sesion
            WHERE token = :token
            LIMIT 1';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':token' => $token]);

    $fila = $stmt->fetch();
    if (!$fila) {
      return null;
    }

    return TokenSesion::fromArray($fila);
  }

  public function eliminarToken(string $token): bool {
    $sql = 'DELETE FROM tokens_sesion WHERE token = :token';
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([':token' => $token]);
  }

  public function eliminarTokensDeUsuario(int $usuarioId): bool {
    $sql = 'DELETE FROM tokens_sesion WHERE usuario_id = :usuario_id';
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([':usuario_id' => $usuarioId]);
  }
}
