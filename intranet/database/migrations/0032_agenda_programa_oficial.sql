-- ===========================================================================
--  0032 · AGENDA — EL PROGRAMA OFICIAL DE LA SANTA SEDE
-- ---------------------------------------------------------------------------
--  Hasta ahora el itinerario era referencial: seis jornadas con actividades
--  genéricas —«Llegada del Santo Padre», «Celebración eucarística»— y sin una
--  sola hora, porque el programa no se había publicado. Y con las ciudades
--  mal repartidas: cuatro de los seis días nombraban una sede que no era.
--
--  Esto pone el programa oficial, con sus horas.
--
--  ── Qué cambia respecto a lo que había ───────────────────────────────────
--
--    día 12   decía Chiclayo   → es LIMA
--    día 13   decía Pucallpa   → es LIMA – CHICLAYO – PIMENTEL – CHICLAYO
--    día 14   decía Cusco      → es CHICLAYO – SANTA CRUZ DE SUCCHABAMBA – ZAÑA
--    día 15   decía Lima       → es CHICLAYO – CUSCO – PUCALLPA – LIMA
--
--  El 15 pasa a ser DOS jornadas, una en Cusco por la mañana y otra en
--  Pucallpa por la tarde, porque ese día el Papa recorre cuatro ciudades. La
--  vista agrupa por la ciudad con la que empieza el título, así que cada una
--  cae en su sede.
--
--  ── Lo que NO entra ──────────────────────────────────────────────────────
--
--  El martes 17 de noviembre, que en el programa oficial es la llegada a Roma
--  (10:15, Fiumicino). Queda fuera a propósito: la web anuncia el viaje «del
--  11 al 16» y ese día ya no hay nada en el Perú. Si se decide incluirlo, se
--  añade desde el panel sin tocar código.
--
--  ── Qué NO toca ──────────────────────────────────────────────────────────
--
--  Sólo los bloques de la sección «itinerario» de la página «agenda». Ni
--  voluntarios, ni usuarios, ni el resto de páginas.
--
--  Es repetible: borra los bloques de esa sección y los vuelve a crear.
-- ===========================================================================

SET NAMES utf8mb4;

SET @pag := (SELECT `id` FROM `paginas` WHERE `clave` = 'agenda' LIMIT 1);
SET @sec := (SELECT `id` FROM `secciones`
              WHERE `pagina_id` = @pag AND `clave` = 'itinerario' LIMIT 1);

DELETE FROM `bloques` WHERE @sec IS NOT NULL AND `seccion_id` = @sec;

