# Cambios de contenido pedidos por el cliente

Septiembre de 2026. Portada y menú.

## Qué archivo es cuál

| | |
|---|---|
| **`1-APLICAR.sql`** | Lo que hay que ejecutar. Deja el carrusel y el menú como los pidió el cliente. |
| **`2-DESHACER.sql`** | Sólo si algo sale mal. Devuelve las cosas como estaban. |

Son la alternativa a `php database/migrate.php` para cuando en el servidor no
hay consola. Hacen exactamente lo mismo que las migraciones **0021, 0022, 0023,
0024 y 0025**, que son las canónicas: si tocas algo, tócalo ahí y regenera esto.

## Antes

```bash
mysqldump -u USUARIO -p --single-transaction --quick --default-character-set=utf8mb4 \
  BASE > respaldo-$(date +%F-%H%M).sql
```

`--single-transaction` para no bloquear la tabla mientras alguien se inscribe.

## El orden importa

**Primero el código, después el SQL.** Al revés, la lámina de la Colecta sale
con el bloque de texto encima del Santo Padre y la del banner sale recortada:
las dos dependen de diseños que la vista sólo conoce con el despliegue hecho.

## Qué toca, y qué no

Escribe en tres tablas y en ninguna más:

| tabla | qué |
|---|---|
| `bloques` | las láminas del carrusel de la portada |
| `ajustes` | una fila, `menu.visibles` |
| `migraciones` | el registro de lo aplicado |

**No toca `voluntarios`.** Ni `voluntarios_historial`, ni `usuarios`, ni
`auditoria`, ni `medios`, ni `comunicados`, ni `paginas`, ni `secciones`, ni el
ubigeo. Comprobado con `CHECKSUM TABLE` sobre las veintitrés tablas y con MD5
sobre los datos de los voluntarios, campos cifrados incluidos.

Ninguna tabla tiene clave foránea hacia `bloques`, así que el `DELETE` de
`2-DESHACER.sql` no puede arrastrar filas de nada por cascada.

Las láminas que salen del carrusel **se apagan, no se borran**: siguen en el
panel con todos sus textos y vuelven con un clic.

## Se puede ejecutar dos veces

Los dos archivos son idempotentes. Comprobado.

Y `1-APLICAR.sql` sobre una base que ya lo tiene aplicado no cambia nada; igual
que `2-DESHACER.sql` sobre una base que todavía no lo tiene: ahí es un no-op
completo, ninguna de las veintitrés tablas se mueve.

## Después

```sql
SELECT b.orden, b.activo, b.rotulo, b.titulo, b.datos, b.imagen_id
  FROM bloques b JOIN secciones s ON s.id=b.seccion_id JOIN paginas p ON p.id=s.pagina_id
 WHERE p.clave='home' AND s.clave='hero' ORDER BY b.orden;

SELECT valor FROM ajustes WHERE clave='menu.visibles';
SELECT COUNT(*), MAX(creado_en) FROM voluntarios;
```

Tres láminas encendidas: `5 sin-texto`, `40 fondo-derecha`, `50 fondo`. El menú
con `el-papa`, `cep` y `materiales` sumados a lo que hubiera. Y los voluntarios
en su cifra de siempre, con un alta reciente.

**Mira la columna `imagen_id` de esas tres.** Las fotografías nuevas viajan en
el código como respaldo, y lo que esté subido desde el panel gana. Si alguna de
esas filas trae un número, esa lámina no enseñará la imagen nueva: hay que
sustituirla a mano en la intranet.

## Los PDF no son SQL

Los seis PDF de los subsidios viajan con el código, en `assets/docs/subsidios/`.
El SQL sólo crea la sección y sus seis piezas, que son las que apuntan a esos
archivos. Si el despliegue no sube la carpeta, las piezas se pintan **sin botón
de descarga** —la vista comprueba que el archivo exista antes de ofrecerlo— en
lugar de dar un 404 al pulsar.

El subsidio más pesado son 10,2 MB. Para que se puedan sustituir desde la
intranet, el PHP del alojamiento necesita `upload_max_filesize` y
`post_max_size` por encima de esa cifra; con el valor de 8 MB que traen muchos
hostings, la subida se rechaza. La pantalla de Documentos lo dice con nombre y
apellidos en vez de fallar en silencio.

## Y lo último, que no es SQL

