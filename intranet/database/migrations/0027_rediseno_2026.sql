-- ===========================================================================
--  0027 · REDISEÑO 2026 — CONTENIDO DEL SITIO PÚBLICO
-- ---------------------------------------------------------------------------
--  El sitio público se rehízo por completo siguiendo los editables de
--  Illustrator entregados por la Conferencia Episcopal. Las vistas nuevas ya
--  están en views/; esta migración pone en la base el contenido que esperan,
--  para que todo siga siendo editable desde el panel.
--
--  ── Qué hace ─────────────────────────────────────────────────────────────
--
--   1. Da de alta en `medios` las fotografías y la marca del rediseño, con su
--      familia de anchos y formatos, para que se puedan cambiar desde el panel.
--   2. Renombra las tres páginas que cambiaron de dirección:
--        el-papa          → papa-leon-xiv
--        tierra-de-santos → santos
--        materiales       → subsidios
--      Las direcciones antiguas NO se apagan: index.php responde un 301 a la
--      nueva, así que nada de lo indexado o compartido deja de funcionar.
--   3. Da de alta la página nueva «logo-y-lema».
--   4. Crea o actualiza las secciones y los bloques de cada página.
--   5. Retira las secciones que el diseño nuevo ya no pinta.
--   6. Deja el menú con las nueve entradas del diseño y la portada como
--      página de inicio.
--
--  ── Qué NO hace ──────────────────────────────────────────────────────────
--
--  No toca `voluntarios` ni `voluntarios_historial`: ni un UPDATE, ni un
--  DELETE. Las 37.428 inscripciones y su historial quedan exactamente igual.
--  Tampoco toca `usuarios`, `roles`, `permisos`, `ubigeo_*` ni `auditoria`.
--
--  ── Sobre el texto ───────────────────────────────────────────────────────
--
--  Manda el contenido del rediseño. Donde el editable trae relleno —«Lorem
--  ipsum», «XX:00 Hrs.», preguntas sin respuesta— se conserva el texto real
--  que ya había en producción: esos campos simplemente no se tocan.
--
--  ── Volver atrás ─────────────────────────────────────────────────────────
--
--  Restaurando el volcado anterior. Esta migración reordena y sustituye
--  contenido editorial, así que no trae un «down»: el camino de vuelta es la
--  copia de seguridad, que es lo que el proyecto ya usa para el resto.
--
--  Es repetible: todo va con INSERT … ON DUPLICATE KEY UPDATE o con
--  comprobación previa de existencia.
--
--  ── Cómo comprobar antes y después ───────────────────────────────────────
--
--  Este archivo no lleva ningún SELECT: lo ejecuta database/migrate.php con
--  PDO::exec() y una consulta que devuelve filas en medio de un lote deja el
--  resultado sin leer, así que la siguiente sentencia aborta con «Cannot
--  execute queries while there are pending result sets». Para ver el efecto,
--  esta consulta a mano antes y después:
--
--    SELECT (SELECT COUNT(*) FROM paginas)     AS paginas,
--           (SELECT COUNT(*) FROM secciones)   AS secciones,
--           (SELECT COUNT(*) FROM bloques)     AS bloques,
--           (SELECT COUNT(*) FROM medios)      AS medios,
--           (SELECT COUNT(*) FROM voluntarios) AS voluntarios;
--
--  Sobre el volcado de producción del 19/09/2026 debe pasar de
--  24/107/99/9/37428 a 25/120/203/86/37428. Las inscripciones no cambian.
-- ===========================================================================


-- ──────────────────────────────────────────────────────────────────────────
--  0 · Cómo se ejecuta esto
--
--  SET NAMES: el archivo está en UTF-8 y aquí se dice, porque si no queda a
--  merced de lo que traiga la herramienta. Importarlo en latin1 termina sin
--  un solo aviso y deja «La Iglesia en el PerÃº» guardado en la base: una
--  corrupción silenciosa de todo el texto que sólo se ve abriendo el sitio.
--
--  START TRANSACTION: no hay aquí ni un CREATE, ni un ALTER, ni un DROP; son
--  todo INSERT, UPDATE y DELETE sobre tablas InnoDB, así que la transacción
--  aguanta de principio a fin. O entra todo, o no entra nada. Sin ella, un
--  fallo por la mitad dejaba el sitio a medio cambiar y sin marca de por
--  dónde se había quedado.
-- ──────────────────────────────────────────────────────────────────────────

SET NAMES utf8mb4;
START TRANSACTION;


-- ──────────────────────────────────────────────────────────────────────────
--  1 · Las imágenes del rediseño
--
--  Se registran con su familia de variantes para que Sitio::imagen() sirva
--  WebP y varios anchos. `ruta` es única, así que repetir la migración no
--  duplica nada: sólo refresca las medidas.
-- ──────────────────────────────────────────────────────────────────────────

