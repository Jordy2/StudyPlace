<?php
/**
 * Pruebas unitarias aisladas del Módulo 6.
 * No conecta ni modifica la base de datos real.
 * Ejecutar: php backend/tests/verificar_modulo6.php
 */

require_once __DIR__ . '/../lib/autoload.php';

use App\Contratos\ReservaRepositorioInterface;
use App\Modelos\Reserva;
use App\Servicios\ReservaServicio;

final class ReservaRepositorioFalsoM6 implements ReservaRepositorioInterface {
  public bool $transaccion = false;
  public bool $confirmada = false;
  public bool $revertida = false;
  public bool $choqueMentor = false;
  public bool $choqueEstudiante = false;
  public bool $participanteActivo = false;
  public int $participantes = 0;
  public array $usuariosBloqueados = [];
  public ?array $reserva = null;

  public function iniciarTransaccion(): void { $this->transaccion = true; }
  public function confirmarTransaccion(): void { $this->confirmada = true; $this->transaccion = false; }
  public function revertirTransaccion(): void { $this->revertida = true; $this->transaccion = false; }
  public function bloquearDisponibilidad(int $id): ?array {
    return ['id' => $id, 'mentor_id' => 10, 'materia_id' => 20,
      'dia_semana' => $this->diaPrueba(), 'hora_inicio' => '08:00:00',
      'hora_fin' => '18:00:00', 'estado' => 'activo'];
  }
  private function diaPrueba(): string {
    $dias = ['Monday'=>'lunes','Tuesday'=>'martes','Wednesday'=>'miercoles',
      'Thursday'=>'jueves','Friday'=>'viernes','Saturday'=>'sabado','Sunday'=>'domingo'];
    return $dias[(new DateTimeImmutable('+7 days'))->format('l')];
  }
  public function bloquearUsuarios(array $ids): void { $this->usuariosBloqueados = $ids; }
  public function crear(Reserva $reserva): Reserva {
    $datos = $reserva->toArray(); $datos['id'] = 50;
    $this->reserva = $datos; $this->participantes = 1;
    return Reserva::fromArray($datos);
  }
  public function buscarPorId(int $id, bool $bloquear = false): ?array {
    if ($this->reserva === null) {
      $this->reserva = ['id'=>$id,'mentor_id'=>10,'materia_id'=>20,'disponibilidad_id'=>1,
        'espacio_id'=>null,'solicitante_id'=>11,'tipo'=>'grupal','fecha'=>(new DateTimeImmutable('+7 days'))->format('Y-m-d'),
        'hora_inicio'=>'10:00:00','hora_fin'=>'11:00:00','cupo_maximo'=>3,'estado'=>'pendiente'];
    }
    $r = $this->reserva; $r['participantes'] = $this->participantes; return $r;
  }
  public function agregarParticipante(int $reservaId, int $estudianteId): bool { $this->participantes++; $this->participanteActivo = true; return true; }
  public function esParticipanteActivo(int $reservaId, int $estudianteId): bool { return $this->participanteActivo; }
  public function contarParticipantes(int $reservaId): int { return $this->participantes; }
  public function existeChoqueMentor(int $mentorId, string $fecha, string $inicio, string $fin, ?int $excluirReservaId = null): bool { return $this->choqueMentor; }
  public function existeChoqueEstudiante(int $estudianteId, string $fecha, string $inicio, string $fin): bool { return $this->choqueEstudiante; }
  public function listarPorEstudiante(int $estudianteId): array { return []; }
  public function listarPorMentor(int $mentorId): array { return []; }
  public function cancelarParticipacion(int $reservaId, int $estudianteId): bool { return true; }
  public function cambiarEstado(int $reservaId, int $mentorId, string $estado): bool { return true; }
}

