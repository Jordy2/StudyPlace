CREATE DATABASE IF NOT EXISTS `studyplace_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `studyplace_db`;

-- Tabla de usuarios del sistema
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `correo` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol` ENUM('estudiante', 'mentor', 'administrador') NOT NULL,
  `semestre` TINYINT UNSIGNED NULL,
  `estado` ENUM('activo', 'suspendido', 'eliminado') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_usuarios_correo` (`correo`),
  INDEX `idx_usuarios_rol` (`rol`),
  INDEX `idx_usuarios_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tokens de autenticación para sesiones desacopladas
CREATE TABLE IF NOT EXISTS `tokens_sesion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expira_en` DATETIME NOT NULL,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tokens_token` (`token`),
  INDEX `idx_tokens_expira` (`expira_en`),
  CONSTRAINT `fk_tokens_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador semilla (RF16)
-- Contraseña por defecto: Admin123*
INSERT INTO `usuarios` (`nombre`, `correo`, `password_hash`, `rol`, `semestre`, `estado`)
VALUES (
  'Administrador General',
  'admin@studyplace.local',
  '$2y$12$8/p4kMgt9Gg7y/Q2sDWVheEsFnUL45PegKJASyMr/8Y4H6lYigPqS',
  'administrador',
  NULL,
  'activo'
)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `password_hash` = VALUES(`password_hash`),
  `estado` = 'activo';

-- Tabla de materias (RF6)
CREATE TABLE IF NOT EXISTS `materias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(30) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NULL,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_materias_codigo` (`codigo`),
  INDEX `idx_materias_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de espacios: aulas físicas y salas virtuales (RF8)
CREATE TABLE IF NOT EXISTS `espacios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `tipo` ENUM('fisico', 'virtual') NOT NULL,
  `capacidad` INT UNSIGNED NULL,
  `ubicacion` VARCHAR(255) NULL,
  `enlace_virtual` VARCHAR(255) NULL,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_espacios_tipo` (`tipo`),
  INDEX `idx_espacios_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos semilla iniciales para materias (RF6)
INSERT INTO `materias` (`codigo`, `nombre`, `descripcion`, `estado`)
VALUES
  ('MAT-101', 'Cálculo Diferencial', 'Fundamentos de límites, derivadas y aplicaciones.', 'activo'),
  ('MAT-102', 'Cálculo Integral y Vectorial', 'Integrales definidas, métodos de integración y cálculo multivariable.', 'activo'),
  ('MAT-103', 'Álgebra Lineal', 'Matrices, determinantes, espacios vectoriales y transformaciones lineales.', 'activo'),
  ('MAT-201', 'Ecuaciones Diferenciales', 'Ecuaciones diferenciales ordinarias, modelado y transformadas de Laplace.', 'activo'),
  ('PRG-101', 'Fundamentos de Programación', 'Lógica algorítmica, estructuras de control, tipos de datos y modularidad.', 'activo'),
  ('PRG-201', 'Estructuras de Datos y Algoritmos', 'Pilas, colas, listas enlazadas, árboles binarios, grafos y complejidad.', 'activo'),
  ('PRG-301', 'Programación Orientada a Objetos', 'Clases, encapsulamiento, herencia, polimorfismo y patrones de diseño.', 'activo'),
  ('PRG-401', 'Desarrollo y Programación Web', 'Arquitectura frontend y backend, consumo de APIs REST, autenticación y JS moderno.', 'activo'),
  ('BD-301', 'Bases de Datos Relacionales', 'Modelo relacional, SQL, normalización, índices y transacciones ACID.', 'activo'),
  ('BD-401', 'Bases de Datos Avanzadas y NoSQL', 'Modelos NoSQL de documentos, clave-valor, grafos y alta disponibilidad.', 'activo'),
  ('FIS-101', 'Física General y Mecánica', 'Cinemática, dinámica, leyes de Newton, trabajo, energía y cantidad de movimiento.', 'activo'),
  ('EST-201', 'Probabilidad y Estadística', 'Estadística descriptiva, distribuciones probabilísticas e inferencia estadística.', 'activo'),
  ('RED-301', 'Redes de Comunicación', 'Modelo OSI y TCP/IP, direccionamiento IPv4/IPv6, enrutamiento y conmutación.', 'activo'),
  ('SO-201', 'Sistemas Operativos', 'Gestión de procesos, sincronización, memoria virtual y sistemas de archivos.', 'activo'),
  ('ING-401', 'Ingeniería y Arquitectura de Software', 'Metodologías ágiles, ciclo de vida del software, diagramas UML y calidad.', 'activo'),
  ('IA-501', 'Inteligencia Artificial y Machine Learning', 'Búsqueda heurística, aprendizaje supervisado, redes neuronales y visión artificial.', 'activo')
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `descripcion` = VALUES(`descripcion`),
  `estado` = 'activo';

-- Datos semilla iniciales para espacios (RF8)
INSERT INTO `espacios` (`nombre`, `tipo`, `capacidad`, `ubicacion`, `enlace_virtual`, `estado`)
VALUES
  ('Aula 104 - Edificio Central', 'fisico', 25, 'Facultad de Ingeniería, Piso 1', NULL, 'activo'),
  ('Laboratorio de Cómputo B', 'fisico', 20, 'Edificio de Tecnologías, Piso 2', NULL, 'activo'),
  ('Sala Virtual Google Meet 1', 'virtual', NULL, NULL, 'https://meet.google.com/abc-defg-hij', 'activo'),
  ('Sala Virtual Zoom Académica', 'virtual', NULL, NULL, 'https://zoom.us/j/1234567890', 'activo')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Tabla de materias dominadas por mentores (RF5)
CREATE TABLE IF NOT EXISTS `mentor_materias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `materia_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_mentor_materia` (`usuario_id`, `materia_id`),
  INDEX `idx_mentor_materias_usuario` (`usuario_id`),
  INDEX `idx_mentor_materias_materia` (`materia_id`),
  CONSTRAINT `fk_mentor_materias_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mentor_materias_materia` FOREIGN KEY (`materia_id`)
    REFERENCES `materias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Horarios disponibles configurados por el mentor (RF7)
CREATE TABLE IF NOT EXISTS `disponibilidad_mentor` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mentor_id` INT NOT NULL,
  `dia_semana` ENUM('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo') NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_disponibilidad_mentor` (`mentor_id`),
  INDEX `idx_disponibilidad_dia` (`dia_semana`),
  CONSTRAINT `fk_disponibilidad_mentor`
    FOREIGN KEY (`mentor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de auditoría de acciones del administrador (RF19 base, RNF7)
CREATE TABLE IF NOT EXISTS `auditoria_admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `accion` VARCHAR(50) NOT NULL,
  `usuario_afectado_id` INT NULL,
  `detalle` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_auditoria_admin` (`admin_id`),
  INDEX `idx_auditoria_afectado` (`usuario_afectado_id`),
  INDEX `idx_auditoria_fecha` (`created_at`),
  CONSTRAINT `fk_auditoria_admin` FOREIGN KEY (`admin_id`)
    REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_auditoria_afectado` FOREIGN KEY (`usuario_afectado_id`)
    REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
