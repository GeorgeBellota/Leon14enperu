-- ===========================================================================
--  0040 · NOTICIAS — TABLA PROPIA, CON FECHA DE VERDAD
-- ---------------------------------------------------------------------------
--  Las noticias eran BLOQUES de una sección, y de ahí salen casi todas las
--  quejas del cliente:
--
--    «deben mostrarse en orden cronológico descendente»
--        No se podía. La fecha era TEXTO LIBRE —«Agosto de 2026», «5 de
--        agosto de 2026»— guardado en el JSON de `datos`. No hay nada que
--        ordenar en eso. Lo que se veía era el orden manual de las flechas.
--
--    «solo permite una fotografía, un titular y un bloque de texto»
--        Es exactamente lo que da un bloque. No es un fallo del editor: es
--        el molde.
--
--    «no es posible insertar enlaces externos dentro del texto»
--        El campo se pinta escapado, así que un <a> salía como letras.
--
--  Con tabla propia, la fecha es una DATE y el orden sale solo; el cuerpo es
--  HTML con sus imágenes y enlaces; y cada noticia tiene su SEO, que en un
--  bloque no cabía.
--
--  ── Qué pasa con las cuatro que ya hay ───────────────────────────────────
--
--  Se migran con su fecha interpretada del texto:
--
--    «5 de agosto de 2026»        → 2026-08-05
--    «28 de septiembre de 2026»   → 2026-09-28
--    «Agosto de 2026»             → 2026-08-01   ← sin día; queda por revisar
--
--  Las dos últimas quedan el día 1 porque no dicen cuál. Están marcadas con
--  `fecha_aproximada` para poder encontrarlas y corregirlas desde el panel.
--
--  Los BLOQUES NO SE BORRAN. Quedan donde están hasta que la página nueva
--  esté subida y comprobada; los retira la 0042. Si algo sale mal, la web
--  sigue teniendo de dónde pintar.
--
--  ── Las fotografías ──────────────────────────────────────────────────────
--
--  No se tocan. Siguen en `medios` y aquí sólo se apunta su id.
--
--  Es repetible: CREATE TABLE IF NOT EXISTS, y la copia de las cuatro
--  comprueba el slug antes de insertar.
-- ===========================================================================

SET NAMES utf8mb4;

