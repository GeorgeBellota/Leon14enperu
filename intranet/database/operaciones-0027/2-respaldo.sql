-- ===========================================================================
--  0027 · PASO 2 de 4 — LA COPIA DE SEGURIDAD, EN SQL
-- ---------------------------------------------------------------------------
--  Sin mysqldump y sin salir de la base: se duplican las siete tablas que la
--  migración puede tocar, dentro de la misma base, con el prefijo «zz0027_».
--  Son unos 190 KB en total; `voluntarios` no entra porque la migración no la
--  toca, y copiar 37.428 filas de datos personales sin necesidad sería peor.
--
--  Las copias se quedan ahí hasta que alguien las borre (paso 4). No molestan:
--  ni el panel ni el sitio miran tablas que empiecen por «zz0027_».
--
--  IMPORTANTE: ejecutar ESTO antes que la migración, no después.
-- ===========================================================================

DROP TABLE IF EXISTS zz0027_paginas;
DROP TABLE IF EXISTS zz0027_secciones;
DROP TABLE IF EXISTS zz0027_bloques;
DROP TABLE IF EXISTS zz0027_medios;
DROP TABLE IF EXISTS zz0027_ajustes;
DROP TABLE IF EXISTS zz0027_migraciones;
DROP TABLE IF EXISTS zz0027_secciones_versiones;

CREATE TABLE zz0027_paginas             AS SELECT * FROM paginas;
CREATE TABLE zz0027_secciones           AS SELECT * FROM secciones;
CREATE TABLE zz0027_bloques             AS SELECT * FROM bloques;
CREATE TABLE zz0027_medios              AS SELECT * FROM medios;
CREATE TABLE zz0027_ajustes             AS SELECT * FROM ajustes;
CREATE TABLE zz0027_migraciones         AS SELECT * FROM migraciones;
-- Ésta cuelga de `secciones` con ON DELETE CASCADE. Desde que la migración
-- apaga las secciones en vez de borrarlas ya no la puede tocar, pero se copia
-- igual: cuesta nada y cierra la duda.
CREATE TABLE zz0027_secciones_versiones AS SELECT * FROM secciones_versiones;

-- ── Comprobar que la copia salió completa ─────────────────────────────────
-- Las dos columnas de cada fila tienen que coincidir.
SELECT 'paginas'             AS tabla, (SELECT COUNT(1) FROM paginas)             AS original, (SELECT COUNT(1) FROM zz0027_paginas)             AS copia
UNION ALL SELECT 'secciones',           (SELECT COUNT(1) FROM secciones),           (SELECT COUNT(1) FROM zz0027_secciones)
UNION ALL SELECT 'bloques',             (SELECT COUNT(1) FROM bloques),             (SELECT COUNT(1) FROM zz0027_bloques)
UNION ALL SELECT 'medios',              (SELECT COUNT(1) FROM medios),              (SELECT COUNT(1) FROM zz0027_medios)
UNION ALL SELECT 'ajustes',             (SELECT COUNT(1) FROM ajustes),             (SELECT COUNT(1) FROM zz0027_ajustes)
UNION ALL SELECT 'migraciones',         (SELECT COUNT(1) FROM migraciones),         (SELECT COUNT(1) FROM zz0027_migraciones)
UNION ALL SELECT 'secciones_versiones', (SELECT COUNT(1) FROM secciones_versiones), (SELECT COUNT(1) FROM zz0027_secciones_versiones);
