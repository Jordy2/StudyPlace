<?php

namespace App\Contratos;

use App\Modelos\Usuario;

interface GestionUsuariosServicioInterface {
  public function listar(array $filtros = []): array;
  public function suspender(Usuario $admin, int $idUsuario): array;
  public function reactivar(Usuario $admin, int $idUsuario): array;
  public function cambiarRol(Usuario $admin, int $idUsuario, string $nuevoRol): array;
  public function listarAuditoria(int $limite = 100): array;
}
