<?php

require_once __DIR__ . '/../lib/autoload.php';

use App\Contenedor;

echo "INICIANDO PRUEBAS DEL MÓDULO 4\n\n";

$pdo = (new Contenedor())->obtenerConexion();
$contenedor = new Contenedor($pdo);
$disponibilidadServicio = $contenedor->obtenerDisponibilidadServicio();
$pasadas = 0;
$total = 0;

function prueba(string $descripcion, bool $condicion): void {
  global $pasadas, $total;
  $total++;
  echo ($condicion ? '[PASS] ' : '[FAIL] ') . $descripcion . "\n";
  if ($condicion) $pasadas++;
}

$correo = 'mentor.modulos4_5@studyplace.local';
$pdo->prepare('DELETE FROM usuarios WHERE correo=:correo')->execute([':correo' => $correo]);
$pdo->prepare(
  'INSERT INTO usuarios (nombre,correo,password_hash,rol,semestre,estado)
   VALUES (?,?,?,?,?,?)'
)->execute([
  'Mentor Modulos 4 5',
  $correo,
  password_hash('Mentor123*', PASSWORD_BCRYPT),
  'mentor',
  6,
  'activo',
]);
$mentorId = (int)$pdo->lastInsertId();
$materiaId = (int)$pdo->query('SELECT id FROM materias WHERE estado="activo" ORDER BY id LIMIT 1')->fetchColumn();

try {
  $horario = $disponibilidadServicio->crear($mentorId, [
    'materia_id' => $materiaId,
    'dia_semana' => 'lunes',
    'hora_inicio' => '09:00',
    'hora_fin' => '11:00',
  ]);
  prueba('M4: creación válida de disponibilidad', $horario->getId() !== null);
} catch (Throwable $e) {
  prueba('M4: creación válida (' . $e->getMessage() . ')', false);
}

try {
  $disponibilidadServicio->crear($mentorId, [
    'materia_id' => $materiaId,
    'dia_semana' => 'lunes',
    'hora_inicio' => '10:30',
    'hora_fin' => '12:00',
  ]);
  prueba('M4: rechazo de choque horario', false);
} catch (InvalidArgumentException $e) {
  prueba('M4: rechazo de choque horario', str_contains($e->getMessage(), 'cruza'));
}

$pdo->prepare('DELETE FROM usuarios WHERE id=:id')->execute([':id' => $mentorId]);
echo "\nResultado: {$pasadas}/{$total} pruebas pasadas.\n";
