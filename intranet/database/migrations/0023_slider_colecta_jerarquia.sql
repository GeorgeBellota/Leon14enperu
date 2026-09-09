-- ===========================================================================
--  0023 · LA LÁMINA DE LA COLECTA: EL TITULAR ES «COLECTA NACIONAL»
-- ---------------------------------------------------------------------------
--  Corrige un descuido de la 0021.
--
--  El punto 2 del archivo «CAMBIOS PARA LA NUEVA WEB» no sólo daba los textos
--  de la lámina: daba también cuál manda. En el documento del cliente,
--  «Colecta Nacional» está escrito a 24 pt y «Súmate con tu donación» al
--  cuerpo normal. Es decir, al revés de como está publicado.
--
--      hoy          rótulo  COLECTA NACIONAL          titular  Súmate con tu donación
--      pedido       rótulo  SÚMATE CON TU DONACIÓN    titular  Colecta Nacional
--
--  La 0021 se leyó la lista como contenido, vio que las frases ya estaban las
--  dos en la lámina y no tocó nada. Estaban, pero cambiadas de sitio.
--
--  ── Qué hace ──────────────────────────────────────────────────────────────
--
--  Intercambia `rotulo` y `titulo` en UNA lámina del carrusel. Nada más.
--
--  ── Qué NO hace ───────────────────────────────────────────────────────────
--
--  Ni un DELETE, ni un TRUNCATE, ni un DROP, ni un ALTER, ni un INSERT. No
--  toca `voluntarios`, `usuarios`, `auditoria`, `medios`, `comunicados`,
--  `paginas` ni `ajustes`. No cambia el diseño de la lámina, ni su fotografía,
--  ni sus botones, ni su enlace.
--
--  Y NO TOCA EL BLOQUE DE DONACIONES de más abajo, que hoy lleva exactamente
--  el mismo par de textos —rótulo «Colecta Nacional», titular «Súmate con tu
--  donación»—. El cliente pidió invertirlos en el SLIDER; sobre esa sección no
--  dijo nada, así que se queda como está. Por eso el UPDATE va contra
--  `bloques` y además se ata a la sección «hero»: las dos ataduras sobran por
--  separado —la sección vive en otra tabla— y juntas hacen imposible el
--  accidente.
--
--  ── Ojo con el orden ──────────────────────────────────────────────────────
--
--  Tiene que ir DESPUÉS de la 0021, y va. La 0021 localiza esta lámina por
--  `rotulo = 'Colecta Nacional'`; en cuanto esta migración lo cambie, aquella
--  ya no la encontraría. Como la 0021 se aplica antes —y una vez aplicada no
--  se repite—, la secuencia es correcta tanto en producción como en una
--  instalación nueva desde cero.
--
--  ── Y el código, antes que esto ───────────────────────────────────────────
--
--  views/portada.php empareja cada lámina con su fotografía de reserva por el
--  texto. Buscaba la palabra sólo en el rótulo, y al invertirlos «colecta»
--  dejaba de estar ahí: la lámina se habría quedado con la fotografía de la
--  primera. Ahora mira el rótulo Y el titular. Sin ese cambio desplegado, esta
--  migración cambia la foto de la Colecta sin querer.
--
--  ── Es repetible ──────────────────────────────────────────────────────────
--
--  El UPDATE exige encontrar los textos en su posición ANTIGUA. Ejecutado dos
--  veces, la segunda no encuentra nada y no hace nada: no puede volver a
--  intercambiarlos ni dejarlos a medias.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

SELECT b.`orden`, b.`activo`, b.`rotulo`, b.`titulo`
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
 ORDER BY b.`orden`;


-- ═══ EL INTERCAMBIO ═════════════════════════════════════════════════════

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`rotulo` = 'Súmate con tu donación',
        b.`titulo` = 'Colecta Nacional'
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`rotulo` = 'Colecta Nacional'
    AND b.`titulo` = 'Súmate con tu donación';


-- ═══ FOTO DE DESPUÉS ════════════════════════════════════════════════════

SELECT 'DESPUÉS' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `bloques`)     AS bloques;

-- La lámina tiene que quedar con el titular «Colecta Nacional».
SELECT b.`orden`, b.`activo`, b.`rotulo`, b.`titulo`
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
 ORDER BY b.`orden`;

-- Y el bloque de donaciones tiene que seguir COMO ESTABA: rótulo «Colecta
-- Nacional», titular «Súmate con tu donación». Si aquí sale invertido, el
-- UPDATE se ha ido de sección.
SELECT s.`clave`, s.`rotulo`, s.`titulo`
  FROM `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'colecta';