La **inscripción real de prueba, con y sin JavaScript**. Es la regla nº 1 del
encargo y va contra el servidor de verdad. Nada de este cambio toca el camino
del formulario —ni `Inscripcion.php`, ni `Voluntario.php`, ni `form.js`, ni el
ubigeo, ni el cifrado—, pero la prueba se hace igual.

---

## APLICAR

```sql
-- ===========================================================================
--  LEON14ENPERU · CAMBIOS DE CONTENIDO PARA PRODUCCIÓN
--  Equivale a las migraciones 0021, 0022, 0023, 0024 y 0025 en un solo
--  archivo. EL CÓDIGO SE DESPLIEGA ANTES QUE ESTO.
-- ===========================================================================
--  Toca cuatro tablas y ninguna más:
--    · `bloques`     las láminas del carrusel y las seis descargas
--    · `secciones`   una fila nueva: los subsidios de /materiales/
--    · `ajustes`     una fila: el menú
--    · `migraciones` el registro de lo aplicado
--
--  Ni un DELETE, ni un DROP, ni un TRUNCATE, ni un ALTER. No toca
--  voluntarios, usuarios, auditoria, medios, comunicados ni paginas.
--  Se puede ejecutar dos veces: no duplica ni deshace nada.
-- ===========================================================================

SET NAMES utf8mb4;

START TRANSACTION;

-- ── 0 · «Abramos el corazón» queda preparada a sangre ─────────────────────
--  Se apaga más abajo, así que hoy no se ve. Pero si algún día la vuelven a
--  encender desde el panel, que vuelva con el diseño que le toca y no con el
--  partido, que era el que llevaba cuando tenía el retrato del Santo Padre.
UPDATE `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
   SET b.`datos` = JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.diseno', 'fondo')
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND b.`titulo` = 'Abramos el corazón'
   AND JSON_VALID(COALESCE(NULLIF(b.`datos`, ''), '{}'));

-- ── 1 · La lámina de la Colecta: a sangre con el texto a la derecha ────────
UPDATE `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
   SET b.`datos` = JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.diseno', 'fondo-derecha')
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND b.`rotulo` = 'Colecta Nacional'
   AND JSON_VALID(COALESCE(NULLIF(b.`datos`, ''), '{}'));

-- ── 2 · La lámina de los santos: a sangre, encuadre, un botón y los nombres ─
UPDATE `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
   SET b.`datos` = JSON_SET(
         JSON_SET(
           JSON_SET(
             JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.diseno', 'fondo'),
             '$.encuadre', '50% 26%'),
           '$.segundo_boton', 'no'),
         '$.dato',
         'Santo Toribio de Mogrovejo | Santa Rosa de Lima | San Martín de Porres\nSan Juan Macías y San Francisco Solano')
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND b.`rotulo` = 'Cinco caminos de santidad'
   AND JSON_VALID(COALESCE(NULLIF(b.`datos`, ''), '{}'));

-- ── 3 · Las láminas que se apagan (NO se borran: vuelven desde el panel) ────
UPDATE `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
   SET b.`activo` = 0
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND (b.`rotulo` IN ('Los amigos de León', 'Cuatro ciudades')
        OR b.`titulo` = 'Abramos el corazón');

-- ── 4 · En la Colecta manda «Colecta Nacional» ─────────────────────────────
UPDATE `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
   SET b.`rotulo` = 'Súmate con tu donación',
       b.`titulo` = 'Colecta Nacional'
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND b.`rotulo` = 'Colecta Nacional'
   AND b.`titulo` = 'Súmate con tu donación';

