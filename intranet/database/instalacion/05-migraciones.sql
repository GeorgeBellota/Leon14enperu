-- ===========================================================================
--  05 · REGISTRO DE MIGRACIONES
-- ---------------------------------------------------------------------------
--  La estructura del archivo 01 ya lleva aplicadas las quince migraciones del
--  proyecto. Esto las anota como hechas para que `migrate.php` no intente
--  ejecutarlas otra vez encima.
--
--  Sin este paso, el primer `php database/migrate.php` intentaría crear
--  columnas que ya existen. No haría daño —todas llevan IF NOT EXISTS— pero
--  sí insertaría contenido duplicado.
--
--  ── Con qué versiones está probado ───────────────────────────────────────
--
--    Producción      MariaDB 11.8.6  ·  PHP 8.3  ·  Nginx
--    Desarrollo      MariaDB 10.4    ·  PHP 8.1  ·  Apache (XAMPP)
--
--    Mínimo          MariaDB 10.4    ·  PHP 8.0
--
--  MariaDB, NO MySQL. Tres migraciones del proyecto usan
--  «ADD COLUMN IF NOT EXISTS», que MySQL no acepta. Y este archivo se generó
--  con el cliente de MariaDB 10.4 a propósito: así usa la sintaxis más
--  conservadora y entra igual en 10.4 que en 11.8.
-- ===========================================================================

SET NAMES utf8mb4;

INSERT INTO `migraciones` (`archivo`, `aplicada_en`) VALUES
  ('0001_baseline.php', NOW()),
  ('0002_contenido_voluntariado.sql', NOW()),
  ('0003_mantenimiento.sql', NOW()),
  ('0004_ubigeo_y_orden_formulario.sql', NOW()),
  ('0005_limpiar_nombres_ubigeo.sql', NOW()),
  ('0006_texto_ten_a_mano.sql', NOW()),
  ('0007_configuracion_general.sql', NOW()),
  ('0008_testigo_opcional.sql', NOW()),
  ('0009_comunicados.sql', NOW()),
  ('0010_medios.sql', NOW()),
  ('0011_contenido_paginas.sql', NOW()),
  ('0012_secciones_informe.sql', NOW()),
  ('0013_slugs_y_detalle.sql', NOW()),
  ('0014_portada_completa.sql', NOW()),
  ('0015_imagen_movil.sql', NOW());
