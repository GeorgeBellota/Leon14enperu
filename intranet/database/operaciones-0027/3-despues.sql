-- ===========================================================================
--  0027 · PASO 3 de 4 — COMPROBAR QUE SALIÓ BIEN
-- ---------------------------------------------------------------------------
--  Sólo lee. Ejecutar justo después de aplicar la migración.
--  Si algo de aquí no cuadra, el paso 4 devuelve la base a como estaba.
-- ===========================================================================

-- ── 1 · Recuento ──────────────────────────────────────────────────────────
-- Esperado: 25 / 120 / 203 / 86 / 27, y los MISMOS voluntarios que antes.
SELECT 'DESPUÉS'                           AS momento,
       (SELECT COUNT(1) FROM paginas)      AS paginas,
       (SELECT COUNT(1) FROM secciones)    AS secciones,
       (SELECT COUNT(1) FROM bloques)      AS bloques,
       (SELECT COUNT(1) FROM medios)       AS medios,
       (SELECT COUNT(1) FROM migraciones)  AS migraciones,
       (SELECT COUNT(1) FROM voluntarios)  AS voluntarios;

-- ── 2 · Las inscripciones, intactas ───────────────────────────────────────
-- Comparadas contra la copia... que no existe, porque `voluntarios` no se
-- copió: la migración no la nombra. Así que se compara contra sí misma en el
-- tiempo: estos números tienen que ser los mismos que dio el paso 1.
SELECT COUNT(1)              AS inscripciones,
       MIN(id)               AS primer_id,
       MAX(id)               AS ultimo_id,
       MAX(creado_en)        AS ultima_inscripcion
FROM voluntarios;

-- ── 3 · Lo que NO se debía tocar sigue igual ──────────────────────────────
-- Estos números tienen que ser IDÉNTICOS a los del paso 1. Si uno solo
-- cambió, algo fue mal: ir al paso 4.
CHECKSUM TABLE voluntarios, voluntarios_historial, usuarios, roles, permisos,
               rol_permiso, usuario_permiso, auditoria, intentos_login,
               ips_permitidas, comunicados, jurisdicciones, servicios,
               ubigeo_departamento, ubigeo_provincia, ubigeo_distrito,
               secciones_versiones;

-- ── 4 · Las cuentas de la Colecta Nacional, publicadas ────────────────────
-- Tiene que devolver DOS filas, con activa = 1. Si devuelve cero, /donativo/
-- queda avisando contra cuentas falsas sin publicar ninguna verdadera.
SELECT s.activa, b.rotulo, b.titulo, b.datos
FROM bloques b
JOIN secciones s ON s.id = b.seccion_id
JOIN paginas   p ON p.id = s.pagina_id
WHERE p.clave = 'home' AND s.clave = 'colecta'
ORDER BY b.orden;

-- ── 5 · Las tildes ────────────────────────────────────────────────────────
-- Tiene que leerse «La Iglesia en el Perú». Si sale «PerÃº», la importación
-- se hizo con el juego de caracteres equivocado: ir al paso 4 y repetir
-- declarando utf8-mb4.
SELECT s.titulo
FROM secciones s JOIN paginas p ON p.id = s.pagina_id
WHERE p.clave = 'cep' AND s.clave = 'cabecera';

-- Y la comprobación dura, por si la vista del cliente engaña: cero filas.
SELECT COUNT(1) AS filas_con_texto_corrompido
FROM secciones
WHERE HEX(CONCAT(IFNULL(titulo,''), IFNULL(texto,''), IFNULL(rotulo,''))) LIKE '%C383%';

-- ── 6 · Nada apagado de más ───────────────────────────────────────────────
-- Esperado: 27 secciones apagadas, ninguna de ellas «colecta».
SELECT COUNT(1) AS secciones_apagadas FROM secciones WHERE activa = 0;
SELECT p.clave AS pagina, s.clave AS seccion
FROM secciones s JOIN paginas p ON p.id = s.pagina_id
WHERE s.activa = 0 ORDER BY p.clave, s.orden;

-- ── 7 · La migración quedó anotada ────────────────────────────────────────
-- Tiene que devolver una fila. Si no, el próximo `migrate.php` la repetiría.
SELECT * FROM migraciones WHERE archivo = '0027_rediseno_2026.sql';

-- ── 8 · Las tres páginas renombradas ──────────────────────────────────────
-- Esperado: papa-leon-xiv, santos y subsidios. Y que no quede ninguna con la
-- clave vieja.
SELECT clave, nombre, ruta FROM paginas
WHERE clave IN ('papa-leon-xiv','santos','subsidios','el-papa','tierra-de-santos','materiales')
ORDER BY clave;
