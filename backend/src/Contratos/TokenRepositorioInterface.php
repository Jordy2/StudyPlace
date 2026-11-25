<?php

namespace App\Contratos;

use App\Modelos\TokenSesion;

interface TokenRepositorioInterface {
  public function crearToken(int $usuarioId, string $token, string $expiraEn): bool;
  public function buscarToken(string $token): ?TokenSesion;
  public function eliminarToken(string $token): bool;
  public function eliminarTokensDeUsuario(int $usuarioId): bool;
}
