-- ===========================================================================
--  0012 · Las secciones del informe del cliente, con contenido simulado
-- ===========================================================================
--
--  Añade al gestor todo lo que pedía el último informe: el carrusel de
--  portada, el bloque «Abramos el corazón», las tarjetas de sede, el
--  itinerario por jornadas, los cinco santos, las noticias y la sección de
--  la Conferencia Episcopal con sus autoridades y sus trece comisiones.
--
--  ── Sobre el contenido ───────────────────────────────────────────────────
--
--  Donde el informe daba el texto, se usa el suyo. Donde no lo daba, se
--  propone uno siguiendo el patrón del sitio, para que el gestor no se vea
--  vacío y se pueda trabajar encima. Todo es editable y todo es sustituible.
--
--  Las secciones que dependen de material que aún no tenemos —el directorio
--  completo de obispos y el mapa de las 46 jurisdicciones— quedan creadas con
--  una nota dentro que dice qué falta.
--
--  ── Sobre la visibilidad ─────────────────────────────────────────────────
--
--  Al final, TODAS las páginas quedan ocultas salvo voluntariado. Oculta
--  significa 404 en el sitio público, no «fuera del menú»: quien tenga la
--  dirección tampoco la ve. Se publican una a una desde el panel, en
--  Contenidos → Páginas, cuando su contenido esté listo.
--
--  ── Idempotente ──────────────────────────────────────────────────────────
--
--  INSERT IGNORE contra la pareja única (pagina_id, clave). Ejecutarla dos
--  veces no duplica secciones ni pisa lo que un editor haya cambiado.
--
--  Los bloques SÍ se reemplazan por sección sembrada, porque no tienen clave
--  única. Sólo se tocan las secciones que crea esta migración, y sólo si
--  siguen teniendo exactamente los bloques que se sembraron.
-- ===========================================================================

SET NAMES utf8mb4;

-- ── home · Carrusel principal ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'hero', 'Carrusel principal', 'carrusel_hero', 5, 1,
       'Visita apostólica', 'Abramos el corazón', 'Visita Apostólica del Papa León XIV al Perú · 11 al 16 de noviembre de 2026', '{"lema":"Abramos el corazón","sublema":"Visita Apostólica del Papa León XIV al Perú"}'
  FROM `paginas` p WHERE p.clave = 'home';

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 10, 1, '11 – 16 de noviembre de 2026', 'Abramos el corazón',
       'Visita Apostólica del Papa León XIV al Perú.', 'Conoce la visita', 'agenda/',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 20, 1, 'Los amigos de León', 'Sirve en la visita',
       'Hay un lugar para cada talento y cada corazón.', 'Quiero ser voluntario', 'voluntariado/',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 30, 1, 'Cuatro ciudades', 'Lima · Chiclayo · Cusco · Pucallpa',
       'El recorrido del Santo Padre por la costa, la sierra y la selva.', 'Ver las sedes', 'sedes/',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 30);

-- ── home · Bloque «Abramos el corazón» ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'abramos-el-corazon', 'Bloque «Abramos el corazón»', 'destacado', 15, 1,
       'El lema', 'Abramos el corazón', '<p>Una invitación a recibir al Santo Padre, encontrarnos como Iglesia y renovar nuestra esperanza.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'home';

-- ── home · El Papa León XIV llega al Perú ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'llega-al-peru', 'El Papa León XIV llega al Perú', 'destacado', 25, 1,
       'La visita', 'El Papa León XIV llega al Perú', '<p>Del 11 al 16 de noviembre de 2026, el Santo Padre León XIV realizará su Visita Apostólica al Perú, recorriendo Lima, Chiclayo, Cusco y Pucallpa.</p><p>Su llegada será un tiempo de gracia, encuentro y esperanza para el pueblo peruano, que se prepara para recibirlo con los corazones abiertos.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'home';

