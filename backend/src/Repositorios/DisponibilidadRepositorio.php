<?php

namespace App\Repositorios;

use App\Contratos\DisponibilidadRepositorioInterface;
use App\Modelos\Disponibilidad;
use PDO;

class DisponibilidadRepositorio implements DisponibilidadRepositorioInterface {
  public function __construct(private PDO $pdo) {}

  public function obtenerPorMentor(int $mentorId): array {
    $stmt = $this->pdo->prepare(
      'SELECT d.*
       FROM disponibilidad_mentor d
       WHERE d.mentor_id = :mentor AND d.estado = "activo"
       ORDER BY FIELD(d.dia_semana, "lunes","martes","miercoles","jueves","viernes","sabado","domingo"), d.hora_inicio'
    );
    $stmt->execute([':mentor' => $mentorId]);
    return array_map(fn($fila) => Disponibilidad::fromArray($fila), $stmt->fetchAll());
  }

  public function buscarPorId(int $id): ?Disponibilidad {
    $stmt = $this->pdo->prepare('SELECT d.* FROM disponibilidad_mentor d WHERE d.id = :id');
    $stmt->execute([':id' => $id]);
    $fila = $stmt->fetch();
    return $fila ? Disponibilidad::fromArray($fila) : null;
  }

  public function crear(Disponibilidad $d): Disponibilidad {
    $data = $d->toArray();
    $stmt = $this->pdo->prepare('INSERT INTO disponibilidad_mentor (mentor_id,dia_semana,hora_inicio,hora_fin,estado) VALUES (:mentor,:dia,:inicio,:fin,:estado)');
    $stmt->execute([
      ':mentor' => $d->getMentorId(), ':dia' => $data['dia_semana'],
      ':inicio' => $data['hora_inicio'], ':fin' => $data['hora_fin'],
      ':estado' => $data['estado']
    ]);
    $id = (int)$this->pdo->lastInsertId();
    return $this->buscarPorId($id);
  }

  public function actualizar(Disponibilidad $d): bool {
    $data = $d->toArray();
    $stmt = $this->pdo->prepare('UPDATE disponibilidad_mentor SET dia_semana=:dia,hora_inicio=:inicio,hora_fin=:fin,estado=:estado WHERE id=:id');
    return $stmt->execute([
      ':dia' => $data['dia_semana'], ':inicio' => $data['hora_inicio'], ':fin' => $data['hora_fin'],
      ':estado' => $data['estado'], ':id' => $d->getId()
    ]);
  }

  public function eliminar(int $id, bool $bajaLogica = true): bool {
    $sql = $bajaLogica ? 'UPDATE disponibilidad_mentor SET estado="inactivo" WHERE id=:id' : 'DELETE FROM disponibilidad_mentor WHERE id=:id';
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([':id' => $id]);
  }

  public function existeChoque(int $mentorId, string $dia, string $inicio, string $fin, ?int $excluirId = null): bool {
    $sql = 'SELECT COUNT(*) FROM disponibilidad_mentor
            WHERE mentor_id=:mentor AND dia_semana=:dia AND estado="activo"
              AND hora_inicio < :fin AND hora_fin > :inicio';
    $params = [':mentor' => $mentorId, ':dia' => $dia, ':inicio' => $inicio, ':fin' => $fin];
    if ($excluirId !== null) {
      $sql .= ' AND id != :excluir';
      $params[':excluir'] = $excluirId;
    }
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() > 0;
  }

  public function buscarMentores(?int $materiaId, ?string $dia): array {
    $sql = 'SELECT u.id,u.nombre,u.correo,u.semestre,
                   m.id AS materia_id,m.codigo AS materia_codigo,m.nombre AS materia_nombre,
                   d.id AS disponibilidad_id,d.dia_semana,d.hora_inicio,d.hora_fin
            FROM usuarios u
            JOIN mentor_materias mm ON mm.usuario_id=u.id
            JOIN materias m ON m.id=mm.materia_id
            JOIN disponibilidad_mentor d ON d.mentor_id=u.id
            WHERE u.rol="mentor" AND u.estado="activo"
              AND d.estado="activo" AND m.estado="activo"';
    $params = [];
    if ($materiaId !== null) {
      $sql .= ' AND mm.materia_id=:materia';
      $params[':materia'] = $materiaId;
    }
    if ($dia !== null) {
      $sql .= ' AND d.dia_semana=:dia';
      $params[':dia'] = $dia;
    }
    $sql .= ' ORDER BY u.nombre,m.nombre,d.dia_semana,d.hora_inicio';
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }
}