$correctas = 0; $fallidas = 0;
function comprobar(string $nombre, callable $prueba): void {
  global $correctas, $fallidas;
  try { $prueba(); echo "[OK] $nombre\n"; $correctas++; }
  catch (Throwable $e) { echo "[FALLO] $nombre: {$e->getMessage()}\n"; $fallidas++; }
}
function esperarError(callable $accion, string $fragmento): void {
  try { $accion(); } catch (InvalidArgumentException $e) {
    if (str_contains($e->getMessage(), $fragmento)) return;
    throw new RuntimeException('Mensaje inesperado: '.$e->getMessage());
  }
  throw new RuntimeException('Se esperaba InvalidArgumentException.');
}
function datosBase(array $cambios = []): array {
  return array_merge(['disponibilidad_id'=>1,'fecha'=>(new DateTimeImmutable('+7 days'))->format('Y-m-d'),
    'hora_inicio'=>'10:00','hora_fin'=>'11:00','tipo'=>'individual','cupo_maximo'=>1], $cambios);
}

comprobar('Rechaza reserva individual con cupo distinto de 1', function () {
  $s = new ReservaServicio(new ReservaRepositorioFalsoM6());
  esperarError(fn()=>$s->crearReserva(11, datosBase(['cupo_maximo'=>2])), 'cupo');
});
comprobar('Rechaza reserva grupal con cupo menor de 2', function () {
  $s = new ReservaServicio(new ReservaRepositorioFalsoM6());
  esperarError(fn()=>$s->crearReserva(11, datosBase(['tipo'=>'grupal','cupo_maximo'=>1])), 'cupo');
});
comprobar('Rechaza horario donde inicio no es anterior al fin', function () {
  $s = new ReservaServicio(new ReservaRepositorioFalsoM6());
  esperarError(fn()=>$s->crearReserva(11, datosBase(['hora_inicio'=>'11:00','hora_fin'=>'10:00'])), 'horario');
});
comprobar('Rechaza choque de horario del mentor', function () {
  $r = new ReservaRepositorioFalsoM6(); $r->choqueMentor = true; $s = new ReservaServicio($r);
  esperarError(fn()=>$s->crearReserva(11, datosBase()), 'mentor ya tiene');
  if (!$r->revertida) throw new RuntimeException('No revirtió la transacción.');
});
comprobar('Rechaza choque de horario del estudiante', function () {
  $r = new ReservaRepositorioFalsoM6(); $r->choqueEstudiante = true; $s = new ReservaServicio($r);
  esperarError(fn()=>$s->crearReserva(11, datosBase()), 'Ya tienes');
  if (!$r->revertida) throw new RuntimeException('No revirtió la transacción.');
});
comprobar('Crea reserva válida y solicita bloqueo de mentor y estudiante', function () {
  $r = new ReservaRepositorioFalsoM6(); $s = new ReservaServicio($r);
  $resultado = $s->crearReserva(11, datosBase());
  if (($resultado['id'] ?? null) !== 50 || $resultado['participantes'] !== 1 || !$r->confirmada) throw new RuntimeException('No creó/confirmó la reserva correctamente.');
  if ($r->usuariosBloqueados !== [10,11]) throw new RuntimeException('No bloqueó los usuarios esperados.');
});
comprobar('Rechaza unirse cuando la reserva grupal ya está llena', function () {
  $r = new ReservaRepositorioFalsoM6(); $r->participantes = 3; $s = new ReservaServicio($r);
  esperarError(fn()=>$s->unirseReserva(50,12), 'cupo máximo');
  if (!$r->revertida) throw new RuntimeException('No revirtió la transacción.');
});
comprobar('Rechaza unirse si el estudiante tiene choque de horario', function () {
  $r = new ReservaRepositorioFalsoM6(); $r->choqueEstudiante = true; $s = new ReservaServicio($r);
  esperarError(fn()=>$s->unirseReserva(50,12), 'otra tutoría');
});
comprobar('Permite unirse a reserva grupal disponible y bloquea al estudiante', function () {
  $r = new ReservaRepositorioFalsoM6(); $s = new ReservaServicio($r);
  $resultado = $s->unirseReserva(50,12);
  if (($resultado['participantes'] ?? null) !== 1 || !$r->confirmada || $r->usuariosBloqueados !== [12]) throw new RuntimeException('No completó inscripción y bloqueo.');
});

echo "\nResultado M6: $correctas correctas; $fallidas fallidas.\n";
exit($fallidas === 0 ? 0 : 1);
