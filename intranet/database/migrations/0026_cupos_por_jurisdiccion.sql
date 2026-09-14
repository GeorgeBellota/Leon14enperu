-- ===========================================================================
--  0026 · CUPO MÁXIMO POR JURISDICCIÓN
-- ---------------------------------------------------------------------------
--  Cada jurisdicción puede tener un tope de inscripciones. Al alcanzarlo, su
--  opción sigue viéndose en el formulario —«Lima — completado»— pero no se
--  puede elegir, y el servidor rechaza el envío si alguien lo intenta de todas
--  formas.
--
--  ── Qué cuenta como plaza ocupada ────────────────────────────────────────
--
--  TODAS las inscripciones vivas, sea cual sea su estado: «nuevo»,
--  «en_validacion», «validado», «acreditado», «rechazado» y «baja». Decisión
--  de la Conferencia Episcopal: una plaza se ocupa al inscribirse, y un
--  rechazo posterior no la devuelve al montón.
--
--  Lo único que NO cuenta es lo borrado —`borrado_en` con fecha—, que es otra
--  cosa: ahí el registro ya no existe a efectos del sistema.
--
--  ── Qué hace ─────────────────────────────────────────────────────────────
--
--   1. Añade la columna `limite` a `jurisdicciones`. NULA = sin tope, que es
--      como quedan las cinco: esta migración NO cierra nada.
--   2. Añade el índice que hace barata la cuenta de ocupación.
--
--  ── Qué NO hace ──────────────────────────────────────────────────────────
--
--  Ni un DELETE, ni un TRUNCATE, ni un DROP. No toca `voluntarios` ni una
--  sola fila: sólo lee para el informe del final. Bajar un tope por debajo de
--  lo ya inscrito NO borra a nadie; simplemente deja la jurisdicción cerrada.
--
--  Es repetible: usa IF NOT EXISTS, que es sintaxis de MariaDB.
-- ===========================================================================

SELECT 'ANTES' AS momento, COUNT(*) AS jurisdicciones FROM `jurisdicciones`;

-- ── 1 · El tope ────────────────────────────────────────────────────────────

ALTER TABLE `jurisdicciones`
  ADD COLUMN IF NOT EXISTS `limite` INT UNSIGNED NULL DEFAULT NULL
  COMMENT 'Tope de inscripciones. NULL = sin tope.' AFTER `orden`;

-- ── 2 · El índice de la cuenta ─────────────────────────────────────────────
--
--  Ya existe `ix_voluntarios_filtro`, que empieza por `jurisdiccion_id`, y
--  sirve. Éste añade `borrado_en`, que es la otra columna de la condición, y
--  convierte la cuenta en una lectura de índice sin tocar la tabla. Con 36 000
--  filas la diferencia se nota en cada visita al formulario.

ALTER TABLE `voluntarios`
  ADD KEY IF NOT EXISTS `ix_voluntarios_cupo` (`jurisdiccion_id`, `borrado_en`);

-- ── Comprobación ───────────────────────────────────────────────────────────
--  Las cinco deben salir con `limite` NULO: nadie queda cerrado por instalar
--  esto. Los topes se ponen después, desde Intranet → Catálogos.

SELECT 'DESPUÉS' AS momento, COUNT(*) AS jurisdicciones FROM `jurisdicciones`;

SELECT j.`id`, j.`nombre`, j.`activo`, j.`limite`,
       (SELECT COUNT(*) FROM `voluntarios` v
         WHERE v.`jurisdiccion_id` = j.`id` AND v.`borrado_en` IS NULL) AS inscritos
  FROM `jurisdicciones` j
 ORDER BY j.`orden`;
