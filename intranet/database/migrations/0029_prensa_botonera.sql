-- ===========================================================================
--  0029 · PRENSA — LA FILA DE BOTONES DEL EDITABLE NUEVO
-- ---------------------------------------------------------------------------
--  El editable de Prensa entregado en septiembre de 2026 («PÁG PRENSA- nueva.ai»)
--  cambia una sola cosa de la página: donde había un botón «Contacto» centrado
--  ahora hay una fila con dos, en la misma línea y dentro de la caja de 1218:
--
--      «Programa oficial»   pegado a la izquierda, con flecha dorada de descarga
--      «Contacto»           pegado a la derecha, igual que hasta ahora
--
--  ── Qué hace ────────────────────────────────────────────────────────────
--
--   1. Cambia la plantilla de la sección `contacto-prensa` de «destacado»
--      —que sólo sabe de UN botón, en `cta_texto`/`cta_url`— a «botonera»,
--      que es una lista de bloques. Así, desde Páginas → Prensa → Botón de
--      contacto, se pueden añadir, quitar y reordenar botones sin tocar código.
--
--   2. Crea los dos botones como bloques. El rótulo va en `titulo` y el destino
--      en `enlace_url`; el icono, en `datos.icono` («descarga» o vacío).
--
--   3. El botón que ya estaba en `cta_texto`/`cta_url` NO se borra: esas dos
--      columnas se quedan como estaban. Si algún día hubiera que volver a la
--      plantilla anterior, el contenido sigue ahí.
--
--  El resto de la página —cabecera, acreditación, uso de imágenes y del escudo,
--  y multimedia— no cambia: se comparó el editable nuevo con la página en
--  pantalla y sólo difiere esta banda.
--
--  Idempotente: se puede volver a pasar sin duplicar botones.
-- ===========================================================================

SET NAMES utf8mb4;

SET @sec := (
  SELECT s.`id` FROM `secciones` s
    JOIN `paginas` p ON p.`id` = s.`pagina_id`
   WHERE p.`clave` = 'prensa' AND s.`clave` = 'contacto-prensa'
   LIMIT 1
);

-- ── 1 · La sección pasa a ser una fila de botones ──────────────────────────
UPDATE `secciones`
   SET `plantilla` = 'botonera',
       `nombre`    = 'Botones de prensa',
       `titulo`    = COALESCE(NULLIF(`titulo`, ''), 'Contacto de prensa')
 WHERE @sec IS NOT NULL AND `id` = @sec;

-- ── 2 · Los dos botones ────────────────────────────────────────────────────
-- El rótulo del segundo sale de lo que ya hubiera en `cta_texto`, para no
-- pisar un cambio hecho desde el panel («Contacto para medios», por ejemplo).
SET @rotulo2 := (SELECT COALESCE(NULLIF(`cta_texto`, ''), 'Contacto')
                   FROM `secciones` WHERE `id` = @sec);
SET @url2    := (SELECT COALESCE(NULLIF(`cta_url`, ''), 'contacto/')
                   FROM `secciones` WHERE `id` = @sec);

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `enlace_url`, `datos`)
SELECT @sec, 10, 1, 'Programa oficial', 'agenda/', '{"icono": "descarga"}'
  FROM DUAL
 WHERE @sec IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.`seccion_id` = @sec AND b.`orden` = 10);

INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `enlace_url`, `datos`)
SELECT @sec, 20, 1, @rotulo2, @url2, '{}'
  FROM DUAL
 WHERE @sec IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM `bloques`) b
                    WHERE b.`seccion_id` = @sec AND b.`orden` = 20);
