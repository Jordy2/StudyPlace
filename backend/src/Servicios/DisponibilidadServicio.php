<?php

namespace App\Servicios;

use App\Contratos\DisponibilidadRepositorioInterface;
use App\Contratos\DisponibilidadServicioInterface;
use App\Contratos\MateriaRepositorioInterface;
use App\Modelos\Disponibilidad;
use InvalidArgumentException;

class DisponibilidadServicio implements DisponibilidadServicioInterface {
  public function __construct(
    private DisponibilidadRepositorioInterface $disponibilidadRepo,
    private MateriaRepositorioInterface $materiaRepo
  ) {}

  public function listarPorMentor(int $mentorId): array {
    return $this->disponibilidadRepo->obtenerPorMentor($mentorId);
  }

  private function validarDatos(int $mentorId, array $datos, ?int $excluirId = null): array {
    $dias = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
    $materiaId = (int)($datos['materia_id'] ?? 0);
    $dia = strtolower(trim($datos['dia_semana'] ?? ''));
    $inicio = trim($datos['hora_inicio'] ?? '');
    $fin = trim($datos['hora_fin'] ?? '');

    if ($materiaId <= 0 || $this->materiaRepo->buscarPorId($materiaId) === null) {
      throw new InvalidArgumentException('La materia seleccionada no existe.');
    }
    if (!in_array($dia, $dias, true)) {
      throw new InvalidArgumentException('El día seleccionado no es válido.');
    }
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $inicio) ||
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $fin)) {
      throw new InvalidArgumentException('Las horas deben tener formato HH:MM.');
    }
    if ($inicio >= $fin) {
      throw new InvalidArgumentException('La hora de inicio debe ser anterior a la hora de fin.');
    }
    if ($this->disponibilidadRepo->existeChoque($mentorId, $dia, $inicio, $fin, $excluirId)) {
      throw new InvalidArgumentException('El horario se cruza con otra disponibilidad del mentor.');
    }

    return [$materiaId, $dia, $inicio, $fin];
  }

  public function crear(int $mentorId, array $datos): Disponibilidad {
    [$materiaId, $dia, $inicio, $fin] = $this->validarDatos($mentorId, $datos);
    $d = new Disponibilidad(null, $mentorId, $materiaId, $dia, $inicio, $fin);
    return $this->disponibilidadRepo->crear($d);
  }

  public function actualizar(int $mentorId, int $id, array $datos): Disponibilidad {
    $existente = $this->disponibilidadRepo->buscarPorId($id);
    if ($existente === null || $existente->getMentorId() !== $mentorId) {
      throw new InvalidArgumentException('La disponibilidad no existe.');
    }
    $data = $existente->toArray();
    $datosCompletos = [
      'materia_id' => $datos['materia_id'] ?? $data['materia_id'],
      'dia_semana' => $datos['dia_semana'] ?? $data['dia_semana'],
      'hora_inicio' => $datos['hora_inicio'] ?? $data['hora_inicio'],
      'hora_fin' => $datos['hora_fin'] ?? $data['hora_fin'],
    ];
    [$materiaId, $dia, $inicio, $fin] = $this->validarDatos($mentorId, $datosCompletos, $id);
    $d = new Disponibilidad($id, $mentorId, $materiaId, $dia, $inicio, $fin);
    $this->disponibilidadRepo->actualizar($d);
    return $this->disponibilidadRepo->buscarPorId($id);
  }

  public function eliminar(int $mentorId, int $id): bool {
    $existente = $this->disponibilidadRepo->buscarPorId($id);
    if ($existente === null || $existente->getMentorId() !== $mentorId) {
      throw new InvalidArgumentException('La disponibilidad no existe.');
    }
    return $this->disponibilidadRepo->eliminar($id, true);
  }

  public function buscarMentores(?int $materiaId, ?string $dia): array {
    if ($materiaId !== null && $this->materiaRepo->buscarPorId($materiaId) === null) {
      throw new InvalidArgumentException('La materia seleccionada no existe.');
    }
    $dias = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
    $diaNormalizado = $dia === null ? null : strtolower(trim($dia));
    if ($diaNormalizado !== null && $diaNormalizado !== '' && !in_array($diaNormalizado, $dias, true)) {
      throw new InvalidArgumentException('El día seleccionado no es válido.');
    }
    return $this->disponibilidadRepo->buscarMentores(
      $materiaId,
      $diaNormalizado === '' ? null : $diaNormalizado
    );
  }
}
