-- ===========================================================================
--  0013 · Páginas de detalle con dirección propia
-- ===========================================================================
--
--  Hasta ahora una sede, un santo o una noticia eran una PIEZA dentro de una
--  sección: se veían en su listado y nada más. El botón «Conoce Lima →»
--  apuntaba a «#» porque no había ningún sitio al que apuntar.
--
--  Esta migración les da dirección propia:
--
--      /sedes/lima/
--      /tierra-de-santos/santa-rosa-de-lima/
--      /cep/obispos/{slug}/
--      /cep/comisiones/{slug}/
--      /noticias/{slug}/
--      /prensa/{slug}/
--
--  ── La regla del slug ────────────────────────────────────────────────────
--
--  Se calcula del titular al CREAR la pieza y no se regenera al editarla.
--  Si alguien corrige una errata del titular y el slug cambiara, la dirección
--  que ya se compartió, la que indexó Google y la que enlazó la CEP dejarían
--  de existir a la vez. Se puede cambiar a mano desde el panel, con aviso.
--
--  ── Qué colecciones tienen detalle ───────────────────────────────────────
--
--  Se marca por sección, en su columna `datos`, con «detalle»: true. No todo
--  lo que se repite merece página. Las láminas del carrusel, las jornadas del
--  itinerario y las tarjetas de acceso de la portada NO la tienen: son
--  decoración o accesos, y darles dirección sólo crearía URLs que nadie
--  enlaza y que compiten en Google con la página buena.
--
--  ── Idempotente ──────────────────────────────────────────────────────────
--
--  ADD COLUMN IF NOT EXISTS, INSERT IGNORE contra claves únicas y los slugs
--  se rellenan sólo donde están vacíos. Ejecutarla dos veces no cambia nada.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ 1 · LA COLUMNA ═════════════════════════════════════════════════════
--
-- Única por sección: dos piezas de la misma lista no pueden compartir
-- dirección. Entre secciones distintas sí puede repetirse, y la resolución
-- se hace por página, que es como se pide desde el navegador.

ALTER TABLE `bloques`
  ADD COLUMN IF NOT EXISTS `slug` VARCHAR(190) NULL
    COMMENT 'parte legible de la URL; se fija al crear y no se regenera'
    AFTER `titulo`;

ALTER TABLE `bloques`
  ADD UNIQUE KEY IF NOT EXISTS `uq_bloques_slug` (`seccion_id`, `slug`);


-- ═══ 2 · LAS PÁGINAS NUEVAS ═════════════════════════════════════════════

INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`) VALUES
  ('tierra-de-santos', 'Tierra de santos', '/tierra-de-santos/', 0),
  ('obispos',          'Los obispos del Perú', '/cep/obispos/', 0),
  ('comisiones',       'Las comisiones episcopales', '/cep/comisiones/', 0),
  ('participa',        'Participa', '/participa/', 0),
  ('multimedia',       'Multimedia', '/multimedia/', 0);


-- ═══ 3 · SE MUEVEN LAS COLECCIONES A SU PÁGINA ══════════════════════════
--
-- Los obispos y las comisiones se sembraron dentro de /cep/. Ahora que
-- tienen página propia se trasladan enteras, con sus piezas: no se duplica
-- nada y no se pierde ninguna edición.

UPDATE `secciones` s
   JOIN `paginas` origen  ON origen.id = s.pagina_id AND origen.clave = 'cep'
   JOIN `paginas` destino ON destino.clave = 'obispos'
   SET s.pagina_id = destino.id, s.orden = 20
 WHERE s.clave = 'obispos-del-peru';

UPDATE `secciones` s
   JOIN `paginas` origen  ON origen.id = s.pagina_id AND origen.clave = 'cep'
   JOIN `paginas` destino ON destino.clave = 'comisiones'
   SET s.pagina_id = destino.id, s.orden = 20
 WHERE s.clave = 'comisiones-episcopales';

-- ═══ 4 · SECCIONES NUEVAS ═══════════════════════════════════════════════

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Cinco caminos de santidad', 'Tierra de santos', 'Cinco santos, cinco caminos de santidad, un mismo corazón. Sus testimonios acompañan a la Iglesia peruana y pueden iluminar el encuentro del pueblo con el Papa León XIV.', NULL
  FROM `paginas` p WHERE p.clave = 'tierra-de-santos';

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'los-cinco-santos', 'Los cinco santos', 'personas', 20, 1,
       'Siglos XVI y XVII', 'Cinco santos, un mismo corazón', '<p>En los siglos XVI y XVII, cinco figuras dejaron una huella profunda en la vida de la Iglesia. Sus vidas estuvieron marcadas por la oración, la misión, la caridad, la defensa de los más vulnerables y el encuentro con distintas realidades del Perú.</p>', '{"detalle":true}'
  FROM `paginas` p WHERE p.clave = 'tierra-de-santos';

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Directorio', 'Los obispos del Perú', 'Pastores al servicio de la Iglesia y del pueblo peruano. La Iglesia Católica en el Perú está presente en todo el territorio nacional a través de sus diferentes jurisdicciones eclesiásticas.', NULL
  FROM `paginas` p WHERE p.clave = 'obispos';

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       '13 comisiones al servicio de la Iglesia', 'Las comisiones episcopales', 'Las Comisiones Episcopales son organismos de servicio de la Conferencia Episcopal Peruana que estudian, apoyan y coordinan determinadas áreas de la pastoral de la Iglesia en el Perú.', NULL
  FROM `paginas` p WHERE p.clave = 'comisiones';

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'notas-de-prensa', 'Notas de prensa', 'noticias', 35, 1,
       'Comunicados', 'Notas de prensa', '<p>Los comunicados oficiales sobre la Visita Apostólica. Cada uno con su fecha y su texto completo.</p>', '{"detalle":true}'
  FROM `paginas` p WHERE p.clave = 'prensa';

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Únete', 'Participa', 'El himno oficial, la oración por la visita y los subsidios pastorales para prepararse como comunidad.', NULL
  FROM `paginas` p WHERE p.clave = 'participa';

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Banco de recursos', 'Multimedia', 'Fotografías y vídeos de la Visita Apostólica, disponibles para medios y para las comunidades.', NULL
  FROM `paginas` p WHERE p.clave = 'multimedia';


-- ═══ 5 · QUÉ COLECCIONES TIENEN PÁGINA DE DETALLE ═══════════════════════
--
-- Se marca en la columna `datos` de la sección. Las piezas de estas seis
-- reciben slug; las del resto, no.

UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.datos = JSON_SET(COALESCE(s.datos, '{}'), '$.detalle', TRUE)
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes';
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.datos = JSON_SET(COALESCE(s.datos, '{}'), '$.detalle', TRUE)
 WHERE p.clave = 'obispos' AND s.clave = 'obispos-del-peru';
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.datos = JSON_SET(COALESCE(s.datos, '{}'), '$.detalle', TRUE)
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales';
UPDATE `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
   SET s.datos = JSON_SET(COALESCE(s.datos, '{}'), '$.detalle', TRUE)
 WHERE p.clave = 'noticias' AND s.clave = 'ultimas-noticias';


