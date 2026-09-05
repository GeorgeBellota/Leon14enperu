-- ===========================================================================
--  04 · DIEZ VOLUNTARIOS DE EJEMPLO
-- ---------------------------------------------------------------------------
--  ⚠ SON INVENTADOS. Ninguno existe.
--
--  En producción hay 35 018 personas reales, con su nombre y su correo en
--  claro en la base. Nada de eso entra en un repositorio, ni siquiera diez
--  filas: son diez personas de verdad.
--
--  Con diez fichas se ve todo lo que hay que ver en el panel —el listado, los
--  filtros, los estados, la ficha, la exportación— y no hace falta esperar a
--  que carguen treinta y cinco mil.
--
--  ── Por qué las columnas cifradas llevan texto legible ───────────────────
--
--  El DNI, el teléfono y la dirección se guardan cifrados con la clave de la
--  instalación. Como cada máquina tiene la suya, un criptograma de producción
--  no se podría descifrar aquí.
--
--  En su lugar llevan una frase que dice lo que es. No rompe nada:
--  Cripto::descifrar() devuelve el valor tal cual cuando no reconoce el
--  prefijo «v1:» —está pensado así para que un dato anterior al cifrado no
--  tumbe la pantalla del listado— y de paso queda claro que la ficha es de
--  mentira.
--
--  Los DNI van del 90000001 al 90000010, fuera del rango que emite el RENIEC.
--
--  ── Con qué versiones está probado ───────────────────────────────────────
--
--    Producción      MariaDB 11.8.6  ·  PHP 8.3  ·  Nginx
--    Desarrollo      MariaDB 10.4    ·  PHP 8.1  ·  Apache (XAMPP)
--
--    Mínimo          MariaDB 10.4    ·  PHP 8.0
--
--  MariaDB, NO MySQL. Tres migraciones del proyecto usan
--  «ADD COLUMN IF NOT EXISTS», que MySQL no acepta. Y este archivo se generó
--  con el cliente de MariaDB 10.4 a propósito: así usa la sintaxis más
--  conservadora y entra igual en 10.4 que en 11.8.
-- ===========================================================================

SET NAMES utf8mb4;

