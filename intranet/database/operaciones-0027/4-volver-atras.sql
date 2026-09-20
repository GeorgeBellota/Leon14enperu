-- ===========================================================================
--  0027 · PASO 4 de 4 — DESHACER
-- ---------------------------------------------------------------------------
--  Devuelve las siete tablas de contenido al estado en que las dejó el paso 2.
--  No toca `voluntarios` ni ninguna otra tabla: no hace falta, la migración
--  tampoco las tocó.
--
--  Sólo ejecutar esto si el paso 3 dio algo que no cuadra. Y sólo si el paso 2
--  se ejecutó de verdad: si las tablas zz0027_* no existen, esto falla en la
--  primera línea sin haber tocado nada, que es lo que tiene que pasar.
--
--  Va en una transacción: o vuelve todo, o no vuelve nada.
-- ===========================================================================

-- Se apagan mientras se vacía y se rellena: durante unos milisegundos hay
-- tablas sin sus referencias. Es de sesión, no afecta a nadie más.
SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

DELETE FROM secciones_versiones;
INSERT INTO secciones_versiones SELECT * FROM zz0027_secciones_versiones;

DELETE FROM bloques;
INSERT INTO bloques   SELECT * FROM zz0027_bloques;

DELETE FROM secciones;
INSERT INTO secciones SELECT * FROM zz0027_secciones;

DELETE FROM paginas;
INSERT INTO paginas   SELECT * FROM zz0027_paginas;

DELETE FROM medios;
INSERT INTO medios    SELECT * FROM zz0027_medios;

DELETE FROM ajustes;
INSERT INTO ajustes   SELECT * FROM zz0027_ajustes;

DELETE FROM migraciones;
INSERT INTO migraciones SELECT * FROM zz0027_migraciones;

COMMIT;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Comprobar que volvió ──────────────────────────────────────────────────
-- Tiene que dar los mismos números que el paso 1:
--   24 / 107 / 99 / 9 / 26 / 37428
SELECT 'RESTAURADO'                        AS momento,
       (SELECT COUNT(1) FROM paginas)      AS paginas,
       (SELECT COUNT(1) FROM secciones)    AS secciones,
       (SELECT COUNT(1) FROM bloques)      AS bloques,
       (SELECT COUNT(1) FROM medios)       AS medios,
       (SELECT COUNT(1) FROM migraciones)  AS migraciones,
       (SELECT COUNT(1) FROM voluntarios)  AS voluntarios;

-- Y que 0027 vuelve a figurar como no aplicada: cero filas.
SELECT COUNT(1) AS aplicada FROM migraciones WHERE archivo = '0027_rediseno_2026.sql';


-- ===========================================================================
--  LIMPIEZA
-- ---------------------------------------------------------------------------
--  Cuando el rediseño lleve unos días publicado y nadie vaya a volver atrás,
--  estas copias sobran. Descomentar y ejecutar.
-- ===========================================================================
--
-- DROP TABLE IF EXISTS zz0027_paginas;
-- DROP TABLE IF EXISTS zz0027_secciones;
-- DROP TABLE IF EXISTS zz0027_bloques;
-- DROP TABLE IF EXISTS zz0027_medios;
-- DROP TABLE IF EXISTS zz0027_ajustes;
-- DROP TABLE IF EXISTS zz0027_migraciones;
-- DROP TABLE IF EXISTS zz0027_secciones_versiones;