-- ═══ 6 · LOS CINCO SANTOS ═══════════════════════════════════════════════

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `slug`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT s.id, 10, 1, 'santa-rosa-de-lima', 'Oración · Entrega · Servicio', 'Santa Rosa de Lima', '<p>Isabel Flores de Oliva, conocida como Santa Rosa de Lima, nació en Lima en 1586 y dedicó su vida a Dios desde una profunda experiencia de oración y entrega. Vivió como laica consagrada y perteneció a la Tercera Orden de Santo Domingo.</p><p>En medio de una vida sencilla, hizo del servicio a los pobres y enfermos una expresión concreta de su amor a Dios. Atendía a personas necesitadas y convirtió parte de su propia casa en un espacio de acogida para quienes sufrían.</p><p>Murió en Lima en 1617, a los 31 años. Fue canonizada por el papa Clemente X el 12 de abril de 1671, convirtiéndose en la primera santa de América. Es patrona del Perú, de América y de Filipinas.</p>',
       '{"anios":"1586 – 1617","resumen":"Una vida entregada a Dios y al servicio de los más necesitados."}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'tierra-de-santos' AND s.clave = 'los-cinco-santos'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.slug = 'santa-rosa-de-lima');
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `slug`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT s.id, 20, 1, 'san-martin-de-porres', 'Caridad · Humildad · Fraternidad', 'San Martín de Porres', '<p>Martín de Porres nació en Lima en 1579. Creció en una sociedad marcada por profundas diferencias sociales y raciales, y desde joven mostró una especial sensibilidad hacia los pobres y enfermos.</p><p>Ingresó al Convento del Santísimo Rosario de los dominicos de Lima, donde desarrolló diferentes labores de servicio antes de convertirse en fraile. Su servicio no hizo distinciones: atendía a personas de todas las condiciones y manifestó un especial amor por los animales y por toda la creación.</p><p>La tradición lo recuerda como el «Santo de la escoba», imagen que expresa su humildad. Fue canonizado por el papa Juan XXIII el 6 de mayo de 1962.</p>',
       '{"anios":"1579 – 1639","resumen":"Hizo del servicio humilde un camino de santidad."}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'tierra-de-santos' AND s.clave = 'los-cinco-santos'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.slug = 'san-martin-de-porres');
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `slug`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT s.id, 30, 1, 'san-juan-macias', 'Misericordia · Servicio · Solidaridad', 'San Juan Macías', '<p>Juan Macías nació en Ribera del Fresno, España, en 1585. Huérfano desde muy joven, trabajó como pastor antes de emigrar a América. Llegó al Perú y se estableció en Lima, donde ingresó a la Orden de Predicadores.</p><p>Durante más de dos décadas fue hermano portero del convento dominico de La Magdalena. Desde ese lugar desarrolló una intensa labor de ayuda a los pobres: su servicio comenzaba en la puerta del convento y se extendía mediante la distribución de alimentos y limosnas.</p><p>Su amistad con San Martín de Porres es otro elemento importante de la historia de la santidad limeña. Fue canonizado por Pablo VI el 28 de septiembre de 1975.</p>',
       '{"anios":"1585 – 1645","resumen":"Desde la sencillez, hizo de la misericordia una forma de vida."}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'tierra-de-santos' AND s.clave = 'los-cinco-santos'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.slug = 'san-juan-macias');
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `slug`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT s.id, 40, 1, 'san-francisco-solano', 'Misión · Encuentro · Evangelización', 'San Francisco Solano', '<p>Francisco Solano nació en Montilla, España, en 1549. Ingresó a la Orden Franciscana y fue ordenado sacerdote en 1576. Llegó a América en 1589 y desembarcó en Paita, Piura.</p><p>Desde allí emprendió un largo recorrido por territorios del actual Perú y otros países de Sudamérica. Aprendió lenguas indígenas para comunicarse con los pueblos a los que servía y utilizó también la música como instrumento de evangelización.</p><p>Su vida se vincula especialmente con la idea de una Iglesia en salida: caminar, encontrarse con las personas y llevar el Evangelio allí donde se encuentran. Murió en Lima el 14 de julio de 1610.</p>',
       '{"anios":"1549 – 1610","resumen":"Caminó grandes distancias para llevar el Evangelio al encuentro de los pueblos."}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'tierra-de-santos' AND s.clave = 'los-cinco-santos'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.slug = 'san-francisco-solano');
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `slug`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT s.id, 50, 1, 'santo-toribio-de-mogrovejo', 'Pastor · Misión · Defensa de los pueblos', 'Santo Toribio de Mogrovejo', '<p>Toribio Alfonso de Mogrovejo nació en Mayorga, España, en 1538. Estudió Derecho y fue profesor en la Universidad de Salamanca, hasta que fue elegido para asumir el Arzobispado de Lima. Llegó al Perú en 1581.</p><p>Concibió su ministerio como una misión que exigía salir al encuentro de las comunidades. Recorrió extensas regiones del territorio peruano, aprendió quechua y promovió la evangelización en lenguas nativas. Participó decisivamente en el III Concilio Limense.</p><p>Destacó por la defensa de los pueblos indígenas frente a abusos. Murió en Zaña en 1606 y fue canonizado en 1726: en 2026 se conmemoran <strong>300 años de su canonización</strong>, algo que el propio Santo Padre destacó en su encuentro con los obispos del Perú en enero de 2026.</p>',
       '{"anios":"1538 – 1606","resumen":"Un pastor en salida que recorrió el Perú para anunciar el Evangelio y acompañar a su pueblo."}'
  FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'tierra-de-santos' AND s.clave = 'los-cinco-santos'
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.seccion_id = s.id AND b.slug = 'santo-toribio-de-mogrovejo');