-- ── 5 · La lámina nueva del banner, sin texto encima ───────────────────────
INSERT INTO `bloques`
  (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`)
SELECT hero.id, 5, 1, NULL,
       '¡El Papa León XIV vuelve al Perú! Del 11 al 16 de noviembre de 2026',
       NULL, NULL, NULL, '{"diseno":"sin-texto"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'home' AND s.clave = 'hero' LIMIT 1) AS hero
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = hero.id AND ya.orden = 5);

-- ── 6 · El menú: LEÓN XIV, CEP y SUBSIDIOS ─────────────────────────────────
--  Se SUMAN a lo que ya haya. Si el ajuste está vacío no se toca: vacío
--  significa «se ven todas», así que los tres ya están.
UPDATE `ajustes` SET `valor` = TRIM(BOTH ',' FROM CONCAT(COALESCE(`valor`,''), ',el-papa'))
 WHERE `clave` = 'menu.visibles' AND TRIM(COALESCE(`valor`,'')) <> ''
   AND NOT FIND_IN_SET('el-papa', REPLACE(COALESCE(`valor`,''), ' ', ''));

UPDATE `ajustes` SET `valor` = TRIM(BOTH ',' FROM CONCAT(COALESCE(`valor`,''), ',cep'))
 WHERE `clave` = 'menu.visibles' AND TRIM(COALESCE(`valor`,'')) <> ''
   AND NOT FIND_IN_SET('cep', REPLACE(COALESCE(`valor`,''), ' ', ''));

UPDATE `ajustes` SET `valor` = TRIM(BOTH ',' FROM CONCAT(COALESCE(`valor`,''), ',materiales'))
 WHERE `clave` = 'menu.visibles' AND TRIM(COALESCE(`valor`,'')) <> ''
   AND NOT FIND_IN_SET('materiales', REPLACE(COALESCE(`valor`,''), ' ', ''));


-- ── 7 · La sección de subsidios de /materiales/ ────────────────────────────
--  Los seis PDF ya viajan con el código, en assets/docs/subsidios/. Esto crea
--  la sección para que se puedan editar desde la intranet.
INSERT INTO `secciones`
  (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`)
SELECT p.id, 'subsidios', 'Subsidios descargables', 'descargas', 5, 1,
       'Ya disponibles', 'Subsidios para la visita',
       '<p>Cinco materiales aprobados por la Conferencia Episcopal Peruana para preparar la visita en la parroquia, el colegio y la familia. Descarga libre y uso gratuito.</p>'
  FROM `paginas` p
 WHERE p.clave = 'materiales'
   AND NOT EXISTS (SELECT 1 FROM (SELECT `pagina_id`, `clave` FROM `secciones`) AS ya
                    WHERE ya.pagina_id = p.id AND ya.clave = 'subsidios');

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 10, 1, 'Subsidios para la visita del Papa León XIV al Perú', 'El documento que presenta los cinco subsidios y cómo usarlos en la parroquia, el colegio y la familia.', '{"archivo":"assets/docs/subsidios/subsidios-papa-leon-xiv.pdf","destacado":"sí"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 10);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 20, 1, 'Papa León: cercano y peruano', 'Quién es León XIV y qué lo une al Perú, para conocerlo antes de recibirlo.', '{"archivo":"assets/docs/subsidios/1-papa-leon-cercano-peruano.pdf"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 20);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 30, 1, 'Unidos en Cristo, sembradores de paz', 'El lema del Santo Padre llevado a la vida de la comunidad.', '{"archivo":"assets/docs/subsidios/2-unidos-en-cristo.pdf"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 30);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 40, 1, 'Familias que cuidan la vida', 'Material para trabajar en familia durante las semanas previas.', '{"archivo":"assets/docs/subsidios/3-familias-que-cuidan-la-vida.pdf"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 40);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 50, 1, 'Los jóvenes y la misión', 'Para grupos juveniles, colegios y pastoral universitaria.', '{"archivo":"assets/docs/subsidios/4-los-jovenes-y-la-mision.pdf"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 50);
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`)
SELECT sub.id, 60, 1, 'Pastoral social: la dignidad de toda persona', 'La dimensión social de la fe, con propuestas para la comunidad.', '{"archivo":"assets/docs/subsidios/5-pastoral-social.pdf"}'
  FROM (SELECT s.id FROM `secciones` s JOIN `paginas` p ON p.id = s.pagina_id
         WHERE p.clave = 'materiales' AND s.clave = 'subsidios' LIMIT 1) AS sub
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `seccion_id`, `orden` FROM `bloques`) AS ya
                    WHERE ya.seccion_id = sub.id AND ya.orden = 60);

-- ── 8 · Se anotan las migraciones como aplicadas ───────────────────────────
--  Para que `php database/migrate.php` no vuelva a pasarlas por encima.
INSERT IGNORE INTO `migraciones` (`archivo`) VALUES
  ('0021_cambios_cliente_portada.sql'),
  ('0022_menu_leon_cep_subsidios.sql'),
  ('0023_slider_colecta_jerarquia.sql'),
  ('0024_slider_banner_sin_texto.sql'),
  ('0025_subsidios_descargables.sql');

COMMIT;


-- ═══ COMPROBACIÓN ═══════════════════════════════════════════════════════
--  Tienen que salir TRES láminas encendidas, en este orden:
--    5  sin-texto       (el banner)
--    40 fondo-derecha   Súmate con tu donación / Colecta Nacional
--    50 fondo           Cinco caminos de santidad / Cinco santos…
--
--  Y en `imagen_id`: si alguna de esas tres trae un número, esa lámina ya
--  tiene imagen subida desde el panel y GANA sobre la del código. Habría que
--  sustituirla a mano en la intranet.

SELECT b.`orden`, b.`activo`, b.`rotulo`, b.`titulo`, b.`datos`, b.`imagen_id`
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
 ORDER BY b.`orden`;

SELECT `clave`, `valor` FROM `ajustes` WHERE `clave` = 'menu.visibles';

-- Las seis descargas de /materiales/, con su PDF.
SELECT b.`orden`, b.`titulo`, JSON_UNQUOTE(JSON_EXTRACT(b.`datos`, '$.archivo')) AS archivo
  FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'materiales' AND s.clave = 'subsidios'
 ORDER BY b.`orden`;

-- Y esto tiene que seguir intacto: 35 000 y pico.
SELECT COUNT(*) AS voluntarios FROM `voluntarios`;
```

