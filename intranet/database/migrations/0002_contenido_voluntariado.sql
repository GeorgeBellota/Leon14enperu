-- ============================================================================
--  0002 · Contenido editable de la página de voluntariado
--
--  Vuelca a la base los textos que hoy están escritos a mano en
--  voluntariado/index.html, para que index.php los lea de aquí y el panel
--  pueda cambiarlos sin tocar código.
--
--  Los SEIS SERVICIOS no se copian a `bloques` a propósito: ya viven en la
--  tabla `servicios`, que es también la que alimenta el <select> del
--  formulario. Duplicarlos garantizaría que un día la tarjeta diga una cosa y
--  la opción del desplegable otra distinta.
--
--  Idempotente: se puede volver a ejecutar sin duplicar nada.
-- ============================================================================

-- ── Campos propios de cada sección ──────────────────────────────────────

-- Cabecera: el <h1> y la bajada.
UPDATE `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`rotulo` = 'Voluntariado',
       s.`titulo` = 'Los amigos de León',
       s.`texto`  = 'La visita del Santo Padre al Perú será una experiencia inolvidable, y queremos vivirla sirviendo, acogiendo y haciendo comunidad.'
 WHERE p.clave = 'voluntariado' AND s.clave = 'cabecera';

-- Servicios: el encabezado y el bloque de cierre, que no es repetible y por
-- eso va en `datos` en lugar de convertirse en cuatro bloques sueltos.
UPDATE `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`datos` = JSON_OBJECT(
         'cierre',       JSON_ARRAY(
           'Seis servicios, una sola misión: servir con alegría.',
           'Porque encontrarnos con el Santo Padre también significa poner nuestros dones al servicio de los demás.',
           '¿Te animas a ser parte de esta experiencia?'
         ),
         'grito',        '¡El Perú te necesita!',
         'boton_texto',  'Inscríbete ahora',
         'boton_url',    '#inscripcion'
       )
 WHERE p.clave = 'voluntariado' AND s.clave = 'servicios';

-- Inscripción: rótulo, titular y la lista «Ten a mano».
UPDATE `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`datos` = JSON_OBJECT(
         'ten_a_mano_titulo', 'Ten a mano',
         'ten_a_mano', JSON_ARRAY(
           'Tus <strong>nombres y apellidos</strong> completos',
           'Tu <strong>DNI</strong>, ocho dígitos',
           'Tu <strong>fecha de nacimiento</strong>',
           'Tu <strong>dirección completa</strong>: calle, número, distrito y provincia',
           'Tu <strong>correo electrónico</strong> y tu <strong>número telefónico</strong>',
           'El <strong>nombre y el teléfono</strong> de tu contacto de emergencia',
           'Tu <strong>talla de polo</strong>: S, M, L, XL o XXL',
           'La <strong>jurisdicción</strong> en la que quieres servir',
           'El <strong>servicio</strong> que prefieres, de los seis'
         ),
         'nota',        'Si te interrumpen, lo que hayas escrito se guarda en este navegador y puedes volver.',
         'ancla_rotulo','Fase 01 · Solo por internet',
         'ancla_titulo','Inscríbete como voluntario',
         'ancla_dato',  'Once datos, unos cinco minutos',
         -- Texto legal del consentimiento. Sigue con los marcadores del
         -- cliente sin rellenar; ahora al menos se corrigen desde el panel y
         -- no hay que tocar el HTML.
         -- CONCAT y no «||».
         --
         -- En MySQL y MariaDB, `||` es el operador OR LÓGICO, no la
         -- concatenación de cadenas (eso es PostgreSQL, o MySQL con el modo
         -- PIPES_AS_CONCAT activado). Escrito así, el motor intentaba leer
         -- estos textos como números y el resultado era `false`: el texto
         -- legal del consentimiento quedó vacío en la base, y la casilla que
         -- acepta el tratamiento de datos personales se mostraba sin explicar
         -- nada. En un servidor con sql_mode estricto ni siquiera llega a
         -- guardarse: aborta con «Truncated incorrect DOUBLE value».
         'consentimiento', CONCAT(
           'Autorizo el tratamiento de mis datos personales por parte de ',
           '<strong>[RESPONSABLE POR CONFIRMAR]</strong> con la única finalidad de gestionar mi ',
           'inscripción como voluntario del viaje apostólico, asignarme un servicio y comunicarme ',
           'con mi contacto de emergencia si fuera necesario. Mis datos se conservarán hasta ',
           '<strong>[PLAZO POR CONFIRMAR]</strong> y después se eliminarán. Puedo ejercer mis ',
           'derechos de acceso, rectificación, cancelación y oposición escribiendo a ',
           '<strong>[CORREO POR CONFIRMAR]</strong>.'
         )
       )
 WHERE p.clave = 'voluntariado' AND s.clave = 'inscripcion';

-- Después de enviar: tres párrafos de lectura.
UPDATE `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`texto` = CONCAT(
         '<p>Enviar el formulario no es quedar seleccionado, y tampoco hace falta que hagas nada más ',
         'por tu cuenta: <strong>a partir de aquí la organización te busca a ti</strong>.</p>',
         '<p>Te escribirá al correo que hayas dejado para pedirte los documentos de la ',
         '<strong>Fase 02</strong>.</p>',
         '<p>Ya cerca de la visita llega la <strong>Fase 03</strong>: la confirmación oficial, el área ',
         'de servicio que se te asigna y la entrega de credenciales.</p>'
       )
 WHERE p.clave = 'voluntariado' AND s.clave = 'despues';


