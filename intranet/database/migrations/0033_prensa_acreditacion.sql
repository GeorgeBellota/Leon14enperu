-- ===========================================================================
--  0033 · PRENSA — LA ACREDITACIÓN PASA A SER UNA PILA DE TRÁMITES
-- ---------------------------------------------------------------------------
--  El editable de septiembre de 2026 parte lo que era UN texto —«el Ministerio
--  estará a cargo del proceso, la fecha se comunicará oportunamente»— en tres
--  trámites distintos, cada uno con su estado:
--
--    1  Dirigido a la Prensa para la Visita     PROCESO HABILITADO
--    2  Vuelo Papal                             PROCESO HABILITADO
--    3  Señal oficial para medios (IRTP)        PROCESO NO HABILITADO
--
--  Y ya no es texto pendiente: la acreditación está ABIERTA, con un plazo que
--  vence el 28 de septiembre y un correo al que escribir. Lo que había en la
--  web decía lo contrario.
--
--  ── Por qué bloques y no tres secciones ──────────────────────────────────
--
--  Porque los estados caducan con el calendario. El tercero será «habilitado»
--  cuando el IRTP abra su formulario, entre el 19 y el 31 de octubre; el
--  primero vence el 28 de septiembre; y pasado el viaje sobran los tres. Con
--  bloques, todo eso se añade, se quita, se reordena y se apaga desde el panel
--  sin tocar una línea de código ni volver a desplegar.
--
--  La sección cambia de plantilla —de «texto_lectura» a «prensa_acreditacion»—
--  para que el panel ofrezca los campos de cada trámite: el estado, el titular
--  con sus renglones, el subtítulo dorado, la fotografía y dónde va.
--
--  ── Las anclas, para enlazar desde un banner ─────────────────────────────
--
--    /prensa/#acreditacion-medios    el primero
--    /prensa/#vuelo-papal            el segundo
--    /prensa/#senal-oficial          el tercero
--
--  Van escritas a mano y no calculadas del titular a propósito: calculadas
--  cambiarían el día que alguien retoque el titular, y el banner dejaría de
--  funcionar sin que nadie se entere. Se editan en el panel, campo «Nombre
--  del ancla» de cada ficha.
--
--  ── Lo que hay que rellenar a mano después ───────────────────────────────
--
--    · Las fotografías del segundo y el tercer trámite —el avión y la sala de
--      control—, que no están en el repositorio. Se suben desde el panel, en
--      el campo «Imagen» de cada bloque. Sin ellas el texto ocupa el ancho
--      entero, que se ve bien; no queda ningún hueco gris.
--
--  «Inscríbete aquí», en el Vuelo Papal, apunta al sistema de acreditación de
--  la Oficina de Prensa de la Santa Sede:
--
--    https://press.vatican.va/content/salastampa/es/accrediti/pubblico/accredito.html
--
--  Es de fuera, así que se abre en otra pestaña. Si cambia, se cambia desde el
--  panel: está escrito dentro del texto del bloque, no en el código.
--
--  ── Qué NO toca ──────────────────────────────────────────────────────────
--
--  Sólo la sección «como-acreditarse» de la página «prensa». Ni el héroe, ni
--  el uso de imágenes, ni la botonera, ni Multimedia. Ni voluntarios, ni
--  usuarios, ni ninguna otra página.
--
--  Es repetible: borra los bloques de esa sección y los vuelve a crear.
-- ===========================================================================

SET NAMES utf8mb4;

SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'prensa' LIMIT 1);
SET @sec := (SELECT `id` FROM `secciones`
              WHERE `pagina_id` = @pag AND `clave` = 'como-acreditarse' LIMIT 1);

-- La sección deja de pintar titular propio: ahora manda el de cada trámite.
-- El título se conserva porque es lo que nombra la sección en el panel y lo
-- que la página usa como etiqueta para los lectores de pantalla.
UPDATE `secciones`
   SET `plantilla` = 'prensa_acreditacion',
       `titulo`    = 'Acreditación',
       `rotulo`    = '',
       `texto`     = ''
 WHERE @sec IS NOT NULL AND `id` = @sec;

DELETE FROM `bloques` WHERE @sec IS NOT NULL AND `seccion_id` = @sec;

-- INSERT ... SELECT y no VALUES: asi el «WHERE @sec IS NOT NULL» del final
-- protege tambien la insercion. Con VALUES, si la seccion no existiera, esto
-- intentaria escribir un seccion_id nulo y reventaria a media migracion.
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `datos`)
SELECT @sec, v.`orden`, 1, v.`rotulo`, v.`titulo`, v.`texto`, v.`datos`
FROM (
  SELECT 10 AS orden,
         'ACREDITACIÓN / PROCESO HABILITADO' AS rotulo,
         'Dirigido a la Prensa para la\nVisita del Santo Padre al Perú' AS titulo,
         'El **Ministerio de Relaciones Exteriores** informa que ya se encuentra abierta la acreditación de medios de comunicación para la Visita Apostólica de Su Santidad el papa León XIV al Perú, la cual es válida para todo el territorio nacional.\n\nLos medios de comunicación interesados deberán enviar, **hasta el lunes 28 de septiembre a las 23:59 horas**, un correo electrónico a **prensa@rree.gob.pe** indicando:\n\n- Nombre completo\n- Tipo y número de documento de identidad\n- Teléfono celular\n- Dirección de correo electrónico de su coordinador de enlace' AS texto,
         '{"ancla": "acreditacion-medios", "subtitulo": "Medios de Comunicación Nacionales e Internacionales"}' AS datos
  UNION ALL
  SELECT 20 AS orden,
         'ACREDITACIÓN / PROCESO HABILITADO' AS rotulo,
         'Vuelo Papal' AS titulo,
         'A los periodistas que deseen acreditarse **para hacer todo el recorrido del Viaje Apostólico de Su Santidad el Papa León XIV a Uruguay, Argentina y Perú**.\n\nEsta acreditación se solicita a la Oficina de Prensa correspondiente, a través de un sistema de acreditación online.\n\nPara más información [inscríbete aquí](https://press.vatican.va/content/salastampa/es/accrediti/pubblico/accredito.html).' AS texto,
         '{"ancla": "vuelo-papal", "foto": "abajo"}' AS datos
  UNION ALL
  SELECT 30 AS orden,
         'ACREDITACIÓN / PROCESO NO HABILITADO' AS rotulo,
         'Señal oficial para\nmedios de comunicación' AS titulo,
         'El **Instituto Nacional de Radio y Televisión del Perú (IRTP)** acreditará a los medios de comunicación que deseen acceder a la señal de transmisión de la visita del Santo Padre, del 11 al 16 de noviembre. La señal se proporcionará limpia, sin logotipos, banners, cintillos ni otros elementos gráficos.\n\nPara acceder a ella, cada medio deberá acreditarse y completar el formulario correspondiente, indicando las especificaciones técnicas que requiera. El enlace al formulario **estará disponible en la página del IRTP del 19 al 31 de octubre**.' AS texto,
         '{"ancla": "senal-oficial"}' AS datos
) AS v
WHERE @sec IS NOT NULL;
