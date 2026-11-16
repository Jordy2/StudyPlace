<?php

namespace App\Contratos;

use App\Modelos\Usuario;

interface AutenticacionServicioInterface {
  public function registrar(array $datos): Usuario;
  public function autenticar(string $correo, string $password): array;
  public function cerrarSesion(string $token): bool;
  public function validarToken(string $token): ?Usuario;
}
