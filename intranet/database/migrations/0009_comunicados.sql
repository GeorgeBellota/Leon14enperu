-- ============================================================================
--  0009 · Comunicados
--
--  El aviso que aparece sobre la web: una imagen, un texto y un botón que
--  lleva a algún sitio o descarga un documento.
--
--  Sustituye al modal escrito a mano en portada.php, que además llevaba
--  semanas sin verlo nadie: vivía en la portada, y desde el lanzamiento la
--  raíz del dominio sirve voluntariado.
--
--  ── Por qué una tabla y no unos ajustes ──────────────────────────────────
--  Porque hay que llevar la cuenta de cada uno por separado y conservarlos al
--  retirarlos. Un comunicado desactivado no se borra: se queda con su cifra
--  de vistas y de clics, que es lo que permite saber si el de la peregrinación
--  funcionó mejor que el de las inscripciones. Con ajustes sueltos, activar
--  uno nuevo machacaría al anterior y su historial.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `comunicados` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Para reconocerlo en el panel. No se muestra en la web.
  `nombre`      VARCHAR(120)  NOT NULL,

  `descripcion` TEXT          NULL,

  -- Ruta de la imagen, relativa a la raíz del sitio: assets/img/comunicados/…
  `imagen`      VARCHAR(255)  NULL,

  `boton_texto` VARCHAR(80)   NOT NULL DEFAULT 'Ver más',

  -- `enlace`   → se abre en una pestaña nueva
  -- `descarga` → se sirve el archivo como adjunto
  `boton_tipo`  ENUM('enlace','descarga') NOT NULL DEFAULT 'enlace',

  -- La dirección de destino, o la ruta del archivo subido si es descarga.
  `boton_destino` VARCHAR(500) NULL,

  -- Nombre con el que se descarga, para que no salga «a7f3.pdf».
  `archivo_nombre` VARCHAR(200) NULL,

  `activo`      TINYINT(1)    NOT NULL DEFAULT 0,

  -- Caducidad automática. NULL = no caduca.
  --
  -- Se comprueba al mostrar, no con una tarea programada: un hosting
  -- compartido no siempre deja programar nada, y un comunicado que sigue
  -- saliendo tres días después de la fecha es peor que uno que no salió.
  `expira_en`   DATETIME      NULL,

  -- Dónde aparece: lista de claves de página separadas por comas.
  -- Vacío = en todas.
  `paginas`     VARCHAR(500)  NOT NULL DEFAULT '',

  -- Comportamiento. Estaba escrito en el JavaScript y había que tocar el
  -- archivo para cambiarlo.
  `veces_max`   SMALLINT UNSIGNED NOT NULL DEFAULT 3,
  `retraso_ms`  INT UNSIGNED  NOT NULL DEFAULT 3000,
  `autocierre_ms` INT UNSIGNED NOT NULL DEFAULT 0,

  -- ── Los contadores ──────────────────────────────────────────────────────
  -- Se guardan aquí y no en una tabla de eventos: lo que hace falta es la
  -- cifra, no el detalle de cada clic. Una tabla de eventos con una fila por
  -- visita crecería a millones sin que nadie la consulte nunca.
  `vistas`      INT UNSIGNED  NOT NULL DEFAULT 0,
  `clics`       INT UNSIGNED  NOT NULL DEFAULT 0,

  `creado_en`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por`  INT UNSIGNED  NULL,
  `actualizado_en` DATETIME   NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- El índice que usa la consulta de la web: activo + caducidad. Es la única
  -- consulta que se ejecuta en cada visita, así que conviene que no toque
  -- disco más de lo necesario.
  KEY `ix_comunicados_vigencia` (`activo`, `expira_en`),

  CONSTRAINT `fk_comunicados_usuario`
    FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── Permisos ───────────────────────────────────────────────────────────────
-- Se reutiliza la misma pareja que el resto de módulos: ver y editar.
INSERT IGNORE INTO `permisos` (`clave`, `descripcion`) VALUES
  ('comunicados.ver',     'Ver los comunicados y sus cifras.'),
  ('comunicados.editar',  'Crear, editar, activar y retirar comunicados.');

-- A quién se le dan.
--
-- Al editor también: publicar un aviso es trabajo de contenidos, no de
-- administración de sistemas. Al rol de consulta sólo la lectura, que es lo
-- que necesita para leer las cifras en el escritorio.
INSERT IGNORE INTO `rol_permiso` (`rol_id`, `permiso_id`)
SELECT r.id, p.id
  FROM `roles` r
  JOIN `permisos` p
 WHERE r.clave IN ('superadmin', 'coordinador', 'editor')
   AND p.clave IN ('comunicados.ver', 'comunicados.editar');

INSERT IGNORE INTO `rol_permiso` (`rol_id`, `permiso_id`)
SELECT r.id, p.id
  FROM `roles` r
  JOIN `permisos` p
 WHERE r.clave = 'consulta'
   AND p.clave = 'comunicados.ver';


-- ── El comunicado que ya existía ───────────────────────────────────────────
-- El modal de la portada, traído tal cual estaba, para no perder el texto que
-- ya se había escrito. Entra DESACTIVADO: que empiece a salir o no es una
-- decisión de quien lo publique, no de una migración.
INSERT INTO `comunicados`
  (`nombre`, `descripcion`, `imagen`, `boton_texto`, `boton_tipo`, `boton_destino`,
   `activo`, `paginas`, `veces_max`, `retraso_ms`, `autocierre_ms`)
SELECT
  'Los amigos de León · voluntariado',
  'Seis servicios y tres fases para acompañar el viaje del Santo Padre. La inscripción se hace en cinco minutos y no te compromete a nada todavía.',
  'assets/img/fotos/ayudar-voluntariado-1024.jpg',
  'Quiero ser voluntario',
  'enlace',
  'voluntariado/',
  0,
  '',
  3, 3000, 12000
 WHERE NOT EXISTS (SELECT 1 FROM `comunicados`);
