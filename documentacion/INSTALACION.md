# Cómo levantar el proyecto en tu máquina

leon14enperu.com es PHP a secas —sin Composer, sin Node, sin compilar nada— con
una base MariaDB. Se levanta en diez minutos.

Esta guía está escrita para Laragon, que es lo más probable, y trae al final
las variantes para XAMPP y para Docker.

---

## 1 · Lo que hace falta

| | | |
|---|---|---|
| **PHP** | 8.1 o superior | Producción va con 8.3. Por debajo de 8.0 no arranca: el código usa `match`. |
| **MariaDB** | 10.4 o superior | Producción va con 11.8.6. **No sirve MySQL**: ver el apartado 2, es lo único que suele dar guerra. |
| **Apache** | con `mod_rewrite` | Las direcciones limpias salen de los `.htaccess`. |

**Extensiones de PHP**, todas incluidas de serie en Laragon y en XAMPP:

```
pdo_mysql   mbstring   gd   openssl   dom   json   iconv
```

`gd` es para las miniaturas y las versiones webp de las imágenes que se suben
desde el panel. `openssl` cifra los DNI y los teléfonos de los voluntarios.
Sin `dom` no se puede sanear el HTML que llega del gestor de contenidos.

No hace falta `intl`, ni `curl`, ni `zip`.

---

## 2 · ¿Laragon vale? Sí, pero cambia la base de datos

**Laragon trae Apache y Nginx, y arranca con Apache**, que es justo lo que
necesitan los `.htaccess`. Se cambia de uno a otro en
*Menú → Apache/Nginx*; **déjalo en Apache**.

El problema es el otro: **Laragon viene con MySQL 8, y este proyecto necesita
MariaDB.**

### Por qué no vale MySQL

Tres migraciones usan sintaxis que sólo existe en MariaDB:

```sql
ALTER TABLE `secciones` ADD COLUMN IF NOT EXISTS `imagen_movil_id` ...
ALTER TABLE `secciones` ADD KEY    IF NOT EXISTS `fk_secciones_imagen_movil` ...
```

MySQL **no acepta `IF NOT EXISTS` en `ADD COLUMN`**. En MariaDB sí, y es lo que
permite que una migración se pueda ejecutar dos veces sin romperse — una
propiedad de la que depende todo el proceso de despliegue de este proyecto.

Si lo ejecutas contra MySQL, las migraciones `0010`, `0013` y `0015` fallan con
un error de sintaxis. No es que se apliquen a medias: fallan.

### Cambiar Laragon a MariaDB