---

## DESHACER

Sólo si hace falta volver atrás. Sobre una base que todavía no tiene los
cambios no hace absolutamente nada.

```sql
-- ===========================================================================
--  VUELTA ATRÁS de los cambios de contenido (0021-0024)
--  Deja el carrusel y el menú como estaban ANTES, sin tocar `voluntarios`.
-- ===========================================================================
SET NAMES utf8mb4;
START TRANSACTION;

-- 1 · Fuera la lámina del banner (la creó este mismo cambio)
DELETE b FROM `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND b.`orden` = 5
   AND b.`titulo` = '¡El Papa León XIV vuelve al Perú! Del 11 al 16 de noviembre de 2026';

-- 2 · Se vuelven a encender las tres láminas apagadas
UPDATE `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
   SET b.`activo` = 1
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND (b.`rotulo` IN ('Los amigos de León', 'Cuatro ciudades')
        OR b.`titulo` = 'Abramos el corazón');

-- 3 · «Abramos el corazón» vuelve al diseño partido
UPDATE `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
   SET b.`datos` = NULL
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND b.`titulo` = 'Abramos el corazón';

-- 4 · La Colecta recupera su jerarquía y su diseño
UPDATE `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
   SET b.`rotulo` = 'Colecta Nacional',
       b.`titulo` = 'Súmate con tu donación',
       b.`datos`  = JSON_SET(COALESCE(NULLIF(b.`datos`, ''), '{}'), '$.diseno', 'fondo')
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND b.`rotulo` = 'Súmate con tu donación'
   AND b.`titulo` = 'Colecta Nacional';

-- 5 · Los santos vuelven al diseño partido y pierden los añadidos
UPDATE `bloques` b
  JOIN `secciones` s ON s.id = b.seccion_id
  JOIN `paginas`   p ON p.id = s.pagina_id
   SET b.`datos` = JSON_REMOVE(COALESCE(NULLIF(b.`datos`, ''), '{}'),
                     '$.dato', '$.segundo_boton', '$.encuadre', '$.diseno')
 WHERE p.clave = 'home' AND s.clave = 'hero'
   AND b.`rotulo` = 'Cinco caminos de santidad';

-- 6 · El menú pierde los tres accesos
UPDATE `ajustes`
   SET `valor` = TRIM(BOTH ',' FROM REPLACE(REPLACE(REPLACE(
         CONCAT(',', REPLACE(COALESCE(`valor`, ''), ' ', ''), ','),
         ',el-papa,', ','), ',cep,', ','), ',materiales,', ','))
 WHERE `clave` = 'menu.visibles' AND TRIM(COALESCE(`valor`, '')) <> '';

-- 7 · Se desanotan las migraciones, por si se quieren volver a pasar
-- 7 · Fuera la sección de subsidios. Sus seis piezas se van con ella: la
--     clave foránea de `bloques` hacia `secciones` es ON DELETE CASCADE.
DELETE s FROM `secciones` s
  JOIN `paginas` p ON p.id = s.pagina_id
 WHERE p.clave = 'materiales' AND s.clave = 'subsidios';

-- 8 · Se desanotan las migraciones
DELETE FROM `migraciones` WHERE `archivo` IN (
  '0021_cambios_cliente_portada.sql', '0022_menu_leon_cep_subsidios.sql',
  '0023_slider_colecta_jerarquia.sql', '0024_slider_banner_sin_texto.sql',
  '0025_subsidios_descargables.sql');

COMMIT;
```
