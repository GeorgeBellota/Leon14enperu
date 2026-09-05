-- ============================================================================
--  0005 · Espacios sobrantes en los nombres de provincia
--
--  98 de las 196 provincias venían con espacios al final: «CHICLAYO »,
--  «FERREÑAFE », «LAMBAYEQUE ». Se veían en el desplegable del formulario y en
--  la ficha del voluntario.
--
--  Cuesta detectarlos: comparar `nombre <> TRIM(nombre)` devuelve 0 filas
--  porque MySQL ignora los espacios finales al comparar cadenas VARCHAR. Hay
--  que mirar la LONGITUD, que es lo que hace la comprobación de abajo.
-- ============================================================================

UPDATE `ubigeo_provincia`
   SET `nombre_provincia` = TRIM(`nombre_provincia`)
 WHERE CHAR_LENGTH(`nombre_provincia`) <> CHAR_LENGTH(TRIM(`nombre_provincia`));

-- Las otras dos tablas están limpias, pero se pasan igual: es idempotente y
-- así la migración vale también si mañana se recargan los datos de origen.
UPDATE `ubigeo_departamento`
   SET `name` = TRIM(`name`)
 WHERE CHAR_LENGTH(`name`) <> CHAR_LENGTH(TRIM(`name`));

UPDATE `ubigeo_distrito`
   SET `nombre_distrito` = TRIM(`nombre_distrito`)
 WHERE CHAR_LENGTH(`nombre_distrito`) <> CHAR_LENGTH(TRIM(`nombre_distrito`));
