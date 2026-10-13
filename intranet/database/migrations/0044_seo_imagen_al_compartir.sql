-- ===========================================================================
--  0044 · SEO Y REDES — LA IMAGEN QUE SALE AL COMPARTIR UN ENLACE
-- ---------------------------------------------------------------------------
--  El panel estrena la pantalla «SEO y redes»: la imagen general que sale al
--  pegar un enlace en WhatsApp o en redes, la de cada página y un botón para
--  forzar que las redes las vuelvan a descargar. Todo eso vive en DOS ajustes
--  nuevos; las tablas no cambian.
--
--    seo.og_imagen_id   La imagen general (id de `medios`). Vacío = el
--                       pantallazo del banner de la portada, que va con el
--                       código en assets/img/og/og-portada.jpg.
--    seo.version        Un número que va pegado a la dirección de todas las
--                       imágenes para compartir. «Forzar la actualización» lo
--                       sube, y con él cambian todas a la vez.
--
--  ── Qué hace ────────────────────────────────────────────────────────────
--
--  Da de alta los dos ajustes con su valor de partida y su descripción. Si ya
--  existen —porque alguien guardó la pantalla antes de correr esto— NO toca su
--  valor: sólo completa el tipo y la descripción.
--
--  Ni un DELETE, ni un DROP, ni un ALTER. No cambia ninguna página, ninguna
--  imagen ni ningún texto: hasta que alguien elija algo en el panel, todas
--  las páginas salen con el banner de la portada, que es lo que hace el código
--  nuevo por sí solo.
--
--  Es repetible: correrlo dos veces deja lo mismo que una.
--
--  ── Antes de correrlo ───────────────────────────────────────────────────
--
--  El servidor tiene que poder escribir en assets/subidos/ (ya puede: ahí
--  se suben las imágenes del panel). La web crea sola la carpeta
--  assets/subidos/compartir/ la primera vez que prepara una imagen.
-- ===========================================================================

SET NAMES utf8mb4;

INSERT INTO `ajustes` (`clave`, `valor`, `tipo`, `descripcion`)
VALUES
  ('seo.og_imagen_id', NULL, 'numero',
   'Imagen general al compartir (id de medios). Vacío = el banner de la portada.'),
  ('seo.version', '1', 'numero',
   'Versión de las imágenes al compartir. «Forzar la actualización» la sube.')
ON DUPLICATE KEY UPDATE
  `tipo`        = VALUES(`tipo`),
  `descripcion` = VALUES(`descripcion`);

-- ── Comprobación (a mano, después) ───────────────────────────────────────
--  Sin SELECT aquí: database/migrate.php ejecuta el archivo de un tirón y un
--  resultado sin leer le impide registrar la migración. Para mirarlo:
--
--    SELECT `clave`, `valor`, `tipo` FROM `ajustes` WHERE `clave` LIKE 'seo.%';
--
--  Deben salir las dos claves: seo.og_imagen_id vacía y seo.version a 1
--  (o el valor que ya tuvieran si alguien usó la pantalla antes).
