<?php

namespace App\Servicios;

use App\Contratos\ReservaRepositorioInterface;
use App\Contratos\ReservaServicioInterface;
use App\Modelos\Reserva;
use InvalidArgumentException;
use Throwable;

class ReservaServicio implements ReservaServicioInterface {

  public function __construct(
    private ReservaRepositorioInterface $reservaRepo
  ) {}

  // Convierte el día de una fecha al formato usado en la BD.
  private function obtenerDiaSemana(string $fecha): string {
    $dias = [
      'Monday' => 'lunes',
      'Tuesday' => 'martes',
      'Wednesday' => 'miercoles',
      'Thursday' => 'jueves',
      'Friday' => 'viernes',
      'Saturday' => 'sabado',
      'Sunday' => 'domingo'
    ];

    $fechaObj = \DateTime::createFromFormat('!Y-m-d', $fecha);

    if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
      throw new InvalidArgumentException('La fecha no es válida.');
    }

    return $dias[$fechaObj->format('l')];
  }

  // Valida los datos de una nueva reserva.
  public function crearReserva(
    int $estudianteId,
    array $datos
  ): array {

    $disponibilidadId = (int)($datos['disponibilidad_id'] ?? 0);
    $fecha = trim($datos['fecha'] ?? '');
    $inicio = trim($datos['hora_inicio'] ?? '');
    $fin = trim($datos['hora_fin'] ?? '');
    $tipo = trim($datos['tipo'] ?? 'individual');
    $cupo = (int)($datos['cupo_maximo'] ?? 1);
    $espacioId = isset($datos['espacio_id'])
      ? (int)$datos['espacio_id']
      : null;

    if ($disponibilidadId <= 0) {
      throw new InvalidArgumentException('Selecciona una disponibilidad válida.');
    }

    if (!in_array($tipo, ['individual', 'grupal'], true)) {
      throw new InvalidArgumentException('El tipo de reserva no es válido.');
    }

    if ($tipo === 'individual' && $cupo !== 1) {
      throw new InvalidArgumentException('Una reserva individual debe tener un cupo.');
    }

    if ($tipo === 'grupal' && ($cupo < 2 || $cupo > 100)) {
      throw new InvalidArgumentException('El cupo grupal debe estar entre 2 y 100.');
    }

    if (
      !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $inicio) ||
      !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $fin) ||
      $inicio >= $fin
    ) {
      throw new InvalidArgumentException('El horario seleccionado no es válido.');
    }

    $dia = $this->obtenerDiaSemana($fecha);

    if ($fecha < date('Y-m-d')) {
      throw new InvalidArgumentException('No puedes reservar en una fecha pasada.');
    }

    $this->reservaRepo->iniciarTransaccion();

    try {
      // Bloquea la disponibilidad para evitar operaciones simultáneas.
      $disponibilidad = $this->reservaRepo
        ->bloquearDisponibilidad($disponibilidadId);

      if (!$disponibilidad) {
        throw new InvalidArgumentException('La disponibilidad no existe o está inactiva.');
      }

      $mentorId = (int)$disponibilidad['mentor_id'];
      $materiaId = (int)$disponibilidad['materia_id'];

      // Serializa las reservas simultáneas del mentor y del estudiante.
      // Así las comprobaciones de cruces ven también transacciones recién confirmadas.
      $this->reservaRepo->bloquearUsuarios([$mentorId, $estudianteId]);

      if ($mentorId === $estudianteId) {
        throw new InvalidArgumentException('No puedes reservar una tutoría contigo mismo.');
      }

      if ($dia !== $disponibilidad['dia_semana']) {
        throw new InvalidArgumentException('El mentor no tiene disponibilidad ese día.');
      }

      if (
        $inicio < substr($disponibilidad['hora_inicio'], 0, 5) ||
        $fin > substr($disponibilidad['hora_fin'], 0, 5)
      ) {
        throw new InvalidArgumentException('El horario está fuera de la disponibilidad del mentor.');
      }

      if ($this->reservaRepo->existeChoqueMentor(
        $mentorId, $fecha, $inicio, $fin
      )) {
        throw new InvalidArgumentException('El mentor ya tiene una reserva en ese horario.');
      }

      if ($this->reservaRepo->existeChoqueEstudiante(
        $estudianteId, $fecha, $inicio, $fin
      )) {
        throw new InvalidArgumentException('Ya tienes otra tutoría en ese horario.');
      }

      $reserva = new Reserva(
        null,
        $mentorId,
        $materiaId,
        $disponibilidadId,
        $espacioId,
        $estudianteId,
        $tipo,
        $fecha,
        $inicio,
        $fin,
        $cupo,
        'pendiente'
      );

      $nuevaReserva = $this->reservaRepo->crear($reserva);

      // El solicitante queda inscrito automáticamente.
      $this->reservaRepo->agregarParticipante(
        $nuevaReserva->getId(),
        $estudianteId
      );

      $resultado = $nuevaReserva->toArray();
      $resultado['participantes'] = 1;

      $this->reservaRepo->confirmarTransaccion();

      return $resultado;

    } catch (Throwable $e) {
      $this->reservaRepo->revertirTransaccion();
      throw $e;
    }
  }

  // Permite que otro estudiante se una a una tutoría grupal.
  public function unirseReserva(
    int $reservaId,
    int $estudianteId
  ): array {

    if ($reservaId <= 0 || $estudianteId <= 0) {
      throw new InvalidArgumentException('Los datos de la reserva no son válidos.');
    }

    $this->reservaRepo->iniciarTransaccion();

    try {
      $reserva = $this->reservaRepo->buscarPorId($reservaId, true);

      if (!$reserva) {
        throw new InvalidArgumentException('La reserva no existe.');
      }

      // Evita que el mismo estudiante confirme en paralelo otra reserva
      // que se cruce con esta, aunque sean reservas distintas.
      $this->reservaRepo->bloquearUsuarios([$estudianteId]);

      if ($reserva['tipo'] !== 'grupal') {
        throw new InvalidArgumentException('Esta reserva no admite más participantes.');
      }

      if (!in_array($reserva['estado'], ['pendiente', 'aceptada'], true)) {
        throw new InvalidArgumentException('La reserva no está disponible.');
      }

      if ((int)$reserva['solicitante_id'] === $estudianteId) {
        throw new InvalidArgumentException('Ya eres el solicitante de esta reserva.');
      }

      if ($this->reservaRepo->esParticipanteActivo($reservaId, $estudianteId)) {
        throw new InvalidArgumentException('Ya estás inscrito en esta reserva.');
      }

      $participantes = $this->reservaRepo->contarParticipantes($reservaId);

      if ($participantes >= (int)$reserva['cupo_maximo']) {
        throw new InvalidArgumentException('La reserva ya alcanzó su cupo máximo.');
      }

      if ($this->reservaRepo->existeChoqueEstudiante(
        $estudianteId,
        $reserva['fecha'],
        $reserva['hora_inicio'],
        $reserva['hora_fin']
      )) {
        throw new InvalidArgumentException('Ya tienes otra tutoría en ese horario.');
      }

      if (!$this->reservaRepo->agregarParticipante(
        $reservaId,
        $estudianteId
      )) {
        throw new InvalidArgumentException('No fue posible registrar tu participación.');
      }

      $reserva['participantes'] = $this->reservaRepo->contarParticipantes($reservaId);

      $this->reservaRepo->confirmarTransaccion();

      return $reserva;

    } catch (Throwable $e) {
      $this->reservaRepo->revertirTransaccion();
      throw $e;
    }
  }

  public function listarPorEstudiante(int $estudianteId): array {
    return $this->reservaRepo->listarPorEstudiante($estudianteId);
  }

  public function listarPorMentor(int $mentorId): array {
    return $this->reservaRepo->listarPorMentor($mentorId);
  }

  public function cancelarParticipacion(
    int $reservaId,
    int $estudianteId
  ): bool {
    if ($reservaId <= 0 || $estudianteId <= 0) {
      throw new InvalidArgumentException('Los datos de la reserva no son válidos.');
    }

    $this->reservaRepo->iniciarTransaccion();

    try {
      // Usa el mismo bloqueo que la inscripción para evitar carreras
      // entre cancelar una participación y ocupar ese cupo.
      $reserva = $this->reservaRepo->buscarPorId($reservaId, true);

      if (!$reserva) {
        $this->reservaRepo->confirmarTransaccion();
        return false;
      }

      $resultado = $this->reservaRepo->cancelarParticipacion(
        $reservaId,
        $estudianteId
      );

      $this->reservaRepo->confirmarTransaccion();
      return $resultado;

    } catch (Throwable $e) {
      $this->reservaRepo->revertirTransaccion();
      throw $e;
    }
  }
    public function aceptarReserva(
    int $reservaId,
    int $mentorId
  ): bool {
    if ($reservaId <= 0 || $mentorId <= 0) {
      throw new InvalidArgumentException('Los datos de la reserva no son válidos.');
    }

    $reserva = $this->reservaRepo->buscarPorId($reservaId);

    if (!$reserva || (int)$reserva['mentor_id'] !== $mentorId) {
      throw new InvalidArgumentException('No tienes permiso para gestionar esta reserva.');
    }

    if ($reserva['estado'] !== 'pendiente') {
      throw new InvalidArgumentException('Esta reserva ya fue gestionada.');
    }

    return $this->reservaRepo->cambiarEstado(
      $reservaId,
      $mentorId,
      'aceptada'
    );
  }

  public function rechazarReserva(
    int $reservaId,
    int $mentorId
  ): bool {
    if ($reservaId <= 0 || $mentorId <= 0) {
      throw new InvalidArgumentException('Los datos de la reserva no son válidos.');
    }

    $reserva = $this->reservaRepo->buscarPorId($reservaId);

    if (!$reserva || (int)$reserva['mentor_id'] !== $mentorId) {
      throw new InvalidArgumentException('No tienes permiso para gestionar esta reserva.');
    }

    if ($reserva['estado'] !== 'pendiente') {
      throw new InvalidArgumentException('Esta reserva ya fue gestionada.');
    }

    return $this->reservaRepo->cambiarEstado(
      $reservaId,
      $mentorId,
      'rechazada'
    );
  }
}
