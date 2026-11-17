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
  ('PRG-201', 'Estructuras de Datos', 'Pilas, colas, listas enlazadas, árboles y grafos.', 'activo'),
  ('BD-301', 'Bases de Datos Relacionales', 'Modelo relacional, SQL, normalización y transacciones.', 'activo')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Datos semilla iniciales para espacios (RF8)
INSERT INTO `espacios` (`nombre`, `tipo`, `capacidad`, `ubicacion`, `enlace_virtual`, `estado`)
VALUES
  ('Aula 104 - Edificio Central', 'fisico', 25, 'Facultad de Ingeniería, Piso 1', NULL, 'activo'),
  ('Laboratorio de Cómputo B', 'fisico', 20, 'Edificio de Tecnologías, Piso 2', NULL, 'activo'),
  ('Sala Virtual Google Meet 1', 'virtual', NULL, NULL, 'https://meet.google.com/abc-defg-hij', 'activo'),
  ('Sala Virtual Zoom Académica', 'virtual', NULL, NULL, 'https://zoom.us/j/1234567890', 'activo')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Horarios disponibles configurados por el mentor (RF7)
CREATE TABLE IF NOT EXISTS `disponibilidad_mentor` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mentor_id` INT NOT NULL,
  `materia_id` INT NOT NULL,
  `dia_semana` ENUM('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo') NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_disponibilidad_mentor` (`mentor_id`),
  INDEX `idx_disponibilidad_dia` (`dia_semana`),
  INDEX `idx_disponibilidad_materia` (`materia_id`),
  CONSTRAINT `fk_disponibilidad_mentor`
    FOREIGN KEY (`mentor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_disponibilidad_materia`
    FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

