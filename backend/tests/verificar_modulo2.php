<?php

require_once __DIR__ . '/../lib/autoload.php';

use App\Contenedor;
use App\Contratos\MentorMateriaRepositorioInterface;
use App\Contratos\PerfilServicioInterface;
use App\Contratos\UsuarioRepositorioInterface;
use App\Controladores\PerfilControlador;
use App\Modelos\Materia;
use App\Modelos\Usuario;
use App\Repositorios\MentorMateriaRepositorio;
use App\Repositorios\UsuarioRepositorio;
use App\Servicios\PerfilServicio;

echo "=================================================================\n";
echo "INICIANDO PRUEBAS AUTOMATIZADAS DEL MÓDULO 2 (RF5, MVC, SOLID)\n";
echo "=================================================================\n\n";

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

// -----------------------------------------------------------------------------
// 1. SOLID: Verificación de Contratos e Inversión de Dependencias (DIP / LSP)
// -----------------------------------------------------------------------------
try {
  $pdo = App\Config\Conexion::obtenerConexion();
} catch (Throwable $_) {
  // En entornos de sandbox sin socket de red, se provee una instancia PDO en memoria
  $pdo = new PDO('sqlite::memory:');
}

$contenedor = new Contenedor($pdo);

$usuarioRepo = $contenedor->obtenerUsuarioRepositorio();
$mentorMateriaRepo = $contenedor->obtenerMentorMateriaRepositorio();
$perfilServicio = $contenedor->obtenerPerfilServicio();
$perfilControlador = $contenedor->obtenerPerfilControlador();

assertPrueba(
  'SOLID (DIP): UsuarioRepositorio implementa UsuarioRepositorioInterface',
  $usuarioRepo instanceof UsuarioRepositorioInterface
);

assertPrueba(
  'SOLID (DIP): MentorMateriaRepositorio implementa MentorMateriaRepositorioInterface',
  $mentorMateriaRepo instanceof MentorMateriaRepositorioInterface
);

assertPrueba(
  'SOLID (DIP): PerfilServicio implementa PerfilServicioInterface',
  $perfilServicio instanceof PerfilServicioInterface
);

assertPrueba(
  'SOLID (SRP): PerfilControlador instanciado correctamente con middleware y servicio',
  $perfilControlador instanceof PerfilControlador
);

// -----------------------------------------------------------------------------
// 2. SOLID: Verificación de Métodos requeridos por Interfaces
// -----------------------------------------------------------------------------
assertPrueba(
  'SOLID (ISP): UsuarioRepositorioInterface define actualizar() y existeOtroConCorreo()',
  method_exists($usuarioRepo, 'actualizar') &&
  method_exists($usuarioRepo, 'existeOtroConCorreo')
);

assertPrueba(
  'SOLID (ISP): MentorMateriaRepositorioInterface define sincronizarMateriasMentor()',
  method_exists($mentorMateriaRepo, 'sincronizarMateriasMentor') &&
  method_exists($mentorMateriaRepo, 'obtenerMateriasPorMentor') &&
  method_exists($mentorMateriaRepo, 'obtenerIdsMateriasPorMentor')
);

// -----------------------------------------------------------------------------
// 3. Pruebas de Lógica de Negocio con Mocks Aislados (Unit Tests)
// -----------------------------------------------------------------------------
$mockUsuarioRepo = new class implements UsuarioRepositorioInterface {
  public array $usuarios = [];
  public function buscarPorId(int $id): ?Usuario {
    return $this->usuarios[$id] ?? null;
  }
  public function buscarPorCorreo(string $correo): ?Usuario {
    foreach ($this->usuarios as $u) {
      if ($u->getCorreo() === $correo) return $u;
    }
    return null;
  }
  public function crear(Usuario $usuario): Usuario {
    $id = count($this->usuarios) + 1;
    $nuevo = new Usuario(
      $id,
      $usuario->getNombre(),
      $usuario->getCorreo(),
      $usuario->getPasswordHash(),
      $usuario->getRol(),
      $usuario->getSemestre(),
      $usuario->getEstado()
    );
    $this->usuarios[$id] = $nuevo;
    return $nuevo;
  }
  public function existeCorreo(string $correo): bool {
    return $this->buscarPorCorreo($correo) !== null;
  }
  public function existeOtroConCorreo(string $correo, int $idExcluir): bool {
    foreach ($this->usuarios as $id => $u) {
      if ($id !== $idExcluir && $u->getCorreo() === $correo) return true;
    }
    return false;
  }
  public function actualizar(Usuario $usuario): bool {
    if (isset($this->usuarios[$usuario->getId()])) {
      $this->usuarios[$usuario->getId()] = $usuario;
      return true;
    }
    return false;
  }
};

