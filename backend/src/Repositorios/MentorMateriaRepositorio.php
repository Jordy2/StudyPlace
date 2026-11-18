<?php

namespace App\Repositorios;

use App\Contratos\MentorMateriaRepositorioInterface;
use App\Modelos\Materia;
use App\Modelos\Usuario;
use PDO;
use Throwable;

class MentorMateriaRepositorio implements MentorMateriaRepositorioInterface {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function obtenerMateriasPorMentor(int $usuarioId): array {
    $sql = 'SELECT m.id, m.codigo, m.nombre, m.descripcion, m.estado, m.created_at, m.updated_at
            FROM materias m
            INNER JOIN mentor_materias mm ON mm.materia_id = m.id
            WHERE mm.usuario_id = :usuario_id AND m.estado = \'activo\'
            ORDER BY m.nombre ASC';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':usuario_id' => $usuarioId]);

    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map(fn(array $f) => Materia::fromArray($f), $filas);
  }

  public function obtenerIdsMateriasPorMentor(int $usuarioId): array {
    $sql = 'SELECT materia_id FROM mentor_materias WHERE usuario_id = :usuario_id';
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':usuario_id' => $usuarioId]);

    $filas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return array_map('intval', $filas);
  }

  public function sincronizarMateriasMentor(int $usuarioId, array $materiaIds): bool {
    $idsUnicos = array_values(array_unique(array_filter(
      array_map('intval', $materiaIds),
      fn($id) => $id > 0
    )));

    $iniciaTransaccion = !$this->pdo->inTransaction();

    if ($iniciaTransaccion) {
      $this->pdo->beginTransaction();
    }

    try {
      $stmtDelete = $this->pdo->prepare('DELETE FROM mentor_materias WHERE usuario_id = :usuario_id');
      $stmtDelete->execute([':usuario_id' => $usuarioId]);

      if (!empty($idsUnicos)) {
        $stmtInsert = $this->pdo->prepare(
          'INSERT INTO mentor_materias (usuario_id, materia_id) VALUES (:usuario_id, :materia_id)'
        );
        foreach ($idsUnicos as $materiaId) {
          $stmtInsert->execute([
            ':usuario_id' => $usuarioId,
            ':materia_id' => $materiaId,
          ]);
        }
      }

      if ($iniciaTransaccion) {
        $this->pdo->commit();
      }

      return true;
    } catch (Throwable $e) {
      if ($iniciaTransaccion && $this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }
      throw $e;
    }
  }

  public function obtenerMentoresPorMateria(int $materiaId): array {
    $sql = 'SELECT u.id, u.nombre, u.correo, u.password_hash, u.rol, u.semestre, u.estado, u.created_at, u.updated_at
            FROM usuarios u
            INNER JOIN mentor_materias mm ON mm.usuario_id = u.id
            WHERE mm.materia_id = :materia_id AND u.rol = \'mentor\' AND u.estado = \'activo\'
            ORDER BY u.nombre ASC';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':materia_id' => $materiaId]);

    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map(fn(array $f) => Usuario::fromArray($f), $filas);
  }
}
