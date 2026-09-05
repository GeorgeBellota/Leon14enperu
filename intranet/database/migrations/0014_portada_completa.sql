-- ===========================================================================
--  0014 · LA PORTADA COMPLETA
-- ---------------------------------------------------------------------------
--  Todo lo que pedía el informe del cliente y todavía no estaba en la portada:
--
--    · El bloque «Abramos el corazón», con su botón.
--    · El bloque «El Papa León XIV llega al Perú», con su botón y la tira de
--      cuatro ciudades enlazadas a sus sedes.
--    · El itinerario, jornada a jornada, con aviso de programa referencial.
--    · Tierra de santos: los cinco retratos, cada uno a su biografía.
--    · Las noticias reales de la CEP, en lugar de ocho tarjetas de ejemplo.
--    · El bloque de la Conferencia Episcopal, con la presidencia y los
--      accesos a obispos, comisiones y jurisdicciones.
--    · Los destacados, los hitos, las formas de ayudar y los cuatro accesos,
--      que se veían en pantalla pero no se podían editar desde el panel.
--
--  ── Qué NO hace ──────────────────────────────────────────────────────────
--
--  Ni un DELETE, ni un TRUNCATE, ni un DROP. No toca `voluntarios`, ni
--  `usuarios`, ni `auditoria`, ni las tablas de ubigeo. No crea ni borra
--  columnas: sólo escribe filas en `secciones`, `bloques` y `ajustes`.
--
--  ── Es repetible ─────────────────────────────────────────────────────────
--
--  Las secciones entran con INSERT IGNORE contra su clave única; las piezas,
--  con una guarda NOT EXISTS por (sección, orden); los textos y los enlaces,
--  sólo donde siguen vacíos o sin cambiar. Ejecutarlo dos veces no duplica
--  nada ni pisa lo que un editor haya escrito.
--
--  ── Antes de ejecutarlo ──────────────────────────────────────────────────
--
--      mysqldump -u devop -p -P 3309 --single-transaction \
--        --default-character-set=utf8mb4 leon14website > ~/antes-0014.sql
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════
-- Apunta estos números. Al final se repiten.

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `paginas`)     AS paginas,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;
-- ═══ 1 · PLANTILLAS ═════════════════════════════════════════════════════
--
-- Seis secciones se registraron como «texto de lectura», que no admite piezas
-- repetibles. Con eso el panel enseñaba el titular pero no las tarjetas, y no
-- había forma de editar los destacados, los hitos ni las formas de ayudar.
--
-- La plantilla no se edita desde el panel, así que reescribirla no pisa
-- ninguna decisión de nadie.
-- todo-necesitas-antes: para poder editar las tres tarjetas de «por dónde empezar».
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`plantilla` = 'tarjetas_foto'
 WHERE p.`clave` = 'home' AND s.`clave` = 'todo-necesitas-antes';
-- falta-poco-encuentro: para poder editar la leyenda de la cuenta atrás.
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`plantilla` = 'contador'
 WHERE p.`clave` = 'home' AND s.`clave` = 'falta-poco-encuentro';
-- cronicas-visita: para poder editar los hitos del camino, con su estado.
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`plantilla` = 'hitos'
 WHERE p.`clave` = 'home' AND s.`clave` = 'cronicas-visita';
-- mas-destacado: para poder editar las noticias, con fecha y fuente.
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`plantilla` = 'noticias'
 WHERE p.`clave` = 'home' AND s.`clave` = 'mas-destacado';
-- pon-tus-dones: para poder editar las tres formas de ayudar.
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`plantilla` = 'tarjetas_foto'
 WHERE p.`clave` = 'home' AND s.`clave` = 'pon-tus-dones';
-- acompana-cada-momento: para poder editar los cuatro accesos con icono.
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`plantilla` = 'accesos'
 WHERE p.`clave` = 'home' AND s.`clave` = 'acompana-cada-momento';