$mockMentorMateriaRepo = new class implements MentorMateriaRepositorioInterface {
  public array $asignaciones = [];
  public function obtenerMateriasPorMentor(int $usuarioId): array {
    $ids = $this->asignaciones[$usuarioId] ?? [];
    return array_map(fn($id) => new Materia($id, "MAT-$id", "Materia $id"), $ids);
  }
  public function obtenerIdsMateriasPorMentor(int $usuarioId): array {
    return $this->asignaciones[$usuarioId] ?? [];
  }
  public function sincronizarMateriasMentor(int $usuarioId, array $materiaIds): bool {
    $this->asignaciones[$usuarioId] = array_values(array_unique($materiaIds));
    return true;
  }
  public function obtenerMentoresPorMateria(int $materiaId): array {
    return [];
  }
};

$mockMateriaRepo = new class implements \App\Contratos\MateriaRepositorioInterface {
  public function obtenerTodas(?string $estado = null): array {
    return [
      new Materia(1, 'MAT-101', 'Cálculo Diferencial', null, 'activo'),
      new Materia(2, 'PRG-201', 'Estructuras de Datos y Algoritmos', null, 'activo'),
      new Materia(3, 'MAT-103', 'Álgebra Lineal', null, 'activo'),
      new Materia(4, 'PRG-401', 'Desarrollo y Programación Web', null, 'activo'),
      new Materia(5, 'BD-301', 'Bases de Datos Relacionales', null, 'activo'),
      new Materia(6, 'FIS-101', 'Física General y Mecánica', null, 'activo'),
    ];
  }
  public function buscarPorId(int $id): ?Materia {
    $lista = $this->obtenerTodas();
    foreach ($lista as $m) {
      if ($m->getId() === $id) return $m;
    }
    return null;
  }
  public function buscarPorCodigo(string $codigo): ?Materia { return null; }
  public function crear(Materia $materia): Materia { return $materia; }
  public function actualizar(Materia $materia): bool { return true; }
  public function eliminar(int $id, bool $bajaLogica = true): bool { return true; }
  public function existeCodigo(string $codigo, ?int $excluirId = null): bool { return false; }
};

$servicioTest = new PerfilServicio($mockUsuarioRepo, $mockMentorMateriaRepo, $mockMateriaRepo);

// Sembrar usuario estudiante y mentor
$estudianteTest = $mockUsuarioRepo->crear(new Usuario(
  null,
  'Estudiante Inicial',
  'estudiante@test.local',
  password_hash('Password123*', PASSWORD_DEFAULT),
  'estudiante',
  3,
  'activo'
));

$mentorTest = $mockUsuarioRepo->crear(new Usuario(
  null,
  'Mentor Académico Inicial',
  'mentor@test.local',
  password_hash('Password123*', PASSWORD_DEFAULT),
  'mentor',
  null,
  'activo'
));

// 4. RF5: Consulta de perfil de estudiante
try {
  $perfilEst = $servicioTest->obtenerPerfil($estudianteTest->getId());
  assertPrueba(
    'RF5: Consulta de perfil de estudiante incluye datos personales y semestre',
    $perfilEst['usuario']['nombre'] === 'Estudiante Inicial' &&
    $perfilEst['usuario']['semestre'] === 3 &&
    !isset($perfilEst['materias_dominadas'])
  );
} catch (Throwable $e) {
  assertPrueba('RF5: Consulta de perfil de estudiante: ' . $e->getMessage(), false);
}

// 5. RF5: Consulta de perfil de mentor con catálogo y materias
try {
  $perfilMen = $servicioTest->obtenerPerfil($mentorTest->getId());
  assertPrueba(
    'RF5: Consulta de perfil de mentor incluye catálogo de materias y lista de dominadas',
    $perfilMen['usuario']['rol'] === 'mentor' &&
    isset($perfilMen['catalogo_materias']) &&
    isset($perfilMen['materias_dominadas'])
  );
} catch (Throwable $e) {
  assertPrueba('RF5: Consulta de perfil de mentor: ' . $e->getMessage(), false);
}

// 6. RF5: Edición exitosa de datos de estudiante (Nombre, Correo, Semestre)
try {
  $actualizado = $servicioTest->actualizarPerfil($estudianteTest->getId(), [
    'nombre' => 'Estudiante Nombre Modificado',
    'correo' => 'estudiante.modificado@test.local',
    'semestre' => 6,
  ]);

  assertPrueba(
    'RF5: Actualización de datos de estudiante (nombre, correo y semestre)',
    $actualizado['usuario']['nombre'] === 'Estudiante Nombre Modificado' &&
    $actualizado['usuario']['correo'] === 'estudiante.modificado@test.local' &&
    $actualizado['usuario']['semestre'] === 6
  );
} catch (Throwable $e) {
  assertPrueba('RF5: Actualización de datos de estudiante: ' . $e->getMessage(), false);
}

