<?php

require_once __DIR__ . '/../lib/autoload.php';

use App\Contenedor;
use App\Contratos\EspacioRepositorioInterface;
use App\Contratos\EspacioServicioInterface;
use App\Contratos\MateriaRepositorioInterface;
use App\Contratos\MateriaServicioInterface;
use App\Modelos\Espacio;
use App\Modelos\Materia;

echo "INICIANDO PRUEBAS AUTOMATIZADAS DEL MÓDULO 3 (RF6, RF8, RNF4, SOLID)\n\n";

$contenedor = new Contenedor();
$pdo = $contenedor->obtenerConexion();
$authServicio = $contenedor->obtenerAutenticacionServicio();
$materiaServicio = $contenedor->obtenerMateriaServicio();
$espacioServicio = $contenedor->obtenerEspacioServicio();
$materiaRepo = $contenedor->obtenerMateriaRepositorio();
$espacioRepo = $contenedor->obtenerEspacioRepositorio();

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

// 1. SOLID: Verificación de Inversión de Dependencias y Contratos
assertPrueba(
  'SOLID (DIP/LSP): Repositorio y Servicio de Materias implementan interfaces de contrato',
  $materiaRepo instanceof MateriaRepositorioInterface &&
  $materiaServicio instanceof MateriaServicioInterface
);

assertPrueba(
  'SOLID (DIP/LSP): Repositorio y Servicio de Espacios implementan interfaces de contrato',
  $espacioRepo instanceof EspacioRepositorioInterface &&
  $espacioServicio instanceof EspacioServicioInterface
);

// Limpieza de datos de prueba previos
$pdo->exec("DELETE FROM materias WHERE codigo IN ('TEST-101', 'TEST-DUP')");
$pdo->exec("DELETE FROM espacios WHERE nombre LIKE 'Espacio Test%'");

// 2. RF6: Creación exitosa de materia con código único
try {
  $materia = $materiaServicio->crear([
    'codigo' => 'TEST-101',
    'nombre' => 'Materia de Prueba Automatizada',
    'descripcion' => 'Descripción para pruebas unitarias',
  ]);

  assertPrueba(
    'RF6: Creación de materia válida con código único',
    $materia->getId() !== null &&
    $materia->getCodigo() === 'TEST-101' &&
    $materia->getNombre() === 'Materia de Prueba Automatizada' &&
    $materia->esActivo()
  );
} catch (Throwable $e) {
  assertPrueba('RF6: Creación de materia válida: ' . $e->getMessage(), false);
}

// 3. RF6: Rechazo de código de materia duplicado
try {
  $materiaServicio->crear([
    'codigo' => 'TEST-101',
    'nombre' => 'Otra Materia Duplicada',
  ]);
  assertPrueba('RF6: Rechazo de materia con código duplicado', false);
} catch (InvalidArgumentException $e) {
  assertPrueba(
    'RF6: Rechazo de materia con código duplicado',
    str_contains($e->getMessage(), 'ya se encuentra registrado')
  );
}

// 4. RF6: Rechazo de código inválido o vacío
try {
  $materiaServicio->crear([
    'codigo' => '',
    'nombre' => 'Sin Código',
  ]);
  assertPrueba('RF6: Rechazo de código vacío', false);
} catch (InvalidArgumentException $e) {
  assertPrueba(
    'RF6: Rechazo de código vacío',
    str_contains($e->getMessage(), 'es obligatorio')
  );
}

// 5. RF6: Actualización de materia existente
try {
  $materiaId = $materia->getId();
  $actualizada = $materiaServicio->actualizar($materiaId, [
    'nombre' => 'Materia de Prueba Nombre Editado',
    'descripcion' => 'Nueva descripción editada',
  ]);

  assertPrueba(
    'RF6: Actualización exitosa de nombre y descripción de materia',
    $actualizada->getNombre() === 'Materia de Prueba Nombre Editado' &&
    $actualizada->getDescripcion() === 'Nueva descripción editada'
  );
} catch (Throwable $e) {
  assertPrueba('RF6: Actualización de materia: ' . $e->getMessage(), false);
}

// 6. RF6: Eliminación (baja lógica) de materia
try {
  $eliminada = $materiaServicio->eliminar($materiaId);
  $materiaBaja = $materiaRepo->buscarPorId($materiaId);

  assertPrueba(
    'RF6/RNF3: Eliminación lógica de materia (estado inactivo)',
    $eliminada === true &&
    $materiaBaja !== null &&
    !$materiaBaja->esActivo() &&
    $materiaBaja->getEstado() === 'inactivo'
  );
} catch (Throwable $e) {
  assertPrueba('RF6: Eliminación lógica: ' . $e->getMessage(), false);
}

// 7. RF8: Creación de aula física con capacidad válida (>0)
try {
  $aulaFisica = $espacioServicio->crear([
    'nombre' => 'Espacio Test Aula Física 202',
    'tipo' => 'fisico',
    'capacidad' => 35,
    'ubicacion' => 'Edificio B, Segundo Piso',
  ]);

  assertPrueba(
    'RF8: Creación de aula física con capacidad y ubicación',
    $aulaFisica->getId() !== null &&
    $aulaFisica->esFisico() &&
    $aulaFisica->getCapacidad() === 35 &&
    $aulaFisica->getUbicacion() === 'Edificio B, Segundo Piso' &&
    $aulaFisica->getEnlaceVirtual() === null
  );
} catch (Throwable $e) {
  assertPrueba('RF8: Creación de aula física: ' . $e->getMessage(), false);
}