-- ── Las noticias ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `noticias` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- La dirección pública: /noticias/{slug}/. Única, porque dos noticias con
  -- el mismo slug harían que una de las dos fuera inalcanzable.
  `slug`      VARCHAR(190) NOT NULL,
  `titulo`    VARCHAR(255) NOT NULL,

  -- El extracto del listado. Es lo que pidió el cliente: «cada publicación
  -- debe presentar únicamente un extracto». Si se deja vacío, la página lo
  -- saca de las primeras líneas del cuerpo.
  `resumen`   VARCHAR(500) NULL DEFAULT NULL,

  -- El cuerpo, en HTML. Lo escribe el editor enriquecido y lo limpia
  -- HtmlSeguro ANTES de llegar aquí: de lo que entra por un formulario no se
  -- guarda ni una etiqueta sin filtrar.
  `cuerpo`    LONGTEXT NULL DEFAULT NULL,

  `imagen_id` INT UNSIGNED NULL DEFAULT NULL,

  -- ── La fecha, que es el motivo de toda esta tabla ────────────────────
  -- DATE y no texto: es lo que permite «de la más reciente a la más
  -- antigua» sin depender de que alguien ordene bien las flechas.
  `fecha`     DATE NOT NULL,

  -- Las que se migran de un texto sin día («Agosto de 2026») quedan el 1 y
  -- marcadas aquí, para poder listarlas y corregirlas.
  `fecha_aproximada` TINYINT(1) NOT NULL DEFAULT 0,

  `fuente`    VARCHAR(160) NULL DEFAULT NULL,

  -- `borrador` se escribe y no se publica. Es lo que falta hoy: una noticia
  -- a medias está en la web en cuanto se guarda.
  `estado`    ENUM('borrador','publicada') NOT NULL DEFAULT 'borrador',

  -- La grande de la izquierda en el diseño. Sólo cuenta la más reciente que
  -- la tenga: dos destacadas a la vez no caben en ese hueco.
  `destacada` TINYINT(1) NOT NULL DEFAULT 0,

  -- ── SEO por noticia ──────────────────────────────────────────────────
  -- Vacíos = se usan el titular y el resumen. No se duplica el contenido por
  -- obligación: se deja cambiarlo cuando el titular bueno para la web no es
  -- el bueno para Google.
  `seo_titulo`      VARCHAR(190) NULL DEFAULT NULL,
  `seo_descripcion` VARCHAR(255) NULL DEFAULT NULL,
  `og_imagen_id`    INT UNSIGNED NULL DEFAULT NULL,

  `creado_por`     INT UNSIGNED NULL DEFAULT NULL,
  `creado_en`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),

  -- El índice del listado: publicadas, de la más reciente a la más antigua.
  KEY `idx_portada` (`estado`, `fecha` DESC, `id` DESC),
  KEY `idx_imagen` (`imagen_id`),

  -- SET NULL y no CASCADE: si alguien borra la fotografía de la biblioteca,
  -- la noticia se queda sin portada, no desaparece. Perder el texto por
  -- borrar una imagen sería absurdo.
  CONSTRAINT `fk_noticia_imagen`
    FOREIGN KEY (`imagen_id`) REFERENCES `medios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_noticia_og`
    FOREIGN KEY (`og_imagen_id`) REFERENCES `medios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Los vídeos ──────────────────────────────────────────────────────────
--  Tabla aparte y no bloques, porque hay que guardar lo que se trae de
--  YouTube —título, autor y la miniatura ya descargada— y eso no cabe en un
--  bloque sin inventarle columnas.
CREATE TABLE IF NOT EXISTS `noticias_videos` (
  `id`  INT UNSIGNED NOT NULL AUTO_INCREMENT,

  `url` VARCHAR(255) NOT NULL,

  -- El identificador suelto, que es lo que se pega en el <iframe> y en la
  -- dirección de la miniatura. Se extrae de la URL al guardar para no tener
  -- que volver a analizarla en cada visita.
  `youtube_id` VARCHAR(20) NOT NULL,

  -- Título y autor vienen de oEmbed, que no pide clave de API. Se guardan
  -- en lugar de pedirlos en cada carga: la página no puede depender de que
  -- YouTube responda.
  `titulo` VARCHAR(255) NULL DEFAULT NULL,
  `autor`  VARCHAR(160) NULL DEFAULT NULL,

  -- La miniatura se DESCARGA al servidor y entra en la biblioteca.
  --
  -- No se enlaza a i.ytimg.com a propósito. La CSP del sitio es
  -- «img-src 'self'» y abrirla haría que el navegador del visitante pidiera
  -- la imagen a Google en cuanto abre /noticias/ —antes de aceptar nada—,
  -- que es justo lo que este sitio evita en todas partes. Además, si el
  -- vídeo se hace privado, la portada sigue estando.
  `miniatura_id` INT UNSIGNED NULL DEFAULT NULL,

  `descripcion` TEXT NULL DEFAULT NULL,
  `orden`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,

  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_youtube` (`youtube_id`),
  KEY `idx_orden` (`activo`, `orden`),
  CONSTRAINT `fk_video_miniatura`
    FOREIGN KEY (`miniatura_id`) REFERENCES `medios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
--  Las cuatro noticias que ya existen
-- ---------------------------------------------------------------------------
--  Se copian de los bloques. El WHERE NOT EXISTS sobre el slug hace que
--  repetir la migración no las duplique ni pise lo que se haya editado ya en
--  la pantalla nueva.
--
--  El cuerpo entra tal cual, envuelto en <p>: lo que hay hoy es texto plano
--  con saltos de línea y, sin envolverlo, se vería todo seguido.
-- ===========================================================================

SET @sec := (SELECT s.`id` FROM `secciones` s
               JOIN `paginas` p ON p.`id` = s.`pagina_id`
              WHERE p.`clave` = 'noticias' AND s.`clave` = 'ultimas-noticias' LIMIT 1);

INSERT INTO `noticias`
  (`slug`, `titulo`, `resumen`, `cuerpo`, `imagen_id`, `fecha`, `fecha_aproximada`, `fuente`, `estado`, `destacada`)
SELECT
  b.`slug`,
  b.`titulo`,
  NULL,
  CONCAT('<p>', REPLACE(TRIM(b.`texto`), '\n\n', '</p><p>'), '</p>'),
  b.`imagen_id`,
  CASE JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.fecha'))
    WHEN '5 de agosto de 2026'      THEN '2026-08-05'
    WHEN '28 de septiembre de 2026' THEN '2026-09-28'
    ELSE '2026-08-01'
  END,
  CASE JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.fecha'))
    WHEN '5 de agosto de 2026'      THEN 0
    WHEN '28 de septiembre de 2026' THEN 0
    ELSE 1
  END,
  JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.fuente')),
  'publicada',
  0
FROM `bloques` b
WHERE @sec IS NOT NULL
  AND b.`seccion_id` = @sec
  AND b.`slug` IS NOT NULL
  AND b.`slug` <> ''
  AND NOT EXISTS (SELECT 1 FROM (SELECT `slug` FROM `noticias`) n WHERE n.`slug` = b.`slug`);

-- La más reciente es la destacada: es la que ocupa el hueco grande de la
-- izquierda en el diseño. Se calcula en lugar de clavarla, para que siga
-- siendo la correcta cuando se publique la siguiente.
UPDATE `noticias` SET `destacada` = 0;

UPDATE `noticias`
   SET `destacada` = 1
 WHERE `estado` = 'publicada'
 ORDER BY `fecha` DESC, `id` DESC
 LIMIT 1;

-- ── Comprobación (a mano, después) ───────────────────────────────────────
--  Sin SELECT aquí: migrate.php no registra la migración si queda un
--  resultado sin leer.
--
--    SELECT `fecha`, `fecha_aproximada`, `destacada`, `titulo`
--      FROM `noticias` ORDER BY `fecha` DESC;
--
--  Deben salir cuatro, de la más reciente a la más antigua, y dos con
--  `fecha_aproximada` = 1 para repasar.
