-- ===========================================================================
--  0031 · LOGO Y LEMA — EL EDITABLE DE SEPTIEMBRE, Y LOS TEXTOS DE VERDAD
-- ---------------------------------------------------------------------------
--  La página se montó con el editable de agosto, que todavía traía «Lorem
--  ipsum» en los tres bloques de texto. El de septiembre —«PÁG LOGO Y LEMA.ai»—
--  llega con la redacción definitiva y con dos cambios de composición:
--
--    · «Su diseño» pone el titular y su entradilla centrados ARRIBA, el collage
--      a todo el ancho y debajo dos notas que lo señalan con una flecha.
--    · «Su color y sus versiones» pasa de una hilera de tres logotipos con un
--      único pie a TRES FILAS, cada una con su logotipo y su explicación.
--
--  ── Qué hace ────────────────────────────────────────────────────────────
--
--   1. Escribe los textos definitivos del lema y de la entradilla de «Su
--      diseño», y quita el «Lorem ipsum» de las tres secciones.
--   2. Cambia la plantilla de «Su diseño» a «texto_con_notas» y crea las dos
--      notas como bloques, para que se puedan editar, reordenar o quitar.
--   3. Escribe la explicación de cada versión del logotipo en su bloque. Los
--      dos asteriscos alrededor de las primeras palabras son la negrita: el
--      campo de la ficha es de texto llano y la vista los convierte.
--   4. Vacía el pie común de «Su color»: ahora cada versión lleva el suyo. Si
--      alguien escribe uno en el panel, la vista lo sigue pintando.
--
--  La máscara del emblema —brand/emblema-mascara.png, la pieza que estrena el
--  collage— NO se da de alta en `medios`: no es una fotografía que alguien vaya
--  a elegir en el panel, es un recurso de la hoja de estilos, como el propio
--  archivo de marca del que salen las otras cinco piezas. En la biblioteca de
--  imágenes aparecería como un rectángulo negro sin explicación.
--
--  Idempotente.
-- ===========================================================================

SET NAMES utf8mb4;

SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'logo-y-lema' LIMIT 1);

-- ── 1 · El lema ────────────────────────────────────────────────────────────
UPDATE `secciones`
   SET `texto` = '<p><strong>Invita a abrirse a Dios para renovar la fe</strong>, al prójimo para <br>construir fraternidad y a la misión para anunciar con alegría <br>el Evangelio, fortalecer la unidad y fomentar la paz.</p>'
 WHERE @pag IS NOT NULL AND `pagina_id` = @pag AND `clave` = 'que-significa';

-- ── 2 · Su diseño: entradilla, plantilla nueva y las dos notas ─────────────
UPDATE `secciones`
   SET `plantilla` = 'texto_con_notas',
       `texto`     = '<p>El conjunto evoca <strong>una Iglesia guiada por el Espíritu Santo</strong> <br>y enriquecida por el testimonio de sus santos.</p>'
 WHERE @pag IS NOT NULL AND `pagina_id` = @pag AND `clave` = 'su-diseno';

SET @sec := (SELECT `id` FROM `secciones`
              WHERE `pagina_id` = @pag AND `clave` = 'su-diseno' LIMIT 1);

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `texto`, `datos`)
SELECT @sec, 10, 1,
       'En el centro figura León XIV, rodeado por santa Rosa de Lima, santo Toribio de Mogrovejo, san Martín de Porres, san Francisco Solano y san Juan Macías, principales santos de la tradición peruana.',
       '{}'
  FROM DUAL
 WHERE @sec IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.`seccion_id` = @sec AND b.`orden` = 10);

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `texto`, `datos`)
SELECT @sec, 20, 1,
       'La paloma del Espíritu Santo, colocada sobre el territorio nacional, recuerda la presencia de Dios que guía a la Iglesia, inspira la reconciliación y renueva la esperanza del pueblo peruano.',
       '{}'
  FROM DUAL
 WHERE @sec IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.`seccion_id` = @sec AND b.`orden` = 20);

-- ── 3 · Su color: el pie de cada versión ───────────────────────────────────
SET @col := (SELECT `id` FROM `secciones`
              WHERE `pagina_id` = @pag AND `clave` = 'su-color' LIMIT 1);

UPDATE `bloques` SET `texto` = '**El rojo** representa la identidad y el espíritu del Perú.'
 WHERE @col IS NOT NULL AND `seccion_id` = @col AND `orden` = 10;

UPDATE `bloques` SET `texto` = '**El dorado** transmite la dimensión espiritual, solemne y luminosa de la visita.'
 WHERE @col IS NOT NULL AND `seccion_id` = @col AND `orden` = 20;

UPDATE `bloques` SET `texto` = '**El gris** garantiza versatilidad y legibilidad en aplicaciones donde se requiere una reproducción más sobria.'
 WHERE @col IS NOT NULL AND `seccion_id` = @col AND `orden` = 30;

-- ── 4 · Fuera el pie común, que ya no dibuja el editable ───────────────────
UPDATE `secciones` SET `texto` = ''
 WHERE @col IS NOT NULL AND `id` = @col;