-- INSERT ... SELECT y no VALUES: asi el «WHERE @sec IS NOT NULL» del final
-- protege tambien la insercion. Con VALUES, si la seccion no existiera, esto
-- intentaria escribir un seccion_id nulo y reventaria a media migracion.
INSERT INTO `bloques` (`seccion_id`, `orden`, `activo`, `rotulo`, `titulo`, `datos`)
SELECT @sec, v.`orden`, 1, v.`rotulo`, v.`titulo`, v.`datos`
FROM (
  SELECT 10 AS orden, '11 de noviembre' AS rotulo, 'Lima · Llegada y bienvenida oficial' AS titulo, '{"actividades": ["14:00 Hrs. Salida en avión del Aeropuerto Internacional de Buenos Aires con destino a Lima.", "17:30 Hrs. Llegada a la Base Aérea del Callao. BIENVENIDA OFICIAL.", "18:15 Hrs. CEREMONIA DE BIENVENIDA en el Palacio de Gobierno.", "18:45 Hrs. Visita a la presidente de la República.", "19:45 Hrs. Encuentro con las autoridades, la sociedad civil y el cuerpo diplomático en el Gran Teatro Nacional. Discurso del Santo Padre."]}' AS datos
  UNION ALL
  SELECT 20 AS orden, '12 de noviembre' AS rotulo, 'Lima · Encuentro con la Iglesia del Perú' AS titulo, '{"actividades": ["09:30 Hrs. Encuentro con los obispos del Perú en la capilla del Palacio Arzobispal de Lima. Discurso del Santo Padre.", "10:20 Hrs. Celebración de la Hora Media con los obispos, sacerdotes, religiosos y religiosas, seminaristas, equipos sinodales y consejos pastorales, en la Basílica Catedral de San Juan Apóstol y Evangelista. Homilía del Santo Padre.", "11:30 Hrs. Encuentro sobre los desafíos y las esperanzas para el futuro del pueblo peruano, en la plaza ubicada frente a la Catedral. Discurso del Santo Padre.", "18:00 Hrs. Vigilia de oración con los jóvenes y universitarios en el Estadio Monumental de Lima. Discurso del Santo Padre.", "20:00 Hrs. Cena con los obispos en la Nunciatura Apostólica."]}' AS datos
  UNION ALL
  SELECT 30 AS orden, '13 de noviembre' AS rotulo, 'Chiclayo · Pimentel y la diócesis que lo vio obispo' AS titulo, '{"actividades": ["07:40 Hrs. Salida en avión de la \\"Base Aérea del Callao\\" con destino a Chiclayo.", "09:10 Hrs. Llegada a la Base Aérea \\"Teniente Coronel Pedro Ruiz Gallo\\".", "10:30 Hrs. SANTA MISA en la explanada situada frente a las \\"Pampas de Pimentel\\". Homilía del Santo Padre.", "12:40 Hrs. Visita privada a la capilla \\"San Óscar A. Romero\\". Saludo del Santo Padre.", "16:30 Hrs. Encuentro con los obispos, sacerdotes, religiosos y religiosas, seminaristas, equipos sinodales y consejos pastorales en el Santuario de Nuestra Señora de la Paz. Discurso del Santo Padre.", "17:45 Hrs. Encuentro con el mundo universitario en la Universidad Católica \\"Santo Toribio de Mogrovejo\\". Discurso del Santo Padre.", "19:00 Hrs. Cena con los sacerdotes de la diócesis de Chiclayo en el Colegio \\"Santo Toribio de Mogrovejo\\"."]}' AS datos
  UNION ALL
  SELECT 40 AS orden, '14 de noviembre' AS rotulo, 'Chiclayo · Santa Cruz de Succhabamba y Zaña' AS titulo, '{"actividades": ["08:00 Hrs. Rito de coronación de la Virgen Inmaculada en la Catedral de Santa María. Homilía del Santo Padre.", "09:10 Hrs. Salida en helicóptero desde la Base Aérea de Chiclayo hacia Santa Cruz de Succhabamba.", "09:50 Hrs. Llegada al helipuerto de Santa Cruz de Succhabamba.", "11:00 Hrs. SANTA MISA en la explanada de Santa Cruz de Succhabamba. Homilía del Santo Padre.", "13:15 Hrs. Salida en helicóptero desde el helipuerto de Santa Cruz de Succhabamba hacia Chiclayo.", "14:00 Hrs. Llegada a la Base Aérea \\"Teniente Coronel Pedro Ruiz Gallo\\".", "17:30 Hrs. Encuentro de oración con la comunidad católica en ocasión de la clausura del año jubilar de \\"Santo Toribio de Mogrovejo\\" en el Santuario de \\"Santo Toribio de Mogrovejo\\". Discurso del Santo Padre."]}' AS datos
  UNION ALL
  SELECT 50 AS orden, '15 de noviembre' AS rotulo, 'Cusco · Saqsaywaman y el Ángelus en la Catedral' AS titulo, '{"actividades": ["07:50 Hrs. Salida en avión desde la Base Aérea de Chiclayo hacia Cusco.", "10:00 Hrs. Llegada al Aeropuerto Internacional \\"Alejandro Velasco Astete\\" de Cusco.", "10:30 Hrs. Encuentro con los fieles y representantes de la Piedad Popular en el Parque Arqueológico Nacional de \\"Saqsaywaman\\". Discurso del Santo Padre.", "11:45 Hrs. Rezo del Ángelus con la comunidad católica en la plaza frente a la Basílica Catedral de la Asunción de la Bienaventurada Virgen María. Ángelus.", "13:05 Hrs. Salida en avión desde el Aeropuerto Internacional de Cusco hacia Pucallpa."]}' AS datos
  UNION ALL
  SELECT 60 AS orden, '15 de noviembre' AS rotulo, 'Pucallpa · Los pueblos amazónicos' AS titulo, '{"actividades": ["14:30 Hrs. Llegada al Aeropuerto Internacional \\"Capitán FAP David Abensur Rengifo\\" de Pucallpa.", "15:00 Hrs. Encuentro con los misioneros, las misioneras y los representantes de los pueblos amazónicos en el \\"Malecón Puerto Callao\\". Discurso del Santo Padre.", "16:45 Hrs. SANTA MISA en la \\"Villa Deportiva Regional Ucayali\\". Homilía del Santo Padre.", "19:05 Hrs. Salida en avión desde el Aeropuerto de Pucallpa hacia Lima.", "20:00 Hrs. Llegada a la \\"Base Aérea del Callao\\"."]}' AS datos
  UNION ALL
  SELECT 70 AS orden, '16 de noviembre' AS rotulo, 'Lima · Despedida y regreso a Roma' AS titulo, '{"actividades": ["08:30 Hrs. Visita a la casa de acogida de las Hermanitas de los Ancianos Desamparados. Saludo del Santo Padre.", "10:30 Hrs. SANTA MISA en la \\"Base Aérea Las Palmas\\". Homilía del Santo Padre.", "13:15 Hrs. CEREMONIA DE DESPEDIDA en la \\"Base Aérea del Callao\\".", "13:45 Hrs. Salida en avión desde la \\"Base Aérea del Callao\\" hacia Roma."]}' AS datos
) AS v
WHERE @sec IS NOT NULL;
