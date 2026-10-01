-- ===========================================================================
--  0038 · LA BIBLIOTECA CONSERVA EL ARCHIVO ORIGINAL
-- ---------------------------------------------------------------------------
--  Hasta ahora, al subir una imagen el servidor generaba sus variantes
--  —640, 1024 y 1600 px, en JPG y en WEBP— y BORRABA el archivo que había
--  subido el editor. Lo más grande que sobrevivía eran 1600 px.
--
--  Para la web está bien: nadie necesita descargarse ocho megas para ver una
--  foto en una tarjeta. Pero la galería de Multimedia sí: un medio que vaya a
--  publicar una fotografía la quiere entera, no recortada a 1600 px.
--
--  Desde aquí el original se conserva siempre. La web sigue sirviendo las
--  variantes —eso no cambia— y el original sólo se entrega cuando alguien
--  pulsa «Descargar».
--
--  ── Qué cuesta en disco ──────────────────────────────────────────────────
--
--  Una fotografía de prensa de unos 8 MB pasa de ocupar 1,5 MB (sólo
--  variantes) a unos 9,5 MB. Con 200 fotografías de la visita, unos 2 GB.
--  El VPS tiene 150 GB.
--
--  ── Las 95 que ya existen ────────────────────────────────────────────────
--
--  No se recuperan: su original se borró al subirlas y no hay de dónde
--  sacarlo. Se quedan con `original` a NULL, y la galería lo entiende: de
--  ésas ofrece la variante más grande en lugar del original. Si alguna tiene
--  que estar en calidad completa, hay que volver a subirla.
--
--  ── Qué NO toca ──────────────────────────────────────────────────────────
--
--  Añade dos columnas a `medios` y nada más. Ninguna fila existente cambia:
--  las dos nuevas nacen a NULL y todo lo que ya funciona sigue igual, porque
--  la web lee `ruta` y `variantes`, que no se tocan.
--
--  Es repetible: comprueba si la columna existe antes de añadirla.
-- ===========================================================================

SET NAMES utf8mb4;

-- MariaDB no tiene «ADD COLUMN IF NOT EXISTS» en todas las versiones, y un
-- ALTER que falla a mitad deja la migración sin terminar. Se pregunta primero
-- al catálogo y sólo se ejecuta si hace falta.
SET @hay := (SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
              WHERE `TABLE_SCHEMA` = DATABASE()
                AND `TABLE_NAME`   = 'medios'
                AND `COLUMN_NAME`  = 'original');

SET @sql := IF(@hay = 0,
  'ALTER TABLE `medios`
     ADD COLUMN `original` VARCHAR(255) NULL DEFAULT NULL AFTER `variantes`,
     ADD COLUMN `peso_original` INT UNSIGNED NULL DEFAULT NULL AFTER `original`',
  'DO 0');

PREPARE paso FROM @sql;
EXECUTE paso;
DEALLOCATE PREPARE paso;

-- ── Comprobación (a mano, después) ───────────────────────────────────────
--  Sin SELECT aquí: migrate.php no registra la migración si queda un
--  resultado sin leer.
--
--    SHOW COLUMNS FROM `medios` WHERE Field IN ('original', 'peso_original');
--
--  (Con LIKE 'original%' sólo sale una: `peso_original` empieza por «peso».)
--
--  Deben salir `original` y `peso_original`, las dos NULL por defecto.