-- ── home · El recorrido · tarjetas de sede ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'el-recorrido', 'El recorrido · tarjetas de sede', 'tarjetas_foto', 35, 1,
       'El recorrido', 'Lima · Chiclayo · Cusco · Pucallpa', '<p>Cuatro ciudades, un solo pueblo. Cada sede tendrá su programa, sus lugares de encuentro y su información para peregrinos.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'home';

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 10, 1, 'Puerta de entrada', 'Lima',
       'Lima será la puerta de entrada de la Visita Apostólica y uno de los espacios de encuentro del Santo Padre con la Iglesia y el pueblo peruano.', 'Conoce Lima', 'sedes/',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'el-recorrido'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 20, 1, 'Su diócesis', 'Chiclayo',
       'Chiclayo ocupa un lugar especial en la historia pastoral del Papa León XIV. En esta tierra sirvió como obispo y compartió durante años la vida y la fe de su pueblo.', 'Conoce Chiclayo', 'sedes/',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'el-recorrido'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 30, 1, 'La fe andina', 'Cusco',
       'Cusco se presenta desde su profunda identidad andina, cultural y religiosa.', 'Conoce Cusco', 'sedes/',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'el-recorrido'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 30);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 40, 1, 'La Amazonía', 'Pucallpa',
       'El encuentro con la Amazonía, sus comunidades y los pueblos originarios.', 'Conoce Pucallpa', 'sedes/',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'el-recorrido'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 40);

-- ── home · Tierra de santos ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'tierra-de-santos', 'Tierra de santos', 'tarjetas_foto', 45, 1,
       'Cinco caminos de santidad', 'Cinco santos, un mismo corazón', '<p>El Perú tiene una tradición de santidad que forma parte de la identidad espiritual de su pueblo. Cinco figuras dejaron una huella profunda en la vida de la Iglesia.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'home';

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 10, 1, 'Oración', 'Santa Rosa de Lima',
       'Una vida entregada a Dios y al servicio de los más necesitados.', 'Conoce su historia', '#',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'tierra-de-santos'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 20, 1, 'Caridad', 'San Martín de Porres',
       'Hizo del servicio humilde un camino de santidad.', 'Conoce su historia', '#',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'tierra-de-santos'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 30, 1, 'Misericordia', 'San Juan Macías',
       'Desde la sencillez, hizo de la misericordia una forma de vida.', 'Conoce su historia', '#',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'tierra-de-santos'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 30);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 40, 1, 'Misión', 'San Francisco Solano',
       'Caminó grandes distancias para llevar el Evangelio al encuentro de los pueblos.', 'Conoce su historia', '#',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'tierra-de-santos'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 40);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 50, 1, 'Pastor', 'Santo Toribio de Mogrovejo',
       'Un pastor en salida que recorrió el Perú para anunciar el Evangelio y acompañar a su pueblo.', 'Conoce su historia', '#',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'tierra-de-santos'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 50);

-- ── agenda · Itinerario del Santo Padre ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'itinerario', 'Itinerario del Santo Padre', 'jornadas', 25, 1,
       'El recorrido', 'El recorrido del Santo Padre', '<p><strong>Programa referencial.</strong> Las fechas, actividades y lugares serán reemplazados por el programa oficial cuando la Santa Sede lo apruebe y publique.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'agenda';

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 10, 1, '11 de noviembre', 'Lima · Llegada y bienvenida oficial',
       'Llegada al Perú y primer mensaje al pueblo peruano.', 'Conoce la sede de Lima', 'sedes/',
       '{"actividades":["Llegada del Santo Padre","Ceremonia de bienvenida","Encuentro con autoridades","Primer mensaje al pueblo peruano"]}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'agenda' AND s.clave = 'itinerario'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 20, 1, '12 de noviembre', 'Chiclayo · El reencuentro con una Iglesia que conoce',
       'Chiclayo tiene una relación personal y pastoral muy fuerte con León XIV: fue obispo de esta diócesis durante años.', 'Conoce la sede de Chiclayo', 'sedes/',
       '{"actividades":["Encuentro con la comunidad de Chiclayo","Celebración eucarística","Encuentro con sacerdotes, religiosos y agentes pastorales","Momento de cercanía con el pueblo"]}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'agenda' AND s.clave = 'itinerario'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 30, 1, '13 de noviembre', 'Pucallpa · Encuentro con la Amazonía',
       'Encuentro con las comunidades amazónicas y los pueblos originarios.', 'Conoce la sede de Pucallpa', 'sedes/',
       '{"actividades":["Encuentro con comunidades amazónicas","Encuentro con representantes de pueblos originarios","Celebración o momento de oración","Mensaje sobre el cuidado de la casa común"]}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'agenda' AND s.clave = 'itinerario'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 30);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 40, 1, '14 de noviembre', 'Cusco · La fe que nace del encuentro',
       'Cusco desde su identidad religiosa, cultural y andina.', 'Conoce la sede de Cusco', 'sedes/',
       '{"actividades":["Celebración eucarística","Encuentro con la comunidad eclesial","Encuentro con jóvenes y familias","Visita o momento de oración en un lugar significativo"]}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'agenda' AND s.clave = 'itinerario'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 40);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 50, 1, '15 de noviembre', 'Lima · Un encuentro con todo el Perú',
       'La gran jornada del encuentro nacional.', 'Conoce la sede de Lima', 'sedes/',
       '{"actividades":["Gran celebración eucarística","Encuentro con familias, jóvenes y diversos sectores","Mensaje del Santo Padre al pueblo peruano","Momento de oración y acción de gracias"]}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'agenda' AND s.clave = 'itinerario'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 50);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 60, 1, '16 de noviembre', 'Lima · Hasta pronto, Perú',
       'Despedida y salida del Perú.', 'Revive la visita', '#',
       '{"actividades":["Encuentro de despedida","Mensaje final del Santo Padre","Ceremonia de despedida","Salida del Perú"]}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'agenda' AND s.clave = 'itinerario'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 60);

