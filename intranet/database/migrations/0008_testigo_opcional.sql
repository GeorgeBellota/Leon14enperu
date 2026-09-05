-- ============================================================================
--  0008 · El testigo del formulario pasa a ser opcional
--
--  ⚠ DEUDA TÉCNICA. Esto NO es una mejora: es un apaño para desbloquear el
--    lanzamiento, y hay que revertirlo.
--
--  QUÉ PASÓ
--    El hosting sirve el formulario desde una caché de página. El visitante
--    recibe un HTML de hace horas y, con él, un testigo ya caducado: su
--    inscripción se rechaza con «el formulario expiró». Ocurrió con copias de
--    hasta dieciocho horas.
--
--  QUÉ SE PIERDE AL APAGARLO
--    El testigo impide que una página ajena publique un formulario que envíe
--    inscripciones a este sitio en nombre de quien la visite. Sin él, ese
--    envío pasa a ser posible.
--
--  QUÉ SIGUE PROTEGIENDO
--    · la trampa para robots (un campo que ningún humano ve),
--    · el tiempo mínimo de relleno,
--    · el tope de inscripciones por hora y por IP,
--    · la validación completa de todos los campos,
--    · el control de DNI duplicado.
--    Un envío automatizado masivo sigue sin pasar. Lo que se abre es la puerta
--    a envíos dirigidos desde otro sitio.
--
--  CÓMO SE VUELVE A ACTIVAR
--    UPDATE ajustes SET valor = '1' WHERE clave = 'voluntariado.exigir_testigo';
--
--    Antes hay que resolver la caché: comprobar que la página responde con
--    «Cache-Control: no-store» y que el HTML que llega es el recién generado.
--    Mientras esté apagado, cada envío con testigo inválido queda anotado en
--    el registro de errores, así que se puede saber si el problema persiste
--    antes de volver a encenderlo.
-- ============================================================================

INSERT INTO `ajustes` (`clave`, `valor`, `tipo`, `descripcion`) VALUES
  ('voluntariado.exigir_testigo', '1', 'booleano',
   'DEUDA TÉCNICA: con 0 se aceptan envíos con el testigo caducado. Apagado para sortear la caché del hosting. Volver a 1 cuando la página deje de servirse cacheada.')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);
