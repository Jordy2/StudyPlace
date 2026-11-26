<?php

namespace App\Repositorios;

use App\Contratos\ReservaRepositorioInterface;
use App\Modelos\Reserva;
use PDO;

class ReservaRepositorio implements ReservaRepositorioInterface {

  public function __construct(private PDO $pdo) {}

  public function iniciarTransaccion(): void {
    $this->pdo->beginTransaction();
  }

  public function confirmarTransaccion(): void {
    if ($this->pdo->inTransaction()) {
      $this->pdo->commit();
    }
  }

  public function revertirTransaccion(): void {
    if ($this->pdo->inTransaction()) {
      $this->pdo->rollBack();
    }
  }

  public function bloquearDisponibilidad(int $id): ?array {
    $stmt = $this->pdo->prepare(
      'SELECT * FROM disponibilidad_mentor
       WHERE id = :id AND estado = "activo"
       FOR UPDATE'
    );
    $stmt->execute([':id' => $id]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    return $fila ?: null;
  }

  /**
   * Serializa las validaciones de horario por usuario.
   * Los IDs se ordenan para reducir el riesgo de interbloqueos.
   */
  public function bloquearUsuarios(array $ids): void {
    $ids = array_values(array_unique(array_filter(
      array_map('intval', $ids),
      static fn(int $id): bool => $id > 0
    )));

    sort($ids, SORT_NUMERIC);

    if (!$ids) {
      return;
    }

    $marcadores = [];
    $parametros = [];

    foreach ($ids as $indice => $id) {
      $clave = ':usuario' . $indice;
      $marcadores[] = $clave;
      $parametros[$clave] = $id;
    }

    $sql = 'SELECT id FROM usuarios
            WHERE id IN (' . implode(',', $marcadores) . ')
            ORDER BY id
            FOR UPDATE';

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($parametros);
    $stmt->fetchAll(PDO::FETCH_COLUMN);
  }

  public function crear(Reserva $reserva): Reserva {
    $d = $reserva->toArray();

    $stmt = $this->pdo->prepare(
      'INSERT INTO reservas
       (mentor_id, materia_id, disponibilidad_id, espacio_id,
        solicitante_id, tipo, fecha, hora_inicio, hora_fin,
        cupo_maximo, estado)
       VALUES
       (:mentor, :materia, :disponibilidad, :espacio,
        :solicitante, :tipo, :fecha, :inicio, :fin,
        :cupo, :estado)'
    );

    $stmt->execute([
      ':mentor' => $d['mentor_id'],
      ':materia' => $d['materia_id'],
      ':disponibilidad' => $d['disponibilidad_id'],
      ':espacio' => $d['espacio_id'],
      ':solicitante' => $d['solicitante_id'],
      ':tipo' => $d['tipo'],
      ':fecha' => $d['fecha'],
      ':inicio' => $d['hora_inicio'],
      ':fin' => $d['hora_fin'],
      ':cupo' => $d['cupo_maximo'],
      ':estado' => $d['estado']
    ]);

    return $this->buscarModeloPorId(
      (int)$this->pdo->lastInsertId()
    );
  }

  private function buscarModeloPorId(int $id): Reserva {
    $stmt = $this->pdo->prepare(
      'SELECT * FROM reservas WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
    return Reserva::fromArray($stmt->fetch(PDO::FETCH_ASSOC));
  }

  public function buscarPorId(
    int $id,
    bool $bloquear = false
  ): ?array {
    $sql = 'SELECT r.*,
              (SELECT COUNT(*)
               FROM reserva_participantes rp
               WHERE rp.reserva_id = r.id
                 AND rp.estado = "activo") AS participantes
            FROM reservas r
            WHERE r.id = :id';

    if ($bloquear) {
      $sql .= ' FOR UPDATE';
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    return $fila ?: null;
  }

  public function agregarParticipante(
    int $reservaId,
    int $estudianteId
  ): bool {
    $stmt = $this->pdo->prepare(
      'INSERT INTO reserva_participantes
       (reserva_id, estudiante_id, estado)
       VALUES (:reserva, :estudiante, "activo")
       ON DUPLICATE KEY UPDATE
         estado = IF(estado = "cancelado", "activo", estado)'
    );

    return $stmt->execute([
      ':reserva' => $reservaId,
      ':estudiante' => $estudianteId
    ]);
  }

  public function esParticipanteActivo(
    int $reservaId,
    int $estudianteId
  ): bool {
    $stmt = $this->pdo->prepare(
      'SELECT COUNT(*)
       FROM reserva_participantes
       WHERE reserva_id = :reserva
         AND estudiante_id = :estudiante
         AND estado = "activo"'
    );

    $stmt->execute([
      ':reserva' => $reservaId,
      ':estudiante' => $estudianteId
    ]);

    return (int)$stmt->fetchColumn() > 0;
  }

  public function contarParticipantes(int $reservaId): int {
    $stmt = $this->pdo->prepare(
      'SELECT COUNT(*)
       FROM reserva_participantes
       WHERE reserva_id = :id AND estado = "activo"'
    );
    $stmt->execute([':id' => $reservaId]);

    return (int)$stmt->fetchColumn();
  }

  public function existeChoqueMentor(
    int $mentorId,
    string $fecha,
    string $inicio,
    string $fin,
    ?int $excluirReservaId = null
  ): bool {
    $sql = 'SELECT COUNT(*)
            FROM reservas
            WHERE mentor_id = :mentor
              AND fecha = :fecha
              AND estado IN ("pendiente", "aceptada")
              AND hora_inicio < :fin
              AND hora_fin > :inicio';

    $params = [
      ':mentor' => $mentorId,
      ':fecha' => $fecha,
      ':inicio' => $inicio,
      ':fin' => $fin
    ];

    if ($excluirReservaId !== null) {
      $sql .= ' AND id != :excluir';
      $params[':excluir'] = $excluirReservaId;
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn() > 0;
  }

  public function existeChoqueEstudiante(
    int $estudianteId,
    string $fecha,
    string $inicio,
    string $fin
  ): bool {
    $stmt = $this->pdo->prepare(
      'SELECT COUNT(*)
       FROM reserva_participantes rp
       INNER JOIN reservas r ON r.id = rp.reserva_id
       WHERE rp.estudiante_id = :estudiante
         AND rp.estado = "activo"
         AND r.fecha = :fecha
         AND r.estado IN ("pendiente", "aceptada")
         AND r.hora_inicio < :fin
         AND r.hora_fin > :inicio'
    );

    $stmt->execute([
      ':estudiante' => $estudianteId,
      ':fecha' => $fecha,
      ':inicio' => $inicio,
      ':fin' => $fin
    ]);

    return (int)$stmt->fetchColumn() > 0;
  }

  public function listarPorEstudiante(int $estudianteId): array {
    $stmt = $this->pdo->prepare(
      'SELECT r.*,
              m.nombre AS materia_nombre,
              u.nombre AS mentor_nombre,
              e.nombre AS espacio_nombre,
              (SELECT COUNT(*)
               FROM reserva_participantes rp2
               WHERE rp2.reserva_id = r.id
                 AND rp2.estado = "activo") AS participantes
       FROM reserva_participantes rp
       INNER JOIN reservas r ON r.id = rp.reserva_id
       INNER JOIN materias m ON m.id = r.materia_id
       INNER JOIN usuarios u ON u.id = r.mentor_id
       LEFT JOIN espacios e ON e.id = r.espacio_id
       WHERE rp.estudiante_id = :estudiante
       ORDER BY r.fecha DESC, r.hora_inicio DESC'
    );

    $stmt->execute([':estudiante' => $estudianteId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function listarPorMentor(int $mentorId): array {
    $stmt = $this->pdo->prepare(
      'SELECT r.*,
              m.nombre AS materia_nombre,
              u.nombre AS solicitante_nombre,
              e.nombre AS espacio_nombre,
              (SELECT COUNT(*)
               FROM reserva_participantes rp
               WHERE rp.reserva_id = r.id
                 AND rp.estado = "activo") AS participantes
       FROM reservas r
       INNER JOIN materias m ON m.id = r.materia_id
       INNER JOIN usuarios u ON u.id = r.solicitante_id
       LEFT JOIN espacios e ON e.id = r.espacio_id
       WHERE r.mentor_id = :mentor
       ORDER BY r.fecha DESC, r.hora_inicio DESC'
    );

    $stmt->execute([':mentor' => $mentorId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function cancelarParticipacion(
    int $reservaId,
    int $estudianteId
  ): bool {
    $stmt = $this->pdo->prepare(
      'UPDATE reserva_participantes
       SET estado = "cancelado"
       WHERE reserva_id = :reserva
         AND estudiante_id = :estudiante
         AND estado = "activo"'
    );

    $stmt->execute([
      ':reserva' => $reservaId,
      ':estudiante' => $estudianteId
    ]);

    if ($stmt->rowCount() > 0) {
      $actualizar = $this->pdo->prepare(
        'UPDATE reservas
         SET estado = "cancelada"
         WHERE id = :reserva
           AND NOT EXISTS (
             SELECT 1 FROM reserva_participantes
             WHERE reserva_id = :reserva2
               AND estado = "activo"
           )'
      );

      $actualizar->execute([
        ':reserva' => $reservaId,
        ':reserva2' => $reservaId
      ]);

      return true;
    }

    return false;
  }
    public function cambiarEstado(
    int $reservaId,
    int $mentorId,
    string $estado
  ): bool {
    $stmt = $this->pdo->prepare(
      'UPDATE reservas
       SET estado = :estado
       WHERE id = :id
         AND mentor_id = :mentor
         AND estado = "pendiente"'
    );

    $stmt->execute([
      ':estado' => $estado,
      ':id' => $reservaId,
      ':mentor' => $mentorId
    ]);

    return $stmt->rowCount() > 0;
  }
}
