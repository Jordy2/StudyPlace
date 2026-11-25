<?php

require_once __DIR__ . '/../lib/autoload.php';

use App\Contenedor;
use App\Contratos\AuditoriaRepositorioInterface;
use App\Contratos\GestionUsuariosServicioInterface;
use App\Contratos\UsuarioRepositorioInterface;

echo "INICIANDO PRUEBAS AUTOMATIZADAS DEL MÓDULO 11 BASE (RF16, RF17, RF19 base, RNF7, SOLID)\n\n";

$contenedor = new Contenedor();
$pdo = $contenedor->obtenerConexion();
$authServicio = $contenedor->obtenerAutenticacionServicio();
$gestion = $contenedor->obtenerGestionUsuariosServicio();
$auditoriaRepo = $contenedor->obtenerAuditoriaRepositorio();
$usuarioRepo = $contenedor->obtenerUsuarioRepositorio();

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

function limpiar(PDO $pdo): void {
  $pdo->exec("DELETE FROM auditoria_admin WHERE usuario_afectado_id IN (SELECT id FROM usuarios WHERE correo LIKE '%@m11test.local')");
  $pdo->exec("DELETE FROM usuarios WHERE correo LIKE '%@m11test.local'");
}

limpiar($pdo);

// Preparación: administrador semilla y usuarios de prueba
$admin = $authServicio->validarToken(
  $authServicio->autenticar('admin@studyplace.local', 'Admin123*')['token']
);

$estudiante = $authServicio->registrar([
  'nombre' => 'Estudiante M11',
  'correo' => 'estudiante@m11test.local',
  'password' => 'Prueba1234',
  'rol' => 'estudiante',
  'semestre' => 3,
]);
$idEst = (int)$estudiante->getId();

$pdo->exec(
  "INSERT INTO usuarios (nombre, correo, password_hash, rol, estado)
   VALUES ('Otro Admin M11', 'otroadmin@m11test.local', 'x', 'administrador', 'activo')"
);
$idOtroAdmin = (int)$pdo->lastInsertId();

// 1. SOLID
assertPrueba(
  'SOLID (DIP/LSP): Servicio y repositorios de M11 implementan sus interfaces',
  $gestion instanceof GestionUsuariosServicioInterface &&
  $auditoriaRepo instanceof AuditoriaRepositorioInterface &&
  $usuarioRepo instanceof UsuarioRepositorioInterface
);

assertPrueba('RF16: existe el administrador semilla', $admin !== null && $admin->tieneRol('administrador'));

// 2. RF17: listar y filtrar
$lista = $gestion->listar(['q' => 'm11test.local']);
assertPrueba('RF17: listar con búsqueda devuelve los usuarios de prueba', count($lista) >= 2);

$soloEst = $gestion->listar(['rol' => 'estudiante', 'q' => 'm11test.local']);
assertPrueba(
  'RF17: filtro por rol devuelve solo estudiantes y no expone password_hash',
  count($soloEst) === 1 && !array_key_exists('password_hash', $soloEst[0])
);

try {
  $gestion->listar(['rol' => 'inexistente']);
  assertPrueba('RNF2: rechazo de filtro de rol inválido', false);
} catch (InvalidArgumentException $e) {
  assertPrueba('RNF2: rechazo de filtro de rol inválido', true);
}

// 3. RF17: suspender y cierre de sesión
$authServicio->autenticar('estudiante@m11test.local', 'Prueba1234');
$resultado = $gestion->suspender($admin, $idEst);
assertPrueba('RF17: suspender cambia el estado a suspendido', $resultado['estado'] === 'suspendido');

$tokensRestantes = (int)$pdo->query("SELECT COUNT(*) FROM tokens_sesion WHERE usuario_id = {$idEst}")->fetchColumn();
assertPrueba('RF17/RNF3: al suspender se cierran las sesiones activas', $tokensRestantes === 0);

