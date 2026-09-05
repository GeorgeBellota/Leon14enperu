-- ===========================================================================
--  01 · ESTRUCTURA · las 23 tablas
-- ---------------------------------------------------------------------------
--  Copia exacta de la estructura de producción a 5 de septiembre de 2026,
--  con las quince migraciones ya aplicadas. No hay que ejecutar ninguna
--  migración después de esto: el archivo 05 las marca como hechas.
--
--  Lleva DROP TABLE IF EXISTS delante de cada tabla, así que ejecutarlo
--  sobre una base con datos LOS BORRA. Es para instalar, no para actualizar.
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

DROP TABLE IF EXISTS `ajustes`;
CREATE TABLE `ajustes` (
  `clave` varchar(64) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('texto','numero','booleano','fecha','json') NOT NULL DEFAULT 'texto',
  `descripcion` varchar(255) DEFAULT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL si lo hizo el formulario público',
  `accion` varchar(64) NOT NULL COMMENT 'crear | editar | borrar | ver_sensible | exportar | login…',
  `entidad` varchar(64) NOT NULL,
  `entidad_id` bigint(20) unsigned DEFAULT NULL,
  `detalle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detalle`)),
  `ip` varbinary(16) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_auditoria_entidad` (`entidad`,`entidad_id`),
  KEY `ix_auditoria_usuario_fecha` (`usuario_id`,`creado_en`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `bloques`;
CREATE TABLE `bloques` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seccion_id` int(10) unsigned NOT NULL,
  `orden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `rotulo` varchar(120) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `slug` varchar(190) DEFAULT NULL COMMENT 'parte legible de la URL; se fija al crear y no se regenera',
  `texto` text DEFAULT NULL,
  `icono` varchar(48) DEFAULT NULL COMMENT 'id del sprite SVG',
  `imagen_id` int(10) unsigned DEFAULT NULL,
  `imagen_movil_id` int(10) unsigned DEFAULT NULL,
  `enlace_texto` varchar(120) DEFAULT NULL,
  `enlace_url` varchar(255) DEFAULT NULL,
  `datos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos`)),
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bloques_slug` (`seccion_id`,`slug`),
  KEY `ix_bloques_seccion_orden` (`seccion_id`,`orden`),
  KEY `fk_bloques_imagen` (`imagen_id`),
  KEY `fk_bloques_imagen_movil` (`imagen_movil_id`),
  CONSTRAINT `fk_bloques_imagen` FOREIGN KEY (`imagen_id`) REFERENCES `medios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bloques_imagen_movil` FOREIGN KEY (`imagen_movil_id`) REFERENCES `medios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bloques_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `comunicados`;
CREATE TABLE `comunicados` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `boton_texto` varchar(80) NOT NULL DEFAULT 'Ver más',
  `boton_tipo` enum('enlace','descarga') NOT NULL DEFAULT 'enlace',
  `boton_destino` varchar(500) DEFAULT NULL,
  `archivo_nombre` varchar(200) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 0,
  `expira_en` datetime DEFAULT NULL,
  `paginas` varchar(500) NOT NULL DEFAULT '',
  `veces_max` smallint(5) unsigned NOT NULL DEFAULT 3,
  `retraso_ms` int(10) unsigned NOT NULL DEFAULT 3000,
  `autocierre_ms` int(10) unsigned NOT NULL DEFAULT 0,
  `vistas` int(10) unsigned NOT NULL DEFAULT 0,
  `clics` int(10) unsigned NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `creado_por` int(10) unsigned DEFAULT NULL,
  `actualizado_en` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_comunicados_vigencia` (`activo`,`expira_en`),
  KEY `fk_comunicados_usuario` (`creado_por`),
  CONSTRAINT `fk_comunicados_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `intentos_login`;
CREATE TABLE `intentos_login` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `correo` varchar(190) NOT NULL,
  `ip` varbinary(16) NOT NULL,
  `exito` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_intentos_correo_fecha` (`correo`,`creado_en`),
  KEY `ix_intentos_ip_fecha` (`ip`,`creado_en`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `ips_permitidas`;
CREATE TABLE `ips_permitidas` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) NOT NULL,
  `etiqueta` varchar(120) NOT NULL COMMENT 'de quién es: «oficina CEP», «casa de Jorge»…',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por` int(10) unsigned DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_uso` datetime DEFAULT NULL COMMENT 'última vez que esta IP entró durante un mantenimiento',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ips_permitidas` (`ip`),
  KEY `fk_ips_usuario` (`creado_por`),
  CONSTRAINT `fk_ips_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `jurisdicciones`;
