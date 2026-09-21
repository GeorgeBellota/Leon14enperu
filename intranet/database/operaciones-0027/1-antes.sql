-- ===========================================================================
--  0027 · PASO 1 de 4 — MIRAR ANTES DE TOCAR
-- ---------------------------------------------------------------------------
--  Sólo lee. No modifica nada. Ejecutar sobre la base de producción y guardar
--  el resultado: es con lo que se compara después.
-- ===========================================================================

-- ── Recuento general ──────────────────────────────────────────────────────
-- Sobre el volcado del 19/09/2026 esto da 24 / 107 / 99 / 9 / 26 / 37428.
SELECT 'ANTES'                                  AS momento,
       (SELECT COUNT(1) FROM paginas)           AS paginas,
       (SELECT COUNT(1) FROM secciones)         AS secciones,
       (SELECT COUNT(1) FROM bloques)           AS bloques,
       (SELECT COUNT(1) FROM medios)            AS medios,
       (SELECT COUNT(1) FROM migraciones)       AS migraciones,
       (SELECT COUNT(1) FROM voluntarios)       AS voluntarios;

-- ── Huella de las tablas que NO se deben tocar ────────────────────────────
-- Anotar estos números. Después de migrar tienen que salir idénticos.
CHECKSUM TABLE voluntarios, voluntarios_historial, usuarios, roles, permisos,
               rol_permiso, usuario_permiso, auditoria, intentos_login,
               ips_permitidas, comunicados, jurisdicciones, servicios,
               ubigeo_departamento, ubigeo_provincia, ubigeo_distrito,
               secciones_versiones;

-- ── ¿Está 0027 ya aplicada? ───────────────────────────────────────────────
-- Si devuelve una fila, no hay nada que hacer.
SELECT * FROM migraciones WHERE archivo = '0027_rediseno_2026.sql';

-- ── Las dos cuentas de la Colecta Nacional ────────────────────────────────
-- Tienen que seguir estando después. Guardar esta salida.
SELECT s.activa, b.rotulo, b.titulo, b.datos
FROM bloques b
JOIN secciones s ON s.id = b.seccion_id
JOIN paginas   p ON p.id = s.pagina_id
WHERE p.clave = 'home' AND s.clave = 'colecta'
ORDER BY b.orden;

-- ── Los dos ajustes que cambian ───────────────────────────────────────────
-- `sitio.pagina_inicio` vale hoy «voluntariado»: la raíz del dominio sirve el
-- formulario de inscripción. La migración lo pasa a «home», la portada nueva.
-- Es una decisión de campaña, no técnica, y se revierte desde el panel.
SELECT clave, valor FROM ajustes
WHERE clave IN ('sitio.pagina_inicio', 'menu.visibles');

-- ── Qué secciones se van a apagar ─────────────────────────────────────────
-- La migración las pone en activa = 0; no las borra. Deben ser 27.
SELECT p.clave AS pagina, s.clave AS seccion, s.plantilla,
       (SELECT COUNT(1) FROM bloques b WHERE b.seccion_id = s.id) AS bloques
FROM secciones s
JOIN paginas p ON p.id = s.pagina_id
WHERE (p.clave, s.clave) NOT IN (
  SELECT * FROM (
    SELECT 'home' AS a, 'hero' AS b UNION ALL SELECT 'home','falta-poco-encuentro'
    UNION ALL SELECT 'home','himno'          UNION ALL SELECT 'home','abramos-el-corazon'
    UNION ALL SELECT 'home','subsidios-home' UNION ALL SELECT 'home','llega-al-peru'
    UNION ALL SELECT 'home','el-recorrido'   UNION ALL SELECT 'home','tierra-de-santos'
    UNION ALL SELECT 'home','colecta'
  ) AS conservadas
)
  AND p.clave = 'home'
ORDER BY s.orden;
