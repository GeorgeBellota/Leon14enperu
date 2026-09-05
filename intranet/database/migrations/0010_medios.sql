-- ===========================================================================
--  0010 · Gestor de medios
-- ===========================================================================
--
--  La tabla `medios` existe desde el baseline y nunca se llenó: no había forma
--  de meter una imagen desde el panel. Esta migración prepara lo que falta.
--
--  ── Por qué una columna `variantes` ──────────────────────────────────────
--
--  El sitio no sirve una imagen: sirve una familia. Cada foto existe en dos
--  formatos y tres anchos, y la página elige con <picture> y srcset:
--
--      retrato-oficial-640.webp    retrato-oficial-640.jpg
--      retrato-oficial-1024.webp   retrato-oficial-1024.jpg
--      retrato-oficial-1131.webp   retrato-oficial-1131.jpg
--
--  Si el CMS guardara una sola ruta, hacer editable una imagen significaría
--  perder eso: el visitante de móvil pasaría de recibir 640 px en WebP a
--  recibir el archivo grande en JPG. Con el tráfico previsto para noviembre,
--  y siendo la mayoría conexiones móviles, sería una regresión seria.
--
--  Así que `medios` guarda la familia entera: la ruta de respaldo en `ruta`
--  —la que va en el <img src>, para navegadores sin WebP— y en `variantes`
--  el nombre base, los anchos y los formatos disponibles.
--
--  Antes de ejecutar: crear en el servidor
--      public_html/assets/subidos/paginas/
--  con permiso de escritura para el usuario de PHP.
-- ===========================================================================

SET NAMES utf8mb4;


-- ── La familia de archivos de cada imagen ─────────────────────────────────
--
-- Ejemplo de contenido:
--   {"base":"assets/subidos/paginas/retrato-a1b2c3d4",
--    "anchos":[640,1024,1600],
--    "formatos":["webp","jpg"]}
--
-- Nulo en las imágenes que no tienen familia: los SVG, que son vectores y no
-- se reescalan.
ALTER TABLE `medios`
  ADD COLUMN IF NOT EXISTS `variantes` JSON NULL COMMENT 'base, anchos y formatos de la familia'
    AFTER `peso`;

-- Buscar por nombre en el selector de imágenes. Con doscientas ochenta
-- imágenes, un desplegable sin búsqueda no se puede usar.
ALTER TABLE `medios`
  ADD INDEX IF NOT EXISTS `ix_medios_nombre` (`nombre_archivo`);


-- ── Permisos: no hay nada que crear ───────────────────────────────────────
--
-- El baseline ya los declaró, y con el reparto correcto:
--
--   medios.ver    → superadmin, coordinador, editor, consulta
--   medios.subir  → superadmin, editor      ← «Subir y borrar archivos»
--
-- Que el coordinador NO tenga `medios.subir` es deliberado: ese rol lleva
-- voluntarios, no contenidos. El código usa esas dos claves tal cual.
--
-- Una versión anterior de esta migración creaba un `medios.editar` que hacía
-- lo mismo que `medios.subir`. Si llegó a aplicarse en algún entorno, se
-- retira aquí: dos permisos para una sola cosa acaban en un rol que tiene uno
-- y no el otro, y en una pantalla que se comporta distinto según quién entre.
DELETE rp FROM `rol_permiso` rp
  JOIN `permisos` p ON p.id = rp.permiso_id
 WHERE p.clave = 'medios.editar';

DELETE FROM `permisos` WHERE `clave` = 'medios.editar';


-- ── Comprobación ──────────────────────────────────────────────────────────
SELECT
  (SELECT COUNT(*) FROM `permisos` WHERE `clave` IN ('medios.ver','medios.subir'))  AS permisos_del_baseline,
  (SELECT COUNT(*) FROM `permisos` WHERE `clave` = 'medios.editar')                 AS duplicado_retirado,
  (SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'medios'
      AND `COLUMN_NAME` = 'variantes')                                              AS columna_variantes,
  (SELECT COUNT(*) FROM `medios`)                                                   AS imagenes_registradas;
