-- ===========================================================================
--  0017 · LA SECCIÓN DE LA COLECTA, Y EL DISEÑO POR LÁMINA DEL CARRUSEL
-- ---------------------------------------------------------------------------
--  Qué hace, cuatro cosas y ninguna más:
--
--   1. Marca la lámina 2 del carrusel con diseño «fondo» (foto a sangre con el
--      texto encima). Escribe UNA clave dentro de su columna JSON `datos`.
--   2. Añade una quinta lámina, la de los cinco santos, también a sangre.
--   3. Apunta el botón de la lámina «Colecta Nacional» al ancla #colecta, para
--      que el clic baje al bloque de la colecta en vez de sacar de la portada.
--   4. Crea la sección «colecta» con sus dos cuentas.
--
--  Qué NO hace: ni un DELETE, ni un TRUNCATE, ni un DROP, ni un ALTER. No toca
--            `voluntarios`, `usuarios`, `auditoria`, `medios` ni el ubigeo. No
--            borra ni reescribe ningún texto de ninguna sección existente. Los
--            dos UPDATE son sobre dos láminas del carrusel y sólo sobre los dos
--            valores que se nombran.
--
--  ── LAS CUENTAS ───────────────────────────────────────────────────────────
--
--  Salen del comunicado oficial de la Colecta Nacional de la Conferencia
--  Episcopal Peruana. Se transcriben aquí una sola vez y desde el panel se
--  editan; que estén en una migración no las convierte en código.
--
--  Un dígito cambiado manda el dinero de alguien a otra cuenta. Antes de tocar
--  cualquiera de estos cuatro números, dos comprobaciones:
--
--   · El CCI peruano son 20 dígitos: 3 de banco + 3 de oficina + 12 de cuenta
--     + 2 de control. Los dos de aquí cuadran con su cuenta:
--         191-7397175-0-37  ->  002 191 007397175037 54
--         191-7397179-1-87  ->  002 191 007397179187 53
--     002 es el BCP y 191 la oficina, iguales en las dos. Si alguien cambia un
--     número y el CCI deja de cuadrar, es que uno de los dos está mal escrito.
--   · La página /donativo/ lleva un aviso que dice que sólo son oficiales las
--     cuentas publicadas en ella o por la Conferencia Episcopal. Estas lo son.
--     Esa página hay que dejarla al día para que las dos no se contradigan; se
--     hace desde el panel, no desde aquí.
--
--  ── Es repetible ──────────────────────────────────────────────────────────
--
--  Los INSERT llevan guarda NOT EXISTS y los UPDATE son idempotentes: escriben
--  el mismo valor si ya estaba. Ejecutarlo dos veces no duplica nada.
--
--  El código se despliega ANTES que esto: la plantilla «colecta» tiene que
--  existir en Cms\Plantillas cuando el panel abra la sección nueva.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;


-- ═══ 1 · LA LÁMINA 2, A SANGRE ══════════════════════════════════════════
--
-- JSON_SET sobre COALESCE: la columna está a NULL en las cuatro láminas y
-- JSON_SET sobre NULL devuelve NULL. Con el COALESCE se parte de un objeto
-- vacío y se conserva cualquier otra clave que hubiera.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`datos` = JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.diseno', 'fondo')
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`rotulo` = 'Los amigos de León'
    AND JSON_VALID(COALESCE(NULLIF(b.`datos`, ''), '{}'));


-- ═══ 2 · LA QUINTA LÁMINA: LOS CINCO SANTOS ═════════════════════════════
--
-- Los textos son los que ya están publicados en la sección «Tierra de santos»
-- y en el documento de la Conferencia Episcopal. No hay nada redactado aquí.

INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT hero.id, 50, 1,
       'Cinco caminos de santidad',
       'Cinco santos, un mismo corazón',
       'Cinco santos nos muestran distintos caminos para vivir la fe y servir a los demás.',
       'Conoce sus historias',
       'tierra-de-santos/',
       '{"diseno":"fondo"}'
  FROM (
        SELECT s.id
          FROM `secciones` s
          JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'home' AND s.clave = 'hero'
         LIMIT 1
       ) AS hero
 WHERE NOT EXISTS (
        SELECT 1 FROM (SELECT `seccion_id`, `rotulo` FROM `bloques`) AS ya
         WHERE ya.seccion_id = hero.id AND ya.rotulo = 'Cinco caminos de santidad'
       );


-- ═══ 3 · EL BOTÓN DE LA COLECTA APUNTA AL ANCLA ═════════════════════════

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`enlace_url` = '#colecta'
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`rotulo` = 'Colecta Nacional';


-- ═══ 4 · LA SECCIÓN DE LA COLECTA ═══════════════════════════════════════
--
-- `orden` 12: entre «todo-necesitas-antes» (10) y «abramos-el-corazon» (15),
-- que es donde queda en la portada. Ningún otro orden se toca.

INSERT INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`,
   `rotulo`, `titulo`, `subtitulo`, `texto`)
SELECT p.id, 'colecta', 'Colecta nacional', 'colecta', 12, 1,
       'Colecta Nacional',
       'Súmate con tu donación',
       'Para la visita del Papa León XIV al Perú.',
       '<p>Con tu aporte ayudamos a preparar este gran encuentro de fe, unidad y esperanza.</p>'
  FROM `paginas` p
 WHERE p.clave = 'home'
   AND NOT EXISTS (
        SELECT 1 FROM (SELECT `pagina_id`, `clave` FROM `secciones`) AS ya
         WHERE ya.pagina_id = p.id AND ya.clave = 'colecta'
       );


-- ═══ 5 · LAS DOS CUENTAS ════════════════════════════════════════════════

INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `datos`)
SELECT col.id, 10, 1,
       'Cuenta Soles BCP',
       'Conferencia Episcopal Peruana - visitapa',
       '{"numero":"191-7397175-0-37","cci":"00219100739717503754"}'
  FROM (
        SELECT s.id
          FROM `secciones` s
          JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'home' AND s.clave = 'colecta'
         LIMIT 1
       ) AS col
 WHERE NOT EXISTS (
        SELECT 1 FROM (SELECT `seccion_id`, `rotulo` FROM `bloques`) AS ya
         WHERE ya.seccion_id = col.id AND ya.rotulo = 'Cuenta Soles BCP'
       );

INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `datos`)
SELECT col.id, 20, 1,
       'Cuenta Dólares BCP',
       'Conferencia Episcopal Peruana - visitapa',
       '{"numero":"191-7397179-1-87","cci":"00219100739717918753"}'
  FROM (
        SELECT s.id
          FROM `secciones` s
          JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'home' AND s.clave = 'colecta'
         LIMIT 1
       ) AS col
 WHERE NOT EXISTS (
        SELECT 1 FROM (SELECT `seccion_id`, `rotulo` FROM `bloques`) AS ya
         WHERE ya.seccion_id = col.id AND ya.rotulo = 'Cuenta Dólares BCP'
       );


-- ═══ FOTO DE DESPUÉS ════════════════════════════════════════════════════

SELECT 'DESPUÉS' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques,
       (SELECT COUNT(*) FROM `bloques` b
          JOIN `secciones` s ON s.id = b.seccion_id
          JOIN `paginas` p   ON p.id = s.pagina_id
         WHERE p.clave = 'home' AND s.clave = 'hero') AS laminas_del_hero,
       (SELECT COUNT(*) FROM `bloques` b
          JOIN `secciones` s ON s.id = b.seccion_id
          JOIN `paginas` p   ON p.id = s.pagina_id
         WHERE p.clave = 'home' AND s.clave = 'colecta') AS cuentas;
