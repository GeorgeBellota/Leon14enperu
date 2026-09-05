# Despliegue de la Colecta Nacional

Todo lo que hace falta para llevar a producción la Colecta Nacional, el
carrusel de cinco láminas y la reestructuración de `/donativo/`.

Son **cinco migraciones**, de la `0016` a la `0020`, y un despliegue de código.
Probadas de principio a fin sobre una base recién instalada, y ejecutadas dos
veces seguidas para comprobar que repetirlas no duplica ni pisa nada.

---

## Lo que cambia

**En la portada.** El carrusel pasa de tres a cinco láminas, con dos
composiciones que se alternan y que se eligen lámina a lámina desde el panel.
Y hay una sección nueva, «Colecta Nacional», con las dos cuentas oficiales,
colocada detrás de «Cinco santos, un mismo corazón».

**En `/donativo/`.** La página decía «Todavía no hay canal de donativos
abierto» mientras la portada publicaba dos cuentas. Ahora dice que la colecta
está abierta, muestra las mismas cuentas y conserva el aviso contra las cuentas
falsas.

**Las cuentas están en un solo sitio.** Viven en la sección `colecta` de la
portada, y `/donativo/` las lee de ahí con el mismo parcial. No hay una segunda
copia: se corrige un dígito en el panel y cambia en las dos páginas.

---

## Todo es administrable

Nada de esta página se queda fuera del panel. En **Páginas → Donativo**:

| Sección | Qué se edita |
|---|---|
| Cabecera | Rótulo, titular, bajada y la fotografía, con su variante de móvil |
| Estado | Rótulo, titular y los dos párrafos |
| A qué se destinan | Rótulo, titular, entradilla y las tres tarjetas, con su icono |
| Avísame | Rótulo, titular y el texto |

Y en **Páginas → Inicio → Colecta Nacional**: el rótulo, el titular, el
subtítulo, el texto, el encabezado que se ve en `/donativo/`, la nota al pie y
las dos cuentas con su titular, su número y su CCI.

Las láminas del carrusel, en **Páginas → Inicio → Carrusel de portada**. Cada
una tiene un campo «Diseño de la lámina»: vacío para la composición partida,
`fondo` para la fotografía a sangre con el texto encima. Cualquier otra cosa
cae en la partida, así que una errata no rompe la portada.

Lo único que sigue en el código son los textos de respaldo, que sólo se pintan
si la base no responde.

---

## El orden importa

**Primero el código, después las migraciones.** Las migraciones `0017` y `0020`
dejan dos secciones usando plantillas —`colecta` y `destinos_aporte`— que se
declaran en `app/Cms/Plantillas.php`. Si la base va por delante del código, el
panel abre esas secciones y no sabe dibujarlas.

### 1 · Copia de seguridad

```bash
mysqldump -u USUARIO -p --single-transaction --default-character-set=utf8mb4 \
  BASE > ~/antes-colecta-$(date +%F).sql
```

### 2 · Subir el código

```
assets/css/components.css
assets/css/pages.css
assets/css/sections.css
assets/js/colecta.js                     (nuevo)
assets/js/hero.js
assets/js/main.js
assets/parciales/cabecera.php
assets/parciales/colecta.php             (nuevo)
assets/img/fotos/cab-colecta-640.jpg     (nuevo, y sus cinco hermanos)
assets/img/fotos/cab-colecta-640.webp
assets/img/fotos/cab-colecta-1024.jpg
assets/img/fotos/cab-colecta-1024.webp
assets/img/fotos/cab-colecta-1486.jpg
assets/img/fotos/cab-colecta-1486.webp
assets/img/leon14enperu-colecta-3.png    (nuevo, el original del banner)
assets/img/san-francisco-solano.jpg      (nuevo, y los otros cuatro santos)
assets/img/san-juan-macias.jpg
assets/img/san-martin.avif
assets/img/santa-rosa.webp
assets/img/Santo-Toribio-Mogrovejo.jpg
intranet/app/Cms/Plantillas.php
views/_plantilla.php
views/donativo.php
views/portada.php
```

