-- ===========================================================================
--  0024 · EL CARRUSEL ABRE CON EL BANNER, SIN TEXTO ENCIMA
-- ---------------------------------------------------------------------------
--  Pedido del cliente. El carrusel queda en tres láminas y en este orden:
--
--      1. El banner de la Conferencia Episcopal, sin nada escrito encima
--      2. La Colecta Nacional
--      3. Los cinco santos
--
--  ── Qué hace ──────────────────────────────────────────────────────────────
--
--   1. Apaga la lámina «Abramos el corazón», que ocupaba el primer puesto.
--   2. Inserta la lámina del banner con diseño «sin-texto» y orden 5, que la
--      deja delante de la Colecta (40) y de los santos (50).
--
--  ── Qué NO hace ───────────────────────────────────────────────────────────
--
--  Ni un DELETE, ni un TRUNCATE, ni un DROP, ni un ALTER. No toca
--  `voluntarios`, `usuarios`, `auditoria`, `medios`, `comunicados`, `paginas`
--  ni `ajustes`. De las láminas que ya existían sólo cambia el interruptor de
--  una; sus textos, su imagen y su enlace se quedan intactos.
--
--  «Abramos el corazón» se APAGA, no se borra: sigue en el panel con todo su
--  contenido y volver a encenderla es un clic. Es lo mismo que ya se hizo con
--  «Los amigos de León» y «Cuatro ciudades» en la 0021.
--
--  ── Por qué la lámina nueva casi no lleva texto ───────────────────────────
--
--  Porque el banner ya lo trae dibujado: el titular, las fechas y el sello. El
--  diseño «sin-texto» no pinta rótulo, ni titular, ni bajada, ni botones.
--
--  El `titulo` sí se rellena, y no es contradictorio: no se pinta, se usa como
--  TEXTO ALTERNATIVO de la imagen. Un mensaje dibujado dentro de un JPEG no lo
--  lee nadie con un lector de pantalla; escrito aquí, sí.
--
--  ── La fotografía ─────────────────────────────────────────────────────────
--
--  No se escribe ninguna: la lámina se queda sin imagen propia y la vista pinta
--  la pieza de reserva que va en el código (assets/img/banners/pieza-1-*). En
--  cuanto alguien suba otra desde el panel, la suya manda.
--
--  ⚠ CONVIENE SUBIR UNA VERSIÓN VERTICAL para móvil, en el campo «imagen para
--    móvil» de la lámina. El banner es casi tres veces más ancho que alto y en
--    esta lámina la imagen NO se recorta —se muestra entera, que es el sentido
--    de «sin-texto»—, así que en un teléfono se ve una franja pequeña en medio
--    de mucho aire. No es un fallo del código: es la proporción de la pieza.
--
--  ── Es repetible ──────────────────────────────────────────────────────────
--
--  El UPDATE escribe el mismo valor si ya estaba y el INSERT lleva guarda NOT
--  EXISTS por (sección del hero, orden 5). Ejecutarlo dos veces no duplica la
--  lámina ni vuelve a apagar nada.
--
--  EL CÓDIGO SE DESPLIEGA ANTES QUE ESTO: la vista tiene que saber pintar
--  «sin-texto». Si entrara primero la migración, la lámina caería en el diseño
--  partido y saldría el banner recortado con un panel de color al lado.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

SELECT b.`orden`, b.`activo`, b.`rotulo`, b.`titulo`,
       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.diseno')), 'partida') AS diseno
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
 ORDER BY b.`orden`;


-- ═══ 1 · SE APAGA «ABRAMOS EL CORAZÓN» ══════════════════════════════════
--
-- Se identifica por el titular y no por el rótulo: el rótulo de esta lámina es
-- una línea de fechas y puede cambiarse desde el panel en cualquier momento.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`activo` = 0
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`titulo` = 'Abramos el corazón';


-- ═══ 2 · LA LÁMINA DEL BANNER ═══════════════════════════════════════════
--
-- Orden 5: delante de todas las demás, que empiezan en 10.
--
-- La guarda NOT EXISTS mira el ORDEN y no el texto. El titular de esta lámina
-- es texto alternativo y es justo lo que alguien puede reescribir desde el
-- panel el primer día; el orden, en cambio, es lo que la define como «la
-- primera». Con la guarda por texto, un retoque del alt habría hecho que una
-- segunda pasada insertara la lámina otra vez.

INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT hero.id, 5, 1,
       NULL,
       '¡El Papa León XIV vuelve al Perú! Del 11 al 16 de noviembre de 2026',
       NULL,
       NULL,
       NULL,
       '{"diseno":"sin-texto"}'
  FROM (
        SELECT s.id
          FROM `secciones` s
          JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'home' AND s.clave = 'hero'
         LIMIT 1
       ) AS hero
 WHERE NOT EXISTS (
        SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
         WHERE ya.seccion_id = hero.id AND ya.orden = 5
       );


-- ═══ FOTO DE DESPUÉS ════════════════════════════════════════════════════

SELECT 'DESPUÉS' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

-- Tienen que quedar TRES encendidas y en este orden: el banner (5, sin-texto),
-- la Colecta (40, fondo-derecha) y los santos (50, fondo).
SELECT b.`orden`, b.`activo`, b.`rotulo`, b.`titulo`,
       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.diseno')), 'partida') AS diseno,
       b.`imagen_id`, b.`imagen_movil_id`
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
 ORDER BY b.`orden`;