-- ═══ 2 · LAS DOS SECCIONES QUE FALTABAN ═════════════════════════════════
--
-- El itinerario y el bloque de la Conferencia Episcopal. Los dos los pedía el
-- informe y ninguno existía en la portada.
--
-- INSERT IGNORE contra la clave única (pagina_id, clave): ejecutarlo dos veces
-- no crea dos secciones ni pisa lo que un editor haya cambiado.
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.`id`, 'itinerario', 'El recorrido del Santo Padre', 'jornadas',
       37, 1, 'Del 11 al 16 de noviembre de 2026', 'El recorrido del Santo Padre', '<p>El Papa León XIV recorrerá Lima, Chiclayo, Cusco y Pucallpa, llevando su mensaje de esperanza, encuentro y unidad a distintas realidades de la Iglesia y del pueblo peruano.</p>'
  FROM `paginas` p WHERE p.`clave` = 'home';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.`id`, 'cep-home', 'Conferencia Episcopal Peruana', 'personas',
       95, 1, 'La Iglesia en el Perú', 'Conferencia Episcopal Peruana', '<p>La Conferencia Episcopal Peruana es la asamblea de los obispos del Perú, que ejercen unidos determinadas funciones pastorales para promover el bien de la Iglesia y de los fieles del país.</p><p>La CEP articula el trabajo de las distintas jurisdicciones eclesiásticas y promueve iniciativas pastorales, sociales, educativas, evangelizadoras y de comunicación al servicio de la Iglesia en el Perú.</p>'
  FROM `paginas` p WHERE p.`clave` = 'home';


-- ═══ 3 · LOS BOTONES QUE FALTABAN ═══════════════════════════════════════
--
-- «Abramos el corazón» y «El Papa León XIV llega al Perú» tenían su texto
-- pero ningún destino, así que eran dos callejones sin salida en mitad de la
-- portada. Sólo se rellenan si están vacíos: si alguien ya puso otro botón
-- desde el panel, se respeta.
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`cta_texto` = 'Conoce el lema', s.`cta_url` = 'el-papa/'
 WHERE p.`clave` = 'home' AND s.`clave` = 'abramos-el-corazon'
   AND (s.`cta_texto` IS NULL OR s.`cta_texto` = '');
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`cta_texto` = 'Conoce la visita', s.`cta_url` = 'el-papa/'
 WHERE p.`clave` = 'home' AND s.`clave` = 'llega-al-peru'
   AND (s.`cta_texto` IS NULL OR s.`cta_texto` = '');

-- La leyenda de la cuenta atrás, debajo de los números.
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`texto` = 'Hasta el inicio del Viaje Apostólico · 11 de noviembre de 2026'
 WHERE p.`clave` = 'home' AND s.`clave` = 'falta-poco-encuentro'
   AND (s.`texto` IS NULL OR s.`texto` = '');


-- ═══ 4 · LAS PIEZAS ═════════════════════════════════════════════════════
--
-- Treinta y una piezas de contenido para las secciones de la portada.
--
-- ── Por qué NOT EXISTS y no INSERT IGNORE ───────────────────────────────
--
-- La clave única de `bloques` es (seccion_id, slug), y estas piezas no tienen
-- slug: no son páginas, son tarjetas. En SQL dos NULL no son iguales, así que
-- INSERT IGNORE las insertaría otra vez en cada pasada. La guarda va contra
-- (sección, orden), que sí identifica la pieza.
--
-- La guarda es POR PIEZA, no por sección. Si fuera por sección, en cuanto
-- entrara la primera el resto no entraría nunca —ése fue un fallo real de la
-- migración 0012—.

-- ── todo-necesitas-antes · 3 piezas ─────────────────────────────
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 10, 1, 'Peregrinos', 'Guía del peregrino', 'Qué llevar, cómo llegar y qué esperar de cada celebración. Se publicará junto con el programa oficial.',
       'Ver más', 'guia-del-peregrino/', NULL
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'todo-necesitas-antes'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 10);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 20, 1, 'Agenda', 'Conoce la agenda', 'Cómo y cuándo se anunciará el programa, y qué suele incluir un viaje apostólico.',
       'Ver más', 'agenda/', NULL
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'todo-necesitas-antes'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 20);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 30, 1, 'Parroquias', 'Materiales de pastoral', 'Subsidios de oración, catequesis y animación para preparar la visita en comunidad.',
       'Ver más', 'materiales/', NULL
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'todo-necesitas-antes'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 30);

