-- ===========================================================================
--  0034 · PORTADA — DOS LÁMINAS NUEVAS EN EL CARRUSEL (SLIDES PÁG HOME.ai)
-- ---------------------------------------------------------------------------
--  El editable de septiembre de 2026 trae dos láminas. Se AÑADEN; las que ya
--  hay no se tocan —ni su texto, ni su imagen, ni si están visibles—:
--
--    1  la primera lámina de siempre                      (no se mueve)
--    2  «Preparémonos para recibirlo.»   diseño «preparemonos»   NUEVA
--       La de siempre enseñaba un calendario con el 1 de noviembre en
--       martes; en 2026 cae en domingo. Ésta trae el calendario bueno y el
--       brillo del botón que dibuja el editable.
--    3  «Señal Oficial» del IRTP          diseño «senal»          NUEVA
--    4… el resto de las láminas, en el mismo orden que tenían
--
--  ── Qué hace ────────────────────────────────────────────────────────────
--
--   1. Da de alta en `medios` las tres imágenes. Los archivos van con el
--      código, en assets/img/rediseno/index/ (hero-preparemonos*,
--      hero-senal*, hero-senal-movil*); hay que subirlos ANTES de ejecutar
--      esto o la lámina saldrá sin foto.
--   2. Corre 20 puestos el `orden` de las láminas que van detrás de la
--      primera, para abrir hueco. Sólo cambia ese número: el orden relativo
--      entre ellas es el mismo.
--   3. Inserta las dos láminas en el hueco, visibles.
--
--  Ni un DELETE, ni un DROP, ni un ALTER.
--
--  Es repetible: si las dos láminas ya están, no corre nada ni inserta nada.
--  Después se ocultan, se muestran y se reordenan desde el panel como
--  cualquier otra: Páginas → Inicio → Carrusel de portada.
-- ===========================================================================

SET NAMES utf8mb4;

SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'home' LIMIT 1);
SET @sec := (SELECT `id` FROM `secciones`
              WHERE `pagina_id` = @pag AND `clave` = 'hero' LIMIT 1);

-- ¿Ya se ejecutó? Se reconoce por el diseño de las láminas nuevas.
SET @hecha := (SELECT COUNT(*) FROM `bloques`
                WHERE `seccion_id` = @sec
                  AND JSON_VALUE(`datos`, '$.diseno') IN ('preparemonos', 'senal'));

-- ── 1 · Las imágenes ─────────────────────────────────────────────────────

INSERT INTO `medios` (`ruta`, `nombre_archivo`, `mime`, `ancho`, `alto`, `peso`, `variantes`, `alt`, `decorativa`)
VALUES
  ('assets/img/rediseno/index/hero-preparemonos.jpg', 'hero-preparemonos.jpg', 'image/jpeg', 2880, 932, 160639,
   '{"base":"assets/img/rediseno/index/hero-preparemonos","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}',
   'Unas manos escriben en un portátil que muestra el calendario de noviembre de 2026 con la visita del Papa León XIV', 0),
  ('assets/img/rediseno/index/hero-senal.jpg', 'hero-senal.jpg', 'image/jpeg', 2880, 932, 109532,
   '{"base":"assets/img/rediseno/index/hero-senal","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}',
   'El logotipo del IRTP junto al Papa León XIV, que saluda con la mano en alto', 0),
  ('assets/img/rediseno/index/hero-senal-movil.png', 'hero-senal-movil.png', 'image/png', 763, 782, 474121,
   '{"base":"assets/img/rediseno/index/hero-senal-movil","anchos":[480,763],"formatos":["webp","png"]}',
   'El Papa León XIV saluda con la mano en alto', 0)
ON DUPLICATE KEY UPDATE `id` = `id`;

SET @img_prep  := (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/hero-preparemonos.jpg');
SET @img_senal := (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/hero-senal.jpg');
SET @img_movil := (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/hero-senal-movil.png');

-- ── 2 · El hueco detrás de la primera ────────────────────────────────────

SET @primera := (SELECT MIN(`orden`) FROM `bloques` WHERE `seccion_id` = @sec);

UPDATE `bloques`
   SET `orden` = `orden` + 20
 WHERE @sec IS NOT NULL AND @hecha = 0
   AND `seccion_id` = @sec AND `orden` > @primera;

-- ── 3 · Las dos láminas ──────────────────────────────────────────────────

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`,
                       `imagen_id`, `enlace_texto`, `enlace_url`, `datos`)
SELECT @sec, @primera + 10, 1,
       'Preparémonos', 'para recibirlo.',
       @img_prep, 'Programa Oficial', 'agenda/',
       JSON_OBJECT('diseno', 'preparemonos')
 WHERE @sec IS NOT NULL AND @hecha = 0;

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`,
                       `imagen_id`, `imagen_movil_id`, `enlace_texto`, `enlace_url`, `datos`)
SELECT @sec, @primera + 20, 1,
       'Señal Oficial', CONCAT('Visita Apostólica del Papa', CHAR(10), 'León XIV al Perú'),
       @img_senal, @img_movil, 'Acreditaciones', 'prensa/#senal-oficial',
       JSON_OBJECT('diseno', 'senal',
                   'nota', CONCAT('de acceso a la señal oficial sin logos ni', CHAR(10),
                                  'banners para medios de comunicación.'))
 WHERE @sec IS NOT NULL AND @hecha = 0;

-- ── Comprobación (a mano, después) ───────────────────────────────────────
--  Sin SELECT aquí: database/migrate.php ejecuta el archivo de un tirón y un
--  resultado sin leer le impide registrar la migración. Para mirarlo:
--
--    SELECT b.`orden`, b.`activo`, b.`titulo`, JSON_VALUE(b.`datos`, '$.diseno') AS diseno
--      FROM `bloques` b JOIN `secciones` s ON s.`id` = b.`seccion_id`
--      JOIN `paginas` p ON p.`id` = s.`pagina_id`
--     WHERE p.`clave` = 'home' AND s.`clave` = 'hero'
--     ORDER BY b.`orden`;
--
--  Deben salir la primera de siempre, las dos nuevas y detrás las demás.
