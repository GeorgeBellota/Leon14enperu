-- ============================================================================
--  0004 · Ubigeo en el formulario de voluntariado
--
--  Tres cosas:
--   1. Deja las tablas de ubigeo en condiciones de usarse (charset, índices,
--      claves foráneas).
--   2. Añade a `voluntarios` el departamento, la provincia y el distrito.
--   3. El contacto de emergencia pasa a ser opcional.
-- ============================================================================

-- ── 1. Ubigeo ───────────────────────────────────────────────────────────
--
-- Venían en utf8/utf8_general_ci mientras el resto de la base está en
-- utf8mb4/utf8mb4_unicode_ci. Mezclar cotejos en un JOIN no da un resultado
-- raro: da un error, «Illegal mix of collations», y la consulta no llega a
-- ejecutarse. Se convierten antes de que ninguna consulta las toque.

ALTER TABLE `ubigeo_departamento` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ubigeo_provincia`    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ubigeo_distrito`     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Sin estos índices, «dame los distritos de esta provincia» —que es lo que
-- pregunta el desplegable en cada cambio— recorre las 1874 filas enteras.
ALTER TABLE `ubigeo_provincia` ADD INDEX `ix_provincia_departamento` (`department_id`);
ALTER TABLE `ubigeo_distrito`  ADD INDEX `ix_distrito_provincia` (`province_id`);
ALTER TABLE `ubigeo_distrito`  ADD INDEX `ix_distrito_departamento` (`department_id`);

-- Integridad: hoy los datos cuadran por suerte, no por diseño.
ALTER TABLE `ubigeo_provincia`
  ADD CONSTRAINT `fk_provincia_departamento`
  FOREIGN KEY (`department_id`) REFERENCES `ubigeo_departamento` (`id`) ON DELETE RESTRICT;

ALTER TABLE `ubigeo_distrito`
  ADD CONSTRAINT `fk_distrito_provincia`
  FOREIGN KEY (`province_id`) REFERENCES `ubigeo_provincia` (`id`) ON DELETE RESTRICT;


-- ── 2. Ubigeo en las inscripciones ──────────────────────────────────────
--
-- Se guardan los tres niveles y no sólo el distrito, aunque el distrito ya
-- implique los otros dos. El motivo es el panel: filtrar por departamento
-- —«todos los voluntarios de Lambayeque»— es la consulta más frecuente, y con
-- sólo el distrito exigiría dos JOIN en cada listado.
--
-- Las columnas `distrito` y `provincia` de texto que ya existían se conservan:
-- las inscripciones anteriores las tienen rellenadas a partir de la dirección
-- escrita a mano, y borrarlas perdería ese dato sin ganar nada.

ALTER TABLE `voluntarios`
  ADD COLUMN `ubigeo_departamento_id` VARCHAR(2) NULL AFTER `direccion_cifrada`,
  ADD COLUMN `ubigeo_provincia_id`    VARCHAR(4) NULL AFTER `ubigeo_departamento_id`,
  ADD COLUMN `ubigeo_distrito_id`     VARCHAR(6) NULL AFTER `ubigeo_provincia_id`;

ALTER TABLE `voluntarios`
  ADD INDEX `ix_voluntarios_ubigeo` (`ubigeo_departamento_id`, `ubigeo_provincia_id`, `ubigeo_distrito_id`);

ALTER TABLE `voluntarios`
  ADD CONSTRAINT `fk_voluntarios_departamento`
    FOREIGN KEY (`ubigeo_departamento_id`) REFERENCES `ubigeo_departamento` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_voluntarios_provincia`
    FOREIGN KEY (`ubigeo_provincia_id`) REFERENCES `ubigeo_provincia` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_voluntarios_distrito`
    FOREIGN KEY (`ubigeo_distrito_id`) REFERENCES `ubigeo_distrito` (`id`) ON DELETE SET NULL;


-- ── 3. El contacto de emergencia pasa a ser opcional ────────────────────
--
-- Se pide, pero ya no se exige: quien no lo tenga a mano no puede quedarse sin
-- inscribirse por eso. La organización lo reclamará en la Fase 02, cuando
-- tenga sentido pedirlo.

ALTER TABLE `voluntarios`
  MODIFY `emergencia_nombre`           VARCHAR(160) NULL,
  MODIFY `emergencia_telefono_cifrado` TEXT         NULL,
  MODIFY `emergencia_telefono_mascara` VARCHAR(20)  NULL;


-- ── 4. Ajuste para la búsqueda por DNI ──────────────────────────────────
INSERT INTO `ajustes` (`clave`, `valor`, `tipo`, `descripcion`) VALUES
  ('reniec.activo', '0', 'booleano',
   'Con 1, la lupa del DNI consulta RENIEC y el nombre pasa a sólo lectura. Mientras esté a 0, el nombre se escribe a mano.'),
  ('reniec.endpoint', '', 'texto',
   'URL del servicio de consulta de DNI. Pendiente de contratar.'),
  ('reniec.token', '', 'texto',
   'Credencial del servicio. NO se muestra en el panel una vez guardada.')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);
