-- ===========================================================================
--  0041 · NOTICIAS — SE RETIRAN DEL GESTOR DE PÁGINAS
-- ---------------------------------------------------------------------------
--  La 0040 copió las noticias a su tabla y dejó los bloques donde estaban, a
--  propósito: hasta que la página nueva no estuviera subida y comprobada, la
--  web tenía que seguir teniendo de dónde pintar.
--
--  Ya lo está. Y dejarlos ahora sería peor que borrarlos: alguien entraría en
--  Páginas → Noticias, editaría «Últimas noticias», guardaría, y no vería
--  cambiar nada en la web, porque la página lee de la tabla. Un panel que
--  acepta cambios que no hacen nada es una trampa.
--
--  ── Qué se va ────────────────────────────────────────────────────────────
--
--    ultimas-noticias   lo sustituye Contenidos → Noticias
--    videos             lo sustituye Contenidos → Noticias → Vídeos
--
--  Y dos que ya estaban apagadas y vacías desde el rediseño, y que tampoco
--  pinta nada la vista nueva:
--
--    santa-sede-anuncia       0 bloques, apagada
--    enterate-cuanto-haya     0 bloques, apagada
--
--  ── Qué se queda ─────────────────────────────────────────────────────────
--
--  «cabecera». La portada y los textos del héroe se siguen editando en
--  Páginas → Noticias, como en todas las demás páginas.
--
--  ── Las fotografías y los vídeos no se pierden ───────────────────────────
--
--  Las fotografías están en `medios` y las noticias nuevas ya apuntan a
--  ellas: la 0040 copió el `imagen_id` de cada bloque.
--
--  El VÍDEO que había —«Subsidios Pastorales para la Visita»— no se copia
--  aquí: se vuelve a añadir en un segundo desde Contenidos → Noticias →
--  Vídeos pegando su enlace, y así entra con el título y la portada que trae
--  YouTube, que es justo lo que pedía el cliente y lo que ese bloque no
--  tenía. El enlace es:
--
--      https://www.youtube.com/watch?v=eoLVnA036Lw
--
--  ── Qué NO toca ──────────────────────────────────────────────────────────
--
--  Sólo esas cuatro secciones de la página «noticias». Ni `medios`, ni la
--  tabla `noticias`, ni ninguna otra página.
--
--  Es repetible: si ya se corrió, no encuentra nada y no hace nada.
-- ===========================================================================

SET NAMES utf8mb4;

SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'noticias' LIMIT 1);

-- Los bloques y el historial primero: borrar la sección antes dejaría filas
-- apuntando a una sección que ya no existe.
DELETE b FROM `bloques` b
  JOIN `secciones` s ON s.`id` = b.`seccion_id`
 WHERE @pag IS NOT NULL
   AND s.`pagina_id` = @pag
   AND s.`clave` IN ('ultimas-noticias', 'videos', 'santa-sede-anuncia', 'enterate-cuanto-haya');

DELETE v FROM `secciones_versiones` v
  JOIN `secciones` s ON s.`id` = v.`seccion_id`
 WHERE @pag IS NOT NULL
   AND s.`pagina_id` = @pag
   AND s.`clave` IN ('ultimas-noticias', 'videos', 'santa-sede-anuncia', 'enterate-cuanto-haya');

DELETE FROM `secciones`
 WHERE @pag IS NOT NULL
   AND `pagina_id` = @pag
   AND `clave` IN ('ultimas-noticias', 'videos', 'santa-sede-anuncia', 'enterate-cuanto-haya');

-- ── Comprobación (a mano, después) ───────────────────────────────────────
--  Sin SELECT aquí: migrate.php no registra la migración si queda un
--  resultado sin leer.
--
--    SELECT s.`clave` FROM `secciones` s
--      JOIN `paginas` p ON p.`id` = s.`pagina_id`
--     WHERE p.`clave` = 'noticias';
--
--  Debe quedar sólo «cabecera».
--
--    SELECT COUNT(*) FROM `noticias`;
--
--  Deben seguir las cuatro.
