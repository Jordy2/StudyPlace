<?php

namespace App\Contratos;

interface MentorMateriaRepositorioInterface {
  /**
   * Obtiene la lista de entidades Materia que un mentor domina.
   *
   * @param int $usuarioId
   * @return array<\App\Modelos\Materia>
   */
  public function obtenerMateriasPorMentor(int $usuarioId): array;

  /**
   * Obtiene los IDs de las materias dominadas por el mentor.
   *
   * @param int $usuarioId
   * @return array<int>
   */
  public function obtenerIdsMateriasPorMentor(int $usuarioId): array;

  /**
   * Sincroniza atómicamente el catálogo de materias dominadas por el mentor.
   *
   * @param int $usuarioId
   * @param array<int> $materiaIds
   * @return bool
   */
  public function sincronizarMateriasMentor(int $usuarioId, array $materiaIds): bool;

  /**
   * Obtiene los usuarios mentores que imparten una materia específica.
   *
   * @param int $materiaId
   * @return array<\App\Modelos\Usuario>
   */
  public function obtenerMentoresPorMateria(int $materiaId): array;
}