try {
  $authServicio->autenticar('estudiante@m11test.local', 'Prueba1234');
  assertPrueba('RF2: cuenta suspendida no puede iniciar sesión', false);
} catch (DomainException $e) {
  assertPrueba('RF2: cuenta suspendida no puede iniciar sesión', str_contains($e->getMessage(), 'suspendida'));
}

try {
  $gestion->suspender($admin, $idEst);
  assertPrueba('RF17: no se puede suspender dos veces', false);
} catch (InvalidArgumentException $e) {
  assertPrueba('RF17: no se puede suspender dos veces', true);
}

// 4. RF17: reactivar
$resultado = $gestion->reactivar($admin, $idEst);
assertPrueba('RF17: reactivar cambia el estado a activo', $resultado['estado'] === 'activo');

try {
  $authServicio->autenticar('estudiante@m11test.local', 'Prueba1234');
  assertPrueba('RF17: usuario reactivado puede iniciar sesión', true);
} catch (Throwable $e) {
  assertPrueba('RF17: usuario reactivado puede iniciar sesión', false);
}

// 5. RF17: cambiar rol
$resultado = $gestion->cambiarRol($admin, $idEst, 'mentor');
assertPrueba('RF17: cambiar rol de estudiante a mentor', $resultado['rol'] === 'mentor');

try {
  $gestion->cambiarRol($admin, $idEst, 'administrador');
  assertPrueba('RF17: no se puede asignar el rol administrador', false);
} catch (InvalidArgumentException $e) {
  assertPrueba('RF17: no se puede asignar el rol administrador', true);
}

try {
  $gestion->cambiarRol($admin, $idEst, 'mentor');
  assertPrueba('RF17: rechazo de cambio al mismo rol', false);
} catch (InvalidArgumentException $e) {
  assertPrueba('RF17: rechazo de cambio al mismo rol', true);
}

// 6. RF17: protecciones sobre administradores
try {
  $gestion->suspender($admin, (int)$admin->getId());
  assertPrueba('RF17: el administrador no puede suspenderse a sí mismo', false);
} catch (DomainException $e) {
  assertPrueba('RF17: el administrador no puede suspenderse a sí mismo', true);
}

try {
  $gestion->suspender($admin, $idOtroAdmin);
  assertPrueba('RF17: no se puede suspender a otro administrador', false);
} catch (DomainException $e) {
  assertPrueba('RF17: no se puede suspender a otro administrador', true);
}

try {
  $gestion->cambiarRol($admin, $idOtroAdmin, 'estudiante');
  assertPrueba('RF17: no se puede cambiar el rol de otro administrador', false);
} catch (DomainException $e) {
  assertPrueba('RF17: no se puede cambiar el rol de otro administrador', true);
}

try {
  $gestion->suspender($admin, 999999999);
  assertPrueba('RF17: usuario inexistente produce error', false);
} catch (OutOfBoundsException $e) {
  assertPrueba('RF17: usuario inexistente produce error', true);
}

// 7. RF19 / RNF7: auditoría
$stmt = $pdo->prepare('SELECT accion, admin_id FROM auditoria_admin WHERE usuario_afectado_id = :id ORDER BY id');
$stmt->execute([':id' => $idEst]);
$filas = $stmt->fetchAll();
$acciones = array_column($filas, 'accion');

assertPrueba(
  'RF19/RNF7: se registraron suspender, reactivar y cambiar rol',
  $acciones === ['suspender_usuario', 'reactivar_usuario', 'cambiar_rol']
);

assertPrueba(
  'RF19: la auditoría guarda quién ejecutó la acción',
  count($filas) === 3 && (int)$filas[0]['admin_id'] === (int)$admin->getId()
);

$historial = $gestion->listarAuditoria(10);
assertPrueba(
  'RF19: el historial incluye nombre del administrador y está ordenado',
  count($historial) > 0 && isset($historial[0]['admin_nombre'])
);

limpiar($pdo);

echo "\n" . sprintf("RESULTADOS: %d/%d pruebas superadas exitosamente.\n", $pruebasPasadas, $totalPruebas);