INSERT INTO `voluntarios` (`id`, `codigo`, `nombres`, `dni_cifrado`, `dni_hash`, `dni_mascara`, `nacimiento`, `correo`, `telefono_cifrado`, `telefono_mascara`, `direccion_cifrada`, `ubigeo_departamento_id`, `ubigeo_provincia_id`, `ubigeo_distrito_id`, `distrito`, `provincia`, `emergencia_nombre`, `emergencia_telefono_cifrado`, `emergencia_telefono_mascara`, `jurisdiccion_id`, `servicio_id`, `talla`, `fase`, `estado`, `area_asignada`, `notas_internas`, `consentimiento`, `consentimiento_en`, `consentimiento_version`, `origen_ip`, `origen_ua`, `creado_en`, `actualizado_en`, `borrado_en`) VALUES
  (1, 'VOL-2026-000001', 'Lucía Milagros Quispe Vargas', '(DNI de ejemplo, sin cifrar: 90000001)', '430092016f3ef7665ea92073330815cfdc767bb09ddd80473206d324750870ef', '*****001', '1970-01-10', 'ejemplo01@example.com', '(teléfono de ejemplo, sin cifrar: 910000111)', '*** *** 101', '(dirección de ejemplo, sin cifrar)', '15', '1501', '150101', 'LIMA', 'LIMA', 'Contacto de ejemplo 1', '(teléfono de ejemplo, sin cifrar)', '*** *** 201', 1, 1, 'S', 1, 'nuevo', NULL, NULL, 1, '2026-09-01 10:30:00', '2026-v1', NULL, NULL, '2026-09-01 10:30:00', '2026-09-01 10:30:00', NULL),
  (2, 'VOL-2026-000002', 'Diego Alonso Ramírez Tello', '(DNI de ejemplo, sin cifrar: 90000002)', 'aaf4e9436540803e12e397ed497bd7612146b32f6c96efbbde934d7ff340d3c9', '*****002', '1971-02-11', 'ejemplo02@example.com', '(teléfono de ejemplo, sin cifrar: 910000222)', '*** *** 102', '(dirección de ejemplo, sin cifrar)', '14', '1401', '140101', 'CHICLAYO', 'LAMBAYEQUE', 'Contacto de ejemplo 2', '(teléfono de ejemplo, sin cifrar)', '*** *** 202', 2, 2, 'M', 1, 'nuevo', NULL, NULL, 1, '2026-09-02 11:30:00', '2026-v1', NULL, NULL, '2026-09-02 11:30:00', '2026-09-02 11:30:00', NULL),
  (3, 'VOL-2026-000003', 'María Fernanda Chávez Ríos', '(DNI de ejemplo, sin cifrar: 90000003)', 'dddf7d43e9932cf931dcddc658baf6cd2d2caddc449a3ca1a47342007fe44aa4', '*****003', '1972-03-12', 'ejemplo03@example.com', '(teléfono de ejemplo, sin cifrar: 910000333)', '*** *** 103', '(dirección de ejemplo, sin cifrar)', '08', '0801', '080101', 'CUSCO', 'CUSCO', 'Contacto de ejemplo 3', '(teléfono de ejemplo, sin cifrar)', '*** *** 203', 3, 3, 'L', 1, 'en_validacion', NULL, NULL, 1, '2026-09-03 12:30:00', '2026-v1', NULL, NULL, '2026-09-03 12:30:00', '2026-09-03 12:30:00', NULL),
  (4, 'VOL-2026-000004', 'José Antonio Huamán Paredes', '(DNI de ejemplo, sin cifrar: 90000004)', '5ef4ac1aa0684dba632b8e1dfbee0bd529dee5be6106bbecc9e9ca34c2fe18aa', '*****004', '1973-04-13', 'ejemplo04@example.com', '(teléfono de ejemplo, sin cifrar: 910000444)', '*** *** 104', '(dirección de ejemplo, sin cifrar)', '25', '2501', '250101', 'CALLERIA', 'CORONEL PORTILLO', 'Contacto de ejemplo 4', '(teléfono de ejemplo, sin cifrar)', '*** *** 204', 4, 4, 'XL', 1, 'validado', NULL, NULL, 1, '2026-09-04 13:30:00', '2026-v1', NULL, NULL, '2026-09-04 13:30:00', '2026-09-04 13:30:00', NULL),
  (5, 'VOL-2026-000005', 'Rosa Elena Mendoza Castillo', '(DNI de ejemplo, sin cifrar: 90000005)', '6731f9696902fe1323888bf96765bba356c6381b44629df6f657e0e893ee7df5', '*****005', '1974-05-14', 'ejemplo05@example.com', '(teléfono de ejemplo, sin cifrar: 910000555)', '*** *** 105', '(dirección de ejemplo, sin cifrar)', '15', '1501', '150101', 'LIMA', 'LIMA', 'Contacto de ejemplo 5', '(teléfono de ejemplo, sin cifrar)', '*** *** 205', 1, 5, 'XXL', 1, 'acreditado', NULL, NULL, 1, '2026-09-05 14:30:00', '2026-v1', NULL, NULL, '2026-09-05 14:30:00', '2026-09-05 14:30:00', NULL),
  (6, 'VOL-2026-000006', 'Carlos Eduardo Flores Salazar', '(DNI de ejemplo, sin cifrar: 90000006)', '3e31411162c1717b37b084beb139aaddea9a494df99305cc917b2309755ed999', '*****006', '1975-06-15', 'ejemplo06@example.com', '(teléfono de ejemplo, sin cifrar: 910000666)', '*** *** 106', '(dirección de ejemplo, sin cifrar)', '14', '1401', '140101', 'CHICLAYO', 'LAMBAYEQUE', 'Contacto de ejemplo 6', '(teléfono de ejemplo, sin cifrar)', '*** *** 206', 2, 6, 'S', 1, 'nuevo', NULL, NULL, 1, '2026-09-01 15:30:00', '2026-v1', NULL, NULL, '2026-09-01 15:30:00', '2026-09-01 15:30:00', NULL),
  (7, 'VOL-2026-000007', 'Ana Sofía Torres Ccahuana', '(DNI de ejemplo, sin cifrar: 90000007)', 'bb8fa4dd60cbebf160edc2cd90291511ba0219b1086e2236c85f17614c84754c', '*****007', '1976-07-16', 'ejemplo07@example.com', '(teléfono de ejemplo, sin cifrar: 910000777)', '*** *** 107', '(dirección de ejemplo, sin cifrar)', '08', '0801', '080101', 'CUSCO', 'CUSCO', 'Contacto de ejemplo 7', '(teléfono de ejemplo, sin cifrar)', '*** *** 207', 3, 1, 'M', 1, 'nuevo', NULL, NULL, 1, '2026-09-02 16:30:00', '2026-v1', NULL, NULL, '2026-09-02 16:30:00', '2026-09-02 16:30:00', NULL),
  (8, 'VOL-2026-000008', 'Miguel Ángel Rojas Ninahuanca', '(DNI de ejemplo, sin cifrar: 90000008)', '83464b025216dd27fe9b3a249e8ee2c7f0fcdc12ac00e9a4dbdd4bde2dc81596', '*****008', '1977-08-17', 'ejemplo08@example.com', '(teléfono de ejemplo, sin cifrar: 910000888)', '*** *** 108', '(dirección de ejemplo, sin cifrar)', '25', '2501', '250101', 'CALLERIA', 'CORONEL PORTILLO', 'Contacto de ejemplo 8', '(teléfono de ejemplo, sin cifrar)', '*** *** 208', 4, 2, 'L', 1, 'en_validacion', NULL, NULL, 1, '2026-09-03 17:30:00', '2026-v1', NULL, NULL, '2026-09-03 17:30:00', '2026-09-03 17:30:00', NULL),
  (9, 'VOL-2026-000009', 'Carmen Julia Espinoza Yupanqui', '(DNI de ejemplo, sin cifrar: 90000009)', '155aef957f704f66dff13dd2550456ae3a3a754b833ccad7fe79ed401b7bb6d3', '*****009', '1978-09-18', 'ejemplo09@example.com', '(teléfono de ejemplo, sin cifrar: 910000999)', '*** *** 109', '(dirección de ejemplo, sin cifrar)', '15', '1501', '150101', 'LIMA', 'LIMA', 'Contacto de ejemplo 9', '(teléfono de ejemplo, sin cifrar)', '*** *** 209', 1, 3, 'XL', 1, 'validado', NULL, NULL, 1, '2026-09-04 18:30:00', '2026-v1', NULL, NULL, '2026-09-04 18:30:00', '2026-09-04 18:30:00', NULL),
  (10, 'VOL-2026-000010', 'Pedro Luis Sánchez Anticona', '(DNI de ejemplo, sin cifrar: 90000010)', 'ef442c5ce6c361a1538babd7a46e7a2171a2ad2667fa5e55b7bc1afcc809df8f', '*****010', '1979-01-10', 'ejemplo10@example.com', '(teléfono de ejemplo, sin cifrar: 910001110)', '*** *** 110', '(dirección de ejemplo, sin cifrar)', '14', '1401', '140101', 'CHICLAYO', 'LAMBAYEQUE', 'Contacto de ejemplo 10', '(teléfono de ejemplo, sin cifrar)', '*** *** 210', 2, 4, 'XXL', 1, 'acreditado', NULL, NULL, 1, '2026-09-05 10:30:00', '2026-v1', NULL, NULL, '2026-09-05 10:30:00', '2026-09-05 10:30:00', NULL);

-- Una entrada de historial por ficha, que es lo que hace el panel al
-- registrar una inscripción.
INSERT INTO `voluntarios_historial` (`voluntario_id`, `usuario_id`, `estado_anterior`, `estado_nuevo`, `nota`, `creado_en`) VALUES
  (1, NULL, NULL, 'nuevo', 'Inscripción de ejemplo', NOW()),
  (2, NULL, NULL, 'nuevo', 'Inscripción de ejemplo', NOW()),
  (3, NULL, NULL, 'nuevo', 'Inscripción de ejemplo', NOW()),
  (4, NULL, NULL, 'nuevo', 'Inscripción de ejemplo', NOW()),
  (5, NULL, NULL, 'nuevo', 'Inscripción de ejemplo', NOW()),
  (6, NULL, NULL, 'nuevo', 'Inscripción de ejemplo', NOW()),
  (7, NULL, NULL, 'nuevo', 'Inscripción de ejemplo', NOW()),
  (8, NULL, NULL, 'nuevo', 'Inscripción de ejemplo', NOW()),
  (9, NULL, NULL, 'nuevo', 'Inscripción de ejemplo', NOW()),
  (10, NULL, NULL, 'nuevo', 'Inscripción de ejemplo', NOW());