-- ── cronicas-visita · 5 piezas ─────────────────────────────
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 10, 1, '5 de agosto de 2026', 'Anuncio de la Santa Sede', 'La Santa Sede anunció el viaje apostólico del Santo Padre al Perú, tercera etapa de su primera gira sudamericana.',
       NULL, NULL, '{"estado":"cumplido"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'cronicas-visita'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 10);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 20, 1, 'Sin fecha anunciada', 'Publicación del programa oficial', 'Lo publicará la Oficina de Prensa de la Santa Sede y lo difundirá la Conferencia Episcopal Peruana.',
       NULL, NULL, '{"estado":"pendiente"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'cronicas-visita'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 20);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 30, 1, '11 de noviembre de 2026', 'Llegada del Santo Padre al Perú', 'Comienzan los seis días del viaje apostólico en territorio peruano, hasta el 16 de noviembre.',
       NULL, NULL, '{"estado":"previsto"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'cronicas-visita'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 30);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 40, 1, 'Del 11 al 16 de noviembre de 2026', 'Seis días y cuatro sedes', 'Lima, Chiclayo, Cusco y Pucallpa. El reparto de actos lo fijará el programa oficial.',
       NULL, NULL, '{"estado":"previsto"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'cronicas-visita'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 40);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 50, 1, '16 de noviembre de 2026', 'Fin del viaje apostólico', 'Último día previsto en territorio peruano. La despedida se conocerá con el programa.',
       NULL, NULL, '{"estado":"previsto"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'cronicas-visita'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 50);

-- ── mas-destacado · 3 piezas ─────────────────────────────
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 10, 1, NULL, 'Episcopado peruano lanza el voluntariado oficial «Los amigos de León»', 'La convocatoria abre la inscripción para acompañar la Visita Apostólica del Papa León XIV al Perú en sus cuatro sedes.',
       'Leer más', 'noticias/episcopado-peruano-lanza-voluntariado-oficial-amigos-leon-visita-papa-leon-xiv/', '{"fecha":"5 de agosto de 2026","fuente":"Conferencia Episcopal Peruana"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'mas-destacado'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 10);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 20, 1, NULL, 'Episcopado peruano anuncia la oración oficial por la Visita del Papa León XIV', 'La oración acompañará la preparación espiritual de las comunidades en todo el país durante los meses previos.',
       'Leer más', 'noticias/episcopado-peruano-anuncia-la-oracion-oficial-por-la-visita-del-papa-leon-xiv/', '{"fecha":"Agosto de 2026","fuente":"Conferencia Episcopal Peruana"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'mas-destacado'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 20);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 30, 1, NULL, 'León XIV regresa al Perú: la Santa Sede confirma la Visita Apostólica', 'El Santo Padre estará en el país del 11 al 16 de noviembre, en la tercera etapa de su primera gira sudamericana.',
       'Leer más', 'noticias/leon-xiv-regresa-peru-santa-sede-confirma-visita-apostolica-11-16-noviembre/', '{"fecha":"Agosto de 2026","fuente":"Conferencia Episcopal Peruana"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'mas-destacado'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 30);

