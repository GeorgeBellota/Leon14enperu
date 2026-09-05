-- ===========================================================================
--  0015 · UNA SEGUNDA IMAGEN, LA DE MÓVIL
-- ---------------------------------------------------------------------------
--  Hasta ahora cada sección y cada pieza tenían una sola fotografía, y la
--  misma se servía en un monitor apaisado y en un teléfono vertical. Una
--  cabecera pensada para 1920 de ancho, recortada a 390, deja fuera justo lo
--  que importa: la cara, el titular del cartel, la fachada de la catedral.
--
--  ── Por qué una segunda imagen y no un recorte por CSS ────────────────────
--
--  Con object-fit el teléfono se descarga igual la imagen apaisada de 1920 y
--  luego tira dos tercios. Con dos imágenes no la descarga siquiera: el
--  navegador lee el «media» del <source> y pide sólo la que le toca.
--
--  ── Qué hace ──────────────────────────────────────────────────────────────
--
--  Añade UNA columna a `secciones` y UNA a `bloques`, las dos opcionales y
--  las dos a NULL. Nada más. Mientras nadie elija una foto de móvil desde el
--  panel, el sitio se comporta exactamente igual que antes.
--
--  ── Qué NO hace ───────────────────────────────────────────────────────────
--
--  Ni un DELETE, ni un TRUNCATE, ni un DROP. No toca `voluntarios`, ni
--  `usuarios`, ni `auditoria`, ni `medios`, ni las tablas de ubigeo.
--
--  Los dos ALTER son ADD COLUMN sobre tablas de 105 y 85 filas: instantáneos.
--
--  ── Es repetible ──────────────────────────────────────────────────────────
--
--  IF NOT EXISTS en las columnas y en los índices. Ejecutarlo dos veces no
--  falla ni cambia nada.
--
--  ── Antes de ejecutarlo ───────────────────────────────────────────────────
--
--      mysqldump -u devop -p -P 3309 --single-transaction \
--        --default-character-set=utf8mb4 leon14website > ~/antes-0015.sql
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques,
       (SELECT COUNT(*) FROM `medios`)      AS medios;


-- ═══ 1 · LA COLUMNA EN `secciones` ══════════════════════════════════════
--
-- Va justo detrás de imagen_id para que quien mire la tabla las vea juntas y
-- entienda que son pareja.

ALTER TABLE `secciones`
  ADD COLUMN IF NOT EXISTS `imagen_movil_id` INT UNSIGNED NULL DEFAULT NULL AFTER `imagen_id`;

-- El índice y la clave ajena, iguales que los de `imagen_id`.
--
-- ON DELETE SET NULL, no CASCADE: si alguien borra una foto de la biblioteca,
-- la sección se queda sin foto de móvil —y sigue sirviendo la de escritorio—.
-- Con CASCADE se habría borrado la sección entera por retirar una imagen.
ALTER TABLE `secciones`
  ADD KEY IF NOT EXISTS `fk_secciones_imagen_movil` (`imagen_movil_id`);

SET @hay := (
  SELECT COUNT(*) FROM `information_schema`.`TABLE_CONSTRAINTS`
   WHERE `CONSTRAINT_SCHEMA` = DATABASE()
     AND `TABLE_NAME` = 'secciones'
     AND `CONSTRAINT_NAME` = 'fk_secciones_imagen_movil'
);

SET @sql := IF(@hay = 0,
  'ALTER TABLE `secciones` ADD CONSTRAINT `fk_secciones_imagen_movil`
     FOREIGN KEY (`imagen_movil_id`) REFERENCES `medios` (`id`) ON DELETE SET NULL',
  'DO 0');

PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;


-- ═══ 2 · LA COLUMNA EN `bloques` ════════════════════════════════════════
--
-- Las piezas de las colecciones —cada sede, cada santo, cada obispo— tienen
-- su propia página, y esa página también tiene portada.

ALTER TABLE `bloques`
  ADD COLUMN IF NOT EXISTS `imagen_movil_id` INT UNSIGNED NULL DEFAULT NULL AFTER `imagen_id`;

ALTER TABLE `bloques`
  ADD KEY IF NOT EXISTS `fk_bloques_imagen_movil` (`imagen_movil_id`);

SET @hay := (
  SELECT COUNT(*) FROM `information_schema`.`TABLE_CONSTRAINTS`
   WHERE `CONSTRAINT_SCHEMA` = DATABASE()
     AND `TABLE_NAME` = 'bloques'
     AND `CONSTRAINT_NAME` = 'fk_bloques_imagen_movil'
);

SET @sql := IF(@hay = 0,
  'ALTER TABLE `bloques` ADD CONSTRAINT `fk_bloques_imagen_movil`
     FOREIGN KEY (`imagen_movil_id`) REFERENCES `medios` (`id`) ON DELETE SET NULL',
  'DO 0');

PREPARE p FROM @sql; EXECUTE p; DEALLOCATE PREPARE p;


-- ═══ COMPROBACIÓN ═══════════════════════════════════════════════════════

SELECT 'DESPUES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques,
       (SELECT COUNT(*) FROM `medios`)      AS medios;

-- Los cuatro números deben ser IDÉNTICOS a los de antes: esto añade columnas,
-- no filas.

SELECT `TABLE_NAME` AS tabla, `COLUMN_NAME` AS columna, `IS_NULLABLE` AS admite_nulo
  FROM `information_schema`.`COLUMNS`
 WHERE `TABLE_SCHEMA` = DATABASE()
   AND `COLUMN_NAME` IN ('imagen_id', 'imagen_movil_id')
 ORDER BY `TABLE_NAME`, `COLUMN_NAME`;
-- Deben salir cuatro filas: imagen_id e imagen_movil_id en `bloques` y en
-- `secciones`, las cuatro admitiendo nulo.

SELECT `TABLE_NAME` AS tabla, `CONSTRAINT_NAME` AS clave_ajena
  FROM `information_schema`.`TABLE_CONSTRAINTS`
 WHERE `CONSTRAINT_SCHEMA` = DATABASE()
   AND `CONSTRAINT_NAME` LIKE '%imagen_movil%';
-- Deben salir las dos claves ajenas.


-- ═══ REGISTRO DE MIGRACIONES ════════════════════════════════════════════

INSERT IGNORE INTO `migraciones` (`archivo`, `aplicada_en`) VALUES
  ('0015_imagen_movil.sql', NOW());
