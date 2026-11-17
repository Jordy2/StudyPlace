<?php

namespace App\Modelos;

class Disponibilidad {
  public function __construct(
    private ?int $id,
    private int $mentorId,
    private int $materiaId,
    private string $diaSemana,
    private string $horaInicio,
    private string $horaFin,
    private string $estado = 'activo',
    private ?string $materiaNombre = null,
    private ?string $materiaCodigo = null
  ) {}

  public static function fromArray(array $fila): self {
    return new self(
      isset($fila['id']) ? (int)$fila['id'] : null,
      (int)$fila['mentor_id'],
      (int)$fila['materia_id'],
      $fila['dia_semana'],
      substr($fila['hora_inicio'], 0, 5),
      substr($fila['hora_fin'], 0, 5),
      $fila['estado'] ?? 'activo',
      $fila['materia_nombre'] ?? null,
      $fila['materia_codigo'] ?? null
    );
  }

  public function getId(): ?int { return $this->id; }
  public function getMentorId(): int { return $this->mentorId; }
  public function getMateriaId(): int { return $this->materiaId; }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'mentor_id' => $this->mentorId,
      'materia_id' => $this->materiaId,
      'materia_nombre' => $this->materiaNombre,
      'materia_codigo' => $this->materiaCodigo,
      'dia_semana' => $this->diaSemana,
      'hora_inicio' => $this->horaInicio,
      'hora_fin' => $this->horaFin,
      'estado' => $this->estado,
    ];
  }
}