-- ── itinerario · 6 piezas ─────────────────────────────
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 10, 1, '11 de noviembre · Lima', 'Llegada al Perú y bienvenida oficial', 'La puerta de entrada de la Visita Apostólica y el primer encuentro del Santo Padre con el pueblo peruano.',
       'Conoce la sede de Lima', 'sedes/lima/', '{"actividades":["Llegada del Santo Padre","Ceremonia de bienvenida","Encuentro con autoridades","Primer mensaje al pueblo peruano"]}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'itinerario'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 10);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 20, 1, '12 de noviembre · Chiclayo', 'El reencuentro con una Iglesia que lo conoce', 'Chiclayo tiene una relación personal y pastoral muy fuerte con León XIV: fue obispo de esta diócesis entre 2015 y 2023.',
       'Conoce la sede de Chiclayo', 'sedes/chiclayo/', '{"actividades":["Encuentro con la comunidad de Chiclayo","Celebración eucarística","Encuentro con sacerdotes, religiosos y agentes pastorales","Momento de cercanía con el pueblo"]}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'itinerario'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 20);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 30, 1, '13 de noviembre · Pucallpa', 'Encuentro con la Amazonía', 'La Iglesia de la selva, sus comunidades y los pueblos originarios del Ucayali.',
       'Conoce la sede de Pucallpa', 'sedes/pucallpa/', '{"actividades":["Encuentro con comunidades amazónicas","Encuentro con representantes de pueblos originarios","Celebración o momento de oración","Mensaje sobre el cuidado de la casa común"]}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'itinerario'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 30);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 40, 1, '14 de noviembre · Cusco', 'La fe que nace del encuentro', 'Cusco desde su identidad religiosa, cultural y andina, y la Iglesia que sostiene la sierra sur.',
       'Conoce la sede de Cusco', 'sedes/cusco/', '{"actividades":["Celebración eucarística","Encuentro con la comunidad eclesial","Encuentro con jóvenes y familias","Momento de oración en un lugar significativo de la Iglesia local"]}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'itinerario'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 40);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 50, 1, '15 de noviembre · Lima', 'Un encuentro con todo el Perú', 'La jornada del gran encuentro nacional, con fieles llegados de todo el país.',
       'Conoce la sede de Lima', 'sedes/lima/', '{"actividades":["Gran celebración eucarística","Encuentro con familias, jóvenes y diversos sectores de la sociedad","Mensaje del Santo Padre al pueblo peruano","Momento de oración y acción de gracias"]}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'itinerario'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 50);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 60, 1, '16 de noviembre · Lima', 'Hasta pronto, Perú', 'El último día previsto en territorio peruano.',
       'Ver el estado de la agenda', 'agenda/', '{"actividades":["Encuentro de despedida","Mensaje final del Santo Padre","Ceremonia de despedida","Salida del Perú"]}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'itinerario'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 60);

-- ── cep-home · 3 piezas ─────────────────────────────
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 10, 1, 'Presidente', 'Mons. Carlos Enrique García Camader', 'Obispo de Lurín',
       'Conoce el directorio', 'cep/obispos/', NULL
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'cep-home'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 10);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 20, 1, 'Primer vicepresidente', 'Mons. Jorge Enrique Izaguirre Rafael, CSC', 'Obispo de Chosica',
       'Conoce el directorio', 'cep/obispos/', NULL
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'cep-home'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 20);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 30, 1, 'Segundo vicepresidente', 'Mons. Luis Alberto Barrera Pacheco, MCCJ', 'Obispo del Callao',
       'Conoce el directorio', 'cep/obispos/', NULL
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'cep-home'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 30);

-- ── pon-tus-dones · 3 piezas ─────────────────────────────
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 10, 1, '«Los amigos de León»', 'Como voluntario', 'Da igual tu edad, tu profesión o el tiempo que puedas dar: hay un lugar para cada talento.',
       'Conoce más', 'voluntariado/', NULL
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'pon-tus-dones'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 10);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 20, 1, 'Empresas e instituciones', 'Patrocinios', 'Las necesidades de organización son muchas: espacios de trabajo, señalética, indumentaria de voluntarios, pantallas y transporte.',
       'Conoce más', 'patrocinios/', NULL
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'pon-tus-dones'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 20);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 30, 1, 'Aporte económico', 'Con un donativo', 'Los donativos se destinan a los trabajos organizativos y pastorales de la visita, y se gestionan con responsabilidad y transparencia.',
       'Conoce más', 'donativo/', NULL
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'pon-tus-dones'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 30);

