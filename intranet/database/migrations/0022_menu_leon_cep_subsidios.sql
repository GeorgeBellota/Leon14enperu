-- ===========================================================================
--  0022 · TRES ACCESOS MÁS EN EL MENÚ: LEÓN XIV, CEP Y SUBSIDIOS
-- ---------------------------------------------------------------------------
--  Lo pidió el cliente. Es el punto 10 de la solicitud «CAMBIOS PARA LA NUEVA
--  WEB», que el acta dejó POR DEFINIR a la espera de saber si esos tres
--  accesos llevaban a contenido existente o exigían páginas nuevas.
--
--  Ya está respondido: las tres páginas existen y están publicadas.
--
--      LEÓN XIV   → /el-papa/     «El Papa»                 (la biografía)
--      CEP        → /cep/         «La Iglesia en el Perú»
--      SUBSIDIOS  → /materiales/  «Materiales de pastoral»  (los subsidios de
--                                  oración, catequesis y animación)
--
--  Así que no hay nada que crear: sólo dejar de esconderlas en el menú.
--
--  ── Qué hace ──────────────────────────────────────────────────────────────
--
--  Añade tres claves al ajuste `menu.visibles`, que es el apartado de la
--  intranet que decide qué entradas del menú se ven. Una fila de `ajustes`.
--  Nada más.
--
--  ── Qué NO hace ───────────────────────────────────────────────────────────
--
--  Ni un DELETE, ni un TRUNCATE, ni un DROP, ni un ALTER, ni un INSERT. No
--  toca `voluntarios`, `usuarios`, `auditoria`, `medios`, `comunicados`,
--  `paginas`, `secciones` ni `bloques`. No crea páginas, no cambia ninguna
--  dirección y no toca ningún slug.
--
--  ── Por qué SUMA en vez de reescribir ─────────────────────────────────────
--
--  Podría escribirse la lista entera de un tirón, pero entonces habría que
--  saber qué hay hoy en producción. Y lo que hay ahí lo decide el cliente
--  desde el panel: si mañana enciende «Agenda» y este archivo se vuelve a
--  pasar, se la apagaría sin avisar.
--
--  Cada clave se añade sólo si no está ya. Lo que hubiera —hoy, en producción,
--  «voluntariado» y «donativo»— se queda intacto, y el orden del menú tampoco
--  depende de esta cadena: lo fija la lista de assets/parciales/cabecera.php,
--  que es la navegación canónica del sitio. El resultado en pantalla será:
--
--      Papa León XIV · CEP · Voluntariado · Subsidios · Donaciones
--
--  Cinco entradas. Por debajo de las seis a partir de las cuales la cabecera
--  retira la cuenta atrás para que la barra no desborde, así que el contador
--  sigue donde está.
--
--  ── Y si el ajuste está VACÍO, no se toca ────────────────────────────────
--
--  Vacío no significa «ningún enlace»: significa «todos». Es la convención que
--  lee assets/parciales/cabecera.php y por la que el menú completo es también
--  la reserva cuando la base no responde.
--
--  Así que si alguien deja el campo en blanco, los tres accesos ya se ven y
--  aquí no hay nada que hacer. Sin esta guarda pasaría lo contrario: la cadena
--  se quedaría en «el-papa,cep,materiales» y el menú, que enseñaba las
--  dieciséis entradas, se quedaría en tres. De un ajuste vacío no se deduce
--  que el cliente quiera esconder nada.
--
--  ── Es repetible ──────────────────────────────────────────────────────────
--
--  Cada UPDATE lleva su guarda NOT FIND_IN_SET. Ejecutarlo dos veces no
--  duplica ninguna clave ni reordena nada.
--
--  EL CÓDIGO SE DESPLIEGA ANTES QUE ESTO, por una sola cosa: la entrada de
--  /materiales/ pasa a rotularse «Subsidios» en cabecera.php. Si esto entrara
--  primero, el acceso aparecería con el rótulo viejo hasta el despliegue. No
--  rompe nada, sólo se leería «Materiales» unas horas.
-- ===========================================================================

SET NAMES utf8mb4;


-- ═══ FOTO DE ANTES ══════════════════════════════════════════════════════

SELECT 'ANTES' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `paginas`)     AS paginas,
       (SELECT `valor` FROM `ajustes` WHERE `clave` = 'menu.visibles') AS menu_visibles;


-- ═══ LAS TRES CLAVES ════════════════════════════════════════════════════
--
-- La comparación se hace sobre la cadena SIN espacios: el ajuste se edita a
-- mano desde el panel y «voluntariado, donativo» —con el espacio detrás de la
-- coma— es tan válido como sin él. Sin el REPLACE, FIND_IN_SET no reconocería
-- « donativo» y la clave se añadiría por segunda vez.
--
-- El TRIM de comas al final cubre el caso de que el ajuste esté vacío: sin él
-- la cadena quedaría «,el-papa» y la primera entrada sería una clave en blanco.

UPDATE `ajustes`
   SET `valor` = TRIM(BOTH ',' FROM CONCAT(COALESCE(`valor`, ''), ',el-papa'))
 WHERE `clave` = 'menu.visibles'
   AND TRIM(COALESCE(`valor`, '')) <> ''
   AND NOT FIND_IN_SET('el-papa', REPLACE(COALESCE(`valor`, ''), ' ', ''));

UPDATE `ajustes`
   SET `valor` = TRIM(BOTH ',' FROM CONCAT(COALESCE(`valor`, ''), ',cep'))
 WHERE `clave` = 'menu.visibles'
   AND TRIM(COALESCE(`valor`, '')) <> ''
   AND NOT FIND_IN_SET('cep', REPLACE(COALESCE(`valor`, ''), ' ', ''));

UPDATE `ajustes`
   SET `valor` = TRIM(BOTH ',' FROM CONCAT(COALESCE(`valor`, ''), ',materiales'))
 WHERE `clave` = 'menu.visibles'
   AND TRIM(COALESCE(`valor`, '')) <> ''
   AND NOT FIND_IN_SET('materiales', REPLACE(COALESCE(`valor`, ''), ' ', ''));


-- ═══ FOTO DE DESPUÉS ════════════════════════════════════════════════════

SELECT 'DESPUÉS' AS momento,
       (SELECT COUNT(*) FROM `voluntarios`) AS voluntarios,
       (SELECT COUNT(*) FROM `paginas`)     AS paginas,
       (SELECT `valor` FROM `ajustes` WHERE `clave` = 'menu.visibles') AS menu_visibles;

-- Las tres páginas del encargo, para comprobar de un vistazo que están
-- publicadas. Si alguna trae `activa` = 0, el enlace saldría en el menú y
-- llevaría a un 404: se enciende desde el panel, no desde aquí.

SELECT `clave`, `nombre`, `ruta`, `activa`
  FROM `paginas`
 WHERE `clave` IN ('el-papa', 'cep', 'materiales')
 ORDER BY FIELD(`clave`, 'el-papa', 'cep', 'materiales');
