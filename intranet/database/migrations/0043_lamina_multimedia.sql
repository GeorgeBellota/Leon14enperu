-- ===========================================================================
--  0043 · PORTADA — CUARTA LÁMINA DEL CARRUSEL: «MULTIMEDIA» (SALIDE 4 WEB.ai)
-- ---------------------------------------------------------------------------
--  «Recordemos cada momento.» con el botón dorado «Multimedia». Se AÑADE
--  detrás de la última lámina visible; las que ya hay no se tocan —ni su
--  texto, ni su imagen, ni su orden, ni si están visibles—:
--
--    1  «Papa León XIV, le esperamos.»                    (no se mueve)
--    2  «Preparémonos para recibirlo.»                    (no se mueve)
--    3  «Señal Oficial»                                   (no se mueve)
--    4  «Recordemos cada momento.»     diseño «multimedia»   NUEVA, visible
--    …  las ocultas siguen detrás, ocultas
--
--  ── Qué hace ────────────────────────────────────────────────────────────
--
--   1. Da de alta en `medios` las dos imágenes. Los archivos van con el
--      código, en assets/img/rediseno/index/ (hero-multimedia* y
--      hero-multimedia-movil*); hay que subirlos ANTES de ejecutar esto o la
--      lámina saldrá sin foto.
--   2. Inserta la lámina, visible, con `orden` = el de la última visible + 5.
--      No hace falta abrir hueco: ninguna otra fila cambia.
--
--  Ni un UPDATE, ni un DELETE, ni un DROP, ni un ALTER.
--
--  Es repetible: si la lámina ya está, no inserta nada. Después se oculta,
--  se muestra y se reordena desde el panel como cualquier otra:
--  Páginas → Inicio → Carrusel de portada.
-- ===========================================================================

SET NAMES utf8mb4;

SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'home' LIMIT 1);
SET @sec := (SELECT `id` FROM `secciones`
              WHERE `pagina_id` = @pag AND `clave` = 'hero' LIMIT 1);

-- ¿Ya se ejecutó? Se reconoce por el diseño de la lámina.
SET @hecha := (SELECT COUNT(*) FROM `bloques`
                WHERE `seccion_id` = @sec
                  AND JSON_UNQUOTE(JSON_EXTRACT(`datos`, '$.diseno')) = 'multimedia');

-- ── 1 · Las imágenes ─────────────────────────────────────────────────────

INSERT INTO `medios` (`ruta`, `nombre_archivo`, `mime`, `ancho`, `alto`, `peso`, `variantes`, `alt`, `decorativa`)
VALUES
  ('assets/img/rediseno/index/hero-multimedia.jpg', 'hero-multimedia.jpg', 'image/jpeg', 2880, 932, 208126,
   '{"base":"assets/img/rediseno/index/hero-multimedia","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}',
   'Un fotógrafo dispara su cámara ante el altar donde el Papa León XIV saluda con la mano en alto', 0),
  ('assets/img/rediseno/index/hero-multimedia-movil.jpg', 'hero-multimedia-movil.jpg', 'image/jpeg', 1000, 1100, 94059,
   '{"base":"assets/img/rediseno/index/hero-multimedia-movil","anchos":[480,768,1000],"formatos":["webp","jpg"]}',
   'Un fotógrafo dispara su cámara ante el altar donde el Papa León XIV saluda con la mano en alto', 0)
ON DUPLICATE KEY UPDATE `id` = `id`;

SET @img_multi := (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/hero-multimedia.jpg');
SET @img_movil := (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/hero-multimedia-movil.jpg');

-- ── 2 · La lámina ────────────────────────────────────────────────────────

SET @ultima := (SELECT COALESCE(MAX(`orden`), 0) FROM `bloques`
                 WHERE `seccion_id` = @sec AND `activo` = 1);

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`,
                       `imagen_id`, `imagen_movil_id`, `enlace_texto`, `enlace_url`, `datos`)
SELECT @sec, @ultima + 5, 1,
       'Recordemos', 'cada momento.',
       @img_multi, @img_movil, 'Multimedia', 'multimedia/',
       JSON_OBJECT('diseno', 'multimedia')
 WHERE @sec IS NOT NULL AND @hecha = 0;

-- ── Comprobación (a mano, después) ───────────────────────────────────────
--  Sin SELECT aquí: database/migrate.php ejecuta el archivo de un tirón y un
--  resultado sin leer le impide registrar la migración. Para mirarlo:
--
--    SELECT b.`orden`, b.`activo`, b.`titulo`, JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.diseno')) AS diseno
--      FROM `bloques` b JOIN `secciones` s ON s.`id` = b.`seccion_id`
--      JOIN `paginas` p ON p.`id` = s.`pagina_id`
--     WHERE p.`clave` = 'home' AND s.`clave` = 'hero'
--     ORDER BY b.`orden`;
--
--  Deben salir las tres visibles de siempre y, cuarta, «Recordemos» (activo 1).
