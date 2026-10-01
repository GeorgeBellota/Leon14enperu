-- ===========================================================================
--  0039 · GALERÍA DE MULTIMEDIA — ACTIVIDADES Y FOTOGRAFÍAS
-- ---------------------------------------------------------------------------
--  Dos tablas nuevas para la página de Multimedia. Ninguna toca lo que ya
--  existe.
--
--  ── Por qué no van en `medios` ni en `bloques` ───────────────────────────
--
--  `medios` es la biblioteca que comparten TODAS las páginas: la portada, las
--  sedes, Prensa. Meterle columnas de galería —actividad, fecha, descripción—
--  la convertiría en dos cosas a la vez, y una foto del carrusel acabaría con
--  campos vacíos que nadie sabe para qué son. Peor: tocar esa tabla es tocar
--  todo el sitio.
--
--  `bloques` tampoco: sólo tiene dos niveles —sección y pieza— y aquí hacen
--  falta tres. Una actividad ocurre en VARIAS fechas, y cada fecha tiene sus
--  fotografías.
--
--  Así que la galería se guarda aparte y apunta a la biblioteca:
--
--      galeria_actividades        «Conferencia Episcopal Peruana»
--             ▲
--             │ actividad_id
--      galeria_fotos              fecha · descripción · orden
--             │ medio_id
--             ▼
--      medios                     el archivo, sus variantes y su original
--
--  Una misma fotografía puede estar en la galería y en el carrusel sin
--  duplicarse, y quitarla de la galería no la borra de la biblioteca.
--
--  ── Las fechas ───────────────────────────────────────────────────────────
--
--  La fecha va en la FOTOGRAFÍA, no en la actividad. Es lo que permite que
--  una actividad se celebre varios días: el desplegable «Fecha» de la página
--  se arma solo, con las fechas distintas que tengan fotografías dentro de
--  esa actividad. Sin fecha también vale —la fotografía sale en la actividad,
--  fuera de cualquier día—, y por eso la columna admite NULL.
--
--  ── Qué NO toca ──────────────────────────────────────────────────────────
--
--  Crea dos tablas y nada más. Ni `medios`, ni `secciones`, ni `bloques`, ni
--  `paginas`. Si se revierte, basta con borrarlas: no deja nada detrás.
--
--  Es repetible: CREATE TABLE IF NOT EXISTS.
-- ===========================================================================

SET NAMES utf8mb4;

-- ── Las actividades ─────────────────────────────────────────────────────
--  «Conferencia Episcopal Peruana», «Visita Apostólica del Papa León XIV al
--  Perú»… Son los bloques que la página pinta uno debajo de otro.
CREATE TABLE IF NOT EXISTS `galeria_actividades` (
  `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`     VARCHAR(160) NOT NULL,
  -- El rótulo en versalitas que va encima del titular. El diseño pone
  -- «ACTIVIDADES» en todas, pero se deja editable por si alguna necesita
  -- decir otra cosa.
  `rotulo`     VARCHAR(80) NOT NULL DEFAULT 'Actividades',
  `orden`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `activa`     TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Las fotografías de cada actividad ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `galeria_fotos` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actividad_id` SMALLINT UNSIGNED NOT NULL,
  -- Apunta a la biblioteca. ON DELETE CASCADE: si alguien borra la foto de
  -- la biblioteca, su entrada en la galería se va con ella. Lo contrario
  -- dejaría la galería enseñando un hueco.
  `medio_id`     INT UNSIGNED NOT NULL,
  `fecha`        DATE NULL DEFAULT NULL,
  -- Lo que se lee en el modal, bajo la fotografía grande. No es el texto
  -- alternativo: ése vive en `medios.alt` y describe la imagen para quien no
  -- la ve. Éste es el pie, y puede decir otra cosa.
  `descripcion`  VARCHAR(500) NULL DEFAULT NULL,
  `orden`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `activa`       TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  -- El índice que usa la página: pide las fotos de una actividad, agrupadas
  -- por fecha y en su orden.
  KEY `idx_actividad_fecha` (`actividad_id`, `fecha`, `orden`),
  KEY `idx_medio` (`medio_id`),
  CONSTRAINT `fk_galeria_actividad`
    FOREIGN KEY (`actividad_id`) REFERENCES `galeria_actividades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_galeria_medio`
    FOREIGN KEY (`medio_id`) REFERENCES `medios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Comprobación (a mano, después) ───────────────────────────────────────
--  Sin SELECT aquí: migrate.php no registra la migración si queda un
--  resultado sin leer.
--
--    SHOW TABLES LIKE 'galeria_%';
--    SHOW CREATE TABLE `galeria_fotos`;
--
--  Deben salir las dos tablas, y `galeria_fotos` con sus dos claves foráneas.