-- ── sedes · Las cuatro sedes ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'las-cuatro-sedes', 'Las cuatro sedes', 'tarjetas_foto', 25, 1,
       'Las sedes', 'Cuatro ciudades, un solo pueblo', '<p>Cada sede tendrá su programa oficial, sus lugares de encuentro, información para peregrinos, accesos y material multimedia.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'sedes';

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 10, 1, 'Arquidiócesis de Lima', 'Lima',
       'La puerta de entrada de la visita y el espacio del gran encuentro nacional.', 'Conoce Lima', '#',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 20, 1, 'Diócesis de Chiclayo', 'Chiclayo',
       'Donde León XIV ejerció como obispo entre 2015 y 2023.', 'Conoce Chiclayo', '#',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 30, 1, 'Arquidiócesis del Cusco', 'Cusco',
       'La Iglesia andina más antigua del país.', 'Conoce Cusco', '#',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 30);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 40, 1, 'Vicariato de Pucallpa', 'Pucallpa',
       'El encuentro con la Amazonía y sus comunidades.', 'Conoce Pucallpa', '#',
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 40);

-- ── sedes · León XIV y Chiclayo ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'leon-xiv-y-chiclayo', 'León XIV y Chiclayo', 'texto_apartados', 35, 1,
       'Un vínculo especial', 'León XIV y Chiclayo', '<p>Esta sede merece un tratamiento propio: es el lugar donde el Santo Padre sirvió como obispo entre 2015 y 2023, y donde compartió durante años la vida y la fe de su pueblo.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'sedes';

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 10, 1, NULL, 'Fotografías históricas',
       'Imágenes de su ministerio episcopal en la diócesis. Pendiente de recibir el material.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'sedes' AND s.clave = 'leon-xiv-y-chiclayo'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 20, 1, NULL, 'Testimonios',
       'Recuerdos de la comunidad que lo acompañó durante esos años.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'sedes' AND s.clave = 'leon-xiv-y-chiclayo'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 30, 1, NULL, 'Momentos de su ministerio',
       'Los hitos de su servicio pastoral en Chiclayo.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'sedes' AND s.clave = 'leon-xiv-y-chiclayo'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 30);

-- ── noticias · Últimas noticias ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'ultimas-noticias', 'Últimas noticias', 'noticias', 25, 1,
       'Actualidad', 'Lo más reciente', '<p>Las informaciones oficiales sobre la Visita Apostólica, publicadas por la Conferencia Episcopal Peruana.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'noticias';

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 10, 1, NULL, 'Episcopado peruano lanza el voluntariado oficial «Los Amigos de León» para la visita del Papa León XIV',
       'La Conferencia Episcopal Peruana abre la convocatoria de voluntarios para acompañar el viaje apostólico.', 'Ver noticia en la CEP', '#',
       '{"fecha":"Agosto de 2026","fuente":"Conferencia Episcopal Peruana"}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'noticias' AND s.clave = 'ultimas-noticias'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 20, 1, NULL, 'Episcopado peruano anuncia la oración oficial por la Visita del Papa León XIV',
       'La oración oficial acompañará la preparación espiritual de la visita en todas las diócesis del país.', 'Ver noticia en la CEP', '#',
       '{"fecha":"Agosto de 2026","fuente":"Conferencia Episcopal Peruana"}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'noticias' AND s.clave = 'ultimas-noticias'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 30, 1, NULL, 'León XIV regresa al Perú: la Santa Sede confirma la Visita Apostólica del 11 al 16 de noviembre',
       'La Oficina de Prensa de la Santa Sede confirmó las fechas del viaje apostólico al Perú.', 'Ver noticia en la CEP', '#',
       '{"fecha":"5 de agosto de 2026","fuente":"Conferencia Episcopal Peruana"}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'noticias' AND s.clave = 'ultimas-noticias'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 30);