-- ═══ 7 · LAS CUATRO SEDES, CON SU TEXTO AMPLIADO ════════════════════════
--
-- Las piezas ya existen: se les pone el slug y el texto largo que llevará
-- su página propia. El resumen corto se guarda aparte, para el listado.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug  = 'lima',
       b.texto = '<p>Lima será la puerta de entrada de la Visita Apostólica del Papa León XIV al Perú y uno de los espacios de encuentro del Santo Padre con la Iglesia y el pueblo peruano.</p><p><em>Esta página quedará preparada para incorporar el programa oficial, los lugares de encuentro, la información para peregrinos, los accesos, las noticias y el material multimedia.</em></p>',
       b.datos = JSON_SET(COALESCE(b.datos, '{}'), '$.resumen', 'Lima será la puerta de entrada de la Visita Apostólica y uno de los espacios de encuentro del Santo Padre con la Iglesia y el pueblo peruano.'),
       b.enlace_url = 'tierra-de-santos/'
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND b.titulo = 'Lima' AND (b.slug IS NULL OR b.slug = '');

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug  = 'chiclayo',
       b.texto = '<p>Chiclayo ocupa un lugar especial en la historia pastoral del Papa León XIV. En esta tierra, el Santo Padre sirvió como obispo entre 2015 y 2023 y compartió durante años la vida y la fe de su pueblo.</p><p>Por ese vínculo, esta sede tendrá un módulo propio —«León XIV y Chiclayo»— con fotografías históricas, testimonios y momentos de su ministerio episcopal.</p><p><em>Pendiente de recibir el material gráfico de la diócesis.</em></p>',
       b.datos = JSON_SET(COALESCE(b.datos, '{}'), '$.resumen', 'Chiclayo ocupa un lugar especial en la historia pastoral del Papa León XIV.'),
       b.enlace_url = 'tierra-de-santos/'
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND b.titulo = 'Chiclayo' AND (b.slug IS NULL OR b.slug = '');

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug  = 'cusco',
       b.texto = '<p>Cusco se presenta desde su profunda identidad andina, cultural y religiosa, y no como una postal turística. La Iglesia andina más antigua del país acogerá al Santo Padre en una jornada centrada en la comunidad, los jóvenes y las familias.</p><p><em>Esta página incorporará las actividades del Papa, los lugares de encuentro, las indicaciones para peregrinos y los contenidos propios de la Arquidiócesis.</em></p>',
       b.datos = JSON_SET(COALESCE(b.datos, '{}'), '$.resumen', 'Cusco se presenta desde su profunda identidad andina, cultural y religiosa.'),
       b.enlace_url = 'tierra-de-santos/'
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND b.titulo = 'Cusco' AND (b.slug IS NULL OR b.slug = '');

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug  = 'pucallpa',
       b.texto = '<p>El encuentro con la Amazonía, sus comunidades y los pueblos originarios. Una jornada centrada en el cuidado de la casa común y en la realidad de la Iglesia amazónica.</p><p><em>Esta página incorporará las actividades oficiales, las comunidades participantes, los lugares de encuentro, los accesos y los contenidos multimedia.</em></p>',
       b.datos = JSON_SET(COALESCE(b.datos, '{}'), '$.resumen', 'El encuentro con la Amazonía, sus comunidades y los pueblos originarios.'),
       b.enlace_url = 'tierra-de-santos/'
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND b.titulo = 'Pucallpa' AND (b.slug IS NULL OR b.slug = '');


