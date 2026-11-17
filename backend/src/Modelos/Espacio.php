<?php

namespace App\Modelos;

class Espacio {
  private ?int $id;
  private string $nombre;
  private string $tipo;
  private ?int $capacidad;
  private ?string $ubicacion;
  private ?string $enlaceVirtual;
  private string $estado;
  private ?string $createdAt;
  private ?string $updatedAt;

  public function __construct(
    ?int $id,
    string $nombre,
    string $tipo,
    ?int $capacidad = null,
    ?string $ubicacion = null,
    ?string $enlaceVirtual = null,
    string $estado = 'activo',
    ?string $createdAt = null,
    ?string $updatedAt = null
  ) {
    $this->id = $id;
    $this->nombre = $nombre;
    $this->tipo = $tipo;
    $this->capacidad = $capacidad;
    $this->ubicacion = $ubicacion;
    $this->enlaceVirtual = $enlaceVirtual;
    $this->estado = $estado;
    $this->createdAt = $createdAt;
    $this->updatedAt = $updatedAt;
  }

  public function getId(): ?int {
    return $this->id;
  }

  public function getNombre(): string {
    return $this->nombre;
  }

  public function getTipo(): string {
    return $this->tipo;
  }

  public function getCapacidad(): ?int {
    return $this->capacidad;
  }

  public function getUbicacion(): ?string {
    return $this->ubicacion;
  }

  public function getEnlaceVirtual(): ?string {
    return $this->enlaceVirtual;
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

  public function esFisico(): bool {
    return $this->tipo === 'fisico';
  }

  public function esVirtual(): bool {
    return $this->tipo === 'virtual';
  }

  public function esActivo(): bool {
    return $this->estado === 'activo';
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'nombre' => $this->nombre,
      'tipo' => $this->tipo,
      'capacidad' => $this->capacidad,
      'ubicacion' => $this->ubicacion,
      'enlace_virtual' => $this->enlaceVirtual,
      'estado' => $this->estado,
      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];
  }

  public static function fromArray(array $datos): self {
    return new self(
      isset($datos['id']) ? (int)$datos['id'] : null,
      $datos['nombre'] ?? '',
      $datos['tipo'] ?? 'fisico',
      isset($datos['capacidad']) && $datos['capacidad'] !== null && $datos['capacidad'] !== '' ? (int)$datos['capacidad'] : null,
      $datos['ubicacion'] ?? null,
      $datos['enlace_virtual'] ?? null,
      $datos['estado'] ?? 'activo',
      $datos['created_at'] ?? null,
      $datos['updated_at'] ?? null
    );
  }
}