-- ── acompana-cada-momento · 4 piezas ─────────────────────────────
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 10, 1, NULL, 'En directo', 'La transmisión de cada acto, en cuanto haya programa. Aquí quedará el enlace.',
       NULL, 'en-directo/', '{"nota":"Próximamente"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'acompana-cada-momento'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 10);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 20, 1, NULL, 'Canal oficial', 'WhatsApp y redes sociales: una palabra del Papa cada día, hasta el 16 de noviembre.',
       NULL, 'contacto/#canales', '{"nota":"Próximamente"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'acompana-cada-momento'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 20);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 30, 1, NULL, 'Descargables', 'Fondos de pantalla, avatares, banners para parroquias y guía de oración en PDF.',
       NULL, 'materiales/', '{"nota":"Próximamente"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'acompana-cada-momento'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 30);
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.`id`, 40, 1, NULL, 'Comparte', 'La etiqueta oficial y un kit sencillo para parroquias, colegios y movimientos.',
       NULL, 'materiales/#comparte', '{"nota":"Próximamente"}'
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'acompana-cada-momento'
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) b
          WHERE b.`seccion_id` = s.`id` AND b.`orden` = 40);


-- ═══ 5 · LOS ENLACES DE LAS CUATRO SEDES ════════════════════════════════
--
-- Las cuatro tarjetas del recorrido apuntaban todas a «sedes/», el listado.
-- Las páginas propias —/sedes/lima/, /sedes/chiclayo/…— ya existían y
-- funcionaban desde la migración 0013; simplemente nadie las enlazaba.
--
-- Sólo se corrigen las que siguen apuntando al listado: si alguien ya cambió
-- un destino desde el panel, se respeta.
UPDATE `bloques` b
  JOIN `secciones` s ON s.`id` = b.`seccion_id`
  JOIN `paginas`   p ON p.`id` = s.`pagina_id`
   SET b.`enlace_url` = 'sedes/lima/'
 WHERE p.`clave` = 'home' AND s.`clave` = 'el-recorrido'
   AND b.`titulo` = 'Lima'
   AND (b.`enlace_url` IS NULL OR b.`enlace_url` IN ('', 'sedes/', '/sedes/'));
UPDATE `bloques` b
  JOIN `secciones` s ON s.`id` = b.`seccion_id`
  JOIN `paginas`   p ON p.`id` = s.`pagina_id`
   SET b.`enlace_url` = 'sedes/chiclayo/'
 WHERE p.`clave` = 'home' AND s.`clave` = 'el-recorrido'
   AND b.`titulo` = 'Chiclayo'
   AND (b.`enlace_url` IS NULL OR b.`enlace_url` IN ('', 'sedes/', '/sedes/'));
UPDATE `bloques` b
  JOIN `secciones` s ON s.`id` = b.`seccion_id`
  JOIN `paginas`   p ON p.`id` = s.`pagina_id`
   SET b.`enlace_url` = 'sedes/cusco/'
 WHERE p.`clave` = 'home' AND s.`clave` = 'el-recorrido'
   AND b.`titulo` = 'Cusco'
   AND (b.`enlace_url` IS NULL OR b.`enlace_url` IN ('', 'sedes/', '/sedes/'));
UPDATE `bloques` b
  JOIN `secciones` s ON s.`id` = b.`seccion_id`
  JOIN `paginas`   p ON p.`id` = s.`pagina_id`
   SET b.`enlace_url` = 'sedes/pucallpa/'
 WHERE p.`clave` = 'home' AND s.`clave` = 'el-recorrido'
   AND b.`titulo` = 'Pucallpa'
   AND (b.`enlace_url` IS NULL OR b.`enlace_url` IN ('', 'sedes/', '/sedes/'));


-- ═══ 6 · LA FASE DEL SITIO, AL AUTOMÁTICO ═══════════════════════════════
--
-- El ajuste `sitio.fase` valía «pre» a mano. Eso significaba que a las 00:00
-- del 11 de noviembre alguien tenía que entrar al panel a cambiarlo para que
-- la portada dejara de decir «faltan X días». La noche anterior a un viaje
-- apostólico no es el momento de depender de que alguien se acuerde.
--
-- Con «auto» lo decide el calendario a partir de las fechas del viaje. Los
-- valores pre / live / post siguen existiendo en el panel para poder ver cómo
-- quedará cada fase antes de que llegue.
--
-- Sólo se toca si sigue en «pre»: si alguien la ha forzado a propósito, se
-- respeta su decisión.

