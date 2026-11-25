<?php

namespace App\Contratos;

interface AuditoriaRepositorioInterface {
  public function registrar(int $adminId, string $accion, ?int $usuarioAfectadoId, ?string $detalle = null): void;
  public function listar(int $limite = 100): array;
}
