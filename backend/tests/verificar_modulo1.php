<?php

require_once __DIR__ . '/../lib/autoload.php';

use App\Contenedor;
use App\Modelos\Usuario;

echo "INICIANDO PRUEBAS AUTOMATIZADAS DEL MÓDULO 1\n\n";

$contenedor = new Contenedor();
$pdo = $contenedor->obtenerConexion();
$authServicio = $contenedor->obtenerAutenticacionServicio();
$authMiddleware = $contenedor->obtenerAutenticacionMiddleware();

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

$correoPrueba = 'estudiante.test@studyplace.local';
$stmtClean = $pdo->prepare('DELETE FROM usuarios WHERE correo = :correo');
$stmtClean->execute([':correo' => $correoPrueba]);

// 1. Registro exitoso de estudiante (RF1)
try {
  $estudiante = $authServicio->registrar([
    'nombre' => 'Estudiante de Prueba',
    'correo' => $correoPrueba,
    'password' => 'Segura1234*',
    'rol' => 'estudiante',
    'semestre' => 4,
  ]);

  assertPrueba(
    'RF1: Registro de estudiante válido con semestre y rol correcto',
    $estudiante->getId() !== null &&
    $estudiante->getRol() === 'estudiante' &&
    $estudiante->getSemestre() === 4 &&
    $estudiante->esActivo()
  );
} catch (Throwable $e) {
  assertPrueba('RF1: Registro de estudiante válido: ' . $e->getMessage(), false);
}

// 2. Bloqueo de registro con rol administrador (RF16)
try {
  $authServicio->registrar([
    'nombre' => 'Intento Admin',
    'correo' => 'admin.fake@studyplace.local',
    'password' => 'Segura1234*',
    'rol' => 'administrador',
  ]);
  assertPrueba('RF16: Bloqueo estricto de registro con rol administrador', false);
} catch (InvalidArgumentException $e) {
  assertPrueba(
    'RF16: Bloqueo estricto de registro con rol administrador',
    str_contains($e->getMessage(), 'no es válido para registro público')
  );
}

// 3. Validación de correo duplicado (RF1)
try {
  $authServicio->registrar([
    'nombre' => 'Otro Nombre',
    'correo' => $correoPrueba,
    'password' => 'Segura1234*',
    'rol' => 'estudiante',
    'semestre' => 2,
  ]);
  assertPrueba('RF1: Rechazo de correo duplicado', false);
} catch (InvalidArgumentException $e) {
  assertPrueba(
    'RF1: Rechazo de correo duplicado',
    str_contains($e->getMessage(), 'ya se encuentra registrado')
  );
}

// 4. Validación de contraseña corta (<8 caracteres)
try {
  $authServicio->registrar([
    'nombre' => 'Pass Corta',
    'correo' => 'corta@studyplace.local',
    'password' => '12345',
    'rol' => 'estudiante',
    'semestre' => 1,
  ]);
  assertPrueba('RF1: Rechazo de contraseña con menos de 8 caracteres', false);
} catch (InvalidArgumentException $e) {
  assertPrueba(
    'RF1: Rechazo de contraseña con menos de 8 caracteres',
    str_contains($e->getMessage(), 'al menos 8 caracteres')
  );
}

// 5. Login exitoso y generación de token (RF2, RNF10)
$tokenSesion = null;
try {
  $loginRes = $authServicio->autenticar($correoPrueba, 'Segura1234*');
  $tokenSesion = $loginRes['token'] ?? '';

  assertPrueba(
    'RF2/RNF10: Inicio de sesión exitoso y generación de Bearer token (64 caracteres)',
    strlen($tokenSesion) === 64 &&
    isset($loginRes['usuario']['correo']) &&
    $loginRes['usuario']['correo'] === $correoPrueba
  );
} catch (Throwable $e) {
  assertPrueba('RF2: Inicio de sesión exitoso: ' . $e->getMessage(), false);
}

// 6. Login fallido con contraseña incorrecta (RF2)
try {
  $authServicio->autenticar($correoPrueba, 'ClaveErronea123');
  assertPrueba('RF2: Rechazo de login con contraseña incorrecta', false);
} catch (DomainException $e) {
  assertPrueba(
    'RF2: Rechazo de login con contraseña incorrecta',
    str_contains($e->getMessage(), 'Credenciales incorrectas')
  );
}

// 7. Validación de sesión con token activo (RF4)
$usuarioValidado = $authServicio->validarToken($tokenSesion);
assertPrueba(
  'RF4: Validación de sesión activa con Bearer token',
  $usuarioValidado !== null && $usuarioValidado->getCorreo() === $correoPrueba
);

// 8. Middleware: extracción y verificación de permisos (RF3, RF4)
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokenSesion;
$usuarioAutenticado = $authMiddleware->autenticar();
assertPrueba(
  'Middleware: Autenticación por cabecera HTTP Authorization Bearer',
  $usuarioAutenticado->getCorreo() === $correoPrueba
);

$rolValido = $usuarioAutenticado->tieneRol('estudiante');
assertPrueba('Middleware: Verificación de rol autorizado (estudiante)', $rolValido);

$rolInvalido = $usuarioAutenticado->tieneRol('administrador');
assertPrueba('Middleware: Bloqueo de rol no autorizado (administrador para estudiante)', !$rolInvalido);

// 9. Cierre de sesión (RF2, RNF10)
$logoutExito = $authServicio->cerrarSesion($tokenSesion);
$usuarioTrasLogout = $authServicio->validarToken($tokenSesion);
assertPrueba(
  'RF2/RNF10: Cierre de sesión e invalidación inmediata del token en la base de datos',
  $logoutExito && $usuarioTrasLogout === null
);

// 10. Cuenta suspendida: bloqueo de acceso (RF2)
$stmtSuspender = $pdo->prepare('UPDATE usuarios SET estado = "suspendido" WHERE correo = :correo');
$stmtSuspender->execute([':correo' => $correoPrueba]);

try {
  $authServicio->autenticar($correoPrueba, 'Segura1234*');
  assertPrueba('RF2: Bloqueo de inicio de sesión para cuenta suspendida', false);
} catch (DomainException $e) {
  assertPrueba(
    'RF2: Bloqueo de inicio de sesión para cuenta suspendida',
    str_contains($e->getMessage(), 'suspendida')
  );
}

$stmtClean->execute([':correo' => $correoPrueba]);

echo "\nRESULTADOS: {$pruebasPasadas}/{$totalPruebas} pruebas superadas exitosamente.\n";

if ($pruebasPasadas === $totalPruebas) {
  exit(0);
} else {
  exit(1);
}