-- Si ya estaban registradas, se refrescan medidas y variantes pero NO el
-- texto alternativo: ese puede haberlo escrito alguien desde el panel.
INSERT INTO `medios` (`ruta`, `nombre_archivo`, `mime`, `ancho`, `alto`, `peso`, `variantes`, `alt`, `decorativa`) VALUES
  ('assets/img/rediseno/agenda/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 272679, '{"base":"assets/img/rediseno/agenda/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'El Papa León XIV saluda a una multitud de fieles que lo esperan con sus teléfonos en alto', 0),
  ('assets/img/rediseno/agenda/p01.jpg', 'p01.jpg', 'image/jpeg', 1084, 626, 161581, '{"base":"assets/img/rediseno/agenda/p01","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'Plaza Mayor de Lima con la Basílica Catedral y el Palacio Arzobispal', 0),
  ('assets/img/rediseno/agenda/p02.jpg', 'p02.jpg', 'image/jpeg', 1084, 626, 187402, '{"base":"assets/img/rediseno/agenda/p02","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'Catedral Santa María de Chiclayo vista desde el parque principal', 0),
  ('assets/img/rediseno/agenda/p03.jpg', 'p03.jpg', 'image/jpeg', 1084, 626, 188522, '{"base":"assets/img/rediseno/agenda/p03","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'Catedral del Cusco en la Plaza de Armas', 0),
  ('assets/img/rediseno/agenda/p04.jpg', 'p04.jpg', 'image/jpeg', 1041, 601, 193474, '{"base":"assets/img/rediseno/agenda/p04","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'Catedral de Pucallpa con las banderas de la plaza central', 0),
  ('assets/img/rediseno/brand/cep-simbolo-blanco.png', 'cep-simbolo-blanco.png', 'image/png', 600, 625, 93576, '{"base":"assets/img/rediseno/brand/cep-simbolo-blanco","anchos":[480],"formatos":["webp","png"]}', '', 1),
  ('assets/img/rediseno/brand/escudo-color.png', 'escudo-color.png', 'image/png', 1200, 1674, 423792, '{"base":"assets/img/rediseno/brand/escudo-color","anchos":[480,768,1200],"formatos":["webp","png"]}', '', 1),
  ('assets/img/rediseno/brand/logo-lockup-dorado.png', 'logo-lockup-dorado.png', 'image/png', 1552, 1268, 301266, '{"base":"assets/img/rediseno/brand/logo-lockup-dorado","anchos":[480,768,1200],"formatos":["webp","png"]}', 'Versión del logotipo en dorado', 0),
  ('assets/img/rediseno/brand/logo-lockup-hero.png', 'logo-lockup-hero.png', 'image/png', 1600, 1477, 723874, '{"base":"assets/img/rediseno/brand/logo-lockup-hero","anchos":[480,768,1200],"formatos":["webp","png"]}', 'Abramos el corazón — Papa León XIV, Visita Apostólica al Perú, 11–16 de noviembre de 2026', 0),
  ('assets/img/rediseno/brand/logo-lockup-negro.png', 'logo-lockup-negro.png', 'image/png', 1556, 1268, 273231, '{"base":"assets/img/rediseno/brand/logo-lockup-negro","anchos":[480,768,1200],"formatos":["webp","png"]}', 'Versión del logotipo en negro', 0),
  ('assets/img/rediseno/brand/logo-lockup-rojo-alt.png', 'logo-lockup-rojo-alt.png', 'image/png', 1552, 1268, 302405, '{"base":"assets/img/rediseno/brand/logo-lockup-rojo-alt","anchos":[480,768,1200],"formatos":["webp","png"]}', 'Versión principal del logotipo, en rojo', 0),
  ('assets/img/rediseno/brand/logo-lockup-rojo.png', 'logo-lockup-rojo.png', 'image/png', 1600, 1313, 652726, '{"base":"assets/img/rediseno/brand/logo-lockup-rojo","anchos":[480,768,1200],"formatos":["webp","png"]}', 'Abramos el corazón — marca de la Visita Apostólica del Papa León XIV al Perú', 0),
  ('assets/img/rediseno/cep/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 417983, '{"base":"assets/img/rediseno/cep/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'Los obispos de la Conferencia Episcopal Peruana reunidos con el Santo Padre en el Vaticano', 0),
  ('assets/img/rediseno/cep/p01.jpg', 'p01.jpg', 'image/jpeg', 1066, 988, 176891, '{"base":"assets/img/rediseno/cep/p01","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'Un obispo peruano saluda a un cardenal durante un encuentro de la Conferencia Episcopal Peruana', 0),
  ('assets/img/rediseno/contacto/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 141690, '{"base":"assets/img/rediseno/contacto/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'Manos escribiendo en el teclado de una computadora portátil', 0),
  ('assets/img/rediseno/index/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 932, 369225, '{"base":"assets/img/rediseno/index/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'El Papa León XIV saluda desde el papamóvil rodeado de fieles con banderas del Perú', 0),
  ('assets/img/rediseno/index/p00.jpg', 'p00.jpg', 'image/jpeg', 1452, 912, 503617, '{"base":"assets/img/rediseno/index/p00","anchos":[480,768,1024,1440],"formatos":["webp","jpg"]}', '', 1),
  ('assets/img/rediseno/index/p01.png', 'p01.png', 'image/png', 1511, 1489, 1887220, '{"base":"assets/img/rediseno/index/p01","anchos":[480,768,1200],"formatos":["webp","png"]}', '', 1),
  ('assets/img/rediseno/index/p02.jpg', 'p02.jpg', 'image/jpeg', 491, 761, 90809, '{"base":"assets/img/rediseno/index/p02","anchos":[480],"formatos":["webp","jpg"]}', 'Santa Rosa de Lima', 0),
  ('assets/img/rediseno/index/p03.jpg', 'p03.jpg', 'image/jpeg', 484, 750, 64604, '{"base":"assets/img/rediseno/index/p03","anchos":[480],"formatos":["webp","jpg"]}', 'San Martín de Porres', 0),
  ('assets/img/rediseno/index/p04.jpg', 'p04.jpg', 'image/jpeg', 491, 734, 80077, '{"base":"assets/img/rediseno/index/p04","anchos":[480],"formatos":["webp","jpg"]}', 'San Juan Macías', 0),
  ('assets/img/rediseno/index/p05.jpg', 'p05.jpg', 'image/jpeg', 352, 546, 42389, '{"base":"assets/img/rediseno/index/p05","anchos":[352],"formatos":["webp","jpg"]}', 'San Francisco Solano', 0),
  ('assets/img/rediseno/index/p06.jpg', 'p06.jpg', 'image/jpeg', 491, 761, 100365, '{"base":"assets/img/rediseno/index/p06","anchos":[480],"formatos":["webp","jpg"]}', 'Santo Toribio de Mogrovejo', 0),
  ('assets/img/rediseno/index/p08.jpg', 'p08.jpg', 'image/jpeg', 326, 462, 19508, '{"base":"assets/img/rediseno/index/p08","anchos":[326],"formatos":["webp","jpg"]}', 'Portada del subsidio 1: Papa León, cercano y peruano', 0),
  ('assets/img/rediseno/index/p09.jpg', 'p09.jpg', 'image/jpeg', 331, 469, 50946, '{"base":"assets/img/rediseno/index/p09","anchos":[331],"formatos":["webp","jpg"]}', 'Portada del subsidio 2: Unidos en Cristo, sembradores de paz', 0),
  ('assets/img/rediseno/index/p10.jpg', 'p10.jpg', 'image/jpeg', 343, 483, 47972, '{"base":"assets/img/rediseno/index/p10","anchos":[343],"formatos":["webp","jpg"]}', 'Portada del subsidio 3: Familias que cuidan la vida', 0),
  ('assets/img/rediseno/index/p11.jpg', 'p11.jpg', 'image/jpeg', 336, 475, 48855, '{"base":"assets/img/rediseno/index/p11","anchos":[336],"formatos":["webp","jpg"]}', 'Portada del subsidio 4: Los jóvenes y la misión', 0),
  ('assets/img/rediseno/index/p12.jpg', 'p12.jpg', 'image/jpeg', 330, 468, 71665, '{"base":"assets/img/rediseno/index/p12","anchos":[330],"formatos":["webp","jpg"]}', 'Portada del subsidio 5: Pastoral social, la dignidad de toda persona', 0),
  ('assets/img/rediseno/index/p13.jpg', 'p13.jpg', 'image/jpeg', 835, 767, 149896, '{"base":"assets/img/rediseno/index/p13","anchos":[480,768],"formatos":["webp","jpg"]}', 'Basílica Catedral de Lima en la Plaza Mayor', 0),
  ('assets/img/rediseno/index/p14.jpg', 'p14.jpg', 'image/jpeg', 835, 767, 125222, '{"base":"assets/img/rediseno/index/p14","anchos":[480,768],"formatos":["webp","jpg"]}', 'Catedral del Callao', 0),
  ('assets/img/rediseno/index/p15.jpg', 'p15.jpg', 'image/jpeg', 835, 767, 200150, '{"base":"assets/img/rediseno/index/p15","anchos":[480,768],"formatos":["webp","jpg"]}', 'Catedral Santa María de Chiclayo', 0),
  ('assets/img/rediseno/index/p16.jpg', 'p16.jpg', 'image/jpeg', 835, 767, 186817, '{"base":"assets/img/rediseno/index/p16","anchos":[480,768],"formatos":["webp","jpg"]}', 'Catedral del Cusco', 0),
  ('assets/img/rediseno/index/p17.jpg', 'p17.jpg', 'image/jpeg', 826, 759, 208736, '{"base":"assets/img/rediseno/index/p17","anchos":[480,768],"formatos":["webp","jpg"]}', 'Catedral de Pucallpa', 0),
  ('assets/img/rediseno/noticias/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 256561, '{"base":"assets/img/rediseno/noticias/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'El Papa León XIV acompañado por obispos y cardenales', 0),
  ('assets/img/rediseno/noticias/p01.jpg', 'p01.jpg', 'image/jpeg', 1249, 753, 118902, '{"base":"assets/img/rediseno/noticias/p01","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'El Papa León XIV durante una celebración litúrgica', 0),
  ('assets/img/rediseno/papa-leon-xiv/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 236426, '{"base":"assets/img/rediseno/papa-leon-xiv/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'El Papa León XIV saluda con los brazos abiertos desde el balcón de la basílica de San Pedro', 0),
  ('assets/img/rediseno/papa-leon-xiv/p01.jpg', 'p01.jpg', 'image/jpeg', 1162, 755, 188904, '{"base":"assets/img/rediseno/papa-leon-xiv/p01","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'El obispo Robert Prevost en audiencia con el papa Francisco', 0),
  ('assets/img/rediseno/papa-leon-xiv/p02.jpg', 'p02.jpg', 'image/jpeg', 1164, 778, 273190, '{"base":"assets/img/rediseno/papa-leon-xiv/p02","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'Asamblea de obispos de la Conferencia Episcopal Peruana', 0),
  ('assets/img/rediseno/papa-leon-xiv/p03.jpg', 'p03.jpg', 'image/jpeg', 982, 930, 106132, '{"base":"assets/img/rediseno/papa-leon-xiv/p03","anchos":[480,768],"formatos":["webp","jpg"]}', 'Entrega del doctorado honoris causa a Robert Prevost', 0),
  ('assets/img/rediseno/papa-leon-xiv/p04.jpg', 'p04.jpg', 'image/jpeg', 784, 841, 92161, '{"base":"assets/img/rediseno/papa-leon-xiv/p04","anchos":[480,768],"formatos":["webp","jpg"]}', 'El cardenal Robert Francis Prevost con la vestidura cardenalicia', 0),
  ('assets/img/rediseno/papa-leon-xiv/p05.jpg', 'p05.jpg', 'image/jpeg', 726, 458, 49570, '{"base":"assets/img/rediseno/papa-leon-xiv/p05","anchos":[480],"formatos":["webp","jpg"]}', 'El Papa León XIV saluda a los fieles tras su elección', 0),
  ('assets/img/rediseno/papa-leon-xiv/p06.png', 'p06.png', 'image/png', 835, 1165, 281476, '{"base":"assets/img/rediseno/papa-leon-xiv/p06","anchos":[480,768],"formatos":["webp","png"]}', 'Escudo del Papa León XIV: lirio blanco sobre fondo azul y, sobre fondo claro, un libro cerrado con un corazón traspasado por una flecha, con el lema In Illo uno unum', 0),
  ('assets/img/rediseno/papa-leon-xiv/p07.jpg', 'p07.jpg', 'image/jpeg', 816, 1160, 101321, '{"base":"assets/img/rediseno/papa-leon-xiv/p07","anchos":[480,768],"formatos":["webp","jpg"]}', 'Retrato oficial de Su Santidad el Papa León XIV', 0),
  ('assets/img/rediseno/papa-leon-xiv/p08.jpg', 'p08.jpg', 'image/jpeg', 474, 491, 34437, '{"base":"assets/img/rediseno/papa-leon-xiv/p08","anchos":[474],"formatos":["webp","jpg"]}', 'El joven Robert Prevost revestido de diácono en una celebración', 0),
  ('assets/img/rediseno/papa-leon-xiv/p09.jpg', 'p09.jpg', 'image/jpeg', 925, 880, 103477, '{"base":"assets/img/rediseno/papa-leon-xiv/p09","anchos":[480,768],"formatos":["webp","jpg"]}', 'El padre Prevost junto a un seminarista durante su misión en el Perú', 0),
  ('assets/img/rediseno/papa-leon-xiv/p10.jpg', 'p10.jpg', 'image/jpeg', 665, 766, 77924, '{"base":"assets/img/rediseno/papa-leon-xiv/p10","anchos":[480],"formatos":["webp","jpg"]}', 'El padre Prevost con un poblador y dos jóvenes durante la misión agustiniana en el norte del Perú', 0),
  ('assets/img/rediseno/papa-leon-xiv/p11.jpg', 'p11.jpg', 'image/jpeg', 778, 732, 77053, '{"base":"assets/img/rediseno/papa-leon-xiv/p11","anchos":[480,768],"formatos":["webp","jpg"]}', 'Robert Francis Prevost de niño, sobre una vista aérea de Chicago', 0),
  ('assets/img/rediseno/preguntas-frecuentes/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 373831, '{"base":"assets/img/rediseno/preguntas-frecuentes/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'El Papa León XIV bendice a los obispos reunidos en audiencia en el Vaticano', 0),
  ('assets/img/rediseno/prensa/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 334287, '{"base":"assets/img/rediseno/prensa/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'Periodistas y cámaras de televisión durante una conferencia de prensa', 0),
  ('assets/img/rediseno/prensa/p01.jpg', 'p01.jpg', 'image/jpeg', 1550, 946, 98407, '{"base":"assets/img/rediseno/prensa/p01","anchos":[480,768,1024,1440],"formatos":["webp","jpg"]}', 'Una periodista consulta en su teléfono la página oficial del himno de la visita del Papa León XIV', 0),
  ('assets/img/rediseno/santos/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 558974, '{"base":"assets/img/rediseno/santos/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'Bendición del tapiz con las imágenes de los santos del Perú durante una celebración de la Conferencia Episcopal Peruana', 0),
  ('assets/img/rediseno/santos/p01.jpg', 'p01.jpg', 'image/jpeg', 1010, 1594, 420466, '{"base":"assets/img/rediseno/santos/p01","anchos":[480,768],"formatos":["webp","jpg"]}', 'Retrato al óleo de Santo Toribio de Mogrovejo arrodillado en oración, con muceta roja, ante un altar', 0),
  ('assets/img/rediseno/sede/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 302531, '{"base":"assets/img/rediseno/sede/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'Plaza Matriz del Callao con la iglesia Matriz al fondo', 0),
  ('assets/img/rediseno/sedes/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 225175, '{"base":"assets/img/rediseno/sedes/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'El Papa León XIV, con mitra y báculo, preside una celebración junto al altar acompañado por dos ministros', 0),
  ('assets/img/rediseno/sedes/p01.jpg', 'p01.jpg', 'image/jpeg', 847, 774, 147429, '{"base":"assets/img/rediseno/sedes/p01","anchos":[480,768],"formatos":["webp","jpg"]}', 'El Papa León XIV eleva el evangeliario durante una celebración', 0),
  ('assets/img/rediseno/sedes/p02.jpg', 'p02.jpg', 'image/jpeg', 956, 640, 112807, '{"base":"assets/img/rediseno/sedes/p02","anchos":[480,768],"formatos":["webp","jpg"]}', 'El Papa León XIV inciensa el altar rodeado de rosas blancas', 0),
  ('assets/img/rediseno/sedes/p03.jpg', 'p03.jpg', 'image/jpeg', 841, 765, 117090, '{"base":"assets/img/rediseno/sedes/p03","anchos":[480,768],"formatos":["webp","jpg"]}', 'El Papa León XIV sostiene la imagen de un santo obispo durante una audiencia', 0),
  ('assets/img/rediseno/sedes/p04.jpg', 'p04.jpg', 'image/jpeg', 1010, 556, 103663, '{"base":"assets/img/rediseno/sedes/p04","anchos":[480,768],"formatos":["webp","jpg"]}', 'El obispo administra el sacramento de la confirmación con mascarillas durante la pandemia', 0),
  ('assets/img/rediseno/sedes/p05.jpg', 'p05.jpg', 'image/jpeg', 1010, 556, 51538, '{"base":"assets/img/rediseno/sedes/p05","anchos":[480,768],"formatos":["webp","jpg"]}', 'Retrato del arzobispo de Lima con alzacuellos y cruz pectoral', 0),
  ('assets/img/rediseno/sedes/p06.jpg', 'p06.jpg', 'image/jpeg', 1010, 578, 137598, '{"base":"assets/img/rediseno/sedes/p06","anchos":[480,768],"formatos":["webp","jpg"]}', 'Celebración ante la imagen del Señor de los Milagros adornada con flores moradas', 0),
  ('assets/img/rediseno/sedes/p07.jpg', 'p07.jpg', 'image/jpeg', 531, 478, 54816, '{"base":"assets/img/rediseno/sedes/p07","anchos":[480],"formatos":["webp","jpg"]}', 'El obispo Robert Prevost eleva una cruz de plata rodeado de fieles', 0),
  ('assets/img/rediseno/sedes/p08.jpg', 'p08.jpg', 'image/jpeg', 804, 1237, 142342, '{"base":"assets/img/rediseno/sedes/p08","anchos":[480,768],"formatos":["webp","jpg"]}', 'El Papa León XIV saluda con los brazos en alto tras su proclamación', 0),
  ('assets/img/rediseno/sedes/p09.jpg', 'p09.jpg', 'image/jpeg', 1056, 884, 273898, '{"base":"assets/img/rediseno/sedes/p09","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'Catedral Santa María de Chiclayo vista desde la plaza', 0),
  ('assets/img/rediseno/sedes/p10.jpg', 'p10.jpg', 'image/jpeg', 378, 375, 45494, '{"base":"assets/img/rediseno/sedes/p10","anchos":[378],"formatos":["webp","jpg"]}', 'Iglesia de la Compañía de Jesús en la Plaza de Armas del Cusco', 0),
  ('assets/img/rediseno/sedes/p11.jpg', 'p11.jpg', 'image/jpeg', 674, 864, 65480, '{"base":"assets/img/rediseno/sedes/p11","anchos":[480],"formatos":["webp","jpg"]}', 'Un obispo se dirige a los fieles con un micrófono durante una celebración', 0),
  ('assets/img/rediseno/sedes/p12.jpg', 'p12.jpg', 'image/jpeg', 679, 970, 112780, '{"base":"assets/img/rediseno/sedes/p12","anchos":[480],"formatos":["webp","jpg"]}', 'Catedral de la Inmaculada Concepción de Pucallpa', 0),
  ('assets/img/rediseno/sedes/p13.jpg', 'p13.jpg', 'image/jpeg', 623, 1015, 121261, '{"base":"assets/img/rediseno/sedes/p13","anchos":[480],"formatos":["webp","jpg"]}', 'El Papa León XIV con báculo sobre una vista aérea del río Ucayali', 0),
  ('assets/img/rediseno/sedes/p14.jpg', 'p14.jpg', 'image/jpeg', 1194, 568, 247550, '{"base":"assets/img/rediseno/sedes/p14","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'Basílica Catedral de Lima en la Plaza Mayor al atardecer', 0),
  ('assets/img/rediseno/subsidios/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 199630, '{"base":"assets/img/rediseno/subsidios/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'Agentes pastorales y obispos de la Conferencia Episcopal Peruana saludan durante la presentación de los subsidios', 0),
  ('assets/img/rediseno/subsidios/p01.jpg', 'p01.jpg', 'image/jpeg', 397, 563, 25644, '{"base":"assets/img/rediseno/subsidios/p01","anchos":[397],"formatos":["webp","jpg"]}', 'Portada del subsidio 1: Papa León, cercano y peruano', 0),
  ('assets/img/rediseno/subsidios/p02.jpg', 'p02.jpg', 'image/jpeg', 399, 565, 67084, '{"base":"assets/img/rediseno/subsidios/p02","anchos":[399],"formatos":["webp","jpg"]}', 'Portada del subsidio 2: Unidos en Cristo, sembradores de paz', 0),
  ('assets/img/rediseno/subsidios/p03.jpg', 'p03.jpg', 'image/jpeg', 400, 563, 60164, '{"base":"assets/img/rediseno/subsidios/p03","anchos":[400],"formatos":["webp","jpg"]}', 'Portada del subsidio 3: Familias que cuidan la vida', 0),
  ('assets/img/rediseno/subsidios/p04.jpg', 'p04.jpg', 'image/jpeg', 398, 563, 63821, '{"base":"assets/img/rediseno/subsidios/p04","anchos":[398],"formatos":["webp","jpg"]}', 'Portada del subsidio 4: Los jóvenes y la misión', 0),
  ('assets/img/rediseno/subsidios/p05.jpg', 'p05.jpg', 'image/jpeg', 396, 561, 94510, '{"base":"assets/img/rediseno/subsidios/p05","anchos":[396],"formatos":["webp","jpg"]}', 'Portada del subsidio 5: Pastoral social, la dignidad de toda persona', 0),
  ('assets/img/rediseno/subsidios/p06.jpg', 'p06.jpg', 'image/jpeg', 1198, 674, 149630, '{"base":"assets/img/rediseno/subsidios/p06","anchos":[480,768,1024],"formatos":["webp","jpg"]}', 'Portada del documento «Subsidios para la visita del Papa León XIV al Perú»', 0),
  ('assets/img/rediseno/voluntariado/hero.jpg', 'hero.jpg', 'image/jpeg', 2880, 1164, 379086, '{"base":"assets/img/rediseno/voluntariado/hero","anchos":[480,768,1024,1440,1920,2560],"formatos":["webp","jpg"]}', 'Grupo de jóvenes voluntarios sonriendo, en duotono naranja y verde', 0),
  ('assets/img/rediseno/voluntariado/p01.jpg', 'p01.jpg', 'image/jpeg', 454, 499, 23783, '{"base":"assets/img/rediseno/voluntariado/p01","anchos":[454],"formatos":["webp","jpg"]}', 'Formulario de inscripción del voluntariado: datos personales, ubicación y dirección', 0)
ON DUPLICATE KEY UPDATE
  `mime` = VALUES(`mime`), `ancho` = VALUES(`ancho`), `alto` = VALUES(`alto`),
  `peso` = VALUES(`peso`), `variantes` = VALUES(`variantes`);


-- ──────────────────────────────────────────────────────────────────────────
--  2 · Las páginas
-- ──────────────────────────────────────────────────────────────────────────

-- Los tres renombres. Se hacen sólo si la clave vieja sigue ahí, para que
-- repetir la migración no falle. Sólo cambian la clave y la ruta: el nombre
-- con el que la página aparece en el panel lo pone unas líneas más abajo el
-- alta de cada página, para no decirlo en dos sitios distintos.
UPDATE `paginas` SET `clave` = 'papa-leon-xiv', `ruta` = '/papa-leon-xiv/' WHERE `clave` = 'el-papa';
UPDATE `paginas` SET `clave` = 'santos',        `ruta` = '/santos/'        WHERE `clave` = 'tierra-de-santos';
UPDATE `paginas` SET `clave` = 'subsidios',     `ruta` = '/subsidios/'     WHERE `clave` = 'materiales';


-- ── Página «agenda» ────────────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('agenda', 'Agenda', '/agenda/', 'Agenda · León XIV en el Perú · 11–16 de noviembre de 2026', 'Días de encuentro: cronograma del viaje apostólico del Papa León XIV al Perú en Lima y Callao, Chiclayo y Santa Cruz, Cusco y Pucallpa, del 11 al 16 de noviembre de 2026.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'agenda');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, 'Agenda', 'Días de encuentro', 'El Papa León XIV estará en el Perú del\n**11 al 16 de noviembre** de 2026.', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/agenda/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `datos`)
VALUES (@pag, 'cuatro-ventanas', 'Sedes del cronograma', 'tarjetas_foto', 20, 1, 'Filtrar el cronograma por ciudad', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `imagen_id`) VALUES
  (@sec, 10, 1, 'Lima - Callao', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/agenda/p01.jpg')),
  (@sec, 30, 1, 'Cusco', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/agenda/p03.jpg')),
  (@sec, 40, 1, 'Pucallpa', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/agenda/p04.jpg'));
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `imagen_id`) VALUES
  (@sec, 20, 1, 'Chiclayo', 'Santa Cruz', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/agenda/p02.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'itinerario', 'Itinerario del Santo Padre', 'jornadas', 30, 1, 'El recorrido', 'El recorrido del Santo Padre', '<p><strong>Programa referencial.</strong> Las fechas, actividades y lugares serán reemplazados por el programa oficial cuando la Santa Sede lo apruebe y publique.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`) VALUES
  (@sec, 10, 1, '11 de noviembre', 'Lima · Llegada y bienvenida oficial', 'Llegada al Perú y primer mensaje al pueblo peruano.', 'Conoce la sede de Lima', 'sedes/', '{"actividades":["Llegada del Santo Padre","Ceremonia de bienvenida","Encuentro con autoridades","Primer mensaje al pueblo peruano"]}'),
  (@sec, 20, 1, '12 de noviembre', 'Chiclayo · El reencuentro con una Iglesia que conoce', 'Chiclayo tiene una relación personal y pastoral muy fuerte con León XIV: fue obispo de esta diócesis durante años.', 'Conoce la sede de Chiclayo', 'sedes/', '{"actividades":["Encuentro con la comunidad de Chiclayo","Celebración eucarística","Encuentro con sacerdotes, religiosos y agentes pastorales","Momento de cercanía con el pueblo"]}'),
  (@sec, 30, 1, '13 de noviembre', 'Pucallpa · Encuentro con la Amazonía', 'Encuentro con las comunidades amazónicas y los pueblos originarios.', 'Conoce la sede de Pucallpa', 'sedes/', '{"actividades":["Encuentro con comunidades amazónicas","Encuentro con representantes de pueblos originarios","Celebración o momento de oración","Mensaje sobre el cuidado de la casa común"]}'),
  (@sec, 40, 1, '14 de noviembre', 'Cusco · La fe que nace del encuentro', 'Cusco desde su identidad religiosa, cultural y andina.', 'Conoce la sede de Cusco', 'sedes/', '{"actividades":["Celebración eucarística","Encuentro con la comunidad eclesial","Encuentro con jóvenes y familias","Visita o momento de oración en un lugar significativo"]}'),
  (@sec, 50, 1, '15 de noviembre', 'Lima · Un encuentro con todo el Perú', 'La gran jornada del encuentro nacional.', 'Conoce la sede de Lima', 'sedes/', '{"actividades":["Gran celebración eucarística","Encuentro con familias, jóvenes y diversos sectores","Mensaje del Santo Padre al pueblo peruano","Momento de oración y acción de gracias"]}'),
  (@sec, 60, 1, '16 de noviembre', 'Lima · Hasta pronto, Perú', 'Despedida y salida del Perú.', 'Revive la visita', '#', '{"actividades":["Encuentro de despedida","Mensaje final del Santo Padre","Ceremonia de despedida","Salida del Perú"]}');
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'cuatro-ventanas', 'itinerario');

-- ── Página «cep» ───────────────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('cep', 'La Iglesia en el Perú', '/cep/', 'La Iglesia en el Perú · Conferencia Episcopal Peruana · León XIV en el Perú', 'La Conferencia Episcopal Peruana y las cinco jurisdicciones eclesiásticas que acogen el viaje apostólico del Papa León XIV al Perú: Lima, Callao, Chiclayo y Santa Cruz, Cusco y Pucallpa.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'cep');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, 'Quién recibe la visita', 'La Iglesia en el Perú', 'La Conferencia Episcopal Peruana y las cinco jurisdicciones eclesiásticas que acogen el viaje apostólico.', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/cep/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'quien-organiza-visita', 'Quién organiza la visita', 'texto_lectura', 20, 1, 'La Conferencia', '¿Quién organiza la visita?', '<p>La <strong>Conferencia Episcopal Peruana</strong> reúne a los obispos de las diócesis, arquidiócesis, prelaturas y vicariatos apostólicos del país. Es el organismo que coordina la preparación del viaje en el Perú, difunde y adapta lo que publica la Santa Sede, y articula el trabajo de las cuatro jurisdicciones que reciben al Santo Padre.</p><p>León XIV la conoce por dentro. Fue su segundo vicepresidente desde marzo de 2018, miembro de su Consejo Económico y presidente de la Comisión Episcopal de Cultura y Educación. En 2023 la propia Conferencia le concedió la Medalla de Oro de Santo Toribio de Mogrovejo.</p><p>Los datos de contacto institucional de la Conferencia se publicarán en la página de contacto.</p>', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/cep/p01.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'quien-acoge-cada', 'Quién acoge cada sede', 'texto_apartados', 30, 1, 'Cuatro jurisdicciones', '¿Quién acoge cada sede?', '<p>Una arquidiócesis primada, una arquidiócesis andina, dos diócesis y un vicariato apostólico de territorio de misión.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, 'Lima\nArquidiócesis de Lima · Primada del Perú', 'Creada en 1546 por el papa Paulo III. Confirmada como arquidiócesis primada en 1943. Abarca a más de nueve millones de habitantes.'),
  (@sec, 20, 1, 'Callao\nDiócesis del Callao', 'Fue creada por S.S. Pablo VI, mediante el documento pontificio <em>Aptiorem Ecclesiarum</em> en 1967. La fe de su comunidad se expresa en la profunda devoción a sus santos patrones: la Virgen del Carmen de la Legua y el Señor del Mar. Se organiza en cuatro decanatos, que reúnen cerca de 60 parroquias.'),
  (@sec, 30, 1, 'Chiclayo - Santa Cruz\nDiócesis de Chiclayo · Lambayeque y Santa Cruz', 'Erigida el 17 de diciembre de 1956 con territorio de la arquidiócesis de Trujillo y de la diócesis de Cajamarca. Robert Prevost fue su obispo entre 2015 y 2023.'),
  (@sec, 40, 1, 'Cusco\nArquidiócesis del Cusco', 'Una de las Iglesias más antiguas del continente, elevada al rango de arquidiócesis en 1943. El Señor de los Temblores es su Patrón Jurado.'),
  (@sec, 50, 1, 'Pucallpa\nVicariato Apostólico de Pucallpa · Ucayali', 'Erigido el 2 de marzo de 1956 al dividirse el vicariato de Ucayali. Depende directamente de la Santa Sede y cubre más de 52.000 km² de Amazonía.');
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'quien-organiza-visita', 'quien-acoge-cada');

-- ── Página «contacto» ──────────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('contacto', 'Contacto', '/contacto/', 'Contacto · Viaje de León XIV al Perú', 'Escríbenos y te respondemos. Formulario de contacto y canales directos del Viaje Apostólico de Su Santidad el Papa León XIV al Perú, 11–16 de noviembre de 2026.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'contacto');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, 'Organización del viaje', 'Contacto', 'Cuéntanos qué necesitas y te respondemos. Si tu consulta es sobre el programa, quizá ya esté resuelta en las preguntas frecuentes.', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/contacto/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `cta_texto`, `cta_url`, `datos`)
VALUES (@pag, 'escribenos', 'Escríbenos', 'generica', 20, 1, 'Formulario', 'Escríbenos', '', 'Enviar mensaje', '', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `cta_texto` = VALUES(`cta_texto`), `cta_url` = VALUES(`cta_url`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`) VALUES
  (@sec, 10, 1, 'voluntariado', 'Voluntariado'),
  (@sec, 20, 1, 'patrocinios', 'Patrocinios'),
  (@sec, 30, 1, 'donativo', 'Donativo'),
  (@sec, 40, 1, 'prensa', 'Prensa'),
  (@sec, 50, 1, 'materiales', 'Materiales de pastoral'),
  (@sec, 60, 1, 'otra', 'Otra consulta');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'canales', 'Canales directos', 'generica', 30, 1, 'Canales directos', '', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `enlace_url`) VALUES
  (@sec, 10, 1, 'Voluntariado', 'comunica.laicosyjuventud@iglesiacatolica.org.pe', 'mailto:comunica.laicosyjuventud@iglesiacatolica.org.pe'),
  (@sec, 20, 1, 'Prensa', 'contacto@leon14enperu.com', 'mailto:contacto@leon14enperu.com');
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`) VALUES
  (@sec, 30, 1, 'youtube', 'Youtube', '@leon14enperu'),
  (@sec, 40, 1, 'facebook', 'Facebook', '/LeonXIVEnPeru'),
  (@sec, 50, 1, 'instagram', 'Instagram', 'León XIV en el Perú'),
  (@sec, 60, 1, 'tiktok', 'Tik Tok', '@leon14enperu'),
  (@sec, 70, 1, 'whatsapp', 'Canal de difusión WhatsApp', 'Papa León XIV en el Perú');
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'escribenos', 'canales');

-- ── Página «home» ──────────────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('home', 'Inicio', '/', 'León XIV en el Perú · Visita Apostólica 11–16 de noviembre de 2026', 'Sitio oficial de la Visita Apostólica de Su Santidad el Papa León XIV al Perú, del 11 al 16 de noviembre de 2026. Lima, Callao, Chiclayo, Santa Cruz, Cusco y Pucallpa.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'home');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'hero', 'Carrusel principal', 'carrusel_hero', 10, 1, 'Papa León XIV, le esperamos', 'Las láminas que abren la portada. La primera es la que ve todo el mundo.', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `imagen_id`) VALUES
  (@sec, 10, 1, 'Papa León XIV,', 'le esperamos.', 'En directo', 'en-directo/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/hero.jpg')),
  (@sec, 20, 1, 'Abramos', 'el corazón.', 'En directo', 'en-directo/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/hero.jpg')),
  (@sec, 30, 1, 'Del 11 al 16', 'de noviembre.', 'En directo', 'en-directo/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/hero.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'falta-poco-encuentro', 'Fechas y cuenta regresiva', 'contador', 20, 1, '11 - 16', 'NOVIEMBRE 2026', 'LIMA CALLAO · CHICLAYO · CUSCO · PUCALLPA', '{"antes":"Faltan","despues":"para recibir al Papa León XIV en el Perú"}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `cta_texto`, `cta_url`, `datos`)
VALUES (@pag, 'himno', 'Himno oficial', 'destacado', 30, 1, 'HIMNO OFICIAL', '“León, hermano del camino”', 'Escuchar', 'noticias/', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `cta_texto` = VALUES(`cta_texto`), `cta_url` = VALUES(`cta_url`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `texto`, `cta_texto`, `cta_url`, `datos`, `imagen_id`)
VALUES (@pag, 'abramos-el-corazon', 'La marca «Abramos el corazón»', 'destacado', 40, 1, 'Abramos el corazón', '<p><strong>Una invitación</strong> a recibir al Santo Padre, encontrarnos como Iglesia y renovar juntos la esperanza.</p>', 'Conoce más', 'logo-y-lema/', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/brand/logo-lockup-rojo.png'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `cta_texto` = VALUES(`cta_texto`), `cta_url` = VALUES(`cta_url`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'subsidios-home', 'Subsidios pastorales', 'tarjetas_foto', 50, 1, 'Subsidios', 'pastorales', '<p>Preparémos espiritualmente para la <strong>recibir al Santo Padre</strong>, encontrarnos como Iglesia y renovar nuestra esperanza.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `enlace_url`, `imagen_id`) VALUES
  (@sec, 10, 1, 'SUBSIDIO #1', 'PAPA LEÓN:\nCERCANO Y PERUANO', 'subsidios/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p08.jpg')),
  (@sec, 20, 1, 'SUBSIDIO #2', 'UNIDOS EN CRISTO,\nSEMBRADORES DE\nPAZ', 'subsidios/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p09.jpg')),
  (@sec, 30, 1, 'SUBSIDIO #3', 'FAMILIAS QUE\nCUIDAN LA VIDA', 'subsidios/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p10.jpg')),
  (@sec, 40, 1, 'SUBSIDIO #4', 'LOS JÓVENES Y LA\nMISIÓN', 'subsidios/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p11.jpg')),
  (@sec, 50, 1, 'SUBSIDIO #5', 'PASTORAL SOCIAL:\nLA DIGNIDAD DE\nTODA PERSONA', 'subsidios/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p12.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `cta_texto`, `cta_url`, `datos`, `imagen_id`)
VALUES (@pag, 'llega-al-peru', 'La visita · El Papa León llega al Perú', 'destacado', 60, 1, 'La visita', 'El Papa León llega al Perú', '<p><strong>Del 11 al 16 de noviembre de 2026</strong>, el Santo Padre León XIV realizará su Visita Apostólica al Perú, recorriendo Lima, Chiclayo, Cusco y Pucallpa.</p><p>el Santo Padre León XIV recorrerá Lima y Callao, Chiclayo y Santa Cruz, Cusco y Pucallpa para encontrarse con el pueblo peruano. Su llegada será una oportunidad para renovar la fe, fortalecer los vínculos que nos unen y reconocer, en medio de nuestras diferencias, aquello que compartimos como nación.</p>', 'Agenda', 'agenda/', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p00.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `cta_texto` = VALUES(`cta_texto`), `cta_url` = VALUES(`cta_url`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'el-recorrido', 'El recorrido · las sedes', 'tarjetas_foto', 70, 1, 'El recorrido', 'Seis ciudades, una sola nación', '<p>Seis ciudades que expresan la diversidad y riqueza de la Iglesia en el Perú. Cada sede será espacio de encuentro con el Santo Padre y reflejará una historia, una comunidad y una forma particular de vivir la fe.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `enlace_url`, `imagen_id`) VALUES
  (@sec, 10, 1, 'Lima', 'sedes/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p13.jpg')),
  (@sec, 20, 1, 'Callao', 'sedes/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p14.jpg')),
  (@sec, 40, 1, 'Cusco', 'sedes/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p16.jpg')),
  (@sec, 50, 1, 'Pucallpa', 'sedes/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p17.jpg'));
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `enlace_url`, `imagen_id`) VALUES
  (@sec, 30, 1, 'Chiclayo', 'Santa Cruz', 'sedes/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p15.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'tierra-de-santos', 'Tierra de santos', 'tarjetas_foto', 80, 1, 'Cinco santos, un mismo corazón', '<p>El Perú tiene una tradición de santidad que forma parte de la <strong>identidad espiritual</strong> de su pueblo.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `enlace_url`, `imagen_id`) VALUES
  (@sec, 10, 1, 'ORACIÓN', 'Santa Rosa\nde Lima', 'santos/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p02.jpg')),
  (@sec, 20, 1, 'CARIDAD', 'San Martín\nde Porres', 'santos/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p03.jpg')),
  (@sec, 30, 1, 'MISERICORDIA', 'San Juan\nMacías', 'santos/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p04.jpg')),
  (@sec, 40, 1, 'MISIÓN', 'San Francisco\nSolano', 'santos/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p05.jpg')),
  (@sec, 50, 1, 'PASTOR', 'Santo Toribio\nde Mogrovejo', 'santos/', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p06.jpg'));
-- Se quedan encendidas porque las lee otra página: colecta.
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('hero', 'falta-poco-encuentro', 'himno', 'abramos-el-corazon', 'subsidios-home', 'llega-al-peru', 'el-recorrido', 'tierra-de-santos', 'colecta');

-- ── Página «logo-y-lema» ───────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('logo-y-lema', 'Logo y lema', '/logo-y-lema/', 'Logo y lema · «Abramos el corazón» · León XIV en el Perú', 'El logotipo y el lema «Abramos el corazón» de la Visita Apostólica del Papa León XIV al Perú: qué significa, cómo se diseñó, su color y sus versiones.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'logo-y-lema');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `datos`, `imagen_id`)
VALUES (@pag, 'marca', 'Banda de la marca', 'cabecera_pagina', 10, 1, 'Abramos el corazón', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/brand/logo-lockup-hero.png'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'que-significa', '¿Qué significa? · El lema', 'texto_lectura', 20, 1, 'El lema', '¿Qué significa?', '<p>Lorem ipsum dolor sit amet consectetur <br>adipiscing elit primis at tortor maecenas.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'su-diseno', 'Su diseño', 'texto_lectura', 30, 1, 'Su diseño', '<p>Lorem ipsum dolor sit amet <br>consectetur adipiscing elit <br>primis at tortor maeceprimis <br>at tortor maecenasnas.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'su-color', 'Su color y sus versiones', 'tarjetas_foto', 40, 1, 'Su color\ny sus versiones', '<p>Lorem ipsum dolor sit amet consectetur adipiscing elit <br>primis at tortor maecenas.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `imagen_id`) VALUES
  (@sec, 10, 1, 'Versión principal del logotipo, en rojo', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/brand/logo-lockup-rojo-alt.png')),
  (@sec, 20, 1, 'Versión del logotipo en dorado', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/brand/logo-lockup-dorado.png')),
  (@sec, 30, 1, 'Versión del logotipo en negro', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/brand/logo-lockup-negro.png'));
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('marca', 'que-significa', 'su-diseno', 'su-color');

-- ── Página «noticias» ──────────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('noticias', 'Noticias', '/noticias/', 'Noticias · León XIV en el Perú', 'Actualidad y comunicaciones oficiales rumbo a la Visita Apostólica del Papa León XIV al Perú, del 11 al 16 de noviembre de 2026.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'noticias');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, '', 'Noticias', 'Actualidad y comunicaciones oficiales\nrumbo a la visita del Santo Padre.', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/noticias/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'ultimas-noticias', 'Últimas noticias', 'noticias', 20, 1, 'Actualidad', 'Lo más reciente', '<p>Las informaciones oficiales sobre la Visita Apostólica, publicadas por la Conferencia Episcopal Peruana.</p>', '{"detalle":true}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
-- Los bloques de «ultimas-noticias» se conservan tal cual.
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `datos`)
VALUES (@pag, 'videos', 'Vídeos', 'tarjetas_foto', 30, 1, 'Videos', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `enlace_url`) VALUES
  (@sec, 10, 1, '', ''),
  (@sec, 20, 1, '', ''),
  (@sec, 30, 1, '', '');
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'ultimas-noticias', 'videos');

-- ── Página «papa-leon-xiv» ─────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('papa-leon-xiv', 'Papa León XIV', '/papa-leon-xiv/', 'Papa León XIV · El regreso de nuestro Pastor', 'Quién es el Papa León XIV: Robert Francis Prevost, primer Papa de la Orden de San Agustín y primer Papa nacido en Estados Unidos con nacionalidad peruana. Su historia, su escudo y su magisterio.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'papa-leon-xiv');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, '267.º sucesor de Pedro', 'El *regreso* de', 'nuestro Pastor', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'quien-leon-xiv', 'Quién es León XIV', 'texto_apartados', 20, 1, 'En breve', '¿Quién es León XIV?', '<p><strong>Primer Papa perteneciente a la Orden de San Agustín y <br>primer Papa nacido en Estados Unidos y con nacionalidad <br>peruana</strong>. Fue elegido el 8 de mayo de 2025, en la tarde del <br>segundo día del cónclave, y tomó el nombre de León XIV.</p>', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p07.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, 'NOMBRE', 'Robert Francis Prevost, O.S.A.'),
  (@sec, 20, 1, 'NACIMIENTO', '14 de septiembre de 1955,\nChicago, Estados Unidos (Illinois)'),
  (@sec, 30, 1, 'NACIONALIDAD', 'Estadounidense y peruano\npor naturalización'),
  (@sec, 40, 1, 'ORDEN RELIGIOSA', 'Orden de San Agustín\nPrimera profesión: 1978\nVotos solemnes: 1981'),
  (@sec, 50, 1, 'ORDENACIÓN SACERDOTAL', '19 de junio de 1982, Roma'),
  (@sec, 60, 1, 'MISIÓN EN EL PERÚ', 'Chulucanas, Trujillo, Chiclayo\ny Callao, desde 1985'),
  (@sec, 70, 1, 'OBISPO DE CHICLAYO', '2015 – 2023'),
  (@sec, 80, 1, 'ELECCIÓN COMO PAPA', '8 de mayo de 2025'),
  (@sec, 90, 1, 'NOMBRE PONTIFICIO', 'León XIV'),
  (@sec, 100, 1, 'LEMA EPISCOPAL', '<em>In Illo uno unum</em>');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'raices-vocacion-agustiniana', 'Etapa 1955–1984 · Raíces y vocación agustiniana', 'tarjetas_foto', 30, 1, '1955 - 1984', 'Raíces y vocación\nagustiniana', '', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `texto`, `imagen_id`) VALUES
  (@sec, 10, 1, '<p><strong>Robert Francis Prevost</strong> nació en Chicago, Estados Unidos, <br>el 14 de septiembre de 1955. Es el menor de los tres hijos <br>de Louis Marius Prevost y Mildred Agnes Martínez. Sus <br>hermanos son Louis Martín y John Joseph.</p><p>Realizó sus estudios primarios en la Saint Mary School of <br>the Assumption y luego estudió en la Saint Augustine <br>Seminary High School, en Holland, Michigan, donde se <br>graduó en 1973. Ese mismo año ingresó a la Universidad <br>de Villanova, en Filadelfia, donde obtuvo en 1977 la <br>licenciatura en Matemáticas y cursó estudios de Filosofía.</p>', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p11.jpg')),
  (@sec, 20, 1, '<p>El 1 de septiembre de 1977 <strong>ingresó al noviciado de la Orden de <br>San Agustín</strong>, en Saint Louis, perteneciente a la Provincia del <br>Medio Oeste de Nuestra Madre del Buen Consejo. <strong class="pl-negro">Emitió sus <br>votos temporales</strong> el 2 de septiembre de 1978 y, mientras <br>continuaba su formación teológica en la Catholic Theological <br>Union de Chicago, obtuvo allí la licenciatura en Teología y <br><strong>realizó su profesión religiosa definitiva</strong> en 1981.</p><p>En septiembre de 1981 <strong>fue enviado a Roma para estudiar <br>Derecho Canónico</strong> en la Pontificia Universidad de Santo <br>Tomás de Aquino (Angelicum), residiendo en el Colegio <br>Internacional Santa Mónica. Fue ordenado diácono el 10 de <br>septiembre de 1981, en la parroquia Santa Clara de Montefalco, <br>en Grosse Pointe Park, diócesis de Detroit. El 19 de junio de 1982 <br>recibió la ordenación sacerdotal en la capilla de Santa <br>Mónica. En 1984 obtuvo la licenciatura en Derecho Canónico.</p>', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p08.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'misionero-formador-peru', 'Etapa 1985–1999 · Misionero y formador en el Perú', 'tarjetas_foto', 40, 1, '1985 – 1999', 'Misionero y\nformador en el Perú', '<strong>«Patrística»</strong>: Estudio de los Padres de la Iglesia.', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `texto`, `imagen_id`) VALUES
  (@sec, 10, 1, '<p>En 1984 obtuvo la licenciatura en Derecho Canónico y, al <br>año siguiente, mientras preparaba su tesis doctoral, fue <br>enviado a la misión agustiniana de <strong>Chulucanas, en la <br>región Piura.</strong> Allí permaneció un año, como vicepárroco <br>de la Catedral de la Sagrada Familia y canciller de la <br>entonces Prelatura Territorial de Chulucanas.</p><p>En 1985 defendió su tesis doctoral sobre <strong>el papel del <br>prior local en la Orden de San Agustín</strong>, publicada dos <br>años después. En 1987 regresó a Estados Unidos, donde <br>fue nombrado director de Vocaciones y director de <br>Misiones de su provincia agustiniana, con residencia en <br>Olympia Fields.</p>', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p09.jpg')),
  (@sec, 20, 1, '<p>En 1988 volvió al Perú, esta vez a la <strong>misión agustiniana <br>de Trujillo</strong>, para dirigir la primera casa de formación <br>conjunta de los vicariatos agustinianos de Chulucanas, <br>Iquitos y Apurímac. Allí fue prior de la comunidad <br>(1988–1992), director de Formación (1988–1998) y <br>maestro de profesos (1993–1998).</p><p>En la <strong>Arquidiócesis de Trujillo</strong> fue director de Estudios y <br>rector interino del Seminario Mayor San Carlos y San <br>Marcelo, donde enseñó Derecho Canónico, Teología <br>Moral y <strong class="t-wine">*Patrística</strong>. También ejerció como vicario judicial <br>y miembro del Colegio de Consultores de Trujillo.</p><p>En el ámbito pastoral, fue párroco de <strong>Nuestra Señora <br>Madre de la Iglesia</strong>, hoy parroquia Santa Rita de Casia <br>(1988–1999), y administrador de <strong>Nuestra Señora de <br>Montserrat </strong>(1992–1999). En 1998 fue elegido prior <br>provincial de la Provincia del Medio Oeste de Nuestra <br>Madre del Buen Consejo y regresó a Estados Unidos el 8 <br>de marzo de 1999 para iniciar su mandato.</p>', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p10.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'prior-general-obispo', 'Etapa 1999–2023 · De prior general a obispo de Chiclayo', 'tarjetas_foto', 50, 1, '1999 – 2023', 'De prior general a obispo de Chiclayo', '', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `texto`, `imagen_id`) VALUES
  (@sec, 10, 1, '<p>En 1999 inició su mandato como prior provincial de <br>la Provincia del Medio Oeste de Nuestra Madre del <br>Buen Consejo, con sede en Chicago. En el Capítulo <br>General de 2001 fue elegido <strong>prior general de la <br>Orden de San Agustín</strong> y, en 2007, fue confirmado <br>para un segundo mandato de seis años.</p><p>Al concluir su servicio en 2013, regresó a su <br>provincia en Chicago, donde fue director de <br>Formación en el convento de San Agustín, <br>además de primer consejero y vicario provincial. <br>El 3 de noviembre de 2014, el papa Francisco lo <br>nombró <strong>administrador apostólico de la diócesis <br>de Chiclayo y obispo titular de Sufar</strong>. Tomó <br>posesión de la diócesis el 7 de noviembre y recibió <br>la ordenación episcopal el 12 de diciembre, en la <br>catedral de Santa María, en la fiesta de Nuestra <br>Señora de Guadalupe. El 26 de septiembre de 2015 <br>fue nombrado obispo de Chiclayo.</p><p>En marzo de 2018 fue elegido <strong>segundo <br>vicepresidente de la Conferencia Episcopal <br>Peruana</strong>, donde también integró el Consejo <br>Económico y presidió la Comisión Episcopal de <br>Cultura y Educación, y fue miembro de la <br>Comisión de la Protección del Menor. En abril de <br>2020 fue nombrado como administrador <br>apostólico de la diócesis del Callao.</p>', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p01.jpg')),
  (@sec, 20, 1, '', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p02.jpg')),
  (@sec, 30, 1, '<p>El 30 de enero de 2023 fue llamado a Roma por <br>el papa Francisco para asumir como <strong>Prefecto <br>del Dicasterio para los Obispos y Presidente <br>de la Pontificia Comisión para América Latina</strong>, <br>dejando así su servicio pastoral en el Perú. Ese <br>mismo año, la Conferencia Episcopal Peruana <br>le otorgó la <strong>Medalla de Oro de Santo Toribio de <br>Mogrovejo</strong>, en reconocimiento a su servicio a la <br>Iglesia en el Perú, y la Universidad Católica <br>Santo Toribio de Mogrovejo (USAT), de Chiclayo, <br>le concedió el <strong>doctorado honoris causa en <br>Derecho</strong>. En noviembre de 2023, la Pontificia <br>Universidad Católica del Perú (PUCP) entregó la <br>medalla de Honor R.P. Jorge Dintilhac SS.CC. a <br>Robert Prevost en reconocimiento a su <br>trayectoria pastoral, su vínculo con la <br>comunidad universitaria y <strong>su labor como <br>miembro de la Asamblea Universitaria</strong> de la <br>PUCP entre 2017 y 2023.</p>', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p03.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'chiclayo-pontificado', 'Etapa 2023–hoy · De Chiclayo al pontificado', 'tarjetas_foto', 60, 1, '2023 – Hoy', 'De Chiclayo al pontificado', '<strong>«Prefecto»</strong>: Responsable del Dicasterio de la Santa Sede.', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `texto`, `imagen_id`) VALUES
  (@sec, 10, 1, '<p>El 30 de enero de 2023, el papa Francisco lo llamó <br>a Roma como <strong class="t-wine">*Prefecto</strong><strong> del Dicasterio para los <br>Obispos</strong> y Presidente de la Pontificia Comisión <br>para América Latina, elevándolo a la dignidad de <br>arzobispo. El 30 de septiembre de ese mismo año <br>fue nombrado cardenal, con la diaconía de Santa <br>Mónica, de la que tomó posesión el 28 de enero <br>de 2024.</p><p>El 6 de febrero de 2025, el papa Francisco lo <br>promovió al <strong>orden de los cardenales obispos</strong>, <br>asignándole el título de la Iglesia suburbicaria de <br>Albano. Durante la última hospitalización del Papa <br>Francisco, presidió el 3 de marzo el Rosario por la <br>salud del Pontífice en la Plaza de San Pedro.</p>', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p04.jpg')),
  (@sec, 20, 1, '<p>El cónclave comenzó el 7 de mayo de 2025. Al <br>día siguiente, 8 de mayo, fue elegido Papa y <br>tomó el nombre de <strong>León XIV</strong>. Es <strong>el 267.º <br>Pontífice</strong>, el primero procedente de los <br>Estados Unidos de América y el primero <br>perteneciente a la Orden de San Agustín. El 18 <br>de mayo presidió la celebración eucarística <br>de inicio de su ministerio petrino.</p>', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p05.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'escudo-lema', 'Heráldica · El escudo y el lema', 'texto_lectura', 70, 1, 'Heráldica', 'El escudo y el lema', '<p>El escudo está dividido diagonalmente en dos sectores. La parte <br>superior tiene fondo azul y presenta un lirio blanco. La inferior, <br>sobre fondo claro, lleva la imagen que recuerda a la Orden de <br>San Agustín: un libro cerrado sobre el que descansa un corazón <br>traspasado por una flecha.</p><p>Esa imagen evoca la conversión de San Agustín, que él mismo <br>explicó con las palabras <em>«Vulnerasti cor meum verbo tuo»</em>: has <br>traspasado mi corazón con tu Palabra.</p><p>El lema, <em class="pl-em-fuerte">«In Illo uno unum»</em>, procede de un sermón de San <br>Agustín, la Exposición del Salmo 127, y significa que aunque los <br>cristianos seamos muchos, en el único Cristo somos uno. León <br>XIV confirmó en lo esencial el escudo y el lema que había <br>elegido para su consagración episcopal en Chiclayo.</p>', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/papa-leon-xiv/p06.png'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'magisterio-hasta-hoy', 'Documentos · Su magisterio hasta hoy', 'noticias', 80, 1, 'Documentos', 'Su magisterio hasta hoy', '<p>Los documentos que marcan el inicio de su pontificado y <br>expresan las principales líneas de su magisterio. El contenido <br>completo puede consultarse en la web de la Santa Sede.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`, `datos`) VALUES
  (@sec, 10, 1, 'Dilexi te', '<p><cite>Dilexi te</cite><strong> («Te he amado»)</strong> es su primera <br>exhortación apostólica y uno de los primeros <br>grandes documentos de su pontificado. En <br>ella, León XIV reflexiona sobre el amor de <br>Dios hacia los pobres y el compromiso de la <br>Iglesia con ellos.</p>', 'Texto completo', 'noticias/', '{"fecha":"4 de octubre de 2025","fuente":"Exhortación Apostólica"}'),
  (@sec, 20, 1, 'Magnifica humanitas', '<p><cite>Magnifica humanitas</cite> es la primera <br>encíclica de León XIV. Reflexiona sobre la <br>protección de la persona y su dignidad en <br>la era de la inteligencia artificial, así como <br>sobre sus implicaciones para la vida social.</p>', 'Texto completo', 'noticias/', '{"fecha":"Firma: 15 de mayo de 2026 | Publicación: 25 de mayo de 2026","fuente":"Carta Encíclica"}');
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'quien-leon-xiv', 'raices-vocacion-agustiniana', 'misionero-formador-peru', 'prior-general-obispo', 'chiclayo-pontificado', 'escudo-lema', 'magisterio-hasta-hoy');

-- ── Página «preguntas-frecuentes» ──────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('preguntas-frecuentes', 'Preguntas frecuentes', '/preguntas-frecuentes/', 'Preguntas frecuentes · Viaje de León XIV al Perú', 'Fechas, sedes, cómo asistir, voluntariado y cómo seguir la visita del Papa León XIV al Perú, del 11 al 16 de noviembre de 2026.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'preguntas-frecuentes');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, 'Diecinueve respuestas', 'Preguntas frecuentes', 'Lo que más se pregunta, respondido con lo que hay. Cuando algo no está confirmado, aquí lo dice.', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/preguntas-frecuentes/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `datos`)
VALUES (@pag, 'visita', 'La visita', 'texto_apartados', 20, 1, 'Preguntas', 'La visita', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, '¿Cuándo viene el Papa León XIV al Perú?', 'Del 11 al 16 de noviembre de 2026. Es la tercera etapa de su primera gira sudamericana, después de Uruguay y Argentina. La Santa Sede lo anunció el 5 de agosto de 2026.'),
  (@sec, 20, 1, '¿Qué ciudades visitará?', 'Lima y Callao, Chiclayo y Santa Cruz, Cusco y Pucallpa. El orden del viaje y los días que corresponden a cada sede no se han publicado.'),
  (@sec, 30, 1, '¿Por qué esas seis?', 'Son la capital y su arquidiócesis primada, con la provincia del Callao; la diócesis que el Santo Padre pastoreó entre 2015 y 2023, con el pueblo de Santa Cruz; la Iglesia andina más antigua del país y un vicariato apostólico amazónico. Costa, sierra y selva.'),
  (@sec, 40, 1, '¿Cuándo se sabrá el programa?', 'No hay fecha anunciada. El programa detallado lo publica la Oficina de Prensa de la Santa Sede, habitualmente algunas semanas antes del viaje, y la Conferencia Episcopal Peruana lo difunde en el país.'),
  (@sec, 50, 1, '¿Se transmitirán los actos?', 'Los viajes apostólicos se transmiten habitualmente por los medios de la Santa Sede y por las emisoras y canales de la Iglesia en el país. Los enlaces concretos se publicarán cuando existan.'),
  (@sec, 60, 1, '¿Hay cuentas oficiales en redes sociales?', 'Todavía no. Cuando existan se anunciarán en este sitio. Cualquier cuenta que hoy diga representar la visita no es oficial.'),
  (@sec, 70, 1, '¿Dónde consigo materiales para mi parroquia?', 'En la página de materiales de pastoral. Guía de oración, subsidio de catequesis, cantoral y banners, todo de descarga libre, conforme se vayan aprobando.');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `datos`)
VALUES (@pag, 'asistir-actos', 'Actividades', 'texto_apartados', 30, 1, 'Preguntas', 'Actividades', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, '¿Habrá que inscribirse para asistir?', 'Por confirmar. Las condiciones de acceso forman parte del programa oficial y todavía no se han publicado. No des por válida ninguna inscripción que no proceda de la Conferencia Episcopal Peruana o de tu diócesis.'),
  (@sec, 20, 1, '¿Las misas tendrán entradas?', 'Por confirmar. En otros viajes apostólicos algunas celebraciones han sido de acceso libre y otras han requerido pase por razones de aforo y seguridad.'),
  (@sec, 30, 1, '¿Los encuentros son gratuitos?', 'Sí. Todos los actos de la Visita Apostólica son gratuitos. Si alguien te cobra por una entrada, por una inscripción o por un pase, no es oficial.'),
  (@sec, 40, 1, '¿Qué debo llevar?', 'Agua, protección para el sol, calzado cómodo, tu documento de identidad y tus medicinas. La guía del peregrino lo detalla.');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `datos`)
VALUES (@pag, 'voluntariado', 'Voluntariado', 'texto_apartados', 40, 1, 'Preguntas', 'Voluntariado', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, '¿Quién puede ser voluntario?', 'Hay seis servicios y un lugar para cada talento: resguardo y orden, acogida y hospitalidad, comunicación, logística, primeros auxilios y traducción e interpretación. La inscripción es la Fase 01 y se hace por internet.'),
  (@sec, 20, 1, '¿Qué documentos me pedirán?', 'En la Fase 02 se solicitan carta de recomendación de un sacerdote, religioso o religiosa u obispo, declaración o certificado de antecedentes judiciales y penales, entrevista personal según necesidad y evaluación psicológica cuando sea posible. Nada de eso se sube a este sitio: la organización indicará el canal.'),
  (@sec, 30, 1, '¿Puedo elegir el servicio?', 'Indicas tu preferencia al inscribirte. La asignación definitiva se comunica en la Fase 03, junto con la credencial.'),
  (@sec, 40, 1, '¿Qué hacen con mis datos?', 'Se usan únicamente para el fin por el que los diste: avisarte de una publicación, responder tu consulta o gestionar tu inscripción como voluntario. La política de privacidad lo detalla.');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `datos`)
VALUES (@pag, 'este-sitio', 'Sobre la web', 'texto_apartados', 50, 1, 'Preguntas', 'Sobre la web', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, '¿Usa cookies?', 'No. Este sitio no instala cookies ni tiene analítica ni scripts de seguimiento. Solo usa almacenamiento del navegador para guardar el borrador del formulario de voluntariado y para no repetirte el aviso de la portada.');
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'visita', 'asistir-actos', 'voluntariado', 'este-sitio');

-- ── Página «prensa» ────────────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('prensa', 'Prensa', '/prensa/', 'Prensa · Viaje de León XIV al Perú', 'Acreditación, contacto y condiciones de uso del material gráfico para los medios que cubran la Visita Apostólica del Papa León XIV al Perú.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'prensa');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, 'Para medios', 'Prensa', 'Acreditación, contacto y condiciones de uso del material gráfico.', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/prensa/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'como-acreditarse', 'Acreditación', 'texto_lectura', 20, 1, 'PROCESO AÚN NO HABILITADO', 'Acreditación', '<p>El <strong>Ministerio de Relaciones <br>Exteriores</strong> estará a cargo <br>del proceso de acreditación <br>de prensa. La fecha de inicio <br>y los requisitos se <br><strong>comunicarán <br>oportunamente a través de <br>los canales oficiales: </strong>página <br>web de la Cancillería y la <br>web oficial de la visita del <br>Papa León XIV.</p>', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/prensa/p01.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `datos`)
VALUES (@pag, 'uso-imagenes-escudo', 'Uso de imágenes y del escudo', 'texto_apartados', 30, 1, 'Material', 'Uso de imágenes y del escudo', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, 'Retrato pontificio', 'El retrato oficial se publica sin recortes sobre el rostro, sin filtros de color y sin\ntexto superpuesto. Crédito: Santa Sede.'),
  (@sec, 20, 1, 'Escudo pontificio', 'Conserva siempre sus esmaltes propios. No se recorta, no se deforma y no se\nusa como elemento decorativo repetido.'),
  (@sec, 30, 1, 'Citas', 'Las palabras del Santo Padre se citan por su fuente oficial. Este sitio no publica\nninguna frase suya que no conste en un documento de la Santa Sede.'),
  (@sec, 40, 1, 'Kit de prensa', 'Dosier, logotipos y fotografías en alta resolución.');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `cta_texto`, `cta_url`, `datos`)
VALUES (@pag, 'contacto-prensa', 'Botón de contacto', 'destacado', 40, 1, 'Contacto de prensa', 'Contacto', 'contacto/', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `cta_texto` = VALUES(`cta_texto`), `cta_url` = VALUES(`cta_url`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `titulo`, `datos`)
VALUES (@pag, 'multimedia', 'Multimedia', 'tarjetas_foto', 50, 1, 'Multimedia', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'como-acreditarse', 'uso-imagenes-escudo', 'contacto-prensa', 'multimedia');

-- ── Página «santos» ────────────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('santos', 'Santos del Perú', '/santos/', 'Cinco Santos, un mismo corazón · Santos del Perú · León XIV en el Perú', 'Los santos del Perú: cinco caminos distintos y una misma llamada, vivir la fe y hacerla visible en la vida cotidiana. Santa Rosa de Lima, San Martín de Porres, San Juan Macías, San Francisco Solano y Santo Toribio de Mogrovejo.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'santos');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, '', 'Cinco Santos, un mismo corazón', 'Son cinco caminos distintos, pero una misma llamada: vivir la fe y hacerla visible en la vida cotidiana.', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/santos/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'los-cinco-santos', 'Los cinco santos', 'personas', 20, 1, 'Siglos XVI y XVII', 'Cinco santos, un mismo corazón', '<p>En los siglos XVI y XVII, cinco figuras dejaron una huella profunda en la vida de la Iglesia. Sus vidas estuvieron marcadas por la oración, la misión, la caridad, la defensa de los más vulnerables y el encuentro con distintas realidades del Perú.</p>', '{"detalle":true}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `slug`, `texto`, `datos`, `imagen_id`) VALUES
  (@sec, 10, 1, 'Oración · Entrega · Servicio', 'Santa Rosa de Lima', 'santa-rosa-de-lima', '<p>Isabel Flores de Oliva, conocida como Santa Rosa de Lima, nació en Lima en 1586 y dedicó su vida a Dios desde una profunda experiencia de oración y entrega. Vivió como laica consagrada y perteneció a la Tercera Orden de Santo Domingo.</p><p>En medio de una vida sencilla, hizo del servicio a los pobres y enfermos una expresión concreta de su amor a Dios. Atendía a personas necesitadas y convirtió parte de su propia casa en un espacio de acogida para quienes sufrían.</p><p>Murió en Lima en 1617, a los 31 años. Fue canonizada por el papa Clemente X el 12 de abril de 1671, convirtiéndose en la primera santa de América. Es patrona del Perú, de América y de Filipinas.</p>', '{"anios":"1586 – 1617","resumen":"Una vida entregada a Dios y al servicio de los más necesitados."}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p02.jpg')),
  (@sec, 20, 1, 'Caridad · Humildad · Fraternidad', 'San Martín de Porres', 'san-martin-de-porres', '<p>Martín de Porres nació en Lima en 1579. Creció en una sociedad marcada por profundas diferencias sociales y raciales, y desde joven mostró una especial sensibilidad hacia los pobres y enfermos.</p><p>Ingresó al Convento del Santísimo Rosario de los dominicos de Lima, donde desarrolló diferentes labores de servicio antes de convertirse en fraile. Su servicio no hizo distinciones: atendía a personas de todas las condiciones y manifestó un especial amor por los animales y por toda la creación.</p><p>La tradición lo recuerda como el «Santo de la escoba», imagen que expresa su humildad. Fue canonizado por el papa Juan XXIII el 6 de mayo de 1962.</p>', '{"anios":"1579 – 1639","resumen":"Hizo del servicio humilde un camino de santidad."}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p03.jpg')),
  (@sec, 30, 1, 'Misericordia · Servicio · Solidaridad', 'San Juan Macías', 'san-juan-macias', '<p>Juan Macías nació en Ribera del Fresno, España, en 1585. Huérfano desde muy joven, trabajó como pastor antes de emigrar a América. Llegó al Perú y se estableció en Lima, donde ingresó a la Orden de Predicadores.</p><p>Durante más de dos décadas fue hermano portero del convento dominico de La Magdalena. Desde ese lugar desarrolló una intensa labor de ayuda a los pobres: su servicio comenzaba en la puerta del convento y se extendía mediante la distribución de alimentos y limosnas.</p><p>Su amistad con San Martín de Porres es otro elemento importante de la historia de la santidad limeña. Fue canonizado por Pablo VI el 28 de septiembre de 1975.</p>', '{"anios":"1585 – 1645","resumen":"Desde la sencillez, hizo de la misericordia una forma de vida."}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p04.jpg')),
  (@sec, 40, 1, 'Misión · Encuentro · Evangelización', 'San Francisco Solano', 'san-francisco-solano', '<p>Francisco Solano nació en Montilla, España, en 1549. Ingresó a la Orden Franciscana y fue ordenado sacerdote en 1576. Llegó a América en 1589 y desembarcó en Paita, Piura.</p><p>Desde allí emprendió un largo recorrido por territorios del actual Perú y otros países de Sudamérica. Aprendió lenguas indígenas para comunicarse con los pueblos a los que servía y utilizó también la música como instrumento de evangelización.</p><p>Su vida se vincula especialmente con la idea de una Iglesia en salida: caminar, encontrarse con las personas y llevar el Evangelio allí donde se encuentran. Murió en Lima el 14 de julio de 1610.</p>', '{"anios":"1549 – 1610","resumen":"Caminó grandes distancias para llevar el Evangelio al encuentro de los pueblos."}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/index/p05.jpg')),
  (@sec, 50, 1, 'Pastor · Misión · Defensa de los pueblos', 'Santo Toribio de Mogrovejo', 'santo-toribio-de-mogrovejo', '<p>Toribio Alfonso de Mogrovejo nació en Mayorga, España, en 1538. Estudió Derecho y fue profesor en la Universidad de Salamanca, hasta que fue elegido para asumir el Arzobispado de Lima. Llegó al Perú en 1581.</p><p>Concibió su ministerio como una misión que exigía salir al encuentro de las comunidades. Recorrió extensas regiones del territorio peruano, aprendió quechua y promovió la evangelización en lenguas nativas. Participó decisivamente en el III Concilio Limense.</p><p>Destacó por la defensa de los pueblos indígenas frente a abusos. Murió en Zaña en 1606 y fue canonizado en 1726: en 2026 se conmemoran <strong>300 años de su canonización</strong>, algo que el propio Santo Padre destacó en su encuentro con los obispos del Perú en enero de 2026.</p>', '{"anios":"1538 – 1606","resumen":"Un pastor en salida que recorrió el Perú para anunciar el Evangelio y acompañar a su pueblo."}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/santos/p01.jpg'));
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'los-cinco-santos');

-- ── Página «sedes» ─────────────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('sedes', 'Sedes', '/sedes/', 'Sedes · Seis ciudades, una sola nación · León XIV en el Perú', 'Lima y Callao, Chiclayo y Santa Cruz, Cusco y Pucallpa: las seis ciudades de la Visita Apostólica del Papa León XIV al Perú, del 11 al 16 de noviembre de 2026.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'sedes');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, '', 'Seis ciudades, una sola nación', 'La costa, el norte, los Andes y la Amazonía. Seis maneras de ser Iglesia en el mismo país.', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'estas-cuatro', 'El anuncio de la visita', 'generica', 20, 1, '', 'El anuncio de la visita', '', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `texto`) VALUES
  (@sec, 10, 1, '<p>El 5 de agosto de 2026, <strong>la Santa Sede anunció <br>oficialmente que el Papa León XIV visitará Lima</strong>, <br>Chiclayo, Cusco y Pucallpa del 11 al 16 de noviembre <br>de 2026. El anuncio confirmó las seis ciudades, pero <br>dejó pendiente la publicación del programa <br>detallado del viaje.</p><p>La elección de estas sedes permite recorrer distintos <br>rostros de la Iglesia peruana: Lima - y la provincia <br>del Callao - sede de la Iglesia metropolitana y <br>centro histórico de la evangelización del país; <br>Chiclayo - y el pueblo de Santa Cruz - la diócesis <br>que León XIV pastoreó como obispo; Cusco, con su <br>profunda tradición de fe y evangelización en los <br>Andes; y Pucallpa, en el corazón de la Amazonía, <br>donde la Iglesia vive su misión junto a comunidades <br>y pueblos diversos.</p>'),
  (@sec, 40, 1, '<p>Pero este recorrido también tiene un <br>significado personal para el Santo Padre. <br><strong>León XIV llegó al Perú como misionero en <br>1985</strong> y desarrolló aquí buena parte de su <br>vida pastoral: primero en Chulucanas y luego <br>en Trujillo, antes de ser nombrado obispo de <br>Chiclayo en 2015. En 2015 obtuvo además la <br>nacionalidad peruana por naturalización.</p><p>Costa, sierra y selva. Seis ciudades, seis <br>realidades eclesiales y una misma nación <br>que se prepara para recibir a un Papa que <br>conoce de cerca la vida, fe y misión de la <br>Iglesia en el Perú.</p>');
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `imagen_id`) VALUES
  (@sec, 20, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p01.jpg')),
  (@sec, 30, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p02.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'las-cuatro-sedes', 'Las sedes', 'tarjetas_foto', 30, 1, 'Las sedes', 'Cuatro ciudades, un solo pueblo', '<p>Cada sede tendrá su programa oficial, sus lugares de encuentro, información para peregrinos, accesos y material multimedia.</p>', '{"detalle":true}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `slug`, `texto`, `enlace_texto`, `enlace_url`, `datos`, `imagen_id`) VALUES
  (@sec, 10, 1, 'Arquidiócesis de Lima', 'Lima', 'lima', '<p>Lima será la puerta de entrada de la Visita Apostólica del Papa León XIV al Perú y uno de los espacios de encuentro del Santo Padre con la Iglesia y el pueblo peruano.</p><p><em>Esta página quedará preparada para incorporar el programa oficial, los lugares de encuentro, la información para peregrinos, los accesos, las noticias y el material multimedia.</em></p>', 'Conoce Lima', 'sedes/lima/', '{"resumen":"Lima será la puerta de entrada de la Visita Apostólica y uno de los espacios de encuentro del Santo Padre con la Iglesia y el pueblo peruano."}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p14.jpg')),
  (@sec, 20, 1, 'Diócesis de Chiclayo', 'Chiclayo', 'chiclayo', '<p>Chiclayo ocupa un lugar especial en la historia pastoral del Papa León XIV. En esta tierra, el Santo Padre sirvió como obispo entre 2015 y 2023 y compartió durante años la vida y la fe de su pueblo.</p><p>Por ese vínculo, esta sede tendrá un módulo propio —«León XIV y Chiclayo»— con fotografías históricas, testimonios y momentos de su ministerio episcopal.</p><p><em>Pendiente de recibir el material gráfico de la diócesis.</em></p>', 'Conoce Chiclayo', 'sedes/chiclayo/', '{"resumen":"Chiclayo ocupa un lugar especial en la historia pastoral del Papa León XIV."}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p09.jpg')),
  (@sec, 30, 1, 'Arquidiócesis del Cusco', 'Cusco', 'cusco', '<p>Cusco se presenta desde su profunda identidad andina, cultural y religiosa, y no como una postal turística. La Iglesia andina más antigua del país acogerá al Santo Padre en una jornada centrada en la comunidad, los jóvenes y las familias.</p><p><em>Esta página incorporará las actividades del Papa, los lugares de encuentro, las indicaciones para peregrinos y los contenidos propios de la Arquidiócesis.</em></p>', 'Conoce Cusco', 'sedes/cusco/', '{"resumen":"Cusco se presenta desde su profunda identidad andina, cultural y religiosa."}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p10.jpg')),
  (@sec, 40, 1, 'Vicariato de Pucallpa', 'Pucallpa', 'pucallpa', '<p>El encuentro con la Amazonía, sus comunidades y los pueblos originarios. Una jornada centrada en el cuidado de la casa común y en la realidad de la Iglesia amazónica.</p><p><em>Esta página incorporará las actividades oficiales, las comunidades participantes, los lugares de encuentro, los accesos y los contenidos multimedia.</em></p>', 'Conoce Pucallpa', 'sedes/pucallpa/', '{"resumen":"El encuentro con la Amazonía, sus comunidades y los pueblos originarios."}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p12.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `subtitulo`, `cta_texto`, `cta_url`, `datos`)
VALUES (@pag, 'lima', 'Lima — Callao · franja de la página', 'generica', 40, 1, '11, 12 y 16 de noviembre', 'Lima - Callao', 'Arquidiócesis de Lima · Primada del Perú\nDiócesis del Callao', 'Ver Agenda', 'agenda/', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `subtitulo` = VALUES(`subtitulo`), `cta_texto` = VALUES(`cta_texto`), `cta_url` = VALUES(`cta_url`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, '¿Por qué esta ciudad?', '<p>Lima y Callao la puerta de entrada del Santo Padre <br>al Perú. La Diócesis del Callao, es una jurisdicción <br>eclesiástica con 59 años de creación. Por su parte, <br>Lima es la sede de la Arquidiócesis Primada del Perú <br>y fue creada el 12 de febrero de 1546 por el papa <br>Paulo III y, desde sus primeros años, se convirtió en <br>un importante centro desde el que se extendió la <br>misión evangelizadora por amplios territorios de <br>América del Sur. En 1572 recibió el título de Primada <br>del Perú, posteriormente confirmado en 1834 y <br>ratificado en 1943.</p>'),
  (@sec, 60, 1, 'Su relación con León XIV', '<p>El vínculo de León XIV con Lima está unido a su <br>servicio a la Iglesia del Perú. Como obispo de <br>Chiclayo, fue elegido en marzo de 2018 segundo <br>vicepresidente de la Conferencia Episcopal Peruana, <br>además de integrar su Consejo Económico y presidir <br>la Comisión Episcopal de Cultura y Educación. En <br>abril de 2020 fue nombrado también administrador <br>apostólico de la diócesis del Callao.</p><p>De 2020 hasta 2021, el Papa León XIV sirvió como <br>administrador apostólico de la Diócesis del Callao. En <br>plena pandemia de 2020 llevó las riendas de la <br>Iglesia del Callao y, en medio de las dificultades <br>propias de ese tiempo, se entregó completamente a <br>la tarea de velar por el cuidado de esta Diócesis.</p>'),
  (@sec, 80, 1, '¿Qué encontraremos?', '<p>Lima reúne algunas de las expresiones más <br>significativas de la fe del pueblo peruano: la Basílica <br>Catedral, la memoria de santo Toribio de Mogrovejo, la <br>devoción a Santa Rosa de Lima y la profunda tradición <br>de piedad popular expresada en el Señor de los <br>Milagros.</p><p>La procesión del Cristo de Pachacamilla convoca cada <br>octubre a miles de fieles en las calles de la capital. El <br>propio León XIV se hizo cercano a esta tradición <br>cuando, en octubre de 2025, saludó desde Roma a la <br>Hermandad del Señor de los Milagros con ocasión de <br>su tradicional procesión.</p>');
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `imagen_id`) VALUES
  (@sec, 20, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p03.jpg')),
  (@sec, 30, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p14.jpg')),
  (@sec, 50, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p05.jpg')),
  (@sec, 70, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p04.jpg')),
  (@sec, 90, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p06.jpg'));
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `texto`) VALUES
  (@sec, 40, 1, '<p>Esta historia tiene en <strong>Santo Toribio de <br>Mogrovejo </strong>una de sus figuras centrales. <br>Segundo arzobispo de Lima, recorrió <br>extensamente su territorio, impulsó la formación <br>del clero y promovió la evangelización en las <br>lenguas de los pueblos originarios. La Santa <br>Sede lo reconoce como una figura fundamental <br>de la evangelización de América y patrono del <br>Episcopado Latinoamericano.</p>');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `subtitulo`, `cta_texto`, `cta_url`, `datos`)
VALUES (@pag, 'chiclayo', 'Chiclayo — Santa Cruz · franja de la página', 'generica', 50, 1, '13 - 14 de noviembre', 'Chiclayo - Santa Cruz', 'Diócesis de Chiclayo · Lambayeque y provincia de Santa Cruz, Cajamarca', 'Ver Agenda', 'agenda/', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `subtitulo` = VALUES(`subtitulo`), `cta_texto` = VALUES(`cta_texto`), `cta_url` = VALUES(`cta_url`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, '¿Por qué esta ciudad?', '<p>Hay lugares que forman parte de una historia y otros <br>que se convierten en parte de la vida. Para León XIV, <br>Chiclayo es ambas cosas. Es la diócesis que lo <br>recibió como administrador apostólico en 2014, <br>donde fue ordenado obispo y donde ejerció su <br>ministerio pastoral durante más de ocho años.</p><p>La Diócesis de Chiclayo fue creada el 17 de diciembre <br>de 1956 por el papa Pío XII, mediante la bula Sicut <br>materfamilias. Su jurisdicción comprende todo el <br>departamento de Lambayeque y la provincia <br>cajamarquina de Santa Cruz. Su sede es la iglesia <br>Santa María Catedral, cuya construcción comenzó en <br>1869 y que hoy forma parte de la memoria y de la <br>vida de la Iglesia local.</p>'),
  (@sec, 40, 1, 'Su relación con León XIV', '<p>El vínculo entre León XIV y Chiclayo es <br>profundamente pastoral y personal. <br>El 3 de noviembre de 2014, el papa Francisco lo <br>nombró administrador apostólico de la diócesis. El 7 <br>de noviembre tomó posesión y, el 12 de diciembre, <br>fiesta de Nuestra Señora de Guadalupe, recibió la <br>ordenación episcopal en la Catedral de Santa <br>María. El 26 de septiembre de 2015 fue nombrado <br>obispo de Chiclayo.</p><p>Durante sus años en la diócesis, recorrió sus <br>comunidades, acompañó a sacerdotes y fieles y <br>participó activamente en la vida de la Iglesia local. <br>En marzo de 2018 fue elegido segundo <br>vicepresidente de la Conferencia Episcopal <br>Peruana y, desde esa responsabilidad, continuó <br>sirviendo a la Iglesia del país.</p><p>En 2023, al ser llamado a Roma, la Conferencia <br>Episcopal Peruana le concedió la Medalla de Oro <br>de Santo Toribio de Mogrovejo y la Universidad <br>Católica Santo Toribio de Mogrovejo (USAT) le <br>otorgó el doctorado honoris causa en Derecho, en <br>reconocimiento a su servicio a la Iglesia en el Perú.</p>'),
  (@sec, 60, 1, '¿Qué esperar?', '<p>En Lambayeque, la fe se expresa en sus parroquias, <br>peregrinaciones y celebraciones, y encuentra en <br>Ciudad Eten uno de sus lugares más significativos. Allí, <br>la tradición del Milagro Eucarístico de 1649 ha dado <br>origen a una arraigada devoción y a numerosas <br>peregrinaciones de fieles. Durante sus años como <br>obispo, Robert Prevost acompañó personalmente esta <br>tradición y llevó a Roma documentación sobre su <br>historia y miles de testimonios de fe.</p>');
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `imagen_id`) VALUES
  (@sec, 20, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p07.jpg')),
  (@sec, 50, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p09.jpg'));
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `texto`, `imagen_id`) VALUES
  (@sec, 30, 1, 'Palabras del proclamado\nPapa León XIV a su antigua\ndiócesis en Chiclayo:', '<p>“Mi querida diócesis de Chiclayo, en el <br>Perú, donde un pueblo fiel ha <br>acompañado a su obispo, ha compartido <br>su fe y ha dado tanto, tanto, para seguir <br>siendo Iglesia fiel de Jesucristo”.</p>', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p08.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `subtitulo`, `cta_texto`, `cta_url`, `datos`)
VALUES (@pag, 'cusco', 'Cusco · franja de la página', 'generica', 60, 1, '15 de noviembre', 'Cusco', 'Arquidiócesis del Cusco', 'Ver Agenda', 'agenda/', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `subtitulo` = VALUES(`subtitulo`), `cta_texto` = VALUES(`cta_texto`), `cta_url` = VALUES(`cta_url`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, '¿Por qué esta ciudad?', '<p>En Cusco, la fe tiene raíces profundas. <strong>La Iglesia llegó <br>a los Andes en los primeros tiempos de la <br>evangelización del Perú</strong> y, desde entonces, la vida <br>cristiana fue tomando rostro propio en sus <br>comunidades, celebraciones y tradiciones. La antigua <br>sede episcopal del Cusco fue elevada a <br>arquidiócesis en 1943.</p><p>Hoy, esa historia sigue viva. Se reza en castellano y en <br>quechua; se celebra en las ciudades, en los pueblos <br>y en las comunidades de altura; y la fe se expresa en <br>peregrinaciones, fiestas y devociones que forman <br>parte de la identidad del pueblo cusqueño.</p>'),
  (@sec, 30, 1, 'Su relación con León XIV', '<p>En Trujillo, entre 1988 y 1998, dirigió la primera casa de <br>formación conjunta de los vicariatos agustinianos de <br>Chulucanas, Iquitos y Apurímac. Desde allí acompañó <br>la formación de religiosos llamados a servir en <br>territorios tan diversos como los Andes y la Amazonía.</p><p>Por eso, aunque Cusco no forma parte de su historia <br>pastoral directa, sí representa una de las realidades de <br>la Iglesia peruana que León XIV conoció y valoró desde <br>su experiencia misionera y formativa.</p>'),
  (@sec, 50, 1, '¿Qué esperar?', '<p>El Señor de los Temblores, Patrón Jurado del Cusco, <br>cuya procesión del Lunes Santo detiene la ciudad <br>entera. El Corpus Christi cusqueño, con sus <br>imágenes recorriendo las calles hasta la catedral. Y <br>la Virgen de los Remedios, patrona de la <br>arquidiócesis. Cusco no necesita que le enseñen a <br>recibir: lleva siglos haciéndolo.</p>');
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `imagen_id`) VALUES
  (@sec, 20, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p10.jpg')),
  (@sec, 40, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p11.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `subtitulo`, `cta_texto`, `cta_url`, `datos`)
VALUES (@pag, 'pucallpa', 'Pucallpa · franja de la página', 'generica', 70, 1, '15 de noviembre', 'Pucallpa', 'Vicariato Apostólico de Pucallpa · Ucayali', 'Ver Agenda', 'agenda/', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `subtitulo` = VALUES(`subtitulo`), `cta_texto` = VALUES(`cta_texto`), `cta_url` = VALUES(`cta_url`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, '¿Por qué esta ciudad?', '<p><strong>Pucallpa abre la Visita Apostólica al corazón de la <br>Amazonía peruana.</strong> No es sede de una diócesis, sino <br>de un Vicariato Apostólico: una circunscripción <br>eclesiástica destinada a territorios de misión y <br>directamente dependiente de la Santa Sede.</p><p>El Vicariato Apostólico de Pucallpa fue creado el 2 de <br>marzo de 1956 por el papa Pío XII, a partir de la división <br>del antiguo Vicariato Apostólico de Ucayali. Su territorio <br>abarca más de 52 000 kilómetros cuadrados y <br>comprende las provincias de Coronel Portillo y Padre <br>Abad, parte de Atalaya, en Ucayali, y la parte oriental <br>de Puerto Inca, en Huánuco. Su sede está en la ciudad <br>de Pucallpa, donde se encuentra la Catedral de la <br>Inmaculada Concepción.</p><p>Que Pucallpa forme parte de las cinco sedes de la <br>Visita Apostólica expresa algo esencial: la Amazonía no <br>está al margen de la vida de la Iglesia en el Perú. Es <br>parte de su rostro, misión y esperanza.</p>'),
  (@sec, 30, 1, 'Su relación con León XIV', '<p>La relación de León XIV con la Amazonía peruana se <br>remonta a sus años como misionero y formador en <br>el país.</p><p>Cuando regresó al Perú en 1988, fue enviado a Trujillo <br>para dirigir la primera casa de formación conjunta <br>de los vicariatos agustinianos de Chulucanas, Iquitos <br>y Apurímac. Allí acompañó durante una década la <br>formación de religiosos llamados a servir en <br>realidades muy distintas del país, entre ellas la <br>Amazonía.</p><p>Años después, ya como prefecto del Dicasterio para <br>los Obispos y presidente de la Pontificia Comisión <br>para América Latina, su servicio en la Iglesia universal <br>lo puso en contacto con numerosas Iglesias <br>particulares y territorios de misión de América Latina.</p><h3>¿Qué esperar?</h3><p>La misión del Vicariato se desarrolla junto a <br>poblaciones urbanas, rurales y comunidades <br>indígenas de Ucayali. Su labor pastoral comprende <br>la evangelización, la formación de agentes <br>pastorales, el acompañamiento de las comunidades <br>y el servicio a los pueblos que habitan este territorio.</p><p>En esta tierra, la misión de la Iglesia también está <br>estrechamente vinculada al cuidado de la vida y de <br>la casa común. La Amazonía no es únicamente el <br>escenario de la visita: es una realidad humana, <br>cultural y espiritual que el Santo Padre viene a <br>encontrar y escuchar.</p>');
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `imagen_id`) VALUES
  (@sec, 20, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p12.jpg')),
  (@sec, 40, 1, (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/sedes/p13.jpg'));
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'estas-cuatro', 'las-cuatro-sedes', 'lima', 'chiclayo', 'cusco', 'pucallpa');

-- ── Página «subsidios» ─────────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('subsidios', 'Subsidios pastorales', '/subsidios/', 'Subsidios Pastorales · León XIV en el Perú', 'Cinco subsidios pastorales de la Conferencia Episcopal Peruana para preparar la visita del Papa León XIV al Perú en la parroquia, el colegio y la familia. Descarga libre y uso gratuito.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'subsidios');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, '', 'Subsidios', 'Lo que necesitas para preparar la visita en comunidad.', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/subsidios/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'subsidios', 'Subsidios descargables', 'descargas', 20, 1, '', 'Subsidios para la visita', '<p>Cinco materiales elaborados por la Conferencia Episcopal <br>Peruana a ser difundidos para preparar la visita en la parroquia, <br>el colegio y la familia. <strong>Descarga libre y uso gratuito</strong>.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `datos`, `imagen_id`) VALUES
  (@sec, 10, 1, 'PARA LA VISITA DEL PAPA\nLEÓN XIV AL PERÚ', 'El documento que presenta los cinco \nsubsidios y cómo usarlos en la parroquia, \nel colegio y la familia.', '{"archivo":"assets/docs/subsidios/subsidios-papa-leon-xiv.pdf","destacado":"sí"}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/subsidios/p06.jpg')),
  (@sec, 20, 1, 'PAPA LEÓN:\nCERCANO Y PERUANO', 'Quién es León XIV y qué lo une\nal Perú, para conocerlo antes\nde recibirlo.', '{"archivo":"assets/docs/subsidios/1-papa-leon-cercano-peruano.pdf"}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/subsidios/p01.jpg')),
  (@sec, 30, 1, 'UNIDOS EN CRISTO,\nSEMBRADORES DE PAZ', 'El lema del Santo Padre\nllevado a la vida de la\ncomunidad.', '{"archivo":"assets/docs/subsidios/2-unidos-en-cristo.pdf"}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/subsidios/p02.jpg')),
  (@sec, 40, 1, 'FAMILIAS QUE\nCUIDAN LA VIDA', 'Material para trabajar en\nfamilia durante las semanas\nprevias.', '{"archivo":"assets/docs/subsidios/3-familias-que-cuidan-la-vida.pdf"}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/subsidios/p03.jpg')),
  (@sec, 50, 1, 'LOS JÓVENES Y\nLA MISIÓN', 'Para grupos juveniles, colegios\ny pastoral universitaria.', '{"archivo":"assets/docs/subsidios/4-los-jovenes-y-la-mision.pdf"}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/subsidios/p04.jpg')),
  (@sec, 60, 1, 'PASTORAL SOCIAL: LA\nDIGNIDAD DE TODA PERSONA', 'La dimensión social de la fe,\ncon propuestas para la\ncomunidad.', '{"archivo":"assets/docs/subsidios/5-pastoral-social.pdf"}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/subsidios/p05.jpg'));
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'habra-disponible', 'Qué habrá disponible', 'texto_apartados', 30, 1, '', '¿Qué habrá disponible?', '<p>Además de los subsidios ya publicados, esto es lo que la <br>Conferencia Episcopal prepara. Todo será de descarga libre <br>y de uso gratuito para parroquias, colegios y movimientos.</p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`) VALUES
  (@sec, 10, 1, 'Guía de oración', 'PDF · para las semanas previas'),
  (@sec, 20, 1, 'Subsidio de catequesis', 'PDF · Niños, jóvenes y adultos'),
  (@sec, 30, 1, 'Himno', 'Video'),
  (@sec, 40, 1, 'Fondos de pantalla y avatares', 'Móvil, escritorio y redes');
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'subsidios', 'habra-disponible');

-- ── Página «voluntariado» ──────────────────────────────────────────────
INSERT INTO `paginas` (`clave`, `nombre`, `ruta`, `titulo_seo`, `descripcion_seo`, `activa`)
VALUES ('voluntariado', 'Voluntariado', '/voluntariado/', 'Voluntariado «Los amigos de León» · Viaje de León XIV al Perú', 'Si tienes entre 18 y 45 años participa como voluntario en la Visita del Papa León XIV al Perú. Seis servicios, tres pasos y un proceso de selección claro.', 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`), `ruta` = VALUES(`ruta`),
  `titulo_seo` = VALUES(`titulo_seo`), `descripcion_seo` = VALUES(`descripcion_seo`),
  `activa` = VALUES(`activa`);
SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'voluntariado');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`, `imagen_id`)
VALUES (@pag, 'cabecera', 'Cabecera de página', 'cabecera_pagina', 10, 1, 'Voluntariado', 'Los amigos de León', '<strong>Si tienes entre 18 y 45 años</strong> participa como voluntario en la Visita del Papa León XIV. Una experiencia para <strong>servir, acoger y hacer comunidad</strong>.', '{}', (SELECT `id` FROM `medios` WHERE `ruta` = 'assets/img/rediseno/voluntariado/hero.jpg'))
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`), `imagen_id` = VALUES(`imagen_id`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `datos`)
VALUES (@pag, 'resumen', 'En treinta segundos', 'pasos_numerados', 20, 1, '', '¡Quiero ser voluntario!', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `titulo`, `texto`, `enlace_texto`, `enlace_url`) VALUES
  (@sec, 10, 1, 'Eliges tu servicio', 'Son seis servicios. No importa tu profesión o el tiempo que puedas dar. Todos son bienvenidos.', 'Ver los seis', '#servicios'),
  (@sec, 20, 1, 'Rellenas el formulario', 'Es la Fase 01 y el único paso que se hace por internet. Solo cinco minutos.', 'Ir al formulario', '#inscripcion'),
  (@sec, 30, 1, 'La organización te escribe', 'Validación de documentos y, más adelante, acreditación y credenciales.', 'Ver el proceso', '#proceso');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'servicios', 'Los seis servicios', 'tarjetas_icono', 30, 1, '', 'Un lugar para cada talento', '', '{"cierre":["Seis servicios, una sola misión: servir con alegría.","Porque encontrarnos con el Santo Padre también significa poner nuestros dones al servicio de los demás.","¿Te animas a ser parte de esta experiencia?"],"grito":"","boton_texto":"Inscríbete ahora","boton_url":"#inscripcion"}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'proceso', 'Las tres fases', 'fases', 40, 1, '', 'Proceso de selección', '', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `texto`, `datos`) VALUES
  (@sec, 10, 1, '1', 'Inscripción', '<strong>Rellenas el formulario</strong> de esta página con tus datos, la jurisdicción en la que quieres servir y el servicio que prefieres. Es el único paso que se hace por internet.', '{"vinetas":["Nombres y apellidos, DNI y fecha de nacimiento","Dirección completa, correo electrónico y número telefónico","Talla de polo y contacto de emergencia","Jurisdicción y servicio"]}'),
  (@sec, 20, 1, '2', 'Validación', 'Se solicitarán algunos documentos adicionales. <strong>Nada de esto se sube a esta web:</strong> la organización te indicará por qué canal entregarlos.', '{"vinetas":["Carta de recomendación de sacerdote, religioso(a) u obispo","Certificado Único Laboral (documento oficial, gratuito y digital emitido por el Ministerio de Trabajo y Promoción del Empleo)","Entrevista personal (según necesidad)","Evaluación psicológica (cuando sea posible)"]}'),
  (@sec, 30, 1, '3', 'Acreditación', '<strong>El último paso</strong>, ya cerca de la visita.', '{"vinetas":["Confirmación oficial","Asignación de área de servicio","Entrega de credenciales"]}');
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `datos`)
VALUES (@pag, 'inscripcion', 'Formulario de inscripción', 'formulario', 50, 1, '', 'Inscríbete como voluntario', '{"ten_a_mano_titulo":"Ten a mano","ten_a_mano":["Tu <strong>DNI</strong>, ocho dígitos","Tu <strong>nombre completo</strong>","Tu <strong>fecha de nacimiento</strong>","Tu <strong>departamento, provincia y distrito</strong>","Tu <strong>dirección</strong>: calle o avenida y número","Tu <strong>correo electrónico</strong> y tu <strong>número telefónico</strong>","Tu <strong>talla de polo</strong>: S, M, L, XL o XXL","La <strong>jurisdicción</strong> en la que quieres servir","El <strong>servicio</strong> que prefieres, de los seis"],"nota":"El contacto de emergencia es opcional: puedes darlo más adelante. Y si te interrumpen, lo que hayas escrito se guarda en este navegador.","consentimiento":"La CONFERENCIA EPISCOPAL PERUANA (CEP) tratará sus datos personales para gestionar su inscripción, selección y participación en el voluntariado, conforme a la Ley N.º 29733 y su Reglamento.\\nSus datos podrán ser transferidos, según corresponda, al Arzobispado de Lima, Obispado de Chiclayo, Arzobispado del Cusco y Vicariato Apostólico de Pucallpa, y comunicados, cuando resulte necesario, a entidades públicas competentes en materia de seguridad, salud o protección. Serán conservados por un plazo máximo de cuatro (4) meses y luego eliminados.\\nEl uso de fotografías, imagen o voz requerirá una autorización independiente.","ancla_rotulo":"Fase 01 · Solo por internet","ancla_titulo":"Inscríbete como voluntario","ancla_dato":"Nueve datos, unos cinco minutos"}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
INSERT INTO `secciones` (`pagina_id`, `clave`, `nombre`, `plantilla`, `orden`, `activa`, `rotulo`, `titulo`, `texto`, `datos`)
VALUES (@pag, 'despues', 'Después de enviar', 'texto_lectura', 60, 1, '', '¿Qué procede?', '<h3>Consultas sobre el voluntariado.</h3><p>Enviar el formulario no es quedar seleccionado, y tampoco hace falta que hagas nada más por tu cuenta: a partir de aquí la organización te busca a ti.</p><p>Para consultas o dudas relacionadas con el voluntariado para la visita del papa León XIV al Perú 2026, puedes escribir a <a href="mailto:comunica.laicosyjuventud@iglesiacatolica.org.pe">comunica.laicosyjuventud@iglesiacatolica.org.pe</a></p>', '{}')
ON DUPLICATE KEY UPDATE `id` = LAST_INSERT_ID(`id`), `nombre` = VALUES(`nombre`), `plantilla` = VALUES(`plantilla`), `orden` = VALUES(`orden`), `activa` = VALUES(`activa`), `rotulo` = VALUES(`rotulo`), `titulo` = VALUES(`titulo`), `texto` = VALUES(`texto`), `datos` = VALUES(`datos`);
SET @sec := LAST_INSERT_ID();
DELETE FROM `bloques` WHERE `seccion_id` = @sec;
UPDATE `secciones` SET `activa` = 0
  WHERE `pagina_id` = @pag AND `clave` NOT IN ('cabecera', 'resumen', 'servicios', 'proceso', 'inscripcion', 'despues');


-- ──────────────────────────────────────────────────────────────────────────
--  4 · Ajustes del sitio
--
--  `sitio.pagina_inicio` valía «voluntariado»: la raíz del dominio enseñaba
--  la página de inscripción porque el resto del sitio no estaba terminado.
--  Con el rediseño ya hay portada, así que pasa a «home». Se cambia desde el
--  panel en cualquier momento, sin desplegar nada.
--
--  `menu.visibles` valía «voluntariado», es decir, un solo enlace en la
--  barra. Pasa a las nueve entradas del diseño. Las demás páginas siguen
--  publicadas y se añaden al menú desde el panel cuando se quiera: a partir
--  de once entradas la cabecera se repliega sola en el menú desplegable.
-- ──────────────────────────────────────────────────────────────────────────

INSERT INTO `ajustes` (`clave`, `valor`, `tipo`, `descripcion`) VALUES
  ('sitio.pagina_inicio', 'home', 'texto', 'Qué página se sirve en la raíz del dominio'),
  ('menu.visibles', 'papa-leon-xiv,sedes,agenda,cep,subsidios,voluntariado,noticias,prensa,contacto', 'texto', 'Claves de las páginas que aparecen en el menú, separadas por comas')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);


-- ──────────────────────────────────────────────────────────────────────────
--  5 · Se anota a sí misma
--
--  Normalmente esta fila la escribe database/migrate.php después de ejecutar
--  el archivo. Pero si alguien lo importa a mano —phpMyAdmin, el cliente
--  mysql—, el migrador no se entera: la base queda migrada y `migraciones`
--  sigue sin la fila, así que el siguiente `php database/migrate.php` la ve
--  pendiente y la vuelve a aplicar. Y una segunda pasada borra y recrea
--  todos los bloques, llevándose por delante lo que se haya corregido desde
--  el panel entretanto.
--
--  Con INSERT IGNORE da igual quién llegue primero: el migrador usa también
--  IGNORE (migrate.php:102), así que no chocan. Es lo mismo que ya hace
--  database/instalacion/05-migraciones.sql para quien instala a mano.
-- ──────────────────────────────────────────────────────────────────────────

INSERT IGNORE INTO `migraciones` (`archivo`) VALUES ('0027_rediseno_2026.sql');

COMMIT;
