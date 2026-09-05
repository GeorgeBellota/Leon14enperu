-- ============================================================================
--  0003 · Modo mantenimiento con lista blanca de direcciones IP
--
--  Cuando se activa, el sitio público deja de mostrarse y responde una página
--  de aviso. Las IP registradas aquí siguen viendo el sitio normal, para poder
--  revisar los cambios antes de abrir la puerta.
--
--  La intranet NUNCA se corta: si el mantenimiento dejara fuera al panel,
--  quien lo activó no tendría por dónde volver a desactivarlo.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ips_permitidas` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  -- Se guarda en texto, no en VARBINARY: aquí también se admiten rangos
  -- (203.0.113.0/24), que no caben en una dirección binaria.
  `ip`          VARCHAR(64)  NOT NULL,
  `etiqueta`    VARCHAR(120) NOT NULL COMMENT 'de quién es: «oficina CEP», «casa de Jorge»…',
  `activa`      TINYINT(1)   NOT NULL DEFAULT 1,
  `creado_por`  INT UNSIGNED NULL,
  `creado_en`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_uso`  DATETIME     NULL COMMENT 'última vez que esta IP entró durante un mantenimiento',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ips_permitidas` (`ip`),
  CONSTRAINT `fk_ips_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ajustes` (`clave`, `valor`, `tipo`, `descripcion`) VALUES
  ('mantenimiento.activo', '0', 'booleano',
   'Con 1, el sitio público muestra la página de mantenimiento a todo el mundo salvo a las IP autorizadas.'),
  ('mantenimiento.titulo', 'Volvemos enseguida', 'texto',
   'Titular de la página de mantenimiento.'),
  ('mantenimiento.mensaje',
   'Estamos preparando esta web para el viaje apostólico del Papa León XIV al Perú. Vuelve dentro de un rato.',
   'texto', 'Texto de la página de mantenimiento.'),
  ('mantenimiento.vuelve', '', 'texto',
   'Cuándo se espera volver. Si está vacío no se muestra nada.')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

INSERT INTO `permisos` (`clave`, `modulo`, `nombre`, `descripcion`) VALUES
  ('mantenimiento.gestionar', 'ajustes', 'Modo mantenimiento',
   'Cerrar y abrir el sitio público, y gestionar las IP autorizadas.')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Sólo el administrador. Cerrar la web al público no es una acción de edición
-- de contenidos: quien edita textos no debería poder apagar el sitio entero.
INSERT IGNORE INTO `rol_permiso` (`rol_id`, `permiso_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permisos` p ON p.clave = 'mantenimiento.gestionar'
 WHERE r.clave = 'superadmin';