// 7. RF5: Validación de semestre inválido en estudiante
try {
  $servicioTest->actualizarPerfil($estudianteTest->getId(), [
    'nombre' => 'Estudiante Prueba',
    'correo' => 'estudiante.modificado@test.local',
    'semestre' => 15, // inválido (> 12)
  ]);
  assertPrueba('RF5: Rechazo de semestre fuera del rango 1-12', false);
} catch (InvalidArgumentException $e) {
  assertPrueba(
    'RF5: Rechazo de semestre fuera del rango 1-12',
    str_contains($e->getMessage(), 'entre 1 y 12')
  );
}

// 8. RF5: Validación de correo duplicado
try {
  $servicioTest->actualizarPerfil($estudianteTest->getId(), [
    'nombre' => 'Estudiante Prueba',
    'correo' => 'mentor@test.local', // correo del mentor
    'semestre' => 6,
  ]);
  assertPrueba('RF5: Rechazo de correo en uso por otro usuario', false);
} catch (InvalidArgumentException $e) {
  assertPrueba(
    'RF5: Rechazo de correo en uso por otro usuario',
    str_contains($e->getMessage(), 'ya se encuentra registrado')
  );
}

// 9. RF5: Selección de materias dominadas por el mentor
try {
  $mentorActualizado = $servicioTest->actualizarPerfil($mentorTest->getId(), [
    'nombre' => 'Mentor Académico Actualizado',
    'correo' => 'mentor.nuevo@test.local',
    'materias' => [1, 2], // Materias dominadas
  ]);

  assertPrueba(
    'RF5: Mentor guarda y sincroniza exitosamente sus materias dominadas',
    count($mentorActualizado['materias_ids']) === 2 &&
    in_array(1, $mentorActualizado['materias_ids'], true) &&
    in_array(2, $mentorActualizado['materias_ids'], true)
  );
} catch (Throwable $e) {
  assertPrueba('RF5: Selección de materias dominadas: ' . $e->getMessage(), false);
}

// 10. RF5: Cambio exitoso de contraseña
try {
  $usuarioConPass = $servicioTest->actualizarPerfil($mentorTest->getId(), [
    'nombre' => 'Mentor Académico Actualizado',
    'correo' => 'mentor.nuevo@test.local',
    'password' => 'NuevaSegura1234*',
    'password_confirm' => 'NuevaSegura1234*',
  ]);

  $usuarioBd = $mockUsuarioRepo->buscarPorId($mentorTest->getId());
  assertPrueba(
    'RF5: Actualización opcional de contraseña con hash seguro',
    password_verify('NuevaSegura1234*', $usuarioBd->getPasswordHash())
  );
} catch (Throwable $e) {
  assertPrueba('RF5: Actualización opcional de contraseña: ' . $e->getMessage(), false);
}

// 11. RF5: Rechazo de contraseña con menos de 8 caracteres
try {
  $servicioTest->actualizarPerfil($mentorTest->getId(), [
    'nombre' => 'Mentor Académico Actualizado',
    'correo' => 'mentor.nuevo@test.local',
    'password' => 'corta',
  ]);
  assertPrueba('RF5: Rechazo de contraseña menor a 8 caracteres', false);
} catch (InvalidArgumentException $e) {
  assertPrueba(
    'RF5: Rechazo de contraseña menor a 8 caracteres',
    str_contains($e->getMessage(), 'al menos 8 caracteres')
  );
}

// 12. RF5: Actualización independiente exclusiva de materias dominadas
try {
  $soloMaterias = $servicioTest->actualizarPerfil($mentorTest->getId(), [
    'materias' => [1], // Se cambia de [1, 2] a solo [1]
  ]);

  assertPrueba(
    'RF5: Actualización independiente de materias dominadas sin reenviar nombre ni correo',
    $soloMaterias['materias_ids'] === [1] &&
    $soloMaterias['usuario']['nombre'] === 'Mentor Académico Actualizado'
  );
} catch (Throwable $e) {
  assertPrueba('RF5: Actualización independiente de materias: ' . $e->getMessage(), false);
}

echo "\n-----------------------------------------------------------------\n";
echo "RESUMEN DE PRUEBAS MÓDULO 2: $pruebasPasadas de $totalPruebas aprobadas.\n";
echo "=================================================================\n";

if ($pruebasPasadas === $totalPruebas) {
  exit(0);
} else {
  exit(1);
}
