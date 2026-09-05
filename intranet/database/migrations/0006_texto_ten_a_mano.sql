-- ============================================================================
--  0006 · «Ten a mano» al día con el formulario nuevo
--
--  La lista seguía pidiendo la dirección completa con distrito y provincia —que
--  ahora salen de desplegables— y daba el contacto de emergencia por
--  obligatorio, cuando ya no lo es. Una lista que promete unos campos y luego
--  aparecen otros deja a la persona buscando lo que no le van a pedir.
--
--  El orden es el mismo del formulario: DNI primero, porque es la llave.
-- ============================================================================

UPDATE `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
   SET s.`datos` = JSON_SET(
         COALESCE(s.`datos`, JSON_OBJECT()),
         '$.ten_a_mano', JSON_ARRAY(
           'Tu <strong>DNI</strong>, ocho dígitos',
           'Tu <strong>nombre completo</strong>',
           'Tu <strong>fecha de nacimiento</strong>',
           'Tu <strong>departamento, provincia y distrito</strong>',
           'Tu <strong>dirección</strong>: calle o avenida y número',
           'Tu <strong>correo electrónico</strong> y tu <strong>número telefónico</strong>',
           'Tu <strong>talla de polo</strong>: S, M, L, XL o XXL',
           'La <strong>jurisdicción</strong> en la que quieres servir',
           'El <strong>servicio</strong> que prefieres, de los seis'
         ),
         -- CONCAT, no «||»: en MySQL las barras dobles son el OR lógico y el
         -- texto se guardaba como `false`.
         '$.nota', CONCAT(
           'El contacto de emergencia es opcional: puedes darlo más adelante. ',
           'Y si te interrumpen, lo que hayas escrito se guarda en este navegador.'
         ),
         '$.ancla_dato', 'Nueve datos, unos cinco minutos'
       )
 WHERE p.clave = 'voluntariado' AND s.clave = 'inscripcion';
