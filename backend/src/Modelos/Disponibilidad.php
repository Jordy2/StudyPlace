<?php

namespace App\Modelos;

class Disponibilidad {
  public function __construct(
    private ?int $id,
    private int $mentorId,
    private string $diaSemana,
    private string $horaInicio,
    private string $horaFin,
    private string $estado = 'activo'
  ) {}

  public static function fromArray(array $fila): self {
    return new self(
      isset($fila['id']) ? (int)$fila['id'] : null,
      (int)$fila['mentor_id'],
      $fila['dia_semana'],
      substr($fila['hora_inicio'], 0, 5),
      substr($fila['hora_fin'], 0, 5),
      $fila['estado'] ?? 'activo'
    );
  }

  public function getId(): ?int { return $this->id; }
  public function getMentorId(): int { return $this->mentorId; }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'mentor_id' => $this->mentorId,
      'dia_semana' => $this->diaSemana,
      'hora_inicio' => $this->horaInicio,
      'hora_fin' => $this->horaFin,
      'estado' => $this->estado,
    ];
  }
}
