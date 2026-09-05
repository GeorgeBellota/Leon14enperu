-- ===========================================================================
--  0011 · El contenido de las páginas, administrable
-- ===========================================================================
--
--  Registra las 18 páginas del sitio y sus secciones en el CMS, con el texto
--  que hoy tienen escrito ya cargado. Así el panel muestra el contenido real
--  listo para editar, y no una pantalla de campos vacíos que alguien tendría
--  que rellenar copiando de la web.
--
--  ── Por qué no rompe nada ────────────────────────────────────────────────
--
--  Las vistas leen con texto de reserva: Sitio::campo(..., 'lo de ahora').
--  Mientras una sección no exista en la base, o su campo esté vacío, la
--  página pinta exactamente lo que pintaba antes. Se puede aplicar esta
--  migración sin desplegar ninguna vista, y no cambia nada visible.
--
--  ── Idempotente ──────────────────────────────────────────────────────────
--
--  INSERT IGNORE contra las claves únicas (paginas.clave y la pareja
--  pagina_id + secciones.clave). Ejecutarla dos veces no duplica ni pisa lo
--  que un editor haya cambiado ya.
--
--  voluntariado NO se toca: ya está en el CMS con sus seis secciones.
-- ===========================================================================

SET NAMES utf8mb4;


-- ── Agenda ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('agenda', 'Agenda', '/agenda/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Agenda', 'Los días del encuentro', 'El Papa León XIV estará en el Perú del 11 al 16 de noviembre de 2026. El programa detallado todavía no es público: aquí está todo lo que sí se sabe, y nada de lo que no.'
  FROM `paginas` p WHERE p.clave = 'agenda';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'sabe-hoy', 'Qué se sabe hoy', 'texto_lectura', 20, 1,
       'Actualizado el 13 de agosto de 2026', 'Qué se sabe hoy', NULL
  FROM `paginas` p WHERE p.clave = 'agenda';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cuatro-ventanas', 'Las cuatro ventanas', 'texto_lectura', 30, 1,
       'Cuatro jurisdicciones', 'Las cuatro ventanas', NULL
  FROM `paginas` p WHERE p.clave = 'agenda';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'suele-incluir-viaje', '¿Qué suele incluir un viaje apostólico?', 'texto_apartados', 40, 1,
       'No es el programa peruano', '¿Qué suele incluir un viaje apostólico?', NULL
  FROM `paginas` p WHERE p.clave = 'agenda';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'como-cuando-anunciara', 'Cómo y cuándo se anunciará el programa', 'texto_lectura', 50, 1,
       'Oficina de Prensa de la Santa Sede', 'Cómo y cuándo se anunciará el programa', 'El programa detallado de un viaje apostólico lo publica la Oficina de Prensa de la Santa Sede. Es la fuente primaria: hasta que un acto aparece ahí, no existe oficialmente. Suele difundirse algunas semanas antes del viaje, en un documento con cada acto, su hora local y su sede. En el Perú, la Conferencia Episcopal Peruana difunde y adapta esa información: condiciones de acceso, transporte, puntos de encuentro y las indicaciones de cada diócesis. Las cuatro jurisdicciones implicadas —Lima, Chiclayo, Cusco y Pucallpa— publicarán además sus propias instrucciones. Cuando eso ocurra, esta página ca'
  FROM `paginas` p WHERE p.clave = 'agenda';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'avisame-cuando-publique', 'Avísame cuando se publique la agenda', 'texto_lectura', 60, 1,
       'Aviso', 'Avísame cuando se publique la agenda', NULL
  FROM `paginas` p WHERE p.clave = 'agenda';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'mas-pregunta', 'Lo que más se pregunta', 'texto_lectura', 70, 1,
       'Cinco dudas', 'Lo que más se pregunta', NULL
  FROM `paginas` p WHERE p.clave = 'agenda';


-- ── Aviso legal ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('aviso-legal', 'Aviso legal', '/aviso-legal/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Legal', 'Aviso legal', 'Titularidad del sitio, condiciones de uso y propiedad intelectual de los contenidos.'
  FROM `paginas` p WHERE p.clave = 'aviso-legal';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'titular-sitio', 'Titular del sitio', 'texto_lectura', 20, 1,
       NULL, 'Titular del sitio', 'Los datos de titularidad de este apartado los debe completar la organización antes de la publicación del sitio. Aparecen entre corchetes. Titular del sitio Denominación: [RAZÓN SOCIAL POR CONFIRMAR]. Domicilio: [DOMICILIO POR CONFIRMAR]. Registro: [DATOS REGISTRALES POR CONFIRMAR]. Correo de contacto: [CORREO POR CONFIRMAR]. Objeto Este sitio tiene una finalidad exclusivamente informativa y pastoral: dar a conocer el viaje apostólico de Su Santidad el Papa León XIV al Perú, previsto del 11 al 16 de noviembre de 2026, y canalizar la participación de los fieles como voluntarios o colaboradores. '
  FROM `paginas` p WHERE p.clave = 'aviso-legal';


-- ── La Iglesia en el Perú ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('cep', 'La Iglesia en el Perú', '/cep/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Quién recibe la visita', 'La Iglesia en el Perú', 'La Conferencia Episcopal Peruana y las cuatro jurisdicciones eclesiásticas que acogen el viaje apostólico.'
  FROM `paginas` p WHERE p.clave = 'cep';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'quien-organiza-visita', 'Quién organiza la visita', 'texto_lectura', 20, 1,
       'La Conferencia', 'Quién organiza la visita', 'La Conferencia Episcopal Peruana reúne a los obispos de las diócesis, arquidiócesis, prelaturas y vicariatos apostólicos del país. Es el organismo que coordina la preparación del viaje en el Perú, difunde y adapta lo que publica la Santa Sede, y articula el trabajo de las cuatro jurisdicciones que reciben al Santo Padre. León XIV la conoce por dentro. Fue su segundo vicepresidente desde marzo de 2018, miembro de su Consejo Económico y presidente de la Comisión Episcopal de Cultura y Educación. En 2023 la propia Conferencia le concedió la Medalla de Oro de Santo Toribio de Mogrovejo. Los datos '
  FROM `paginas` p WHERE p.clave = 'cep';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'quien-acoge-cada', 'Quién acoge cada sede', 'texto_lectura', 30, 1,
       'Cuatro jurisdicciones', 'Quién acoge cada sede', NULL
  FROM `paginas` p WHERE p.clave = 'cep';


-- ── Contacto ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('contacto', 'Contacto', '/contacto/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Organización del viaje', 'Contacto', 'Cuéntanos qué necesitas y te respondemos. Si tu consulta es sobre el programa, quizá ya esté resuelta en las preguntas frecuentes.'
  FROM `paginas` p WHERE p.clave = 'contacto';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'escribenos', 'Escríbenos', 'texto_lectura', 20, 1,
       'Formulario', 'Escríbenos', NULL
  FROM `paginas` p WHERE p.clave = 'contacto';


-- ── Cookies ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('cookies', 'Cookies', '/cookies/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Legal', 'Cookies', 'La respuesta corta: este sitio no usa cookies. La larga explica qué guarda tu navegador y por qué.'
  FROM `paginas` p WHERE p.clave = 'cookies';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'este-sitio-instala', 'Este sitio no instala cookies', 'texto_lectura', 20, 1,
       NULL, 'Este sitio no instala cookies', 'Este sitio no instala cookies Ni propias ni de terceros. No hay analítica, no hay píxeles de seguimiento, no hay publicidad y no hay perfilado. Por eso no verás un banner de consentimiento: no habría nada que consentir. Lo que sí guarda tu navegador El sitio usa el almacenamiento del navegador para dos cosas, ambas técnicas y ambas dentro de tu propio equipo. Este almacenamiento no se envía al servidor en cada petición, a diferencia de una cookie. 1. El borrador del formulario de voluntariado. Clave l14-inscripcion-borrador, en localStorage. Guarda lo que hayas escrito en el formulario para qu'
  FROM `paginas` p WHERE p.clave = 'cookies';


-- ── Donativo ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('donativo', 'Donativo', '/donativo/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Cómo ayudar', 'Con un donativo', 'Los donativos se destinan a los trabajos organizativos y pastorales de la visita, y se gestionan con responsabilidad y transparencia.'
  FROM `paginas` p WHERE p.clave = 'donativo';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'todavia-canal-donativos', 'Todavía no hay canal de donativos abierto', 'texto_lectura', 20, 1,
       'Estado', 'Todavía no hay canal de donativos abierto', 'Este sitio no tiene pasarela de pago y no la tendrá hasta que la organización defina la titularidad de la cuenta y el tratamiento fiscal de los aportes. Habilitar cobros antes de eso sería irresponsable. Cuando el canal esté abierto, aparecerá aquí con la cuenta oficial, el nombre exacto del titular y el procedimiento para pedir constancia del aporte. Desconfía de cualquier cuenta que circule antes de ese anuncio, aunque venga con el escudo y con fotos del Santo Padre. Si no está publicada en esta página o por la Conferencia Episcopal Peruana, no es oficial.'
  FROM `paginas` p WHERE p.clave = 'donativo';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'destinan', 'A qué se destinan', 'texto_lectura', 30, 1,
       'Destino', 'A qué se destinan', NULL
  FROM `paginas` p WHERE p.clave = 'donativo';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'avisame-cuando-abra', 'Avísame cuando se abra el canal', 'texto_lectura', 40, 1,
       'Aviso', 'Avísame cuando se abra el canal', NULL
  FROM `paginas` p WHERE p.clave = 'donativo';


-- ── El Papa ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('el-papa', 'El Papa', '/el-papa/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       '267.º sucesor de Pedro', 'Cuarenta años de Perú', 'Robert Francis Prevost llegó a Chulucanas en 1985 con treinta años. Salió del Perú en 2023 siendo ciudadano peruano y obispo de Chiclayo.'
  FROM `paginas` p WHERE p.clave = 'el-papa';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'quien-leon-xiv', 'Quién es León XIV', 'texto_lectura', 20, 1,
       'En breve', 'Quién es León XIV', NULL
  FROM `paginas` p WHERE p.clave = 'el-papa';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'raices-vocacion-agustiniana', 'Raíces y vocación agustiniana', 'texto_lectura', 30, 1,
       '1955 – 1984', 'Raíces y vocación agustiniana', 'Nació en Chicago el 14 de septiembre de 1955, hijo de Louis Marius Prevost, de ascendencia francesa e italiana, y de Mildred Martínez, de ascendencia española. Tiene dos hermanos, Louis Martín y John Joseph. Estudió primero en el Seminario Menor de los Padres Agustinos y después en la Universidad de Villanova, en Pensilvania, donde se licenció en Matemáticas y cursó Filosofía en 1977. El 1 de septiembre de ese mismo año ingresó en el noviciado de la Orden de San Agustín en St. Louis, en la provincia de Nuestra Señora del Buen Consejo de Chicago. Hizo su primera profesión el 2 de septiembre de '
  FROM `paginas` p WHERE p.clave = 'el-papa';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'misionero-formador-peru', 'Misionero y formador en el Perú', 'texto_lectura', 40, 1,
       '1985 – 1999', 'Misionero y formador en el Perú', 'Se licenció en 1984 y al año siguiente, mientras preparaba su tesis doctoral, fue enviado a la misión agustiniana de Chulucanas, en Piura. Allí empezaron sus cuarenta años de Perú. En 1987 defendió su tesis sobre el papel del prior local en la Orden de San Agustín y fue nombrado director de Vocaciones y director de Misiones de su provincia agustiniana en Illinois. Al año siguiente volvió, esta vez a Trujillo, como director del proyecto de formación común para los aspirantes agustinos de los vicariatos de Chulucanas, Iquitos y Apurímac: el desierto, la selva y los Andes en una misma casa de for'
  FROM `paginas` p WHERE p.clave = 'el-papa';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'prior-general-obispo', 'De prior general a obispo de Chiclayo', 'texto_lectura', 50, 1,
       '1999 – 2023', 'De prior general a obispo de Chiclayo', 'En 1999 fue elegido prior provincial de la provincia agustiniana de Chicago y, dos años y medio después, sus hermanos lo eligieron prior general de la Orden. Fue confirmado en 2007 para un segundo mandato. El 3 de noviembre de 2014 el Papa Francisco lo nombró administrador apostólico de la diócesis de Chiclayo y lo elevó a la dignidad episcopal como obispo titular de Sufar. Entró en la diócesis el 7 de noviembre y fue ordenado obispo el 12 de diciembre, festividad de Nuestra Señora de Guadalupe, en la catedral de Santa María. El 26 de septiembre de 2015 fue nombrado obispo de Chiclayo. En marz'
  FROM `paginas` p WHERE p.clave = 'el-papa';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'chiclayo-pontificado', 'De Chiclayo al pontificado', 'texto_lectura', 60, 1,
       '2023 – hoy', 'De Chiclayo al pontificado', 'El 30 de enero de 2023 el Papa Francisco lo llamó a Roma como prefecto del Dicasterio para los Obispos y presidente de la Pontificia Comisión para América Latina, elevándolo al rango de arzobispo. En el consistorio del 30 de septiembre de ese año fue creado cardenal, con la diaconía de Santa Mónica, de la que tomó posesión en enero de 2024. El 6 de febrero de 2025 fue promovido al orden de los cardenales obispos, con el título suburbicario de Albano. Durante la última hospitalización del Papa Francisco presidió, el 3 de marzo, el rosario por la salud del Pontífice en la plaza de San Pedro. El '
  FROM `paginas` p WHERE p.clave = 'el-papa';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'escudo-lema', 'El escudo y el lema', 'texto_lectura', 70, 1,
       'Heráldica', 'El escudo y el lema', 'El escudo está dividido diagonalmente en dos sectores. La parte superior tiene fondo azul y presenta un lirio blanco. La inferior, sobre fondo claro, lleva la imagen que recuerda a la Orden de San Agustín: un libro cerrado sobre el que descansa un corazón traspasado por una flecha. Esa imagen evoca la conversión de san Agustín, que él mismo explicó con las palabras «Vulnerasti cor meum verbo tuo»: has traspasado mi corazón con tu Palabra. El lema, «In Illo uno unum», procede de un sermón de san Agustín, la Exposición del Salmo 127, y significa que aunque los cristianos seamos muchos, en el úni'
  FROM `paginas` p WHERE p.clave = 'el-papa';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'magisterio-hasta-hoy', 'Su magisterio hasta hoy', 'texto_lectura', 80, 1,
       'Documentos', 'Su magisterio hasta hoy', NULL
  FROM `paginas` p WHERE p.clave = 'el-papa';


-- ── En directo ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('en-directo', 'En directo', '/en-directo/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Del 11 al 16 de noviembre', 'En directo', 'No hace falta estar en la explanada. Aquí estarán los enlaces de cada transmisión en cuanto existan.'
  FROM `paginas` p WHERE p.clave = 'en-directo';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'todavia-enlaces', 'Todavía no hay enlaces', 'texto_lectura', 20, 1,
       'Estado', 'Todavía no hay enlaces', 'Los viajes apostólicos se transmiten habitualmente por los medios de comunicación de la Santa Sede y por las emisoras y los canales de la Iglesia en el país que recibe la visita. Es razonable esperar que así sea también aquí, pero ningún enlace concreto está confirmado. En cuanto se publique el programa, esta página tendrá una fila por acto, con su hora en horario de Lima y el enlace directo a la transmisión. Mientras tanto, no publicamos nada que no podamos sostener.'
  FROM `paginas` p WHERE p.clave = 'en-directo';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'tres-maneras-seguirlo', 'Tres maneras de seguirlo', 'texto_lectura', 30, 1,
       'Mientras tanto', 'Tres maneras de seguirlo', NULL
  FROM `paginas` p WHERE p.clave = 'en-directo';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'avisame-cuando-haya', 'Avísame cuando haya transmisión', 'texto_lectura', 40, 1,
       'Aviso', 'Avísame cuando haya transmisión', NULL
  FROM `paginas` p WHERE p.clave = 'en-directo';


-- ── Guía del peregrino ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('guia-del-peregrino', 'Guía del peregrino', '/guia-del-peregrino/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Para quien va a ir', 'Guía del peregrino', 'Lo que sí puedes preparar desde ya, y lo que habrá que esperar. Ninguna de estas indicaciones sustituye a las de tu diócesis.'
  FROM `paginas` p WHERE p.clave = 'guia-del-peregrino';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'esta-confirmado', 'Qué está confirmado y qué no', 'texto_lectura', 20, 1,
       'Antes de nada', 'Qué está confirmado y qué no', 'Esta guía no inventa logística. Lo que encontrarás aquí son las cosas que puedes preparar sin saber todavía dónde ni a qué hora será cada acto: el equipaje, la salud, los niños, la accesibilidad y, sobre todo, la preparación interior. En cuanto la Oficina de Prensa de la Santa Sede publique el programa y la Conferencia Episcopal Peruana difunda las indicaciones de acceso, esta página se completará con los recintos, los accesos por puerta, los horarios de apertura y el transporte.'
  FROM `paginas` p WHERE p.clave = 'guia-del-peregrino';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'llevar', 'Qué llevar', 'texto_apartados', 30, 1,
       'Equipaje', 'Qué llevar', NULL
  FROM `paginas` p WHERE p.clave = 'guia-del-peregrino';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'preparar-corazon', 'Preparar el corazón', 'texto_lectura', 40, 1,
       'Lo importante', 'Preparar el corazón', 'No se peregrina solo con los pies. Las semanas previas son parte de la visita, y lo que ocurra en ellas se nota el día del encuentro. Vuelve a la oración en familia. Un rato corto y a la misma hora vale más que un propósito largo que no se sostiene. Acércate al sacramento de la reconciliación. Tu parroquia tendrá horarios reforzados conforme se acerquen las fechas. Reconcíliate con quien tengas pendiente. Es lo más difícil de la lista y lo único que no se puede improvisar el día antes. Visita a quien está solo. Un enfermo, un vecino mayor, alguien de la comunidad que ya no puede salir. Muchos '
  FROM `paginas` p WHERE p.clave = 'guia-del-peregrino';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'mayores-ninos-accesibilidad', 'Mayores, niños y accesibilidad', 'texto_lectura', 50, 1,
       'Cuidados', 'Mayores, niños y accesibilidad', NULL
  FROM `paginas` p WHERE p.clave = 'guia-del-peregrino';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'como-llegar-tu', 'Cómo llegar a tu sede', 'texto_lectura', 60, 1,
       'Transporte', 'Cómo llegar a tu sede', 'Las indicaciones de acceso, transporte y puntos de encuentro dependen de los recintos, y los recintos todavía no se han anunciado. En cuanto se publiquen, aquí encontrarás para cada sede el punto de acceso, los cortes de tránsito previstos y las rutas recomendadas. Mientras tanto, lo más útil que puedes hacer es preguntar en tu parroquia. Muchas organizan traslado en grupo, y ese suele ser el modo más ordenado de llegar y de volver.'
  FROM `paginas` p WHERE p.clave = 'guia-del-peregrino';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'te-avisamos-cuando', 'Te avisamos cuando esté la guía completa', 'texto_lectura', 70, 1,
       'Aviso', 'Te avisamos cuando esté la guía completa', NULL
  FROM `paginas` p WHERE p.clave = 'guia-del-peregrino';


-- ── Materiales de pastoral ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('materiales', 'Materiales de pastoral', '/materiales/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Para parroquias y colegios', 'Materiales de pastoral', 'Lo que necesitas para preparar la visita en comunidad. Se irá publicando conforme la Conferencia Episcopal lo apruebe.'
  FROM `paginas` p WHERE p.clave = 'materiales';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'habra-disponible', 'Qué habrá disponible', 'texto_lectura', 20, 1,
       'En preparación', 'Qué habrá disponible', NULL
  FROM `paginas` p WHERE p.clave = 'materiales';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'corre-voz', 'Corre la voz', 'texto_lectura', 30, 1,
       'Comparte', 'Corre la voz', 'La mejor difusión de esta visita no la va a hacer una campaña: la van a hacer las parroquias, los colegios y los movimientos contándolo a la gente que tienen al lado. Cuando estén listos, aquí encontrarás un kit sencillo —piezas para redes, un cartel imprimible y un texto breve para leer en las misas dominicales— pensado para que cualquier comunidad pueda usarlo sin saber de diseño. La etiqueta oficial del viaje se anunciará junto con los materiales. Hasta entonces, lo más útil es enlazar directamente a este sitio.'
  FROM `paginas` p WHERE p.clave = 'materiales';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'avisame-cuando-esten', 'Avísame cuando estén publicados', 'texto_lectura', 40, 1,
       'Aviso', 'Avísame cuando estén publicados', NULL
  FROM `paginas` p WHERE p.clave = 'materiales';


-- ── Noticias ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('noticias', 'Noticias', '/noticias/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Actualizado el 13 de agosto de 2026', 'Noticias', 'Lo que se ha hecho público sobre el viaje, en orden. Aquí no se publica nada que no proceda de una fuente oficial.'
  FROM `paginas` p WHERE p.clave = 'noticias';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'santa-sede-anuncia', 'La Santa Sede anuncia el viaje apostólico de León XIV al Perú', 'texto_apartados', 20, 1,
       NULL, 'La Santa Sede anuncia el viaje apostólico de León XIV al Perú', NULL
  FROM `paginas` p WHERE p.clave = 'noticias';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'enterate-cuanto-haya', 'Entérate en cuanto haya novedades', 'texto_lectura', 30, 1,
       'Aviso', 'Entérate en cuanto haya novedades', NULL
  FROM `paginas` p WHERE p.clave = 'noticias';


-- ── Patrocinios ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('patrocinios', 'Patrocinios', '/patrocinios/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Empresas e instituciones', 'Patrocinios', 'Organizar seis días en cuatro ciudades tiene un coste. Estas son las necesidades reales y cómo se puede ayudar a cubrirlas.'
  FROM `paginas` p WHERE p.clave = 'patrocinios';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'hace-falta', 'Qué hace falta', 'texto_apartados', 20, 1,
       'Necesidades', 'Qué hace falta', NULL
  FROM `paginas` p WHERE p.clave = 'patrocinios';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'como-proponer-patrocinio', 'Cómo proponer un patrocinio', 'texto_lectura', 30, 1,
       'Cómo se hace', 'Cómo proponer un patrocinio', 'Escribe a la organización contando qué puedes aportar —producto, servicio, horas de trabajo o aporte económico—, en qué sede y en qué fechas. No hace falta un dosier: hace falta una descripción clara. La organización responderá indicando si la necesidad sigue abierta y con qué condiciones. Cada aporte se acredita y se refleja en la rendición de cuentas del viaje. Canal de contacto para patrocinios: [CORREO POR CONFIRMAR].'
  FROM `paginas` p WHERE p.clave = 'patrocinios';


-- ── Portada ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('home', 'Portada', '/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'todo-necesitas-antes', 'Todo lo que necesitas antes del viaje', 'texto_apartados', 10, 1,
       'Por dónde empezar', 'Todo lo que necesitas antes del viaje', NULL
  FROM `paginas` p WHERE p.clave = 'home';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'falta-poco-encuentro', 'Falta poco para el encuentro', 'texto_lectura', 20, 1,
       'Cuenta atrás', 'Falta poco para el encuentro', NULL
  FROM `paginas` p WHERE p.clave = 'home';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cronicas-visita', 'Crónicas de la visita', 'texto_apartados', 30, 1,
       'Hitos verificables', 'Crónicas de la visita', NULL
  FROM `paginas` p WHERE p.clave = 'home';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'dias-encuentro', 'Los días del encuentro', 'texto_lectura', 40, 1,
       '11–16 de noviembre de 2026', 'Los días del encuentro', NULL
  FROM `paginas` p WHERE p.clave = 'home';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cuatro-ciudades-solo', 'Cuatro ciudades, un solo pueblo', 'texto_lectura', 50, 1,
       'Costa, sierra y selva', 'Cuatro ciudades, un solo pueblo', NULL
  FROM `paginas` p WHERE p.clave = 'home';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'mas-destacado', 'Lo más destacado', 'texto_lectura', 60, 1,
       'Actualizado el 5 de agosto de 2026', 'Lo más destacado', NULL
  FROM `paginas` p WHERE p.clave = 'home';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'prepara-tu-corazon', 'Prepara tu corazón', 'texto_lectura', 70, 1,
       'Prepárate', 'Prepara tu corazón', NULL
  FROM `paginas` p WHERE p.clave = 'home';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'pon-tus-dones', 'Pon tus dones al servicio', 'texto_lectura', 80, 1,
       'Tres formas', 'Pon tus dones al servicio', NULL
  FROM `paginas` p WHERE p.clave = 'home';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'acompana-cada-momento', 'Acompaña cada momento de la visita', 'texto_lectura', 90, 1,
       'Cuatro accesos', 'Acompaña cada momento de la visita', NULL
  FROM `paginas` p WHERE p.clave = 'home';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'iglesia-peru-te', 'La Iglesia en el Perú te espera', 'texto_lectura', 100, 1,
       'Conferencia Episcopal Peruana', 'La Iglesia en el Perú te espera', NULL
  FROM `paginas` p WHERE p.clave = 'home';


-- ── Preguntas frecuentes ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('preguntas-frecuentes', 'Preguntas frecuentes', '/preguntas-frecuentes/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Diecinueve respuestas', 'Preguntas frecuentes', 'Lo que más se pregunta, respondido con lo que hay. Cuando algo no está confirmado, aquí lo dice.'
  FROM `paginas` p WHERE p.clave = 'preguntas-frecuentes';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'visita', 'La visita', 'texto_lectura', 20, 1,
       'Preguntas', 'La visita', NULL
  FROM `paginas` p WHERE p.clave = 'preguntas-frecuentes';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'asistir-actos', 'Asistir a los actos', 'texto_lectura', 30, 1,
       'Preguntas', 'Asistir a los actos', NULL
  FROM `paginas` p WHERE p.clave = 'preguntas-frecuentes';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'voluntariado', 'Voluntariado', 'texto_lectura', 40, 1,
       'Preguntas', 'Voluntariado', NULL
  FROM `paginas` p WHERE p.clave = 'preguntas-frecuentes';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'seguir-visita', 'Seguir la visita', 'texto_lectura', 50, 1,
       'Preguntas', 'Seguir la visita', NULL
  FROM `paginas` p WHERE p.clave = 'preguntas-frecuentes';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'este-sitio', 'Este sitio', 'texto_lectura', 60, 1,
       'Preguntas', 'Este sitio', NULL
  FROM `paginas` p WHERE p.clave = 'preguntas-frecuentes';


-- ── Prensa ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('prensa', 'Prensa', '/prensa/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Para medios', 'Prensa', 'Acreditación, contacto y condiciones de uso del material gráfico.'
  FROM `paginas` p WHERE p.clave = 'prensa';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'como-acreditarse', 'Cómo acreditarse', 'texto_lectura', 20, 1,
       'Acreditación', 'Cómo acreditarse', 'En los viajes apostólicos la acreditación de medios la gestiona la Oficina de Prensa de la Santa Sede para el vuelo papal y los actos internacionales, y la conferencia episcopal del país para los actos locales. Los plazos y los formularios se publican con algunas semanas de antelación. En cuanto se abra el proceso, aquí encontrarás el formulario, el plazo de solicitud, los requisitos y los puntos de recogida de credenciales en cada sede.'
  FROM `paginas` p WHERE p.clave = 'prensa';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'uso-imagenes-escudo', 'Uso de imágenes y del escudo', 'texto_apartados', 30, 1,
       'Material', 'Uso de imágenes y del escudo', NULL
  FROM `paginas` p WHERE p.clave = 'prensa';


-- ── Privacidad ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('privacidad', 'Privacidad', '/privacidad/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Legal', 'Política de privacidad', 'Qué datos pedimos, para qué, cuánto tiempo los guardamos y cómo ejercer tus derechos.'
  FROM `paginas` p WHERE p.clave = 'privacidad';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'responsable-tratamiento', 'Responsable del tratamiento', 'texto_lectura', 20, 1,
       NULL, 'Responsable del tratamiento', 'Este sitio recoge datos personales en tres puntos y en ninguno más: el aviso por correo, el formulario de contacto y la inscripción de voluntariado. No hay analítica, ni píxeles de seguimiento, ni scripts de terceros que recojan datos. Responsable del tratamiento [RAZÓN SOCIAL POR CONFIRMAR], con domicilio en [DOMICILIO POR CONFIRMAR]. Correo para ejercicio de derechos: [CORREO POR CONFIRMAR]. Qué datos y para qué Aviso por correo. Solo tu dirección de correo electrónico, para escribirte cuando se publique aquello de lo que pediste aviso. No se usa para ningún otro envío. Formulario de contact'
  FROM `paginas` p WHERE p.clave = 'privacidad';


-- ── Sedes ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('sedes', 'Sedes', '/sedes/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Sedes', 'Cuatro ciudades, un solo pueblo', 'Lima, Chiclayo, Cusco y Pucallpa. La costa, el norte, los Andes y la Amazonía. Cuatro maneras de ser Iglesia en el mismo país.'
  FROM `paginas` p WHERE p.clave = 'sedes';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'estas-cuatro', 'Por qué estas cuatro', 'texto_lectura', 20, 1,
       'Costa, sierra y selva', 'Por qué estas cuatro', 'La Santa Sede anunció el 5 de agosto de 2026 que el Papa León XIV visitará Lima, Chiclayo, Cusco y Pucallpa entre el 11 y el 16 de noviembre. Es la única información de itinerario que hay: no se ha publicado ni el orden del viaje ni los días que corresponden a cada ciudad. Aun así, la elección dice bastante. Están la capital y su arquidiócesis primada; la diócesis que el Santo Padre pastoreó durante ocho años; la Iglesia andina más antigua del país; y un vicariato apostólico amazónico. Costa, sierra y selva. Una arquidiócesis, una diócesis y un territorio de misión. En esta página encontrarás,'
  FROM `paginas` p WHERE p.clave = 'sedes';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'lima', 'Lima', 'texto_lectura', 30, 1,
       NULL, 'Lima', NULL
  FROM `paginas` p WHERE p.clave = 'sedes';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cuatro-sedes-mapa', 'Las cuatro sedes en el mapa del país', 'texto_lectura', 40, 1,
       'Diagrama esquemático', 'Las cuatro sedes en el mapa del país', 'Del norte costero a los Andes del sur, pasando por la Amazonía central: las cuatro sedes recorren de un extremo a otro las tres regiones del país. Es una geografía que obliga a volar y que explica por qué el viaje ocupa seis días completos.'
  FROM `paginas` p WHERE p.clave = 'sedes';


-- ── Transparencia ─────────────────────────────────────────────
INSERT IGNORE INTO `paginas` (`clave`, `nombre`, `ruta`, `activa`)
VALUES ('transparencia', 'Transparencia', '/transparencia/', 1);

INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1,
       'Rendición de cuentas', 'Transparencia', 'Quien da algo tiene derecho a saber en qué se usó. Este es el compromiso, escrito antes de recibir el primer aporte.'
  FROM `paginas` p WHERE p.clave = 'transparencia';
INSERT IGNORE INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'publicara-cuando', 'Qué se publicará y cuándo', 'texto_apartados', 20, 1,
       'Compromiso', 'Qué se publicará y cuándo', 'Todavía no hay nada que rendir: el canal de donativos no está abierto y no se ha recibido ningún aporte a través de este sitio. Lo que sí puede escribirse desde ya es el compromiso.'
  FROM `paginas` p WHERE p.clave = 'transparencia';


-- ═══ COMPROBACIÓN ═══════════════════════════════════════════════════════
SELECT p.clave, p.nombre, COUNT(s.id) AS secciones
  FROM `paginas` p
  LEFT JOIN `secciones` s ON s.pagina_id = p.id
 GROUP BY p.id
 ORDER BY p.clave = 'home' DESC, p.nombre;

SELECT COUNT(*) AS paginas_totales FROM `paginas`;
SELECT COUNT(*) AS secciones_totales FROM `secciones`;