-- ── cep · Sobre la Conferencia Episcopal ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'sobre-la-cep', 'Sobre la Conferencia Episcopal', 'destacado', 15, 1,
       'Institucional', 'Conferencia Episcopal Peruana', '<p>La Conferencia Episcopal Peruana es la asamblea de los obispos del Perú, que ejercen unidos determinadas funciones pastorales para promover el bien de la Iglesia y de los fieles del país.</p><p>La CEP articula el trabajo de las distintas jurisdicciones eclesiásticas y promueve diversas iniciativas pastorales, sociales, educativas, evangelizadoras y de comunicación.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'cep';

-- ── cep · Presidencia y autoridades ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'presidencia', 'Presidencia y autoridades', 'personas', 25, 1,
       'Periodo 2025 – 2028', 'Presidencia de la CEP', '<p>Las autoridades de la Conferencia Episcopal Peruana para el periodo 2025–2028.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'cep';

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 10, 1, 'Presidente', 'Mons. Carlos Enrique García Camader',
       'Obispo de Lurín. Presidente de la Conferencia Episcopal Peruana.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'presidencia'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 20, 1, 'Primer vicepresidente', 'Mons. Jorge Enrique Izaguirre Rafael, CSC',
       'Obispo de Chosica.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'presidencia'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 30, 1, 'Segundo vicepresidente', 'Mons. Luis Alberto Barrera Pacheco, MCCJ',
       'Obispo del Callao.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'presidencia'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 30);

-- ── cep · Los obispos del Perú ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'obispos-del-peru', 'Los obispos del Perú', 'personas', 35, 1,
       'Directorio', 'Pastores al servicio de la Iglesia y del pueblo peruano', '<p>La Iglesia Católica en el Perú está presente en todo el territorio nacional a través de sus diferentes jurisdicciones eclesiásticas. Sus arzobispos, obispos y vicarios apostólicos acompañan a las comunidades y desarrollan su misión pastoral en las distintas realidades del país.</p><p><strong>Pendiente:</strong> el directorio completo se cargará con la información oficial de la CEP.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'cep'
   -- No recrearla en «cep» si ya vive en su página propia: en una
   -- segunda pasada, el INSERT IGNORE no la vería allí y la duplicaría.
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT * FROM `secciones`) s2
           JOIN (SELECT * FROM `paginas`) p2 ON p2.id = s2.pagina_id
          WHERE p2.clave = 'obispos' AND s2.clave = 'obispos-del-peru');

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 10, 1, 'Obispo de Lurín', 'Mons. Carlos Enrique García Camader',
       'Presidente de la Conferencia Episcopal Peruana.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'obispos-del-peru'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 20, 1, 'Obispo de Chiclayo', 'Mons. Edinson Edgardo Farfán Córdova, OSA',
       'Preside la Comisión de Comunicación.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'obispos-del-peru'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 30, 1, 'Arzobispo de Arequipa', 'Mons. Javier Augusto Del Río Alba',
       'Preside la Comisión Episcopal de Doctrina de la Fe.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'obispos-del-peru'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 30);

-- ── cep · Mapa de la Iglesia en el Perú ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'mapa-iglesia-peru', 'Mapa de la Iglesia en el Perú', 'destacado', 45, 1,
       'Una Iglesia presente en todo el territorio', 'La Iglesia Católica en el Perú', '<p>El Perú cuenta con <strong>46 jurisdicciones eclesiásticas</strong>: 7 arquidiócesis, 21 diócesis, 10 prelaturas y 8 vicariatos apostólicos.</p><p><strong>Pendiente:</strong> el mapa interactivo requiere la cartografía oficial de la CEP. Las jurisdicciones eclesiásticas no coinciden con los departamentos, así que no puede derivarse de un mapa político.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'cep';

