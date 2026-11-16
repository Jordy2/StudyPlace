<?php

namespace App\Modelos;

class TokenSesion {
  private ?int $id;
  private int $usuarioId;
  private string $token;
  private string $expiraEn;
  private ?string $creadoEn;

  public function __construct(
    ?int $id,
    int $usuarioId,
    string $token,
    string $expiraEn,
    ?string $creadoEn = null
  ) {
    $this->id = $id;
    $this->usuarioId = $usuarioId;
    $this->token = $token;
    $this->expiraEn = $expiraEn;
    $this->creadoEn = $creadoEn;
  }

  public function getId(): ?int {
    return $this->id;
  }

  public function getUsuarioId(): int {
    return $this->usuarioId;
  }

  public function getToken(): string {
    return $this->token;
  }

  public function getExpiraEn(): string {
    return $this->expiraEn;
  }

  public function getCreadoEn(): ?string {
    return $this->creadoEn;
  }

  public function haExpirado(): bool {
    return strtotime($this->expiraEn) <= time();
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'usuario_id' => $this->usuarioId,
      'token' => $this->token,
      'expira_en' => $this->expiraEn,
      'creado_en' => $this->creadoEn,
    ];
  }

  public static function fromArray(array $datos): self {
    return new self(
      isset($datos['id']) ? (int)$datos['id'] : null,
      (int)($datos['usuario_id'] ?? 0),
      $datos['token'] ?? '',
      $datos['expira_en'] ?? '',
      $datos['creado_en'] ?? null
    );
  }
}
