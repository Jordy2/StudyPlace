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
