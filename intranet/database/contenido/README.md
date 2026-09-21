# Contenido del rediseño 2026

Un archivo por página. Describe qué secciones y bloques tiene y con qué texto,
para que la migración pueda cargarlos sin escribir SQL a mano.

- `"imagen"` es una clave de `assets/img/rediseno/variantes.json`, con la forma
  `"carpeta/base"` — por ejemplo `"prensa/hero"` o `"index/p13"`.
- `"@bd"` en un campo significa «conserva lo que ya hay en la base». Se usa
  donde el editable de Illustrator trae texto de relleno («Lorem ipsum»,
  «XX:00 Hrs.», preguntas sin respuesta) y producción tiene el texto real.
- Las secciones que la página nueva ya no pinta no se listan aquí. La migración
  **las apaga (`activa = 0`), no las borra**: el sitio queda igual —ninguna
  vista dibuja una sección inactiva— pero el texto sigue en el panel y se
  recupera con un interruptor. Borrarlas se llevaba por delante contenido que
  sólo existía ahí, y sus bloques y su historial de versiones por cascada, sin
  posibilidad de volver atrás salvo restaurando una copia.
- `"conservar"` es la lista de secciones que **ni se apagan ni se tocan**,
  porque las lee otra página aunque la suya ya no las dibuje. Hoy sólo contiene
  `colecta`, en `home.json`: es la única copia en la base de las cuentas
  bancarias de la Colecta Nacional, y `/donativo/` las lee de ahí.

De estos archivos sale `migrations/0027_rediseno_2026.sql`.

## Cómo se resuelve `"@bd"`

**No lo resuelve MySQL.** La migración que sale de aquí no lleva ningún
`"@bd"`: lleva ya escrito el texto que tenía producción. El marcador se
sustituye al generar, leyendo una copia de la base de producción, y por eso la
migración se puede aplicar tal cual sobre una base vacía y da el mismo
resultado.

Esto importa si algún día se vuelve a generar el SQL desde estos archivos:

1. **La sustitución necesita una copia de producción a mano.** Sin ella no hay
   con qué rellenar los `"@bd"`, y el generador avisa por pantalla de cada uno
   que se quede sin resolver en vez de dejarlo pasar en silencio.
2. **Los bloques se emparejan con los de producción por su `"slug"`**; si no lo
   tienen, por la posición que ocupan dentro de la sección.

### Por qué no se resuelve dejando el campo sin escribir

Era lo que se hacía antes, y sólo funciona a medias:

- En las **secciones** sí, porque se actualizan con `ON DUPLICATE KEY UPDATE`:
  una columna que no se menciona se queda como estaba.
- En los **bloques** no. Se borran y se vuelven a insertar, así que la columna
  que no se menciona acaba en `NULL` y el texto de producción se pierde.
- Dentro de **`"datos"`** tampoco, porque esa columna se escribe entera de una
  pieza: el marcador se guardaba tal cual, como si «@bd» fuera el texto. El
  consentimiento legal de voluntarios llegó a decir literalmente «@bd» en la
  base por esto.