// 8. RF8: Rechazo de aula física con capacidad inválida (cero o negativa)
try {
  $espacioServicio->crear([
    'nombre' => 'Espacio Test Inválido',
    'tipo' => 'fisico',
    'capacidad' => 0,
  ]);
  assertPrueba('RF8: Rechazo de aula física con capacidad 0', false);
} catch (InvalidArgumentException $e) {
  assertPrueba(
    'RF8: Rechazo de aula física con capacidad 0',
    str_contains($e->getMessage(), 'mayor a cero')
  );
}

// 9. RF8: Creación de sala virtual con enlace externo válido
try {
  $salaVirtual = $espacioServicio->crear([
    'nombre' => 'Espacio Test Sala Virtual Teams',
    'tipo' => 'virtual',
    'enlace_virtual' => 'https://teams.microsoft.com/l/meetup-join/test1234',
  ]);

  assertPrueba(
    'RF8: Creación de sala virtual con enlace web válido',
    $salaVirtual->getId() !== null &&
    $salaVirtual->esVirtual() &&
    $salaVirtual->getCapacidad() === null &&
    $salaVirtual->getEnlaceVirtual() === 'https://teams.microsoft.com/l/meetup-join/test1234'
  );
} catch (Throwable $e) {
  assertPrueba('RF8: Creación de sala virtual: ' . $e->getMessage(), false);
}

// 10. RF8: Rechazo de sala virtual sin enlace o con URL inválida
try {
  $espacioServicio->crear([
    'nombre' => 'Espacio Test Virtual Inválido',
    'tipo' => 'virtual',
    'enlace_virtual' => 'enlace-no-url',
  ]);
  assertPrueba('RF8: Rechazo de sala virtual con URL inválida', false);
} catch (InvalidArgumentException $e) {
  assertPrueba(
    'RF8: Rechazo de sala virtual con URL inválida',
    str_contains($e->getMessage(), 'enlace URL válido')
  );
}

// 11. RF8: Actualización de espacio existente
try {
  $espacioId = $aulaFisica->getId();
  $espacioActualizado = $espacioServicio->actualizar($espacioId, [
    'nombre' => 'Espacio Test Aula Física Editada',
    'capacidad' => 40,
  ]);

  assertPrueba(
    'RF8: Actualización de espacio físico (nombre y capacidad)',
    $espacioActualizado->getNombre() === 'Espacio Test Aula Física Editada' &&
    $espacioActualizado->getCapacidad() === 40
  );
} catch (Throwable $e) {
  assertPrueba('RF8: Actualización de espacio: ' . $e->getMessage(), false);
}

// 12. RF8: Filtro de espacios por tipo
$fisicos = $espacioServicio->listar('fisico');
$virtuales = $espacioServicio->listar('virtual');
assertPrueba(
  'RF8: Filtrado de catálogo de espacios por tipo (fisico vs virtual)',
  count($fisicos) > 0 &&
  count($virtuales) > 0 &&
  array_reduce($fisicos, fn($acc, $e) => $acc && $e->esFisico(), true) &&
  array_reduce($virtuales, fn($acc, $e) => $acc && $e->esVirtual(), true)
);

// 13. RF4/RNF1: Verificación de control de roles (Middleware)
$authMiddleware = $contenedor->obtenerAutenticacionMiddleware();

// Obtener o registrar estudiante
$correoEst = 'estudiante.catalogos@studyplace.local';
$stmt = $pdo->prepare('DELETE FROM usuarios WHERE correo = :correo');
$stmt->execute([':correo' => $correoEst]);
$estudianteUser = $authServicio->registrar([
  'nombre' => 'Estudiante Catálogo Test',
  'correo' => $correoEst,
  'password' => 'PassSegura123*',
  'rol' => 'estudiante',
  'semestre' => 3,
]);
$loginEst = $authServicio->autenticar($correoEst, 'PassSegura123*');
$tokenEstudiante = $loginEst['token'];

// Validar que un estudiante puede autenticarse
$validado = $authServicio->validarToken($tokenEstudiante);
assertPrueba(
  'RF4: Sesión válida para estudiante que consulta catálogos',
  $validado !== null && $validado->getRol() === 'estudiante'
);

// Validar que la comprobación de rol administrador bloquea a un usuario estudiante
assertPrueba(
  'RF4/RF6/RF8: Restricción estricta - estudiante NO tiene rol administrador',
  !$validado->tieneRol('administrador')
);

// Validar que el administrador del sistema sí tiene rol administrador
$adminUser = $authServicio->validarToken(
  $authServicio->autenticar('admin@studyplace.local', 'Admin123*')['token']
);
assertPrueba(
  'RF4/RF16: Administrador autenticado tiene rol de administración de catálogos',
  $adminUser !== null && $adminUser->tieneRol('administrador')
);

// Limpieza final de datos de prueba temporales
$pdo->exec("DELETE FROM materias WHERE codigo IN ('TEST-101', 'TEST-DUP')");
$pdo->exec("DELETE FROM espacios WHERE nombre LIKE 'Espacio Test%'");
$pdo->exec("DELETE FROM usuarios WHERE correo = 'estudiante.catalogos@studyplace.local'");

echo "\n" . sprintf("RESULTADOS: %d/%d pruebas superadas exitosamente.\n", $pruebasPasadas, $totalPruebas);