CREATE TABLE `jurisdicciones` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(48) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `tipo` enum('arquidiocesis','diocesis','vicariato','prelatura','otro') NOT NULL DEFAULT 'otro',
  `orden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jurisdicciones_clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `medios`;
CREATE TABLE `medios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ruta` varchar(255) NOT NULL COMMENT 'relativa a la raíz del sitio',
  `nombre_archivo` varchar(190) NOT NULL,
  `mime` varchar(96) NOT NULL,
  `ancho` int(10) unsigned DEFAULT NULL,
  `alto` int(10) unsigned DEFAULT NULL,
  `peso` int(10) unsigned DEFAULT NULL COMMENT 'bytes',
  `variantes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'base, anchos y formatos de la familia' CHECK (json_valid(`variantes`)),
  `alt` varchar(255) NOT NULL DEFAULT '',
  `decorativa` tinyint(1) NOT NULL DEFAULT 0,
  `creado_por` int(10) unsigned DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_medios_ruta` (`ruta`),
  KEY `fk_medios_usuario` (`creado_por`),
  KEY `ix_medios_nombre` (`nombre_archivo`),
  CONSTRAINT `fk_medios_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `migraciones`;
CREATE TABLE `migraciones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `archivo` varchar(190) NOT NULL,
  `aplicada_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migraciones_archivo` (`archivo`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DROP TABLE IF EXISTS `paginas`;
CREATE TABLE `paginas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(64) NOT NULL COMMENT 'voluntariado, home…',
  `nombre` varchar(120) NOT NULL,
  `ruta` varchar(190) NOT NULL COMMENT 'ruta pública: /voluntariado/',
  `titulo_seo` varchar(190) DEFAULT NULL,
  `descripcion_seo` varchar(255) DEFAULT NULL,
  `og_imagen_id` int(10) unsigned DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `actualizado_por` int(10) unsigned DEFAULT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_paginas_clave` (`clave`),
  KEY `fk_paginas_og` (`og_imagen_id`),
  KEY `fk_paginas_usuario` (`actualizado_por`),
  CONSTRAINT `fk_paginas_og` FOREIGN KEY (`og_imagen_id`) REFERENCES `medios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_paginas_usuario` FOREIGN KEY (`actualizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `permisos`;
CREATE TABLE `permisos` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(64) NOT NULL COMMENT 'formato modulo.accion',
  `modulo` varchar(48) NOT NULL COMMENT 'para agrupar en la pantalla de roles',
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permisos_clave` (`clave`),
  KEY `ix_permisos_modulo` (`modulo`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `rol_permiso`;
CREATE TABLE `rol_permiso` (
  `rol_id` smallint(5) unsigned NOT NULL,
  `permiso_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`rol_id`,`permiso_id`),
  KEY `ix_rol_permiso_permiso` (`permiso_id`),
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(48) NOT NULL COMMENT 'identificador estable en código',
  `nombre` varchar(96) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `es_sistema` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `secciones`;
