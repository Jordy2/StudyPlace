-- Corrige caracteres dañados en datos del catálogo de materias.
-- Es seguro ejecutar esta migración más de una vez.
SET NAMES utf8mb4;
START TRANSACTION;

UPDATE `materias`
SET `nombre` = 'Cálculo Diferencial',
    `descripcion` = 'Fundamentos de límites, derivadas y aplicaciones.'
WHERE `codigo` = 'MAT-101';

UPDATE `materias`
SET `descripcion` = 'Modelo relacional, SQL, normalización y transacciones.'
WHERE `codigo` = 'BD-301';

UPDATE `materias`
SET `descripcion` = 'Pilas, colas, listas enlazadas, árboles y grafos.'
WHERE `codigo` = 'PRG-201';

COMMIT;
