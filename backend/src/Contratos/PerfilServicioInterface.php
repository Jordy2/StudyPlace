<?php

namespace App\Contratos;

interface PerfilServicioInterface {
  /**
   * Obtiene los datos del perfil de un usuario, incluyendo materias si es mentor.
   *
   * @param int $usuarioId
   * @return array
   */
  public function obtenerPerfil(int $usuarioId): array;

  /**
   * Actualiza los datos personales, académicos y materias dominadas de un usuario.
   *
   * @param int $usuarioId
   * @param array $datos
   * @return array
   */
  public function actualizarPerfil(int $usuarioId, array $datos): array;
}
