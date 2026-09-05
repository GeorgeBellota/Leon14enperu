-- ============================================================================
--  0007 · Configuración general del sitio
--
--  Tres ajustes que hacían falta para el lanzamiento y que hasta ahora estaban
--  escritos a mano en los archivos:
--
--    · qué página se muestra en la raíz del dominio,
--    · qué entradas se ven en el menú,
--    · cuánto pie se muestra.
--
--  No es el módulo de páginas completo —eso sigue pendiente—, pero permite
--  lanzar con el sitio recortado y abrir el resto sin tocar código.
-- ============================================================================

INSERT INTO `ajustes` (`clave`, `valor`, `tipo`, `descripcion`) VALUES

  ('sitio.pagina_inicio', 'voluntariado', 'texto',
   'Carpeta que se sirve en la raíz del dominio. Vacío o «home» para la portada original.'),

  -- Lista separada por comas de las claves del menú que se muestran. Vacío =
  -- todas. Es una lista y no un campo por página porque el módulo de páginas
  -- todavía no existe; cuando exista, esto se sustituye por la columna
  -- `en_menu` de cada página.
  ('menu.visibles', 'voluntariado', 'texto',
   'Entradas del menú que se muestran, separadas por comas. Vacío para mostrarlas todas.'),

  -- completo | simple | simple_en_internas
  ('pie.modo', 'simple', 'texto',
   'completo = las cuatro columnas de enlaces · simple = sólo el copyright · simple_en_internas = completo en la portada y simple en el resto.')

ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

INSERT INTO `permisos` (`clave`, `modulo`, `nombre`, `descripcion`) VALUES
  ('ajustes.general', 'ajustes', 'Configuración general',
   'Página de inicio, entradas del menú y pie del sitio.')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Sólo el administrador: cambiar la página de inicio o vaciar el menú afecta a
-- todo el sitio de golpe, y no es una tarea de edición de contenidos.
INSERT IGNORE INTO `rol_permiso` (`rol_id`, `permiso_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permisos` p ON p.clave = 'ajustes.general'
 WHERE r.clave = 'superadmin';
