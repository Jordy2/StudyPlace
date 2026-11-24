<?php

require_once __DIR__ . '/../lib/autoload.php';

use App\Contenedor;

echo "INICIANDO PRUEBAS DE LOS MÓDULOS 4 Y 5\n\n";

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
    'dia_semana' => 'lunes',
    'hora_inicio' => '09:00',
    'hora_fin' => '11:00',
  ]);
  prueba('M4: creación válida de disponibilidad', $horario->getId() !== null);
} catch (Throwable $e) {
  prueba('M4: creación válida (' . $e->getMessage() . ')', false);
}

if (isset($horario)) {
  try {
    $actualizado = $disponibilidadServicio->actualizar($mentorId, $horario->getId(), [
      'hora_inicio' => '09:30',
      'hora_fin' => '11:30',
    ]);
    prueba(
      'M4: actualización de una franja general',
      $actualizado->toArray()['hora_inicio'] === '09:30' &&
      $actualizado->toArray()['hora_fin'] === '11:30'
    );
    $horario = $actualizado;
  } catch (Throwable $e) {
    prueba('M4: actualización de una franja (' . $e->getMessage() . ')', false);
  }
}

$buscarMentor = function () use ($disponibilidadServicio, $materiaId, $mentorId): bool {
  $resultados = $disponibilidadServicio->buscarMentores($materiaId, 'lunes');
  return count(array_filter($resultados, fn($mentor) => (int)$mentor['id'] === $mentorId)) > 0;
};

prueba('M5: no lista mentores que no declararon la materia en su perfil', !$buscarMentor());

$pdo->prepare('INSERT INTO mentor_materias (usuario_id,materia_id) VALUES (:usuario,:materia)')
  ->execute([':usuario' => $mentorId, ':materia' => $materiaId]);
prueba('M5: búsqueda por materia y día usa materias dominadas de M2 y horarios de M4', $buscarMentor());

$pdo->prepare('DELETE FROM mentor_materias WHERE usuario_id=:usuario AND materia_id=:materia')
  ->execute([':usuario' => $mentorId, ':materia' => $materiaId]);
prueba('M5: deja de listar al mentor si quita la materia dominada de su perfil', !$buscarMentor());

try {
  $disponibilidadServicio->crear($mentorId, [
    'dia_semana' => 'lunes',
    'hora_inicio' => '10:30',
    'hora_fin' => '12:00',
  ]);
  prueba('M4: rechazo de choque horario', false);
} catch (InvalidArgumentException $e) {
  prueba('M4: rechazo de choque horario', str_contains($e->getMessage(), 'cruza'));
}

if (isset($horario)) {
  try {
    $disponibilidadServicio->eliminar($mentorId, $horario->getId());
    $horariosActivos = $disponibilidadServicio->listarPorMentor($mentorId);
    prueba(
      'M4: baja lógica de una franja general',
      count(array_filter($horariosActivos, fn($d) => $d->getId() === $horario->getId())) === 0
    );
  } catch (Throwable $e) {
    prueba('M4: baja lógica de una franja (' . $e->getMessage() . ')', false);
  }
}

$pdo->prepare('DELETE FROM usuarios WHERE id=:id')->execute([':id' => $mentorId]);
echo "\nResultado: {$pasadas}/{$total} pruebas pasadas.\n";
