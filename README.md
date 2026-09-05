# leon14enperu.com

Sitio oficial del **viaje apostólico del Papa León XIV al Perú**, del 11 al 16
de noviembre de 2026, y la intranet que lo administra.

Encargo de la **Conferencia Episcopal Peruana**. En producción desde agosto de
2026, con más de 35 000 voluntarios inscritos a través del formulario.

---

## Qué es esto, en una pantalla

Dos cosas dentro del mismo proyecto:

**El sitio público.** Veinticuatro páginas —la portada, el itinerario, las
cuatro sedes, los cinco santos, las noticias, los obispos, las trece comisiones
episcopales, el voluntariado— y el formulario de inscripción, que es la pieza
crítica: por ahí han entrado 35 000 personas.

**La intranet.** El gestor de contenidos con el que la CEP edita todo eso sin
tocar código, más la administración de las inscripciones.

### Cómo está hecho

**PHP 8 a secas.** Sin Composer, sin framework, sin Node, sin nada que
compilar. Se clona y funciona.

No es nostalgia: el sitio tiene que seguir en pie el día que el Papa aterrice,
y cada dependencia es algo que puede romperse el peor día. Lo que hay es un
enrutador propio, un autocargador PSR-4 de treinta líneas y un puñado de
clases.

| | |
|---|---|
| **Un solo punto de entrada** | `index.php` resuelve todas las direcciones contra un mapa de rutas escrito en código. Las páginas viven en `views/`, sin un `index.php` por carpeta. |
| **El contenido está en la base** | Las páginas, las secciones y las fichas se editan desde el panel. Las vistas leen de ahí y llevan un texto de respaldo por si la base no responde. |
| **Direcciones propias por ficha** | Cada sede, cada santo, cada obispo y cada noticia tiene su página: `/sedes/chiclayo/`, `/tierra-de-santos/santa-rosa-de-lima/`. El slug se calcula del título la primera vez y **no vuelve a cambiar solo**. |
| **Datos personales cifrados** | El DNI, el teléfono y la dirección se guardan con AES-256-GCM. El DNI además lleva un índice ciego para poder buscarlo sin descifrarlo. |
| **Imágenes servidas por tamaño** | Cada foto tiene su familia de anchos en webp y jpg, y una versión aparte para móvil. El navegador pide la que le toca. |

### Cómo se sirve

| | Tu máquina | Producción |
|---|---|---|
| Servidor | Apache | Nginx |
| Reglas | los `.htaccess` del proyecto | el `vhost` del servidor |
| Modelo | se permite salvo lo prohibido | **lista blanca** |

En producción **sólo cinco archivos PHP pueden ejecutarse**. Cualquier otro
`.php` que dejes suelto funcionará en tu máquina y dará 404 en el servidor. No
es un fallo: es lo que hace que un archivo subido por error no se ejecute.

La configuración completa y comentada está en
[`documentacion/vhost-produccion.conf`](documentacion/vhost-produccion.conf).

---

## Levantarlo en Laragon

**Laragon trae Apache y Nginx, y arranca con Apache**, que es lo que necesitan
los `.htaccess`. Déjalo así.

### Lo único que suele dar guerra

**Laragon viene con MySQL, y esto necesita MariaDB.**

Tres migraciones usan `ADD COLUMN IF NOT EXISTS`, sintaxis que MySQL no acepta.
Se cambia en *Menú → MySQL → Version →* eligiendo una MariaDB. El instalador lo
comprueba y se niega a seguir si te encuentra en MySQL, así que no vas a
enterarte tarde.

### 1 · Clonar

```bash
cd C:\laragon\www
git clone https://github.com/GeorgeBellota/Leon14enperu.git leon14peru
```

Laragon crea solo el dominio `leon14peru.test`. Si no aparece,
*Menú → Apache → Reload*.

### 2 · Crear la base, vacía

```sql
CREATE DATABASE leon14peru
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### 3 · La configuración local

```bash
cd leon14peru\intranet\config
copy config.local.example.php config.local.php
```

Genera la clave de cifrado y pégala en el archivo:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Ajusta también las direcciones:

```php
'url' => [
    'sitio' => 'http://leon14peru.test',
    'panel' => 'http://leon14peru.test/intranet/public',
],
```

### 4 · Instalar la base

```bash
cd ..                       # …\leon14peru\intranet
php database/instalar.php
```

Un comando. Comprueba la versión de PHP, las siete extensiones, la conexión y
que sea MariaDB; después carga la estructura, los catálogos, el ubigeo del Perú
y **el contenido real del sitio**.

```
 páginas           24        distritos      1 874
 secciones        105        servicios          6
 fichas            85        voluntarios       10
 ajustes           19
