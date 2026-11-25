<?php

require_once __DIR__ . '/../lib/autoload.php';

use App\Contenedor;

echo "INICIANDO PRUEBAS AUTOMATIZADAS DEL MÓDULO 10 (BASE: PUNTOS XP)\n\n";

$contenedor = new Contenedor();
$pdo = $contenedor->obtenerConexion();
$gamificacion = $contenedor->obtenerGamificacionServicio();

$totalPruebas = 0;
$pruebasPasadas = 0;

function assertPrueba(string $descripcion, bool $condicion): void {
  global $totalPruebas, $pruebasPasadas;
  $totalPruebas++;
  if ($condicion) {
    $pruebasPasadas++;
    echo "[PASS] " . $descripcion . "\n";
  } else {
    echo "[FAIL] " . $descripcion . "\n";
  }
}

// Usuario temporal solo para esta prueba
$correoPrueba = 'xp.test@studyplace.local';
$stmtClean = $pdo->prepare('DELETE FROM usuarios WHERE correo = :correo');
$stmtClean->execute([':correo' => $correoPrueba]);

$stmtCrear = $pdo->prepare(
  "INSERT INTO usuarios (nombre, correo, password_hash, rol, semestre)
   VALUES ('Estudiante XP', :correo, 'no_usar', 'estudiante', 1)"
);
$stmtCrear->execute([':correo' => $correoPrueba]);
$usuarioId = (int) $pdo->lastInsertId();

try {
  // 1. Las reglas iniciales existen (RF20)
  $stmtReglas = $pdo->query("SELECT COUNT(*) FROM reglas_puntos WHERE estado = 'activo'");
  assertPrueba(
    'RF20: Las 7 reglas de puntos iniciales están cargadas en la tabla',
    (int) $stmtReglas->fetchColumn() >= 7
  );

  // 2. Otorgar puntos por primera vez (RF20)
  assertPrueba(
    'RF20: Se otorgan 50 puntos por completar_perfil la primera vez',
    $gamificacion->otorgarPuntos($usuarioId, 'completar_perfil') === 50
  );

  // 3. No duplicar puntos (RNF8)
  assertPrueba(
    'RNF8: Repetir la misma acción no otorga puntos de nuevo',
    $gamificacion->otorgarPuntos($usuarioId, 'completar_perfil') === 0
  );

  // 4. Misma acción con referencias distintas (RNF8)
  assertPrueba(
    'RNF8: solicitar_sesion con referencia 1 otorga 10 puntos',
    $gamificacion->otorgarPuntos($usuarioId, 'solicitar_sesion', '1') === 10
  );

  assertPrueba(
    'RNF8: solicitar_sesion con otra referencia (2) vuelve a otorgar 10 puntos',
    $gamificacion->otorgarPuntos($usuarioId, 'solicitar_sesion', '2') === 10
  );

  assertPrueba(
    'RNF8: repetir solicitar_sesion con la referencia 1 no otorga puntos',
    $gamificacion->otorgarPuntos($usuarioId, 'solicitar_sesion', '1') === 0
  );

  // 5. Acción inexistente
  assertPrueba(
    'RF20: Una acción que no existe en la tabla de reglas devuelve 0 puntos',
    $gamificacion->otorgarPuntos($usuarioId, 'accion_que_no_existe') === 0
  );

  // 6. XP total
  $total = $gamificacion->obtenerXpTotal($usuarioId);
  assertPrueba(
    "RF20: El XP total acumulado es 70 (50 + 10 + 10), obtenido: {$total}",
    $total === 70
  );

  // 7. Historial: solo 3 filas, sin duplicados (RNF8)
  $stmtHist = $pdo->prepare('SELECT COUNT(*) FROM historial_xp WHERE usuario_id = :id');
  $stmtHist->execute([':id' => $usuarioId]);
  assertPrueba(
    'RNF8: El historial de XP tiene exactamente 3 registros, sin duplicados',
    (int) $stmtHist->fetchColumn() === 3
  );

  // 8. Cálculo de nivel (RF20)
  $nivel = $gamificacion->calcularNivel(250);
  assertPrueba(
    'RF20: calcularNivel(250) devuelve nivel 3 con 50% de progreso',
    $nivel['nivel'] === 3 && $nivel['progreso_porcentaje'] === 50
  );
} catch (Throwable $e) {
  assertPrueba('Error inesperado durante las pruebas: ' . $e->getMessage(), false);
}

// Limpieza (el historial se borra solo por ON DELETE CASCADE)
$stmtClean->execute([':correo' => $correoPrueba]);

echo "\nRESULTADOS: {$pruebasPasadas}/{$totalPruebas} pruebas superadas exitosamente.\n";

if ($pruebasPasadas === $totalPruebas) {
  exit(0);
} else {
  exit(1);
}