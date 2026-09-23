-- ===========================================================================
--  0035 · PORTADA — EL CARRUSEL QUEDA EN TRES LÁMINAS
-- ---------------------------------------------------------------------------
--  Visibles, en este orden:
--
--    1  la primera lámina de siempre («Papa León XIV, le esperamos.»)
--    2  «Preparémonos para recibirlo.»   diseño «preparemonos»  (0034)
--    3  «Señal Oficial» del IRTP          diseño «senal»         (0034)
--
--  Las demás se OCULTAN, no se borran: es lo mismo que desmarcar «Visible» en
--  Páginas → Inicio → Carrusel de portada, y desde ahí se vuelven a mostrar.
--  Entre ellas, la «Preparémonos» antigua, la del calendario con el 1 de
--  noviembre en martes.
--
--  Va después de 0034: sin las dos láminas nuevas no hace nada, para no dejar
--  la portada con una sola lámina.
--
--  Ni un DELETE, ni un DROP, ni un ALTER. Es repetible.
-- ===========================================================================

SET NAMES utf8mb4;

SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'home' LIMIT 1);
SET @sec := (SELECT `id` FROM `secciones`
              WHERE `pagina_id` = @pag AND `clave` = 'hero' LIMIT 1);

SET @nuevas := (SELECT COUNT(*) FROM `bloques`
                 WHERE `seccion_id` = @sec
                   AND JSON_VALUE(`datos`, '$.diseno') IN ('preparemonos', 'senal'));

SET @primera := (SELECT MIN(`orden`) FROM `bloques` WHERE `seccion_id` = @sec);

-- Las tres que se ven: la primera y las dos nuevas.
UPDATE `bloques`
   SET `activo` = 1
 WHERE @sec IS NOT NULL AND @nuevas = 2 AND `seccion_id` = @sec
   AND (`orden` = @primera
        OR JSON_VALUE(`datos`, '$.diseno') IN ('preparemonos', 'senal'));

-- Todas las demás, ocultas.
UPDATE `bloques`
   SET `activo` = 0
 WHERE @sec IS NOT NULL AND @nuevas = 2 AND `seccion_id` = @sec
   AND `orden` <> @primera
   AND COALESCE(JSON_VALUE(`datos`, '$.diseno'), '') NOT IN ('preparemonos', 'senal');

-- ── Comprobación (a mano, después) ───────────────────────────────────────
--  Sin SELECT aquí: migrate.php no registra la migración si queda un
--  resultado sin leer.
--
--    SELECT b.`orden`, b.`activo`, b.`titulo`, JSON_VALUE(b.`datos`, '$.diseno') AS diseno
--      FROM `bloques` b JOIN `secciones` s ON s.`id` = b.`seccion_id`
--      JOIN `paginas` p ON p.`id` = s.`pagina_id`
--     WHERE p.`clave` = 'home' AND s.`clave` = 'hero'
--     ORDER BY b.`orden`;
--
--  Deben salir con activo = 1 sólo las tres primeras.