```

Los diez voluntarios son **inventados**. Las 35 000 inscripciones reales no
salen de producción, y no hacen falta para trabajar.

### 5 · Tu usuario del panel

```bash
php database/crear-admin.php "Tu Nombre" tu@correo.pe
```

### 6 · Abrir

| | |
|---|---|
| La web | http://leon14peru.test |
| El panel | http://leon14peru.test/intranet/public |

### Comprobar que quedó bien

```bash
curl -s -o nul -w "%{http_code}\n" http://leon14peru.test/
curl -s -o nul -w "%{http_code}\n" http://leon14peru.test/voluntariado/
curl -s -o nul -w "%{http_code}\n" http://leon14peru.test/intranet/config/config.local.php
```

**200**, **200** y **403**. Si la tercera devuelve el contenido del archivo,
Apache está ignorando los `.htaccess`.

> **Con XAMPP** es igual, y más fácil: ya trae MariaDB. El proyecto queda en
> `http://localhost/leon14peru/` y las direcciones de `config.local.php`
> cambian en consecuencia.
>
> La guía completa, con Docker y los problemas frecuentes, está en
> [`documentacion/INSTALACION.md`](documentacion/INSTALACION.md).

---

## Cómo está repartido

```
index.php              el controlador frontal: resuelve TODAS las direcciones
views/                 una vista por página, más coleccion.php y detalle.php
                       que sirven las páginas generadas por slug
assets/                css, js, imágenes y los parciales de cabecera y pie
intranet/
  app/                 el código del panel y del sitio público
  config/              configuración (config.local.php no se versiona)
  database/            instalación, migraciones y utilidades de consola
  public/              el único directorio del panel accesible por web
documentacion/         cómo instalarlo, la arquitectura y el vhost
```

---

## Cinco reglas que no se negocian

Vienen del encargo y de lo que ya ha pasado. Merecen leerse antes del primer
cambio.

**1 · El formulario de voluntariado no puede romperse.** Es lo único que la web
tiene que hacer sí o sí. Cada despliegue exige una inscripción real de prueba,
**con y sin JavaScript**. Se ha roto en silencio más de una vez: la página
respondía 200 y el envío se rechazaba.

**2 · El retrato del Santo Padre nunca lleva filtro.** Ni duotono, ni velo, ni
degradado, ni parallax, ni un recorte que le pase por la cara. Si cambias una
fotografía, comprueba el encuadre; el recorte no se rehace solo.

**3 · Nada inventado.** Ni una cita del Papa o de un santo, ni una cifra, ni un
horario, ni un aforo. Lo que todavía no es oficial se dice que no lo es: el
itinerario de la portada lleva su aviso de «programa referencial» y ese aviso
no se quita hasta que la Santa Sede publique el programa.

**4 · Los slugs no se cambian.** La dirección de una ficha se calcula del
título una vez. Cambiarla rompe los enlaces ya compartidos e indexados.

**5 · La clave de cifrado no se pierde ni se sube.** Vive en
`config.local.php`, fuera del repositorio. Sin ella, los DNI, los teléfonos y
las direcciones de 35 000 personas quedan ilegibles para siempre.

---

## Lo que sigue pendiente

- **Los textos legales.** `/privacidad/` y `/aviso-legal/` conservan
  marcadores entre corchetes, y también el consentimiento que aceptan los
  voluntarios. Hacen falta cuatro datos de la organización; no es trabajo de
  programación y es lo más urgente de la lista.
- **El mapa de las 46 jurisdicciones eclesiásticas** y las fichas de los
  obispos —hay tres de cuarenta y seis—. Bloqueado esperando los datos y la
  cartografía de la CEP.
- **Las fotografías** de los santos y de la presidencia. Mientras no estén, se
  pinta un medallón con la inicial.
- **La carta de la Conferencia Episcopal** al pie de la portada es un borrador
  del estudio, pendiente de revisión y firma.