-- ── cep · Las 13 comisiones episcopales ──
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'comisiones-episcopales', 'Las 13 comisiones episcopales', 'personas', 55, 1,
       '13 comisiones al servicio de la Iglesia', 'Comisiones Episcopales', '<p>Las Comisiones Episcopales son organismos de servicio de la Conferencia Episcopal Peruana que estudian, apoyan y coordinan determinadas áreas de la pastoral de la Iglesia en el Perú.</p>', NULL
  FROM `paginas` p WHERE p.clave = 'cep'
   -- No recrearla en «cep» si ya vive en su página propia: en una
   -- segunda pasada, el INSERT IGNORE no la vería allí y la duplicaría.
   AND NOT EXISTS (
         SELECT 1 FROM (SELECT * FROM `secciones`) s2
           JOIN (SELECT * FROM `paginas`) p2 ON p2.id = s2.pagina_id
          WHERE p2.clave = 'comisiones' AND s2.clave = 'comisiones-episcopales');

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 10, 1, 'Mons. Javier Augusto Del Río Alba · Arzobispo de Arequipa', 'Doctrina de la Fe',
       'Doctrina, enseñanza de la fe y reflexión teológica.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 20, 1, 'Mons. Gerardo Anton Zerdin, OFM · Vicario Apostólico de San Ramón', 'Catequesis y Pastoral Bíblica',
       'Catequesis, formación en la fe y animación bíblica.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 30, 1, 'Mons. Miguel Ángel Cadenas Cardo, OSA · Vicario Apostólico de Iquitos', 'Misiones y Pastoral Indígena',
       'Evangelización, misión y acompañamiento de los pueblos indígenas y comunidades amazónicas.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 30);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 40, 1, 'Mons. Ricardo García García · Obispo Prelado de Yauyos', 'Educación, Cultura y Bienes Culturales',
       'Educación, cultura y conservación del patrimonio cultural de la Iglesia.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 40);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 50, 1, 'Mons. Marco Antonio Cortez Lara · Obispo de Tacna y Moquegua', 'Clero, Seminarios y Vocaciones',
       'Formación de sacerdotes, seminarios y promoción de las vocaciones.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 50);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 60, 1, 'Mons. Juan José Salaverry Villarreal, OP · Obispo Auxiliar de Lima', 'Vida Consagrada y Sociedades de Vida Apostólica',
       'Acompañamiento y promoción de la vida consagrada.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 60);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 70, 1, 'Mons. Guillermo Elías Millares · Obispo Auxiliar de Lima', 'Familia, Infancia y Vida',
       'Familia, infancia, defensa y promoción de la vida.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 70);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 80, 1, 'Mons. Edinson Edgardo Farfán Córdova, OSA · Obispo de Chiclayo', 'Comunicación',
       'Comunicación institucional, medios y presencia de la Iglesia en el entorno digital.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 80);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 90, 1, 'Mons. Luis Alberto Huamán Camayo, OMI · Arzobispo de Huancayo', 'Jóvenes y Laicos',
       'Participación de los laicos y acompañamiento pastoral de los jóvenes.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 90);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 100, 1, 'Mons. Pedro Bustamante López · Obispo de Huánuco', 'Liturgia',
       'Vida litúrgica, celebraciones y formación litúrgica.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 100);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 110, 1, 'Mons. Pascual Benjamín Rivera Montoya · Obispo Prelado de Huamachuco', 'Protección del Menor',
       'Prevención, protección de menores y promoción de ambientes seguros.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 110);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 120, 1, 'Mons. Víctor Emiliano Villegas Suclupe · Obispo Prelado de Chota', 'Acción Social · CEAS',
       'Acción social, dignidad humana, justicia y acompañamiento de poblaciones vulnerables.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 120);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT s.id, 130, 1, 'Mons. Guillermo Monzón · Obispo Auxiliar de Lima', 'Cáritas del Perú',
       'Acción caritativa y social, atención a poblaciones vulnerables y desarrollo humano integral.', NULL, NULL,
       NULL
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'cep' AND s.clave = 'comisiones-episcopales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.orden = 130);


-- ═══ VISIBILIDAD ════════════════════════════════════════════════════════
--
-- Todo oculto salvo voluntariado, que es lo único abierto al público.
-- Se publica desde el panel cuando cada página esté lista.

UPDATE `paginas` SET `activa` = 0 WHERE `clave` <> 'voluntariado';
UPDATE `paginas` SET `activa` = 1 WHERE `clave` =  'voluntariado';


-- ═══ COMPROBACIÓN ═══════════════════════════════════════════════════════
SELECT p.clave,
       IF(p.activa, 'publicada', 'oculta')      AS estado,
       COUNT(DISTINCT s.id)                     AS secciones,
       COUNT(b.id)                              AS piezas
  FROM `paginas` p
  LEFT JOIN `secciones` s ON s.pagina_id = p.id
  LEFT JOIN `bloques`   b ON b.seccion_id = s.id
 GROUP BY p.id
 ORDER BY p.activa DESC, p.nombre;