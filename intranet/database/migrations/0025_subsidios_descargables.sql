-- ===========================================================================
--  0025 · LOS SUBSIDIOS, DESCARGABLES Y ADMINISTRABLES
-- ---------------------------------------------------------------------------
--  Es el punto 07.a de la solicitud del cliente: poner a disposición los cinco
--  materiales aprobados. Se suma el documento que los presenta, que el cliente
--  envió aparte y que va destacado por delante de los cinco.
--
--  ── Qué hace ──────────────────────────────────────────────────────────────
--
--   1. Crea la sección «subsidios» en la página /materiales/, con la plantilla
--      `descargas`, y la coloca la primera.
--   2. Inserta sus seis piezas: título, descripción y ruta del PDF.
--
--  ── Qué NO hace ───────────────────────────────────────────────────────────
--
--  Ni un DELETE, ni un TRUNCATE, ni un DROP, ni un ALTER. No toca
--  `voluntarios`, `usuarios`, `auditoria`, `medios`, `comunicados`, `ajustes`
--  ni el ubigeo. No cambia ninguna sección ni ninguna pieza existente: la
--  sección «Qué habrá disponible» que ya estaba en esa página se queda como
--  está, con su lista de lo que sigue en preparación.
--
--  ── Dónde están los archivos ──────────────────────────────────────────────
--
--  En `assets/docs/subsidios/`, que viaja con el código. Los nombres se
--  limpiaron al moverlos —venían con espacios y en mayúsculas, y un espacio en
--  una URL se convierte en «%20» en cuanto alguien copia el enlace—, pero el
--  archivo que se descarga NO se llama así: la vista le pone de nombre el
--  título de la pieza.
--
--  El PESO no se guarda. Lo mide la vista del archivo real en cada visita, así
--  que no puede quedarse desfasado el día que sustituyan un PDF por otro.
--
--  ── Cómo se cambia un subsidio desde la intranet ──────────────────────────
--
--  Documentos → subir el PDF nuevo → copiar su ruta → pegarla en el campo
--  «Archivo PDF» de la pieza, en Páginas → Materiales → Subsidios. La portada
--  se elige de la biblioteca de imágenes como cualquier otra.
--
--  Mientras una pieza no tenga portada elegida, la vista pinta la que viene en
--  el código: la primera página del PDF, ya renderizada. Se emparejan por el
--  NOMBRE DEL ARCHIVO, así que cambiar la ruta de un PDF cambia también su
--  portada de reserva, y reordenar las piezas no mueve ninguna imagen.
--
--  ── Es repetible ──────────────────────────────────────────────────────────
--
--  Los INSERT llevan guarda NOT EXISTS. Ejecutarlo dos veces no duplica la
--  sección ni ninguna pieza.
--
--  EL CÓDIGO SE DESPLIEGA ANTES QUE ESTO: la plantilla `descargas` tiene que
--  existir en Cms\Plantillas cuando el panel abra la sección nueva, o esa
--  pantalla no sabrá dibujarla.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

SELECT s.`clave`, s.`nombre`, s.`plantilla`, s.`orden`
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'materiales' ORDER BY s.`orden`;


-- ═══ 1 · LA SECCIÓN ═════════════════════════════════════════════════════
--
-- `orden` 5: por delante de «habra-disponible», que es la primera de la página
-- y está en 10. Lo que ya se puede descargar va antes que lo que todavía no.

INSERT INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'subsidios', 'Subsidios descargables', 'descargas', 5, 1,
       'Ya disponibles',
       'Subsidios para la visita',
       '<p>Cinco materiales aprobados por la Conferencia Episcopal Peruana para preparar la visita en la parroquia, el colegio y la familia. Descarga libre y uso gratuito.</p>'
  FROM `paginas` p
 WHERE p.clave = 'materiales'
   AND NOT EXISTS (
        SELECT 1 FROM (SELECT `pagina_id`, `clave` FROM `secciones`) AS ya
         WHERE ya.pagina_id = p.id AND ya.clave = 'subsidios'
       );


-- ═══ 2 · LAS SEIS PIEZAS ════════════════════════════════════════════════
--
-- La primera va marcada como destacada: es la que presenta el conjunto, y su
-- portada es apaisada mientras que las otras cinco son A4.

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 10, 1,
       'Subsidios para la visita del Papa León XIV al Perú',
       'El documento que presenta los cinco subsidios y cómo usarlos en la parroquia, el colegio y la familia.',
       '{"archivo":"assets/docs/subsidios/subsidios-papa-leon-xiv.pdf","destacado":"sí"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 10);

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 20, 1,
       'Papa León: cercano y peruano',
       'Quién es León XIV y qué lo une al Perú, para conocerlo antes de recibirlo.',
       '{"archivo":"assets/docs/subsidios/1-papa-leon-cercano-peruano.pdf"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 20);

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 30, 1,
       'Unidos en Cristo, sembradores de paz',
       'El lema del Santo Padre llevado a la vida de la comunidad.',
       '{"archivo":"assets/docs/subsidios/2-unidos-en-cristo.pdf"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 30);

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 40, 1,
       'Familias que cuidan la vida',
       'Material para trabajar en familia durante las semanas previas.',
       '{"archivo":"assets/docs/subsidios/3-familias-que-cuidan-la-vida.pdf"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 40);

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 50, 1,
       'Los jóvenes y la misión',
       'Para grupos juveniles, colegios y pastoral universitaria.',
       '{"archivo":"assets/docs/subsidios/4-los-jovenes-y-la-mision.pdf"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 50);

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 60, 1,
       'Pastoral social: la dignidad de toda persona',
       'La dimensión social de la fe, con propuestas para la comunidad.',
       '{"archivo":"assets/docs/subsidios/5-pastoral-social.pdf"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 60);


-- ═══ FOTO DE DESPUÉS ════════════════════════════════════════════════════

SELECT 'DESPUÉS' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

-- Seis piezas, la primera destacada, y todas con su ruta de PDF.
SELECT b.`orden`, b.`activo`, b.`titulo`,
       JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.archivo'))   AS archivo,
       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.destacado')), '—') AS destacado
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'materiales' AND s.clave = 'subsidios'
 ORDER BY b.`orden`;
