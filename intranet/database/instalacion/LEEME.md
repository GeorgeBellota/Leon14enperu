# La base de partida

Estos cinco archivos dejan una base de datos igual a la de producción, con el
contenido real del sitio y **sin un solo dato de una persona real**.

No los ejecutes a mano. Hay un comando que los carga en orden y comprueba
antes que todo esté en su sitio:

```bash
cd intranet
php database/instalar.php
```

---

## Con qué versiones

| | |
|---|---|
| **Producción** | MariaDB 11.8.6 · PHP 8.3 · Nginx |
| **Desarrollo** | MariaDB 10.4 · PHP 8.1 · Apache (XAMPP) |
| **Mínimo** | MariaDB 10.4 · PHP 8.0 |

**MariaDB, no MySQL.** Tres migraciones del proyecto usan
`ADD COLUMN IF NOT EXISTS`, que MySQL rechaza. El instalador lo comprueba y se
niega a seguir si te encuentra en MySQL.

Los archivos se generaron con el cliente de **MariaDB 10.4** a propósito: usa
la sintaxis más conservadora y entra igual en 10.4 que en 11.8.

---

## Qué trae cada uno

| Archivo | Qué carga |
|---|---|
| `01-estructura.sql` | Las 23 tablas, tal como están en producción con las quince migraciones aplicadas. |
| `02-catalogos.sql` | Roles, permisos, jurisdicciones, los seis servicios y el ubigeo completo: 25 departamentos, 196 provincias, 1 874 distritos. |
| `03-contenido.sql` | Las 24 páginas con sus 105 secciones y sus 85 fichas, más los 19 ajustes y los dos comunicados. |
| `04-voluntarios-ejemplo.sql` | Diez fichas de voluntario **inventadas**. |
| `05-migraciones.sql` | Marca las quince migraciones como aplicadas. |

Después hace falta un usuario para entrar al panel:

```bash
php database/crear-admin.php "Tu Nombre" tu@correo.pe
```

---

## Los diez voluntarios son falsos, y es a propósito

En producción hay **35 018 inscripciones de personas reales**, con su nombre y
su correo en claro en la base. Nada de eso entra en un repositorio —ni diez
filas: serían diez personas de verdad—.

Con diez fichas inventadas se ve todo lo que hay que ver en el panel: el
listado, los filtros, los estados, la ficha completa y la exportación a CSV.

### Por qué las columnas cifradas llevan texto legible

El DNI, el teléfono y la dirección se guardan cifrados con la clave de cada
instalación. Un criptograma de producción no se podría descifrar en tu máquina,
porque tu clave es otra.

En su lugar llevan una frase que dice lo que es:

```
(DNI de ejemplo, sin cifrar: 90000001)
```

No rompe nada. `Cripto::descifrar()` devuelve el valor tal cual cuando no
reconoce el prefijo `v1:` —está pensado así para que un dato anterior al
cifrado no tumbe la pantalla del listado—, y de paso queda claro de un vistazo
que la ficha es de mentira. Los DNI van del 90000001 al 90000010, fuera del
rango que emite el RENIEC.

---

## Qué NO se siembra, y por qué

**La biblioteca de imágenes (`medios`) se queda vacía.** En producción tiene
una sola fila que apunta a un archivo de `assets/subidos/`, y esa carpeta no se
versiona: las subidas del panel son datos, no código. Sembrarla dejaría una
imagen rota. Además no la usa ninguna sección ni ninguna ficha.

Las páginas se ven igual: cada una lleva su fotografía de respaldo escrita en
la vista. Cuando subas imágenes desde el panel, esas sustituyen al respaldo.

**Los dos comunicados entran apagados**, por lo mismo: su cartel se subió desde
el panel. Se ven en la intranet y se pueden editar; para probar el aviso
emergente, sube un cartel y enciéndelos.

**Tampoco se siembran** los usuarios (creas el tuyo), ni la auditoría, ni los
intentos de acceso, ni el historial de secciones: son el registro de lo que
pasó en producción, no contenido.

---

## Volver a empezar

`01-estructura.sql` lleva `DROP TABLE` delante de cada tabla, así que
reinstalar **borra lo que haya**.

El instalador no lo hace por accidente: se niega si encuentra inscripciones, y
se niega otra vez si `config.local.php` no dice `'entorno' => 'desarrollo'`.
Para forzarlo:

```bash
php database/instalar.php --forzar
```

---

## Cómo se regeneran estos archivos

Salen de un volcado de producción, que **nunca se guarda dentro del
proyecto**: se descarga a una carpeta de fuera, se importa en una base
auxiliar y de ahí se genera todo esto con los datos personales sustituidos.

Si vuelves a hacerlo, dos cosas:

- El volcado de MariaDB 11.8 empieza con `/*M!999999\- enable the sandbox mode */`.
  Los clientes de MariaDB 10.x no entienden esa línea y fallan con
  `Unknown command '\-'`. Se salta con `tail -n +2`.
- Borra el volcado y la base auxiliar al terminar. Son 28 MB con los datos de
  35 000 personas.
