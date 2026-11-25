-- Migración M11: registro de auditoría de acciones del administrador (RF19 base, RNF7).
-- Es repetible: usa IF NOT EXISTS.
USE `studyplace_db`;

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
