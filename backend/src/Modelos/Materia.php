<?php

namespace App\Modelos;

class Materia {
  private ?int $id;
  private string $codigo;
  private string $nombre;
  private ?string $descripcion;
  private string $estado;
  private ?string $createdAt;
  private ?string $updatedAt;

  public function __construct(
    ?int $id,
    string $codigo,
    string $nombre,
    ?string $descripcion = null,
    string $estado = 'activo',
    ?string $createdAt = null,
    ?string $updatedAt = null
  ) {
    $this->id = $id;
    $this->codigo = $codigo;
    $this->nombre = $nombre;
    $this->descripcion = $descripcion;
    $this->estado = $estado;
    $this->createdAt = $createdAt;
    $this->updatedAt = $updatedAt;
  }

  public function getId(): ?int {
    return $this->id;
  }

  public function getCodigo(): string {
    return $this->codigo;
  }

  public function getNombre(): string {
    return $this->nombre;
  }

  public function getDescripcion(): ?string {
    return $this->descripcion;
  }

  public function getEstado(): string {
    return $this->estado;
  }

  public function getCreatedAt(): ?string {
    return $this->createdAt;
  }

  public function getUpdatedAt(): ?string {
    return $this->updatedAt;
  }

  public function esActivo(): bool {
    return $this->estado === 'activo';
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'codigo' => $this->codigo,
      'nombre' => $this->nombre,
      'descripcion' => $this->descripcion,
      'estado' => $this->estado,
      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];
  }

  public static function fromArray(array $datos): self {
    return new self(
      isset($datos['id']) ? (int)$datos['id'] : null,
      $datos['codigo'] ?? '',
      $datos['nombre'] ?? '',
      $datos['descripcion'] ?? null,
      $datos['estado'] ?? 'activo',
      $datos['created_at'] ?? null,
      $datos['updated_at'] ?? null
    );
  }
}
