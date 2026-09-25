-- ===========================================================================
--  0036 · EN DIRECTO — LA PÁGINA SE QUEDA EN EL REPRODUCTOR
-- ---------------------------------------------------------------------------
--  Debajo del reproductor había tres secciones y se van las tres:
--
--    todavia-enlaces        «Todavía no hay enlaces», con una nota que dice
--                           «Última actualización: 13 de agosto de 2026»
--    tres-maneras-seguirlo  «Tres maneras de seguirlo»
--    avisame-cuando-haya    el formulario «Avísame cuando haya transmisión»
--
--  Se OCULTAN, no se borran: es lo mismo que desmarcar «Visible» en Páginas →
--  En directo, y desde ahí se vuelven a mostrar el día que hagan falta. Dos
--  de ellas lo harán: «Tres maneras de seguirlo» y el aviso tienen sentido
--  cuando se acerque el viaje.
--
--  ── Por qué el formulario no debería volver tal cual ─────────────────────
--
--  «Avísame cuando haya transmisión» pide un correo y pide aceptar la
--  política de privacidad, y su <form> lleva action="#": no envía a ninguna
--  parte. Lo que se escribía ahí no se guardaba ni se mandaba. Antes de
--  volver a encenderlo hay que darle un destino de verdad —la API del cPanel
--  que ya usa Contacto, o una tabla—, porque pedir un consentimiento para
--  después tirar el dato es peor que no pedir nada.
--
--  ── Qué NO toca ──────────────────────────────────────────────────────────
--
--  Sólo esas tres secciones de la página «en-directo». Ni la cabecera, ni el
--  reproductor —que no es una sección: sale de Configuración, ajustes
--  «directo.youtube» y «directo.titulo»—, ni ninguna otra página.
--
--  Ni un DELETE, ni un DROP, ni un ALTER. Es repetible.
-- ===========================================================================

SET NAMES utf8mb4;

SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'en-directo' LIMIT 1);

UPDATE `secciones`
   SET `activa` = 0
 WHERE @pag IS NOT NULL
   AND `pagina_id` = @pag
   AND `clave` IN ('todavia-enlaces', 'tres-maneras-seguirlo', 'avisame-cuando-haya');

-- ── Comprobación (a mano, después) ───────────────────────────────────────
--  Sin SELECT aquí: migrate.php no registra la migración si queda un
--  resultado sin leer.
--
--    SELECT s.`clave`, s.`activa`
--      FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
--     WHERE p.`clave` = 'en-directo' ORDER BY s.`orden`;
--
--  Debe quedar activa sólo «cabecera».