1. *Menú → Herramientas → Quick add → MariaDB*
   (o descarga MariaDB 10.6+ y descomprímela en `C:\laragon\bin\mysql\`)
2. *Menú → MySQL → Version →* elige la carpeta de MariaDB
3. Reinicia Laragon

Comprueba que quedó bien:

```bash
mysql -u root -e "SELECT VERSION();"
```

Tiene que decir algo con **MariaDB**. Si dice `8.0.x` sin más, sigues en MySQL.

---

## 3 · Puesta en marcha, paso a paso

### 3.1 · Clonar

```bash
cd C:\laragon\www
git clone <url-del-repositorio> leon14peru
```

Laragon crea solo el dominio **`leon14peru.test`** apuntando a esa carpeta.
Si no aparece, *Menú → Apache → Reload*.

> El proyecto funciona igual colgando de una subcarpeta
> (`localhost/leon14peru/`) que en la raíz de un dominio
> (`leon14peru.test`). El `.htaccess` calcula solo el prefijo comparando la
> dirección pedida con la ruta física, así que no hay que tocar nada.

### 3.2 · Crear la base, vacía

```sql
CREATE DATABASE leon14peru
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

**Vacía. No pidas el volcado de producción.** Ver el apartado 5.

### 3.3 · La configuración local

```bash
cd C:\laragon\www\leon14peru\intranet\config
copy config.local.example.php config.local.php
```

Genera la clave de cifrado:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Y rellena `config.local.php`:

```php
return [
    'app' => [
        'entorno' => 'desarrollo',
        'clave'   => 'LA-CLAVE-QUE-ACABAS-DE-GENERAR',
    ],
    'bd' => [
        'host'    => '127.0.0.1',
        'base'    => 'leon14peru',
        'usuario' => 'root',
        'clave'   => '',
    ],
    'url' => [
        'sitio' => 'http://leon14peru.test',
        'panel' => 'http://leon14peru.test/intranet/public',
    ],
];
```

> Este archivo **no está en el repositorio** y no debe estarlo: en producción
> contiene la clave con la que se descifran los datos personales de 35 000
> voluntarios. La tuya es tuya y sólo sirve para tu base local.

### 3.4 · Instalar la base

Un solo comando:

```bash
cd C:\laragon\www\leon14peru\intranet
php database/instalar.php
```

Antes de tocar nada comprueba la versión de PHP, las siete extensiones, que la
base exista y que sea **MariaDB y no MySQL**. Si algo falta, lo dice y para.

Después carga los cinco archivos de `database/instalacion/` y te deja:

```
 páginas           24
 secciones        105
 fichas            85
 ajustes           19
 distritos      1 874
 servicios          6
 voluntarios       10
```

Es la base de producción real —el carrusel, el itinerario, las cuatro sedes,
los cinco santos, las noticias, los obispos, las trece comisiones— con los
**diez voluntarios inventados** en lugar de las 35 018 personas reales.

> **No borra nada por accidente.** Se niega a arrancar si la base ya tiene
> inscripciones, y se niega otra vez si el entorno no dice `desarrollo`. Para
> reinstalar de cero: `php database/instalar.php --forzar`.

Lo que hay dentro de cada archivo está explicado en
`database/instalacion/LEEME.md`.

### 3.5 · Crear tu usuario del panel

```bash
php database/crear-admin.php "Tu Nombre" tu@correo.pe
```

Pide la contraseña por pantalla, sin mostrarla. Con `--generar` inventa una de
un solo uso y obliga a cambiarla al entrar.

### 3.6 · Abrir

| | |
|---|---|
| La web | http://leon14peru.test |
| El panel | http://leon14peru.test/intranet/public |

---

## 4 · Comprueba que quedó bien

```bash
# la portada y el formulario responden
curl -s -o nul -w "%{http_code}\n" http://leon14peru.test/
curl -s -o nul -w "%{http_code}\n" http://leon14peru.test/voluntariado/

# las direcciones limpias funcionan → mod_rewrite está activo
curl -s -o nul -w "%{http_code}\n" http://leon14peru.test/sedes/

# y las carpetas privadas están cerradas → tiene que dar 403
curl -s -o nul -w "%{http_code}\n" http://leon14peru.test/intranet/config/config.local.php
```

Las tres primeras: **200**. La última: **403**. Si la última devuelve el
contenido del archivo, Apache está ignorando los `.htaccess`: revisa que el
`AllowOverride` del directorio sea `All` (en Laragon y XAMPP lo es por
defecto).

Ojo: `/sedes/` responde 404 si esa página está oculta en el panel, y eso es
correcto, no un fallo. Publícala desde *Intranet → Páginas* para probarla.

---

## 5 · Lo que NO hay que hacer

**No copies la base de producción a tu máquina.**

Contiene los nombres, los correos y los DNI cifrados de más de 35 000 personas
reales. Un volcado en la carpeta del proyecto se sirve por HTTP: cualquiera que
alcance tu puerto 80 se lo descarga entero. Ya pasó una vez en este proyecto.

Para trabajar no hace falta: las migraciones dejan el sitio con todo su
contenido —las páginas, las secciones, las fichas— y la tabla de voluntarios
vacía, que es exactamente lo que necesitas.

Si en algún momento necesitas datos de verdad para reproducir un fallo, pide un
extracto anonimizado, nunca el volcado completo.

---

## 6 · Apache aquí, Nginx allá

Vale la pena saberlo porque explica por qué algo puede funcionar en tu máquina
y no en el servidor.

| | Local (Laragon / XAMPP) | Producción |
|---|---|---|
| Servidor | Apache | Nginx |
| Reglas | los `.htaccess` del proyecto | el `vhost` del servidor |
| Modelo | se permite salvo lo prohibido | **lista blanca**: sólo se ejecutan los puntos de entrada declarados |

Nginx **no lee los `.htaccess`**. En producción esas mismas reglas están
escritas en la configuración del servidor, y son más estrictas: sólo **cinco**
archivos PHP pueden ejecutarse, y están declarados uno a uno:

```
/index.php                  el sitio público entero
/ubigeo.php                 departamentos, provincias y distritos
/comunicado.php             las descargas del panel
/intranet/index.php         el redirector del panel
/intranet/public/index.php  el panel
```

Cualquier otro `.php` que dejes suelto en el proyecto **funcionará en tu
máquina y dará 404 en el servidor**. No es un fallo: es el diseño, y es lo que
hace que un archivo subido por error no se pueda ejecutar.

La configuración completa, comentada, está en
[`vhost-produccion.conf`](vhost-produccion.conf).

No es un problema si lo sabes: no crees puntos de entrada nuevos sin avisar.

Los `.htaccess` que hay repartidos por `intranet/` no son decorativos —cierran
`config/`, `app/`, `database/` y `storage/`— y **se versionan a propósito**. Si
alguna vez desaparecen de una copia del proyecto, esas carpetas quedan
abiertas.

---

## 7 · XAMPP

Igual que Laragon, con dos diferencias a favor:

- **XAMPP ya trae MariaDB**, así que el apartado 2 no aplica.
- No crea dominios solo. El proyecto queda en `http://localhost/leon14peru/`,
  y en `config.local.php` las direcciones son:

```php
'url' => [
    'sitio' => 'http://localhost/leon14peru',
    'panel' => 'http://localhost/leon14peru/intranet/public',
],
```

El resto de los pasos, idéntico.

---

## 8 · Docker

No hay imagen preparada. Si la montas, que sea:

- `php:8.3-apache` con `a2enmod rewrite headers` y `AllowOverride All`
- `mariadb:10.6` — **mariadb, no mysql**
- extensiones: `pdo_mysql gd`
  (las demás vienen de serie en la imagen oficial)
- el proyecto montado en `/var/www/html`

Con `php:8.3-fpm` + Nginx te acercas más a producción, pero entonces necesitas
también el `vhost`, y sin él no funciona ninguna dirección limpia.

---

## 9 · Cuando algo no arranca

**Todas las páginas dan 404 menos la portada**
mod_rewrite está apagado, o `AllowOverride` no es `All`. En Laragon,
*Menú → Apache → mod_rewrite*.

**Las migraciones fallan con un error de sintaxis en `ADD COLUMN IF NOT EXISTS`**
Estás en MySQL. Apartado 2.

**«No se pudo conectar a la base»**
Revisa `bd` en `config.local.php`. En Laragon el usuario es `root` sin
contraseña, igual que en XAMPP.

**El panel entra pero se ve sin estilos**
Es la caché del navegador. Las direcciones del CSS del panel llevan `?v=` con
un hash del archivo, así que no debería pasar; si pasa, recarga con Ctrl+F5.

**Las imágenes que subo desde el panel no se ven**
Falta `gd`, o `assets/subidos/` no tiene permiso de escritura.

**Sale «clave de aplicación no configurada»**
No copiaste `config.local.example.php` a `config.local.php`, o dejaste la clave
con el texto de ejemplo.

---

## 10 · Cómo está repartido el código

```
index.php              el controlador frontal: resuelve TODAS las direcciones
views/                 una vista por página, más coleccion.php y detalle.php
                       que sirven las páginas generadas por slug
assets/                css, js, imágenes y los parciales de cabecera y pie
intranet/
  app/                 el código del panel y del sitio público
  config/              configuración (config.local.php no se versiona)
  database/            schema.sql, migraciones y utilidades de línea de comandos
  public/              el único directorio del panel accesible por web
```

El contenido de las páginas **no está en el HTML**: vive en la base y se edita
desde el panel. Las vistas leen de ahí y llevan un texto de reserva por si la
base no responde.