INSERT IGNORE INTO `ajustes` (`clave`, `valor`, `tipo`, `descripcion`) VALUES
  ('sitio.fase', 'auto', 'texto', 'Momento del sitio: auto, pre, live o post.');

UPDATE `ajustes` SET `valor` = 'auto'
 WHERE `clave` = 'sitio.fase' AND `valor` = 'pre';


-- ═══ 7 · LAS DOS SECCIONES HUÉRFANAS ════════════════════════════════════
--
-- «Los días del encuentro» y «Cuatro ciudades, un solo pueblo» eran los dos
-- titulares que la portada antigua usaba para el bloque de sedes. Ahora ese
-- espacio lo ocupan «El recorrido» y «El itinerario», que sí tienen piezas
-- editables, y estas dos se quedaron sin nada que pintar.
--
-- Se apagan en lugar de borrarse: una sección apagada desaparece de la web
-- —conContenido() filtra por activa = 1— pero su texto sigue en la base por
-- si alguien quiere recuperarlo. Borrar contenido que alguien escribió no es
-- tarea de una migración.

UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`activa` = 0
 WHERE p.`clave` = 'home'
   AND s.`clave` IN ('dias-encuentro', 'cuatro-ciudades-solo');


-- ═══ 8 · LOS TEXTOS QUE VIVÍAN EN EL CÓDIGO ═════════════════════════════
--
-- Al pasar la portada al gestor, estos párrafos se quedaron sin sitio: el
-- HTML dejó de llevarlos escritos y la base todavía no los tenía.
--
-- La carta de la Conferencia Episcopal es además la que decide si su sección
-- se pinta: sin texto, la banda roja entera desaparecía de la página.
--
-- ⚠ EL TEXTO DE LA CARTA ES UN BORRADOR DEL ESTUDIO, pendiente de revisión y
-- firma de la Conferencia Episcopal Peruana. No es un texto suyo.
--
-- Sólo se escriben donde el campo sigue vacío.
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`texto` = '<p>Durante los días de la visita, este espacio recogerá lo que vaya ocurriendo en cada sede. Mientras tanto, estos son los hitos ciertos del camino.</p>'
 WHERE p.`clave` = 'home' AND s.`clave` = 'cronicas-visita'
   AND (s.`texto` IS NULL OR s.`texto` = '');
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`texto` = '<p>No hace falta estar en la explanada para vivir estos días. Desde tu casa, tu parroquia o tu colegio también se puede acompañar.</p>'
 WHERE p.`clave` = 'home' AND s.`clave` = 'acompana-cada-momento'
   AND (s.`texto` IS NULL OR s.`texto` = '');
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`texto` = '<p>Escríbenos tu correo y te avisaremos de cada paso: la publicación del programa, los materiales de oración y las formas de participar desde tu parroquia.</p>'
 WHERE p.`clave` = 'home' AND s.`clave` = 'prepara-tu-corazon'
   AND (s.`texto` IS NULL OR s.`texto` = '');
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`texto` = '<p>Hay noticias que se reciben con el corazón antes que con la cabeza. La visita del Santo Padre al Perú es una de ellas. El Papa León XIV volverá en noviembre a esta tierra que recorrió durante cuarenta años como misionero, como formador y como obispo. No llega un huésped: vuelve alguien de casa.</p><p>Quienes lo conocieron en Chulucanas, en Trujillo o en Chiclayo saben de qué hablamos. Conoce nuestros caminos de tierra y nuestras parroquias de barrio. Conoce el nombre de nuestras devociones. Conoce lo que cuesta sostener la fe cuando la vida aprieta. Por eso su regreso no es un acontecimiento que ocurra en el Perú: es un acontecimiento del Perú.</p><p>Como pastores de esta Iglesia peregrina queremos pedirte algo sencillo: prepárate. No se trata sólo de conseguir un lugar en una explanada. Se trata de llegar a ese día con el corazón dispuesto. Vuelve a la oración en familia. Acércate al sacramento de la reconciliación. Reconcíliate con quien tengas pendiente. Visita al enfermo del que nadie se acuerda.</p><p>Queremos también que estos días sean obra de todos. Habrá miles de hermanos que necesitarán ser recibidos, orientados, acompañados y cuidados. Para eso nace «Los amigos de León». Si puedes dar tu tiempo, éste es el momento. Da igual tu edad o tu oficio: hay un lugar para cada talento y para cada corazón.</p><p>El lema del Santo Padre, tomado de san Agustín, dice «In Illo uno unum»: aunque los cristianos seamos muchos, en el único Cristo somos uno. Que esas palabras sean también el modo en que preparemos su llegada. Una sola Iglesia en la costa, en la sierra y en la selva. Una sola Iglesia en Lima, en Chiclayo, en Cusco y en Pucallpa.</p><p>Los detalles del programa se irán conociendo a su debido tiempo, y aquí los encontrarás en cuanto sean oficiales. Mientras llega ese momento, te pedimos una oración por el Santo Padre, por su salud y por los frutos de este viaje.</p><p>Que la Virgen María, Madre de la Iglesia, y los santos de nuestra tierra nos acompañen en la preparación.</p><p>Conferencia Episcopal Peruana</p>'
 WHERE p.`clave` = 'home' AND s.`clave` = 'iglesia-peru-te'
   AND (s.`texto` IS NULL OR s.`texto` = '');