CREATE TABLE `secciones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pagina_id` int(10) unsigned NOT NULL,
  `clave` varchar(64) NOT NULL COMMENT 'coincide con el id del <section> en el HTML',
  `nombre` varchar(120) NOT NULL COMMENT 'cómo se llama en el panel',
  `plantilla` varchar(48) NOT NULL DEFAULT 'generica',
  `orden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `rotulo` varchar(120) DEFAULT NULL COMMENT 'el <span class="rotulo"> versalita',
  `titulo` varchar(255) DEFAULT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `texto` text DEFAULT NULL,
  `imagen_id` int(10) unsigned DEFAULT NULL,
  `imagen_movil_id` int(10) unsigned DEFAULT NULL,
  `cta_texto` varchar(120) DEFAULT NULL,
  `cta_url` varchar(255) DEFAULT NULL,
  `datos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos`)),
  `actualizado_por` int(10) unsigned DEFAULT NULL,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_secciones_pagina_clave` (`pagina_id`,`clave`),
  KEY `ix_secciones_orden` (`pagina_id`,`orden`),
  KEY `fk_secciones_imagen` (`imagen_id`),
  KEY `fk_secciones_usuario` (`actualizado_por`),
  KEY `fk_secciones_imagen_movil` (`imagen_movil_id`),
  CONSTRAINT `fk_secciones_imagen` FOREIGN KEY (`imagen_id`) REFERENCES `medios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_secciones_imagen_movil` FOREIGN KEY (`imagen_movil_id`) REFERENCES `medios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_secciones_pagina` FOREIGN KEY (`pagina_id`) REFERENCES `paginas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_secciones_usuario` FOREIGN KEY (`actualizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `secciones_versiones`;
CREATE TABLE `secciones_versiones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seccion_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `contenido` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'la sección y sus bloques, tal como estaban' CHECK (json_valid(`contenido`)),
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_sv_seccion` (`seccion_id`,`creado_en`),
  KEY `fk_sv_usuario` (`usuario_id`),
  CONSTRAINT `fk_sv_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sv_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `servicios`;
CREATE TABLE `servicios` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(48) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `icono` varchar(48) DEFAULT NULL COMMENT 'id del sprite SVG, ej. i-resguardo',
  `descripcion` text DEFAULT NULL,
  `orden` smallint(5) unsigned NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_servicios_clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `ubigeo_departamento`;
CREATE TABLE `ubigeo_departamento` (
  `id` varchar(2) NOT NULL,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `ubigeo_distrito`;
CREATE TABLE `ubigeo_distrito` (
  `id` varchar(6) NOT NULL,
  `nombre_distrito` varchar(45) DEFAULT NULL,
  `province_id` varchar(4) DEFAULT NULL,
  `department_id` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_distrito_provincia` (`province_id`),
  KEY `ix_distrito_departamento` (`department_id`),
  CONSTRAINT `fk_distrito_provincia` FOREIGN KEY (`province_id`) REFERENCES `ubigeo_provincia` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `ubigeo_provincia`;
CREATE TABLE `ubigeo_provincia` (
  `id` varchar(4) NOT NULL,
  `nombre_provincia` varchar(45) NOT NULL,
  `department_id` varchar(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_provincia_departamento` (`department_id`),
  CONSTRAINT `fk_provincia_departamento` FOREIGN KEY (`department_id`) REFERENCES `ubigeo_departamento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `usuario_permiso`;
CREATE TABLE `usuario_permiso` (
  `usuario_id` int(10) unsigned NOT NULL,
  `permiso_id` smallint(5) unsigned NOT NULL,
  `conceder` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`usuario_id`,`permiso_id`),
  KEY `ix_up_permiso` (`permiso_id`),
  CONSTRAINT `fk_up_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_up_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rol_id` smallint(5) unsigned NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `correo` varchar(190) NOT NULL,
  `clave_hash` varchar(255) NOT NULL COMMENT 'password_hash(), nunca la contraseña',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `debe_cambiar` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_acceso_en` datetime DEFAULT NULL,
  `ultimo_acceso_ip` varbinary(16) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_correo` (`correo`),
  KEY `ix_usuarios_rol` (`rol_id`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `voluntarios`;
CREATE TABLE `voluntarios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL COMMENT 'referencia pública, ej. VOL-2026-000123',
  `nombres` varchar(160) NOT NULL,
  `dni_cifrado` text NOT NULL,
  `dni_hash` char(64) NOT NULL COMMENT 'HMAC-SHA256, sólo para buscar y deduplicar',
  `dni_mascara` varchar(12) NOT NULL COMMENT 'lo que se ve en el listado: *****678',
  `nacimiento` date NOT NULL,
  `correo` varchar(190) NOT NULL COMMENT 'sin cifrar: es el canal de aviso de la Fase 02',
  `telefono_cifrado` text NOT NULL,
  `telefono_mascara` varchar(20) NOT NULL,
  `direccion_cifrada` text NOT NULL,
  `ubigeo_departamento_id` varchar(2) DEFAULT NULL,
  `ubigeo_provincia_id` varchar(4) DEFAULT NULL,
  `ubigeo_distrito_id` varchar(6) DEFAULT NULL,
  `distrito` varchar(96) DEFAULT NULL COMMENT 'se extrae de la dirección para poder filtrar',
  `provincia` varchar(96) DEFAULT NULL,
  `emergencia_nombre` varchar(160) DEFAULT NULL,
  `emergencia_telefono_cifrado` text DEFAULT NULL,
  `emergencia_telefono_mascara` varchar(20) DEFAULT NULL,
  `jurisdiccion_id` smallint(5) unsigned NOT NULL,
  `servicio_id` smallint(5) unsigned NOT NULL,
  `talla` enum('S','M','L','XL','XXL') NOT NULL,
  `fase` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1 inscripción · 2 validación · 3 acreditación',
  `estado` enum('nuevo','en_validacion','validado','acreditado','rechazado','baja') NOT NULL DEFAULT 'nuevo',
  `area_asignada` varchar(120) DEFAULT NULL COMMENT 'se rellena en la Fase 03',
  `notas_internas` text DEFAULT NULL,
  `consentimiento` tinyint(1) NOT NULL DEFAULT 0,
  `consentimiento_en` datetime DEFAULT NULL,
  `consentimiento_version` varchar(24) DEFAULT NULL,
  `origen_ip` varbinary(16) DEFAULT NULL,
  `origen_ua` varchar(255) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `borrado_en` datetime DEFAULT NULL COMMENT 'baja lógica: una solicitud de supresión no debe romper el histórico',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voluntarios_codigo` (`codigo`),
  UNIQUE KEY `uq_voluntarios_dni` (`dni_hash`),
  KEY `ix_voluntarios_nombres` (`nombres`),
  KEY `ix_voluntarios_correo` (`correo`),
  KEY `ix_voluntarios_estado` (`estado`),
  KEY `ix_voluntarios_creado` (`creado_en`),
  KEY `ix_voluntarios_filtro` (`jurisdiccion_id`,`servicio_id`,`estado`),
  KEY `fk_vol_servicio` (`servicio_id`),
  KEY `ix_voluntarios_ubigeo` (`ubigeo_departamento_id`,`ubigeo_provincia_id`,`ubigeo_distrito_id`),
  KEY `fk_voluntarios_provincia` (`ubigeo_provincia_id`),
  KEY `fk_voluntarios_distrito` (`ubigeo_distrito_id`),
  CONSTRAINT `fk_vol_jurisdiccion` FOREIGN KEY (`jurisdiccion_id`) REFERENCES `jurisdicciones` (`id`),
  CONSTRAINT `fk_vol_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`),
  CONSTRAINT `fk_voluntarios_departamento` FOREIGN KEY (`ubigeo_departamento_id`) REFERENCES `ubigeo_departamento` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_voluntarios_distrito` FOREIGN KEY (`ubigeo_distrito_id`) REFERENCES `ubigeo_distrito` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_voluntarios_provincia` FOREIGN KEY (`ubigeo_provincia_id`) REFERENCES `ubigeo_provincia` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=35204 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `voluntarios_historial`;
CREATE TABLE `voluntarios_historial` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voluntario_id` bigint(20) unsigned NOT NULL,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `estado_anterior` varchar(24) DEFAULT NULL,
  `estado_nuevo` varchar(24) NOT NULL,
  `nota` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_vh_voluntario` (`voluntario_id`,`creado_en`),
  KEY `fk_vh_usuario` (`usuario_id`),
  CONSTRAINT `fk_vh_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vh_voluntario` FOREIGN KEY (`voluntario_id`) REFERENCES `voluntarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35063 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