Ojo con `Santo-Toribio-Mogrovejo.jpg`: lleva mayúsculas y el servidor es Linux,
donde el nombre las distingue. Si se sube en minúsculas, ese retrato no carga.

### 3 · Comprobar antes de tocar la base

```bash
mysql -u USUARIO -p BASE < intranet/database/comprobar-antes-de-migrar.sql
```

No cambia nada: sólo mira. Hay que leer la columna `veredicto` de cada bloque.

Dos cosas merecen atención especial:

- **El bloque 5** enseña los textos de `/donativo/` tal como están ahora. Si
  alguien los editó desde el panel, la migración `0019` los reemplaza. El
  veredicto lo dice explícitamente.
- **El bloque 7** deja el recuento previo de voluntarios, usuarios y páginas.
  Anótalo: después tiene que dar exactamente lo mismo.

### 4 · Aplicar

```bash
cd intranet
php database/migrate.php --estado    # enseña qué falta, sin tocar nada
php database/migrate.php             # aplica 0016, 0017, 0018, 0019 y 0020
```

Las aplica en orden y las marca. Ninguna lleva `DELETE`, `TRUNCATE`, `DROP` ni
`ALTER`: sólo `INSERT` con guarda y `UPDATE` sobre campos concretos.

### 5 · Comprobar después

```bash
mysql -u USUARIO -p BASE < intranet/database/comprobar-despues-de-migrar.sql
```

Todos los veredictos tienen que decir `ok`. Lo que se espera:

| | Antes | Después |
|---|---|---|
| voluntarios | *el que sea* | **el mismo** |
| usuarios | *el que sea* | **el mismo** |
| paginas | *el que sea* | **el mismo** |
| secciones | *n* | *n* + 1 |
| bloques | *m* | *m* + 7 |

Las siete piezas nuevas son dos láminas del carrusel, las dos cuentas y las
tres tarjetas de destino.

**Y una comprobación que no puede hacer ningún script: leer los cuatro números
de las cuentas y contrastarlos con el comunicado de la Conferencia Episcopal,
dígito a dígito.** El script comprueba que cada CCI contenga por dentro su
número de cuenta —lo que pilla el dedazo de cambiar uno y no el otro— pero no
puede saber si los dos están mal.

### 6 · Mirar la web

- `https://leon14enperu.com/` — cinco láminas, la cuarta lleva a `#colecta`.
- `https://leon14enperu.com/#colecta` — las dos cuentas.
- `https://leon14enperu.com/donativo/` — el banner nuevo, «La Colecta Nacional
  está abierta», las cuentas con sus botones de copiar y las tres tarjetas.
- Y una inscripción de prueba en `/voluntariado/`, **con y sin JavaScript**,
  que es la regla 1 del encargo y no depende de que este cambio parezca
  inofensivo.

Los botones de copiar sólo aparecen sobre HTTPS: el portapapeles del navegador
no está disponible en conexiones sin cifrar. En producción se verán.

---

## Si hay que volver atrás

El código se revierte con el despliegue anterior. Para la base:

```bash
mysql -u USUARIO -p BASE < ~/antes-colecta-FECHA.sql
```

No hay migraciones de reversa a propósito. Una migración que deshace es otra
migración que puede fallar a medias; la copia de seguridad no.

Si sólo hace falta **quitar la colecta de la vista sin tocar nada más**, se
apaga desde el panel: Páginas → Inicio → Colecta Nacional → quitar «Visible».
La sección deja de pintarse en las dos páginas y las cuentas siguen guardadas.

---

## Lo que queda pendiente y no es de programación

La página `/donativo/` ya no se contradice con la portada. Lo que sigue sin
existir es el **procedimiento para pedir constancia del aporte**, que el propio
texto de la página promete. Cuando la Conferencia Episcopal lo publique, se
escribe desde el panel en la sección «Avísame» y en el texto del estado, sin
tocar código.
