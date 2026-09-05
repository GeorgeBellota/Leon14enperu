-- ===========================================================================
--  0019 · /donativo/ SE PONE AL DÍA CON LA COLECTA NACIONAL
-- ---------------------------------------------------------------------------
--  El problema que arregla:
--
--  La portada publica desde la migración 0017 las dos cuentas oficiales de la
--  Colecta Nacional. La página /donativo/ seguía diciendo «Todavía no hay canal
--  de donativos abierto» y «Cuando el canal esté abierto, aparecerá aquí con la
--  cuenta oficial». Las dos afirmaciones no pueden convivir: una de ellas es
--  falsa, y en una página de donativos eso no es un descuido de redacción sino
--  una puerta abierta al fraude —quien lea que no hay canal y luego vea una
--  cuenta por WhatsApp no tiene forma de saber cuál creer—.
--
--  Qué hace: actualiza el rótulo, el titular y el texto de DOS secciones de la
--            página /donativo/. Nada más.
--
--  Qué NO hace: ni un DELETE, ni un TRUNCATE, ni un DROP, ni un ALTER, ni un
--            INSERT. No crea secciones, no borra ninguna, no toca las cuentas
--            —que viven en la sección «colecta» de la portada y son las mismas
--            para las dos páginas—, y no toca `voluntarios`, `usuarios`,
--            `auditoria`, `medios` ni el ubigeo.
--
--  ── De dónde sale cada texto ───────────────────────────────────────────────
--
--  · La frase que avisa de las cuentas falsas se conserva casi palabra por
--    palabra de la que ya estaba. Sólo cambia «cualquier cuenta que circule
--    antes de ese anuncio» por «cualquier otra cuenta que circule», porque el
--    anuncio ya se hizo y ahora hay unas cuentas oficiales de referencia.
--  · «Este sitio no tiene pasarela de pago» también estaba y sigue siendo
--    cierto: el aporte se hace por depósito, no por la web. Se le quita la
--    continuación —«y no la tendrá hasta que la organización defina la
--    titularidad de la cuenta»—, que era la parte que ha dejado de valer.
--  · Lo demás es la redacción mínima para que la página diga lo que pasa.
--
--  Los textos quedan en la base y se editan desde el panel. La vista lleva los
--  mismos de respaldo por si la base no responde.
--
--  Es repetible: los dos UPDATE escriben el mismo valor si ya estaba.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

SELECT s.`clave`, s.`titulo`
  FROM `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'donativo'
 ORDER BY s.`orden`;


-- ═══ 1 · EL ESTADO: LA COLECTA ESTÁ ABIERTA ═════════════════════════════
--
-- La clave de la sección NO se toca. Sigue siendo «todavia-canal-donativos»
-- aunque el titular ya no diga eso: la clave es la dirección interna con la
-- que el panel y la vista se entienden, y renombrarla obligaría a tocar las
-- dos a la vez sin ganar nada.

UPDATE `secciones` s
   JOIN `paginas` p ON p.id = s.pagina_id
    SET s.`rotulo` = 'Estado',
        s.`titulo` = 'La Colecta Nacional está abierta',
        s.`texto`  = CONCAT(
            '<p>Este sitio <strong>no tiene pasarela de pago</strong>: el aporte se hace por ',
            'depósito o transferencia a las cuentas oficiales de la Conferencia Episcopal ',
            'Peruana, que están publicadas aquí abajo.</p>',
            '<p><strong>Desconfía de cualquier otra cuenta que circule</strong>, aunque venga ',
            'con el escudo y con fotos del Santo Padre. Si no está publicada en esta página o ',
            'por la Conferencia Episcopal Peruana, no es oficial.</p>'
        )
  WHERE p.clave = 'donativo'
    AND s.clave = 'todavia-canal-donativos';


-- ═══ 2 · EL AVISO CAMBIA DE ENCARGO ═════════════════════════════════════
--
-- Prometía avisar «cuando se abra el canal». El canal ya está abierto, así que
-- tal cual estaba era pedirle a alguien que se apunte a un correo que no va a
-- llegar. Lo que sigue pendiente es el procedimiento para pedir constancia del
-- aporte, y ése sí merece el aviso.

UPDATE `secciones` s
   JOIN `paginas` p ON p.id = s.pagina_id
    SET s.`titulo` = 'Avísame de las novedades de la colecta',
        s.`texto`  = '<p>Un solo correo cuando haya novedades, como el procedimiento para pedir constancia del aporte.</p>'
  WHERE p.clave = 'donativo'
    AND s.clave = 'avisame-cuando-abra';


-- ═══ 3 · EL TEXTO DE «A QUÉ SE DESTINAN», A LA BASE ═════════════════════
--
-- Estaba escrito en la vista con una nota de «copy pendiente de validación».
-- Se guarda tal cual, sin cambiar una coma, para que se pueda corregir desde
-- el panel el día que lo validen. Sólo se escribe si está vacío: si alguien ya
-- lo editó, manda lo suyo.

UPDATE `secciones` s
   JOIN `paginas` p ON p.id = s.pagina_id
    SET s.`texto` = '<p>Un donativo no financia el viaje del Santo Padre: financia el trabajo de acoger a quienes vienen a encontrarse con él.</p>'
  WHERE p.clave = 'donativo'
    AND s.clave = 'destinan'
    AND (s.`texto` IS NULL OR s.`texto` = '');


-- ═══ FOTO DE DESPUÉS ════════════════════════════════════════════════════

SELECT 'DESPUÉS' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)   AS secciones,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

SELECT s.`clave`, s.`titulo`
  FROM `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'donativo'
 ORDER BY s.`orden`;