-- ═══ 8a · SLUGS EXACTOS DE LAS PIEZAS CONOCIDAS ═════════════════════════
--
-- Calculados con Cms\Slug, que descarta las palabras vacías cuando el
-- titular no cabe en 90 caracteres. Un titular de noticia largo daría, con
-- una conversión directa, una dirección de cien caracteres que no se puede
-- compartir sin que se parta.
--
-- Se aplican SOLO donde el slug está vacío: no se pisa ninguno editado.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'doctrina-de-la-fe'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Doctrina de la Fe' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'catequesis-y-pastoral-biblica'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Catequesis y Pastoral Bíblica' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'misiones-y-pastoral-indigena'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Misiones y Pastoral Indígena' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'educacion-cultura-y-bienes-culturales'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Educación, Cultura y Bienes Culturales' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'clero-seminarios-y-vocaciones'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Clero, Seminarios y Vocaciones' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'vida-consagrada-y-sociedades-de-vida-apostolica'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Vida Consagrada y Sociedades de Vida Apostólica' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'familia-infancia-y-vida'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Familia, Infancia y Vida' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'comunicacion'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Comunicación' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'jovenes-y-laicos'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Jóvenes y Laicos' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'liturgia'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Liturgia' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'proteccion-del-menor'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Protección del Menor' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'accion-social-ceas'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Acción Social · CEAS' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'caritas-del-peru'
 WHERE p.clave = 'comisiones' AND s.clave = 'comisiones-episcopales'
   AND b.titulo = 'Cáritas del Perú' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'episcopado-peruano-lanza-voluntariado-oficial-amigos-leon-visita-papa-leon-xiv'
 WHERE p.clave = 'noticias' AND s.clave = 'ultimas-noticias'
   AND b.titulo = 'Episcopado peruano lanza el voluntariado oficial «Los Amigos de León» para la visita del Papa León XIV' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'episcopado-peruano-anuncia-la-oracion-oficial-por-la-visita-del-papa-leon-xiv'
 WHERE p.clave = 'noticias' AND s.clave = 'ultimas-noticias'
   AND b.titulo = 'Episcopado peruano anuncia la oración oficial por la Visita del Papa León XIV' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'leon-xiv-regresa-peru-santa-sede-confirma-visita-apostolica-11-16-noviembre'
 WHERE p.clave = 'noticias' AND s.clave = 'ultimas-noticias'
   AND b.titulo = 'León XIV regresa al Perú: la Santa Sede confirma la Visita Apostólica del 11 al 16 de noviembre' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'mons-carlos-enrique-garcia-camader'
 WHERE p.clave = 'obispos' AND s.clave = 'obispos-del-peru'
   AND b.titulo = 'Mons. Carlos Enrique García Camader' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'mons-edinson-edgardo-farfan-cordova-osa'
 WHERE p.clave = 'obispos' AND s.clave = 'obispos-del-peru'
   AND b.titulo = 'Mons. Edinson Edgardo Farfán Córdova, OSA' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'mons-javier-augusto-del-rio-alba'
 WHERE p.clave = 'obispos' AND s.clave = 'obispos-del-peru'
   AND b.titulo = 'Mons. Javier Augusto Del Río Alba' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'lima'
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND b.titulo = 'Lima' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'chiclayo'
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND b.titulo = 'Chiclayo' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'cusco'
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND b.titulo = 'Cusco' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'pucallpa'
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND b.titulo = 'Pucallpa' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'santa-rosa-de-lima'
 WHERE p.clave = 'tierra-de-santos' AND s.clave = 'los-cinco-santos'
   AND b.titulo = 'Santa Rosa de Lima' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'san-martin-de-porres'
 WHERE p.clave = 'tierra-de-santos' AND s.clave = 'los-cinco-santos'
   AND b.titulo = 'San Martín de Porres' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'san-juan-macias'
 WHERE p.clave = 'tierra-de-santos' AND s.clave = 'los-cinco-santos'
   AND b.titulo = 'San Juan Macías' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'san-francisco-solano'
 WHERE p.clave = 'tierra-de-santos' AND s.clave = 'los-cinco-santos'
   AND b.titulo = 'San Francisco Solano' AND (b.slug IS NULL OR b.slug = '');
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.slug = 'santo-toribio-de-mogrovejo'
 WHERE p.clave = 'tierra-de-santos' AND s.clave = 'los-cinco-santos'
   AND b.titulo = 'Santo Toribio de Mogrovejo' AND (b.slug IS NULL OR b.slug = '');

