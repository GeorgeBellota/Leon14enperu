-- ===========================================================================
--  0042 · PRENSA — «USO DE IMÁGENES Y DEL ESCUDO»: TEXTOS Y ENLACES OFICIALES
-- ---------------------------------------------------------------------------
--  La CEP mandó los enlaces definitivos de cada elemento descargable, cambió
--  la tercera fila —«Citas» pasa a «Mensajes, cartas y encíclicas»— y acortó
--  tres de los cuatro subtextos.
--
--  ── Esto es contenido, no código ─────────────────────────────────────────
--
--  La plantilla «Texto con apartados» ya declara un destino por apartado
--  (`enlace_url`), y la página pública ya convierte el titular en enlace
--  cuando lo hay: si el destino es de fuera, lo abre en otra pestaña con
--  rel="noopener noreferrer". No hace falta tocar ni la vista ni el CSS. Los
--  tres apartados de /subsidios/ llevan funcionando así desde septiembre.
--
--  Por eso esto se puede correr solo, sin desplegar nada más.
--
--  ── Los subtextos van sin saltos de línea a mano ─────────────────────────
--
--  Los de antes llevaban un «\n» puesto para forzar el corte exacto del
--  mockup a 1440 px. Los nuevos son más cortos y se dejan corridos: que los
--  parta el navegador donde toque. Un corte escrito a mano sólo acierta en
--  un ancho, y en los demás deja una línea coja.
--
--  ── Lo que este archivo NO arregla ───────────────────────────────────────
--
--  «Mensajes, cartas y encíclicas» pide 449 px y la columna amarilla está
--  fijada en 322 px, así que el rótulo queda en dos líneas. En la maqueta de
--  la CEP va en una, porque allí la columna es más ancha (~480 px). Eso es
--  una línea de `prensa.css` —la variable `--dl` de `.pr-usos__lista`— y se
--  dejó fuera a propósito: aquí sólo va contenido. Nada se rompe ni se
--  solapa; el bloque mide lo mismo con el rótulo en una línea o en dos.
--
--  ── Tres de los cuatro enlaces son la misma carpeta ──────────────────────
--
--  Retrato pontificio, Escudo pontificio y Kit de prensa apuntan los tres a
--  la carpeta «MEDIA KIT PARA PRENSA» de Google Drive, que es lo que mandó
--  la CEP. Si más adelante pasan las subcarpetas, cada fila puede apuntar a
--  la suya desde el gestor, sin tocar esto.
--
--  ── Se puede correr dos veces ────────────────────────────────────────────
--
--  Las filas se buscan por su `orden` dentro de la sección, no por su id ni
--  por su título: el de la tercera es justamente lo que cambia.
-- ===========================================================================

SET @seccion := (
    SELECT s.id
      FROM secciones s
      JOIN paginas p ON p.id = s.pagina_id
     WHERE p.clave = 'prensa'
       AND s.clave = 'uso-imagenes-escudo'
     LIMIT 1
);

SET @drive := 'https://drive.google.com/drive/folders/1uAjlbJusqz7huZ-CL2jEPftWSSKwq7aQ';

-- 1 · Retrato pontificio ----------------------------------------------------
UPDATE `bloques`
   SET `texto`      = 'Sin recortes sobre el rostro, sin filtros de color y sin texto superpuesto. Crédito: Santa Sede.',
       `enlace_url` = @drive
 WHERE `seccion_id` = @seccion AND `orden` = 10;

-- 2 · Escudo pontificio -----------------------------------------------------
UPDATE `bloques`
   SET `texto`      = 'Conserva siempre sus esmaltes propios. No se recorta o deforma.',
       `enlace_url` = @drive
 WHERE `seccion_id` = @seccion AND `orden` = 20;

-- 3 · «Citas» → «Mensajes, cartas y encíclicas» -----------------------------
--
--  El subtexto pierde la segunda frase —«Este sitio no publica ninguna frase
--  suya que no conste en un documento de la Santa Sede»—, que era un
--  compromiso editorial nuestro. Lo quita la CEP en su maqueta, para que el
--  texto calce en la línea del diseño.
UPDATE `bloques`
   SET `titulo`     = 'Mensajes, cartas y encíclicas',
       `texto`      = 'Las palabras del Santo Padre se citan por su fuente oficial.',
       `enlace_url` = 'https://www.vatican.va/content/leo-xiv/es.html'
 WHERE `seccion_id` = @seccion AND `orden` = 30;

-- 4 · Kit de prensa ---------------------------------------------------------
--  El texto ya era el bueno; sólo le faltaba el destino.
UPDATE `bloques`
   SET `texto`      = 'Dosier, logotipos y fotografías en alta resolución.',
       `enlace_url` = @drive
 WHERE `seccion_id` = @seccion AND `orden` = 40;
