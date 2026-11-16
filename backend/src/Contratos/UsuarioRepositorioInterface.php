<?php

namespace App\Contratos;

use App\Modelos\Usuario;

interface UsuarioRepositorioInterface {
  public function buscarPorId(int $id): ?Usuario;
  public function buscarPorCorreo(string $correo): ?Usuario;
  public function crear(Usuario $usuario): Usuario;
  public function existeCorreo(string $correo): bool;
}
