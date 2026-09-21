-- ===========================================================================
--  0028 · SEGUNDA LÁMINA DEL CARRUSEL DE PORTADA — «PREPARÉMONOS»
-- ---------------------------------------------------------------------------
--  La Conferencia Episcopal entregó un editable nuevo para la segunda lámina
--  del héroe de la portada («PORATADA 2 WEB.ai»). No es otro titular sobre la
--  misma fotografía: es otra composición entera.
--
--      fondo    unas manos escribiendo en un portátil que muestra el
--               calendario de noviembre de 2026 con la visita del Papa
--      titular  «Preparémonos» en dorado y negrita, «para recibirlo.» en
--               claro, más arriba que en la primera lámina
--      botón    rectángulo dorado «Programa Oficial» con icono de descarga,
--               a la derecha y bajo el titular, en lugar del granate «En
--               directo» de la izquierda
--
--  ── Qué hace ────────────────────────────────────────────────────────────
--
--   1. Da de alta en `medios` la fotografía nueva con su familia de anchos y
--      formatos, igual que el resto de héroes del sitio.
--   2. Reescribe el bloque de orden 20 de la sección `hero` de la portada:
--      titular, botón y fotografía propios.
--   3. Le pone `datos.diseno = "programa"`, que es lo que mira views/portada.php
--      para elegir la composición. Sin esa marca la lámina se pinta con la
--      composición de siempre, así que el valor es lo único que distingue una
--      lámina de otra.
--
--  La primera y la tercera lámina no se tocan.
--
--  Idempotente: se puede volver a pasar sin duplicar nada.
-- ===========================================================================

SET NAMES utf8mb4;

-- ── 1 · La fotografía ──────────────────────────────────────────────────────
-- Igual que en 0027: si ya estaba registrada se refrescan medidas y variantes
-- pero NO el texto alternativo, que puede haberlo reescrito alguien del panel.
INSERT INTO `medios` (`ruta`, `nombre_archivo`, `mime`, `ancho`, `alto`, `peso`, `variantes`, `alt`, `decorativa`) VALUES
  ('assets/img/rediseno/index/hero-programa.jpg', 'hero-programa.jpg', 'image/jpeg', 2880, 932, 154884,
   '{"base":"assets/img/rediseno/index/hero-programa","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}',
   'Unas manos escriben en un portátil que muestra el calendario de noviembre de 2026 con la visita del Papa León XIV', 0)
ON DUPLICATE KEY UPDATE
  `nombre_archivo` = VALUES(`nombre_archivo`), `mime` = VALUES(`mime`),
  `ancho` = VALUES(`ancho`), `alto` = VALUES(`alto`), `peso` = VALUES(`peso`),
  `variantes` = VALUES(`variantes`), `decorativa` = VALUES(`decorativa`);

-- ── 2 · La lámina ──────────────────────────────────────────────────────────
SET @sec := (
  SELECT s.`id` FROM `secciones` s
    JOIN `paginas` p ON p.`id` = s.`pagina_id`
   WHERE p.`clave` = 'home' AND s.`clave` = 'hero'
   LIMIT 1
);

UPDATE `bloques`
   SET `titulo`       = 'Preparémonos',
       `texto`        = 'para recibirlo.',
       `enlace_texto` = 'Programa Oficial',
       `enlace_url`   = 'agenda/',
       `imagen_id`    = (SELECT `id` FROM `medios`
                          WHERE `ruta` = 'assets/img/rediseno/index/hero-programa.jpg'),
       `datos`        = JSON_SET(
                          CASE WHEN `datos` IS NULL OR `datos` = '' OR NOT JSON_VALID(`datos`)
                               THEN '{}' ELSE `datos` END,
                          '$.diseno', 'programa')
 WHERE @sec IS NOT NULL AND `seccion_id` = @sec AND `orden` = 20;
