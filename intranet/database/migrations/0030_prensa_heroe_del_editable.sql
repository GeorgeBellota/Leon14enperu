-- ===========================================================================
--  0030 · PRENSA — EL HÉROE SALE DEL EDITABLE NUEVO
-- ---------------------------------------------------------------------------
--  La banda del héroe de Prensa se ha vuelto a exportar desde
--  «PÁG PRENSA- nueva.ai», sin los dos textos, que en la web son HTML vivo.
--  El archivo es el mismo —assets/img/rediseno/prensa/hero.jpg— y conserva sus
--  2880 x 1164, así que en la base sólo cambia el peso, que es lo que el panel
--  enseña debajo de cada miniatura en la biblioteca de medios.
--
--  El texto alternativo NO se toca: puede haberlo reescrito alguien desde el
--  panel, igual que en 0027 y 0028.
--
--  Idempotente.
-- ===========================================================================

SET NAMES utf8mb4;

UPDATE `medios`
   SET `peso` = 378361
 WHERE `ruta` = 'assets/img/rediseno/prensa/hero.jpg';