-- ═══ 8 · SLUGS PARA LAS PIEZAS QUE YA EXISTEN ═══════════════════════════
--
-- Sólo donde está vacío: no se pisa ninguno puesto a mano.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   SET b.slug = LOWER(
         TRIM(BOTH '-' FROM REGEXP_REPLACE(
           REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
             b.titulo, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u'), 'ñ','n'), 'Á','a'),
           '[^a-zA-Z0-9]+', '-')))
 WHERE JSON_EXTRACT(s.datos, '$.detalle') = TRUE
   AND (b.slug IS NULL OR b.slug = '')
   AND b.titulo IS NOT NULL AND b.titulo <> '';


-- ═══ 9 · LOS ENLACES QUE APUNTABAN A «#» ════════════════════════════════

-- Las tarjetas de santos de la portada llevan ahora a su página.
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.enlace_url = CONCAT('tierra-de-santos/', LOWER(
         TRIM(BOTH '-' FROM REGEXP_REPLACE(
           REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
             b.titulo, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u'),
           '[^a-zA-Z0-9]+', '-'))), '/')
 WHERE p.clave = 'home' AND s.clave = 'tierra-de-santos' AND b.enlace_url = '#';

-- Y las tarjetas de sede del listado, a la página de cada sede.
UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas` p   ON p.id = s.pagina_id
   SET b.enlace_url = CONCAT('sedes/', b.slug, '/')
 WHERE p.clave = 'sedes' AND s.clave = 'las-cuatro-sedes'
   AND b.slug IS NOT NULL AND (b.enlace_url = '#' OR b.enlace_url = 'tierra-de-santos/');


-- ═══ COMPROBACIÓN ═══════════════════════════════════════════════════════

SELECT p.clave AS pagina, s.clave AS seccion,
       IF(JSON_EXTRACT(s.datos, '$.detalle') = TRUE, 'sí', '—') AS detalle,
       COUNT(b.id) AS piezas,
       SUM(b.slug IS NOT NULL AND b.slug <> '') AS con_slug
  FROM `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
  LEFT JOIN `bloques` b ON b.seccion_id = s.id
 GROUP BY s.id HAVING piezas > 0
 ORDER BY detalle DESC, piezas DESC;

SELECT COUNT(*) AS paginas_de_detalle
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
 WHERE JSON_EXTRACT(s.datos, '$.detalle') = TRUE
   AND b.slug IS NOT NULL AND b.slug <> '';