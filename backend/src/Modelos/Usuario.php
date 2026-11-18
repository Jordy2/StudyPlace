<?php

namespace App\Modelos;

class Usuario {
  private ?int $id;
  private string $nombre;
  private string $correo;
  private string $passwordHash;
  private string $rol;
  private ?int $semestre;
  private string $estado;
  private ?string $createdAt;
  private ?string $updatedAt;

  public function __construct(
    ?int $id,
    string $nombre,
    string $correo,
    string $passwordHash,
    string $rol,
    ?int $semestre = null,
    string $estado = 'activo',
    ?string $createdAt = null,
    ?string $updatedAt = null
  ) {
    $this->id = $id;
    $this->nombre = $nombre;
    $this->correo = $correo;
    $this->passwordHash = $passwordHash;
    $this->rol = $rol;
    $this->semestre = $semestre;
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

  public function getCorreo(): string {
    return $this->correo;
  }

  public function getPasswordHash(): string {
    return $this->passwordHash;
  }

  public function getRol(): string {
    return $this->rol;
  }

  public function getSemestre(): ?int {
    return $this->semestre;
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

  public function setNombre(string $nombre): void {
    $this->nombre = $nombre;
  }

  public function setCorreo(string $correo): void {
    $this->correo = $correo;
  }

  public function setPasswordHash(string $passwordHash): void {
    $this->passwordHash = $passwordHash;
  }

  public function setSemestre(?int $semestre): void {
    $this->semestre = $semestre;
  }

  public function setEstado(string $estado): void {
    $this->estado = $estado;
  }

  public function esActivo(): bool {
    return $this->estado === 'activo';
  }

  public function esSuspendido(): bool {
    return $this->estado === 'suspendido';
  }

  public function esEliminado(): bool {
    return $this->estado === 'eliminado';
  }

  public function tieneRol(string|array $roles): bool {
    if (is_array($roles)) {
      return in_array($this->rol, $roles, true);
    }
    return $this->rol === $roles;
  }

  public function toArray(bool $incluirHash = false): array {
    $datos = [
      'id' => $this->id,
      'nombre' => $this->nombre,
      'correo' => $this->correo,
      'rol' => $this->rol,
      'semestre' => $this->semestre,
      'estado' => $this->estado,
      'created_at' => $this->createdAt,
      'updated_at' => $this->updatedAt,
    ];

    if ($incluirHash) {
      $datos['password_hash'] = $this->passwordHash;
    }

    return $datos;
  }

  public static function fromArray(array $datos): self {
    return new self(
      isset($datos['id']) ? (int)$datos['id'] : null,
      $datos['nombre'] ?? '',
      $datos['correo'] ?? '',
      $datos['password_hash'] ?? '',
      $datos['rol'] ?? 'estudiante',
      isset($datos['semestre']) && $datos['semestre'] !== null ? (int)$datos['semestre'] : null,
      $datos['estado'] ?? 'activo',
      $datos['created_at'] ?? null,
      $datos['updated_at'] ?? null
    );
  }
}
