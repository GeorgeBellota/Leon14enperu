-- ===========================================================================
--  0020 · LO QUE QUEDABA ESCRITO EN LA VISTA DE /donativo/ PASA AL PANEL
-- ---------------------------------------------------------------------------
--  Después de la 0019 la página seguía teniendo dos cosas que sólo se podían
--  cambiar desplegando código:
--
--    1. Las tres tarjetas de «A qué se destinan» —Acogida, Voluntariado y
--       Pastoral— con su icono, su título y su explicación.
--    2. El encabezado «Las cuentas oficiales» que va sobre la lista de cuentas.
--
--  Con esto las dos se editan desde el panel y no queda nada de esa página
--  fuera de la administración.
--
--  Qué hace:
--    · Cambia la plantilla de la sección «destinan» de `texto_lectura` a
--      `destinos_aporte`, que es NUEVA y admite piezas repetibles.
--    · Inserta sus tres piezas con exactamente los textos que ya se veían.
--    · Escribe la clave `titulo_cuentas` en los datos de la sección «colecta»
--      de la portada.
--
--  Qué NO hace: ni un DELETE, ni un TRUNCATE, ni un DROP, ni un ALTER. No toca
--            `voluntarios`, `usuarios`, `auditoria`, `medios` ni el ubigeo. No
--            cambia ningún texto de los que ya se veían: los copia tal cual.
--
--  ── Ojo con el orden ───────────────────────────────────────────────────────
--
--  EL CÓDIGO SE DESPLIEGA ANTES QUE ESTO. La plantilla `destinos_aporte` tiene
--  que existir en Cms\Plantillas cuando esta migración cambie la sección; si no,
--  el panel abre esa sección y no sabe dibujarla.
--
--  ── Por qué la plantilla nueva y no ampliar `texto_lectura` ────────────────
--
--  Porque `texto_lectura` la usan otras dieciséis secciones del sitio. Darle
--  piezas repetibles se las daría a todas. Una plantilla nueva no toca nada de
--  lo que ya funciona.
--
--  Es repetible: el UPDATE escribe el mismo valor si ya estaba y los INSERT
--  llevan guarda NOT EXISTS.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;


-- ═══ 1 · LA SECCIÓN «destinan» PASA A LA PLANTILLA NUEVA ════════════════

UPDATE `secciones` s
   JOIN `paginas` p ON p.id = s.pagina_id
    SET s.`plantilla` = 'destinos_aporte'
  WHERE p.clave = 'donativo'
    AND s.clave = 'destinan';


-- ═══ 2 · SUS TRES PIEZAS ════════════════════════════════════════════════
--
-- Los textos son EXACTAMENTE los que estaban escritos en la vista. No se
-- reescribe ninguno: sólo cambian de sitio, de código a base.

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `icono`, `titulo`, `texto`)
SELECT d.id, 10, 1, 'i-acogida', 'Acogida',
       'Agua, señalética, puntos de información y atención básica en los recintos.'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'donativo' AND s.clave = 'destinan' LIMIT 1) AS d
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `titulo` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = d.id AND ya.titulo = 'Acogida');

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `icono`, `titulo`, `texto`)
SELECT d.id, 20, 1, 'i-manos', 'Voluntariado',
       'Formación, indumentaria, credenciales y traslado de los voluntarios.'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'donativo' AND s.clave = 'destinan' LIMIT 1) AS d
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `titulo` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = d.id AND ya.titulo = 'Voluntariado');

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `icono`, `titulo`, `texto`)
SELECT d.id, 30, 1, 'i-ofrenda', 'Pastoral',
       'Materiales de oración y catequesis, y su distribución a parroquias de todo el país.'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'donativo' AND s.clave = 'destinan' LIMIT 1) AS d
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `titulo` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = d.id AND ya.titulo = 'Pastoral');


-- ═══ 3 · EL ENCABEZADO DE LAS CUENTAS ═══════════════════════════════════
--
-- Vive en los datos de la sección «colecta» de la PORTADA, que es donde están
-- las cuentas. /donativo/ lo lee de ahí, igual que lee los números.

UPDATE `secciones` s
   JOIN `paginas` p ON p.id = s.pagina_id
    SET s.`datos` = JSON_SET(COALESCE(NULLIF(s.`datos`, ''), '{}'), '$.titulo_cuentas', 'Las cuentas oficiales')
  WHERE p.clave = 'home'
    AND s.clave = 'colecta'
    AND JSON_VALID(COALESCE(NULLIF(s.`datos`, ''), '{}'));


-- ═══ FOTO DE DESPUÉS ════════════════════════════════════════════════════

SELECT 'DESPUÉS' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

SELECT s.`clave`, s.`plantilla`, COUNT(b.id) AS piezas
  FROM `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
  LEFT JOIN `bloques` b ON b.seccion_id = s.id
 WHERE p.clave = 'donativo'
 GROUP BY s.id, s.`clave`, s.`plantilla`
 ORDER BY s.`orden`;
