-- Migración única: convertir horarios ligados a una materia en franjas generales del mentor.
-- Conserva el esquema y los datos anteriores en disponibilidad_mentor_legado.
CREATE TABLE IF NOT EXISTS `disponibilidad_mentor_legado` LIKE `disponibilidad_mentor`;

INSERT IGNORE INTO `disponibilidad_mentor_legado`
SELECT * FROM `disponibilidad_mentor`;

ALTER TABLE `disponibilidad_mentor`
  DROP FOREIGN KEY `fk_disponibilidad_materia`,
  DROP INDEX `idx_disponibilidad_materia`,
  DROP COLUMN `materia_id`;
