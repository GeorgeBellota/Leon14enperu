-- ===========================================================================
--  0018 · LOS DISEÑOS DEL CARRUSEL ALTERNAN, Y LA COLECTA BAJA JUNTO A LOS SANTOS
-- ---------------------------------------------------------------------------
--  Qué hace, tres cosas:
--
--   1. La lámina 4 —la Colecta Nacional— pasa a diseño «fondo».
--   2. La lámina 5 —los cinco santos— vuelve al diseño partido.
--      Con eso las cinco quedan alternando: partida, fondo, partida, fondo,
--      partida. La primera es partida por obligación, no por turno: lleva el
--      retrato oficial del Santo Padre y sobre ese retrato no va ningún velo.
--   3. La sección «colecta» pasa del orden 12 al 47, que la deja justo detrás
--      de «tierra-de-santos» (45) y delante de «mas-destacado» (60). Es donde
--      está en la portada desde este cambio.
--
--  Qué NO hace: ni un DELETE, ni un TRUNCATE, ni un DROP, ni un ALTER. No toca
--            `voluntarios`, `usuarios`, `auditoria`, `medios` ni el ubigeo. No
--            cambia ningún texto: los tres UPDATE son sobre el diseño de dos
--            láminas y sobre el número de orden de una sección.
--
--  Es repetible: los tres UPDATE escriben el mismo valor si ya estaba.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;


-- ═══ 1 · LA LÁMINA DE LA COLECTA, A SANGRE ══════════════════════════════

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`datos` = JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.diseno', 'fondo')
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`rotulo` = 'Colecta Nacional'
    AND JSON_VALID(COALESCE(NULLIF(b.`datos`, ''), '{}'));


-- ═══ 2 · LA LÁMINA DE LOS SANTOS, PARTIDA ═══════════════════════════════
--
-- Se quita la clave en vez de vaciarla: «sin diseño elegido» y «diseño puesto
-- a cadena vacía» son lo mismo para la vista, pero en el panel el primero deja
-- el campo limpio y el segundo deja un hueco que parece un valor borrado.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`datos` = JSON_REMOVE(b.`datos`, '$.diseno')
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`rotulo` = 'Cinco caminos de santidad'
    AND b.`datos` IS NOT NULL
    AND JSON_VALID(b.`datos`)
    AND JSON_EXTRACT(b.`datos`, '$.diseno') IS NOT NULL;


-- ═══ 3 · LA COLECTA, DEBAJO DE LOS SANTOS ═══════════════════════════════

UPDATE `secciones` s
   JOIN `paginas` p ON p.id = s.pagina_id
    SET s.`orden` = 47
  WHERE p.clave = 'home'
    AND s.clave = 'colecta';


-- ═══ FOTO DE DESPUÉS ════════════════════════════════════════════════════

SELECT 'DESPUÉS' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

SELECT b.`orden`,
       b.`rotulo`,
       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.diseno')), 'partida') AS diseno
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
 ORDER BY b.`orden`;
