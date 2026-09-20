# Contenido del rediseño 2026

Un archivo por página. Describe qué secciones y bloques tiene y con qué texto,
para que la migración pueda cargarlos sin escribir SQL a mano.

- `"imagen"` es una clave de `assets/img/rediseno/variantes.json`, con la forma
  `"carpeta/base"` — por ejemplo `"prensa/hero"` o `"index/p13"`.
- `"@bd"` en un campo significa «conserva lo que ya hay en la base». Se usa
  donde el editable de Illustrator trae texto de relleno («Lorem ipsum»,
  «XX:00 Hrs.», preguntas sin respuesta) y producción tiene el texto real.
- Las secciones que la página nueva ya no pinta no se listan aquí; la migración
  las retira y deja constancia en su cabecera.

De estos archivos sale `migrations/0027_rediseno_2026.sql`.

## Cómo se resuelve `"@bd"`

No lo resuelve MySQL: lo resuelve el generador, leyendo un volcado de la base
de producción y escribiendo el texto literal en la migración. Por eso la
migración se puede aplicar sobre una base vacía y sale igual.

Conviene saberlo por dos motivos:

1. **Hay que regenerar cuando cambie producción.** El volcado de referencia se
   saca con `refbd.mjs` desde una base reconstruida a partir del `.sql` de
   producción; si ese volcado envejece, `"@bd"` conservará texto viejo.
2. **`"@bd"` vale en cualquier sitio, también dentro de `"datos"`.** Antes no:
   se resolvía dejando el campo fuera de la sentencia, y eso sólo funcionaba
   en las secciones, que se actualizan con `ON DUPLICATE KEY UPDATE`. Los
   bloques se borran y se reinsertan, así que el campo omitido acababa en
   `NULL`; y `"datos"` se escribe de una pieza, así que el marcador se
   guardaba tal cual. El texto legal del consentimiento de voluntarios llegó
   a decir literalmente «@bd» en la base por esto.

Si un `"@bd"` no encuentra su equivalente en producción, el generador lo avisa
por pantalla en vez de dejarlo pasar en silencio.

Los bloques se emparejan con los de producción por su `"slug"`; si no lo
tienen, por la posición que ocupan dentro de la sección.