-- ── Bloques repetibles ──────────────────────────────────────────────────
--
-- Se borran e insertan de nuevo en lugar de usar ON DUPLICATE KEY: `bloques`
-- no tiene una clave natural por la que deduplicar, y esto mantiene la
-- migración repetible sin inventar una columna sólo para eso.
--
-- ⚠ Reejecutar esta migración descarta las ediciones hechas desde el panel en
--   estas dos secciones. Es aceptable en un seed inicial; a partir de aquí,
--   ninguna migración vuelve a tocar contenido que edite el cliente.

DELETE b FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas` p   ON p.id = s.pagina_id
 WHERE p.clave = 'voluntariado' AND s.clave IN ('resumen', 'proceso');

-- Los tres pasos de «En treinta segundos».
INSERT INTO `bloques` (`seccion_id`, `orden`, `titulo`, `texto`, `enlace_texto`, `enlace_url`)
SELECT s.id, b.orden, b.titulo, b.texto, b.enlace_texto, b.enlace_url
  FROM `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
  JOIN (
    SELECT 10 AS orden,
           'Eliges tu servicio' AS titulo,
           'Seis servicios. Da igual tu edad, tu profesión o el tiempo que puedas dar.' AS texto,
           'Ver los seis' AS enlace_texto,
           '#servicios' AS enlace_url
    UNION ALL SELECT 20, 'Rellenas el formulario',
           'Es la Fase 01 y el único paso que se hace por internet. Cinco minutos.',
           'Ir al formulario', '#inscripcion'
    UNION ALL SELECT 30, 'La organización te escribe',
           'Validación de documentos y, más adelante, acreditación y credenciales.',
           'Ver el proceso', '#proceso'
  ) b
 WHERE p.clave = 'voluntariado' AND s.clave = 'resumen';

-- Las tres fases, con sus viñetas en `datos`.
INSERT INTO `bloques` (`seccion_id`, `orden`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT s.id, b.orden, b.rotulo, b.titulo, b.texto, b.datos
  FROM `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
  JOIN (
    SELECT 10 AS orden, '01' AS rotulo, 'Inscripción' AS titulo,
           'Rellenas el formulario de esta página con tus datos, la jurisdicción en la que quieres servir y el servicio que prefieres. Es el único paso que se hace por internet.' AS texto,
           JSON_OBJECT('vinetas', JSON_ARRAY(
             'Nombres y apellidos, DNI y fecha de nacimiento',
             'Dirección completa, correo electrónico y número telefónico',
             'Talla de polo y contacto de emergencia',
             'Jurisdicción y servicio'
           )) AS datos
    UNION ALL SELECT 20, '02', 'Validación',
           'Se solicitarán algunos documentos adicionales. <strong>Nada de esto se sube a esta web:</strong> la organización te indicará por qué canal entregarlos.',
           JSON_OBJECT('vinetas', JSON_ARRAY(
             'Carta de recomendación de sacerdote, religioso(a) u obispo',
             'Declaración o certificado de antecedentes judiciales y penales',
             'Entrevista personal (según necesidad)',
             'Evaluación psicológica (cuando sea posible)'
           ))
    UNION ALL SELECT 30, '03', 'Acreditación',
           'El último paso, ya cerca de la visita.',
           JSON_OBJECT('vinetas', JSON_ARRAY(
             'Confirmación oficial',
             'Asignación de área de servicio',
             'Entrega de credenciales'
           ))
  ) b
 WHERE p.clave = 'voluntariado' AND s.clave = 'proceso';


-- ── Ajustes que usa la página pública ───────────────────────────────────
INSERT INTO `ajustes` (`clave`, `valor`, `tipo`, `descripcion`) VALUES
  ('voluntariado.cerrado_texto',
   'La convocatoria de voluntarios no está abierta en este momento. Vuelve a consultarlo dentro de unos días.',
   'texto', 'Lo que se muestra en lugar del formulario cuando voluntariado.abierto está a 0.'),
  ('voluntariado.exige_mayoria_edad', '0', 'booleano',
   'Pendiente de decisión del cliente: si se admiten menores hacen falta consentimiento de tutor y protocolo de salvaguarda.')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);
