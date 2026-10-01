-- ===========================================================================
--  0037 · PRENSA — FUERA LA SECCIÓN MULTIMEDIA
-- ---------------------------------------------------------------------------
--  Multimedia deja de vivir dentro de Prensa. Pasa a ser una página propia,
--  con su galería agrupada por actividad y fecha, y ahí no pinta nada un
--  apartado suelto al final de la página de prensa.
--
--  ── Esto SÍ borra ────────────────────────────────────────────────────────
--
--  A diferencia de las migraciones anteriores, aquí no basta con apagar: el
--  encargo es que la sección NO aparezca en el gestor, ni siquiera para
--  encenderla. Una sección apagada sigue saliendo en Páginas → Prensa, y
--  quien la vea va a preguntarse por qué no se ve en la web.
--
--  Se borran la sección y sus bloques. Hoy hay UNO: una prueba titulada
--  «demo 1», con la fotografía del portátil que usa el carrusel de la
--  portada.
--
--  ── Lo que NO se pierde ──────────────────────────────────────────────────
--
--  Las FOTOGRAFÍAS no se tocan. Viven en la tabla `medios` —la biblioteca
--  que comparten todas las páginas— y el bloque sólo guardaba el id de una
--  de ellas. Borrar el bloque suelta la referencia; el archivo y su ficha
--  siguen en la biblioteca y se pueden volver a elegir desde cualquier sitio,
--  incluida la galería nueva.
--
--  ── Qué NO toca ──────────────────────────────────────────────────────────
--
--  Sólo la sección «multimedia» de la página «prensa». Ni la acreditación,
--  ni el uso de imágenes, ni la botonera. Ni la PÁGINA «multimedia», que es
--  otra cosa y se queda donde está.
--
--  Es repetible: si ya se corrió, no encuentra nada y no hace nada.
-- ===========================================================================

SET NAMES utf8mb4;

SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'prensa' LIMIT 1);
SET @sec := (SELECT `id` FROM `secciones`
              WHERE `pagina_id` = @pag AND `clave` = 'multimedia' LIMIT 1);

-- Los bloques primero: si hubiera clave foránea, borrar la sección antes
-- fallaría, y si no la hay quedarían huérfanos apuntando a una sección que
-- ya no existe.
DELETE FROM `bloques`   WHERE @sec IS NOT NULL AND `seccion_id` = @sec;

-- El historial de versiones de esa sección tampoco tiene a dónde volver.
DELETE FROM `secciones_versiones` WHERE @sec IS NOT NULL AND `seccion_id` = @sec;

DELETE FROM `secciones` WHERE @sec IS NOT NULL AND `id` = @sec;

-- ── Comprobación (a mano, después) ───────────────────────────────────────
--  Sin SELECT aquí: migrate.php no registra la migración si queda un
--  resultado sin leer.
--
--    SELECT s.`clave`, s.`activa`
--      FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
--     WHERE p.`clave` = 'prensa' ORDER BY s.`orden`;
--
--  No debe aparecer «multimedia». Las otras cinco siguen.
