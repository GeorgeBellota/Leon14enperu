-- ===========================================================================
--  0021 · LOS CAMBIOS DE PORTADA QUE PIDIÓ EL CLIENTE
-- ---------------------------------------------------------------------------
--  De dónde sale: el archivo «CAMBIOS PARA LA NUEVA WEB» remitido por el
--  cliente, el «Acta de alcance y validación de cambios» que lo clasifica, y
--  las precisiones posteriores del cliente sobre el carrusel y el aviso del
--  programa.
--
--  ── Qué hace ──────────────────────────────────────────────────────────────
--
--  Sólo el carrusel de la portada. Cinco láminas pasan a tres:
--
--   1. «Abramos el corazón» pasa a diseño «fondo» —fotografía a sangre con el
--      texto encima—, como las otras dos.
--   2. «Los amigos de León» se APAGA.
--   3. «Cuatro ciudades» se APAGA.
--   4. «Colecta Nacional» pasa a «fondo-derecha»: el mismo bloque a sangre,
--      pero apoyado en el lado contrario.
--   5. «Cinco caminos de santidad» vuelve a «fondo» —el recuadro rojo pequeño
--      sobre la fotografía, «como el anterior slider»—, recibe su encuadre, se
--      queda con un solo botón y cambia la barandilla de datos por los nombres
--      de los cinco santos.
--
--  ── Qué NO hace ───────────────────────────────────────────────────────────
--
--  Ni un DELETE, ni un TRUNCATE, ni un DROP, ni un ALTER, ni un INSERT. No
--  toca `voluntarios` —35 000 inscripciones reales—, ni `usuarios`, ni
--  `auditoria`, ni `medios`, ni `comunicados`, ni el ubigeo. No crea ni borra
--  secciones ni láminas. No cambia ningún rótulo, titular, texto ni enlace.
--
--  LAS DOS LÁMINAS SE APAGAN, NO SE BORRAN. Un DELETE se lleva por delante sus
--  textos y su imagen elegida, y no se deshace. Con el interruptor a cero
--  siguen en el panel, se ven tachadas en el listado y volver a encenderlas es
--  un clic. Es además lo mismo que haría un editor desde la intranet, así que
--  no queda un estado que el panel no sepa explicar.
--
--  Los otros puntos incluidos no tienen nada que hacer aquí y por eso no
--  aparecen:
--    · el flyer del pop-up es maqueta del modal, no contenido: el texto
--      inferior deja de pintarse para cualquier comunicado que tenga cartel y
--      la imagen crece. Ni un UPDATE sobre `comunicados`, que es la tabla que
--      lleva la cuenta de vistas y clics del himno.
--    · la imagen de «El Papa León XIV llega al Perú» y el cartel de la Colecta
--      entran como fotografía de respaldo de la vista. En cuanto alguien suba
--      una desde el panel, la suya manda.
--    · la animación de las ciudades y el aviso de programa referencial son
--      código: CSS el primero, y el segundo se queda escrito en la vista a
--      propósito —la regla nº3 del encargo dice que ese aviso no se quita
--      hasta que la Santa Sede publique el programa, y un campo del panel es
--      un campo que alguien puede vaciar sin querer—.
--
--  ── El texto de los santos ────────────────────────────────────────────────
--
--  Se guarda en caja normal y no en versalitas, como los demás valores del
--  sitio: las mayúsculas las pone el CSS (.hero__dato, text-transform). Si se
--  escribiera ya en mayúsculas, un lector de pantalla deletrearía «S-A-N-T-O».
--
--  La barra vertical separa los elementos de un renglón y el salto de línea
--  abre el segundo. El corte es el que pidió el cliente y no uno automático:
--  «San Juan Macías y San Francisco Solano» van juntos porque los une una «y».
--
--  ── Es repetible ──────────────────────────────────────────────────────────
--
--  Los seis UPDATE son idempotentes: escriben el mismo valor si ya estaba.
--  Ejecutarlo dos veces no cambia nada y no duplica nada.
--
--  EL CÓDIGO SE DESPLIEGA ANTES QUE ESTO. La vista tiene que saber leer
--  «fondo-derecha», el encuadre, la barandilla y el segundo botón cuando esta
--  migración los escriba; y tiene que emparejar las fotografías de reserva por
--  RÓTULO y no por posición, o al apagar dos láminas las tres que quedan
--  heredarían la imagen de otra. Al revés no pasa nada: el código desplegado
--  sin la migración pinta lo mismo que hoy.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`)  AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)    AS secciones,
       (SELECT COUNT(*) FROM `bloques`)      AS bloques,
       (SELECT COUNT(*) FROM `comunicados`)  AS comunicados;

SELECT b.`orden`,
       b.`activo`,
       b.`rotulo`,
       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.diseno')), 'partida') AS diseno,
       b.`imagen_id`
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
 ORDER BY b.`orden`;


-- ═══ 1 · LA PRIMERA LÁMINA, A SANGRE ════════════════════════════════════
--
-- Era la excepción del carrusel: composición partida, con el texto en un panel
-- de color al lado de la fotografía. Y no por variedad, sino por REGLA —llevaba
-- el retrato oficial del Santo Padre, y sobre ese retrato no va ningún velo ni
-- degradado (regla dura nº 2 del encargo)—.
--
-- Al pedir el cliente que las tres láminas compartan diseño, la fotografía
-- tenía que cambiar con él: el retrato es un vertical donde la cara llena el
-- encuadre, y recortarlo a la franja del carrusel con el bloque de texto encima
-- era exactamente lo que la regla prohíbe. La lámina pasa a la toma aérea de la
-- explanada, que no tiene ningún rostro en primer plano. Esa foto vive en el
-- código como reserva de la vista; aquí sólo se escribe el diseño.
--
-- Se identifica por el TÍTULO y no por el rótulo: el rótulo de esta lámina es
-- la línea de fechas y podría cambiar en cualquier momento desde el panel.
--
-- JSON_SET sobre COALESCE, como en la 0017: si la columna estuviera a NULL,
-- JSON_SET sobre NULL devuelve NULL y se perdería el valor. Con el COALESCE se
-- parte de un objeto vacío y se conserva cualquier otra clave que hubiera.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`datos` = JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.diseno', 'fondo')
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`titulo` = 'Abramos el corazón'
    AND JSON_VALID(COALESCE(NULLIF(b.`datos`, ''), '{}'));


-- ═══ 2 · LAS DOS LÁMINAS QUE SE APAGAN ══════════════════════════════════
--
-- «Los amigos de León» y «Cuatro ciudades». Se quedan en la base con todo su
-- contenido; sólo dejan de pintarse.
--
-- Lo que se pierde y conviene saber: «Los amigos de León» era la única lámina
-- que enlazaba al voluntariado con su propio botón. El carrusel NO se queda sin
-- esa puerta —la vista añade «Sé voluntario» detrás del botón de cada lámina, y
-- lo siguen llevando la primera y la de la Colecta—, pero si algún día se apaga
-- también ese segundo botón, hay que volver a mirar esto.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`activo` = 0
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`rotulo` IN ('Los amigos de León', 'Cuatro ciudades');


-- ═══ 3 · LA COLECTA, CON EL TEXTO AL OTRO LADO ══════════════════════════
--
-- Por qué, con detalle, porque es la decisión menos obvia de este archivo:
--
-- La FOTO 1 que envió el cliente tiene al Santo Padre en el TERCIO IZQUIERDO,
-- y ahí es justo donde se apoyaban el bloque y el foco del velo. La regla dura
-- nº 2 del encargo dice que sobre el retrato del Santo Padre no va ni velo, ni
-- degradado, ni recorte.
--
-- No se arregla con el encuadre: la fotografía es 3:2 y el carrusel una franja
-- mucho más apaisada, así que `cover` recorta por arriba y por abajo y NUNCA
-- por los lados. No hay valor de `object-position` que lo mueva a la derecha.
--
-- Así que se mueve el bloque. La fotografía se respeta entera y la regla se
-- cumple sin levantarla.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`datos` = JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.diseno', 'fondo-derecha')
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`rotulo` = 'Colecta Nacional'
    AND JSON_VALID(COALESCE(NULLIF(b.`datos`, ''), '{}'));


-- ═══ 4 · LOS SANTOS, A SANGRE ═══════════════════════════════════════════
--
-- «Cambia ese cuadrado rojo de fondo, que sea más pequeño como el anterior
-- slider». El «anterior slider» es la Colecta, que va a sangre: la fotografía
-- llena la diapositiva y el texto se apoya en un bloque acotado, en lugar del
-- panel de color que se lleva media pantalla.
--
-- Esto deshace el punto 2 de la migración 0018, que la había devuelto al
-- diseño partido para que las cinco láminas alternaran. La alternancia era una
-- decisión de diseño; ésta es una petición del cliente, y manda la petición.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`datos` = JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.diseno', 'fondo')
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`rotulo` = 'Cinco caminos de santidad'
    AND JSON_VALID(COALESCE(NULLIF(b.`datos`, ''), '{}'));


-- ═══ 5 · SU ENCUADRE: QUE SE VEAN LOS ROSTROS ═══════════════════════════
--
-- «Acomodar para que se vea los rostros de los santos».
--
-- La FOTO 2 es un montaje de cinco retratos en tiras verticales, y las caras
-- viven en la franja alta: entre el 9 % y el 23 % de la altura. Con el 42 % de
-- siempre, `cover` empieza a recortar en el 11 % y se comía la fila de arriba.
-- Con el 26 % el recorte empieza en el 5,8 % y entran las cinco enteras.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`datos` = JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.encuadre', '50% 26%')
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`rotulo` = 'Cinco caminos de santidad'
    AND JSON_VALID(COALESCE(NULLIF(b.`datos`, ''), '{}'));


-- ═══ 6 · UN SOLO BOTÓN, Y LOS CINCO NOMBRES ═════════════════════════════
--
-- «Dejar solamente el botón: CONOCE SUS HISTORIAS» y, en lugar de la hilera de
-- fechas y ciudades, los cinco santos en dos renglones.
--
-- Las dos claves se escriben en un solo UPDATE con un JSON_SET encadenado: son
-- la misma petición sobre la misma lámina y separarlas sólo daría dos formas de
-- quedarse a medias.
--
-- El botón que se retira es el de «Sé voluntario», que la vista añade sola
-- detrás del de cada lámina. Se apaga en ÉSTA y sólo en ésta: las otras dos lo
-- conservan, porque el voluntariado sigue siendo la única acción abierta.

UPDATE `bloques` b
   JOIN `secciones` s ON s.id = b.seccion_id
   JOIN `paginas`   p ON p.id = s.pagina_id
    SET b.`datos` = JSON_SET(
          JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.segundo_boton', 'no'),
          '$.dato',
          'Santo Toribio de Mogrovejo | Santa Rosa de Lima | San Martín de Porres\nSan Juan Macías y San Francisco Solano'
        )
  WHERE p.clave = 'home'
    AND s.clave = 'hero'
    AND b.`rotulo` = 'Cinco caminos de santidad'
    AND JSON_VALID(COALESCE(NULLIF(b.`datos`, ''), '{}'));


-- ═══ FOTO DE DESPUÉS ════════════════════════════════════════════════════

SELECT 'DESPUÉS' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`)  AS voluntarios,
       (SELECT COUNT(*) FROM `secciones`)    AS secciones,
       (SELECT COUNT(*) FROM `bloques`)      AS bloques,
       (SELECT COUNT(*) FROM `comunicados`)  AS comunicados;

-- Las cinco láminas, con lo que manda cada una. Deben quedar TRES encendidas.
--
-- ⚠ MIRA LA COLUMNA `imagen_id`. Las fotografías nuevas —la toma aérea en la
--    primera, FOTO 1 en la Colecta, FOTO 2 en los santos— entran como RESPALDO
--    de la vista, que es lo que se pinta mientras la lámina no tenga imagen
--    propia elegida en la biblioteca. Si una de esas filas trae un `imagen_id`,
--    esa imagen gana y la nueva no se verá: hay que entrar al panel, a esa
--    lámina, y sustituirla a mano. No se toca desde aquí a propósito: `medios`
--    es contenido subido por el cliente y una migración no borra lo que subió
--    el cliente.

SELECT b.`orden`,
       b.`activo`,
       b.`rotulo`,
       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.diseno')),        'partida') AS diseno,
       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.encuadre')),      '50% 42%') AS encuadre,
       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.segundo_boton')), 'sí')      AS segundo_boton,
       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.dato')),          '(la de siempre)') AS barandilla,
       b.`imagen_id`
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
 ORDER BY b.`orden`;
