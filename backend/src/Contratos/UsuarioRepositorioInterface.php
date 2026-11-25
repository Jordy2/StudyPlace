<?php

namespace App\Contratos;

use App\Modelos\Usuario;

interface UsuarioRepositorioInterface {
  public function buscarPorId(int $id): ?Usuario;
  public function buscarPorCorreo(string $correo): ?Usuario;
  public function crear(Usuario $usuario): Usuario;
  public function actualizar(Usuario $usuario): bool;
  public function existeCorreo(string $correo): bool;
  public function existeOtroConCorreo(string $correo, int $idExcluir): bool;
  public function listar(array $filtros = []): array;
  public function actualizarRol(int $id, string $rol): bool;
  public function actualizarEstado(int $id, string $estado): bool;
}
