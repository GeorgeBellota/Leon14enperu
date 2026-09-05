-- ===========================================================================
--  0016 · UNA LÁMINA MÁS EN EL CARRUSEL DE PORTADA: LA COLECTA NACIONAL
-- ---------------------------------------------------------------------------
--  Qué hace: inserta UNA fila en `bloques`, la cuarta lámina del carrusel de
--            la portada. Nada más.
--
--  Qué NO hace: ni un DELETE, ni un TRUNCATE, ni un DROP, ni un ALTER. No toca
--            `voluntarios`, ni `usuarios`, ni `auditoria`, ni `medios`, ni el
--            ubigeo, ni ninguna sección o lámina que ya existiera. No cambia
--            ningún slug ni ninguna plantilla del panel: `carrusel_hero` ya
--            admite hasta seis láminas y esta es la cuarta.
--
--  ── De dónde salen los textos ─────────────────────────────────────────────
--
--  Del cartel de la Colecta Nacional que envió la Conferencia Episcopal.
--  Palabra por palabra, sin redactar nada:
--
--      Colecta Nacional
--      Súmate con tu donación
--      Con tu aporte ayudamos a preparar este gran encuentro de fe, unidad
--      y esperanza.
--
--  ── Por qué NO están aquí los números de cuenta ───────────────────────────
--
--  El cartel trae dos cuentas del BCP, en soles y en dólares, con sus CCI.
--  No entran en esta migración, por dos motivos:
--
--   1. Una lámina de carrusel que pasa cada siete segundos no es sitio para
--      veinte dígitos que alguien tiene que copiar sin equivocarse.
--   2. La página /donativo/ dice hoy, y lo dice a propósito: «Desconfía de
--      cualquier cuenta que circule antes de ese anuncio, aunque venga con el
--      escudo y con fotos del Santo Padre. Si no está publicada en esta página
--      o por la Conferencia Episcopal Peruana, no es oficial». Publicar unas
--      cuentas leídas de una imagen, mientras esa advertencia sigue en pie,
--      es exactamente lo que la advertencia pide no hacer.
--
--  Las cuentas se publican cuando la CEP las confirme por su canal, junto con
--  el nombre exacto del titular y el procedimiento para pedir constancia del
--  aporte, que es lo que la propia página promete. Eso será otra migración.
--
--  ── Es repetible ──────────────────────────────────────────────────────────
--
--  El INSERT lleva guarda NOT EXISTS por (sección del hero, rótulo). Ejecutarlo
--  dos veces no duplica la lámina ni pisa lo que un editor haya cambiado desde
--  el panel.
--
--  El código de views/portada.php se despliega ANTES que esto: lleva la misma
--  lámina en su lista de reserva, así que entre el despliegue y la migración la
--  portada no se queda sin nada.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques,
       (SELECT COUNT(*) FROM `bloques` b
          JOIN `secciones` s ON s.id = b.seccion_id
          JOIN `paginas` p   ON p.id = s.pagina_id
         WHERE p.clave = 'home' AND s.clave = 'hero') AS laminas_del_hero;


-- ═══ LA LÁMINA ══════════════════════════════════════════════════════════
--
-- La sección se busca por su clave y la de su página, no por un id escrito a
-- mano: el id 84 es el de esta instalación y no tiene por qué ser el mismo en
-- producción.
--
-- `orden` 40 la deja la última, detrás de «Cuatro ciudades» (30). El panel
-- puede subirla o bajarla con sus flechas cuando quieran.

INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`)
SELECT hero.id, 40, 1,
       'Colecta Nacional',
       'Súmate con tu donación',
       'Con tu aporte ayudamos a preparar este gran encuentro de fe, unidad y esperanza.',
       'Cómo donar',
       'donativo/'
  FROM (
        SELECT s.id
          FROM `secciones` s
          JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'home' AND s.clave = 'hero'
         LIMIT 1
       ) AS hero
 WHERE NOT EXISTS (
        SELECT 1
          FROM (SELECT `seccion_id`, `rotulo` FROM `bloques`) AS ya
         WHERE ya.seccion_id = hero.id
           AND ya.rotulo = 'Colecta Nacional'
       );


-- ═══ FOTO DE DESPUÉS ════════════════════════════════════════════════════

SELECT 'DESPUÉS' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques,
       (SELECT COUNT(*) FROM `bloques` b
          JOIN `secciones` s ON s.id = b.seccion_id
          JOIN `paginas` p   ON p.id = s.pagina_id
         WHERE p.clave = 'home' AND s.clave = 'hero') AS laminas_del_hero;