-- ═══ 9 · EL RÓTULO DE LAS NOTICIAS ══════════════════════════════════════
--
-- Decía «Actualizado el 5 de agosto de 2026». Escrito así, envejece solo: en
-- noviembre seguirá diciendo agosto encima de noticias de noviembre, y quien
-- lo lea entenderá que el sitio está abandonado. Cada noticia ya lleva su
-- propia fecha, que es donde esa información tiene sentido.
--
-- Sólo se cambia si sigue diciendo exactamente lo de antes.

UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`rotulo` = 'Actualidad de la visita'
 WHERE p.`clave` = 'home' AND s.`clave` = 'mas-destacado'
   AND s.`rotulo` = 'Actualizado el 5 de agosto de 2026';

-- ═══ COMPROBACIÓN ═══════════════════════════════════════════════════════

SELECT 'DESPUES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `paginas`)     AS paginas,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

-- «voluntarios» y «paginas» deben ser IDÉNTICOS a los de antes.
-- «secciones» sube 2 y «bloques» sube 27.

SELECT s.`orden`, s.`clave`, s.`plantilla`,
       (SELECT COUNT(*) FROM `bloques` b WHERE b.`seccion_id` = s.`id`) AS piezas
  FROM `secciones` s JOIN `paginas` p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home'
 ORDER BY s.`orden`;
-- Deben salir 17 secciones. Ninguna con «texto_lectura» donde haya tarjetas.

SELECT b.`titulo`, b.`enlace_url`
  FROM `bloques` b
  JOIN `secciones` s ON s.`id` = b.`seccion_id`
  JOIN `paginas`   p ON p.`id` = s.`pagina_id`
 WHERE p.`clave` = 'home' AND s.`clave` = 'el-recorrido'
 ORDER BY b.`orden`;
-- Los cuatro destinos deben ser sedes/lima/, sedes/chiclayo/,
-- sedes/cusco/ y sedes/pucallpa/ — no «sedes/» a secas.

SELECT `clave`, `valor` FROM `ajustes`
 WHERE `clave` IN ('viaje.inicio', 'viaje.fin', 'sitio.fase');
-- La fase debe decir «auto». Las fechas se editan en el panel.


-- ═══ REGISTRO DE MIGRACIONES ════════════════════════════════════════════

INSERT IGNORE INTO `migraciones` (`archivo`, `aplicada_en`) VALUES
  ('0014_portada_completa.sql', NOW());
