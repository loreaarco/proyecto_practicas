-- ============================================================
-- OSCISA SOLUTIONS - Esquema SQL inicial
-- Motor: MySQL 8+ / MariaDB 10.6+
-- Charset: utf8mb4 (soporte completo Unicode y emojis)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Tabla: roles
-- Roles de usuario del sistema (admin, comercial, etc.)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`      VARCHAR(50)  NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_roles_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`nombre`, `descripcion`) VALUES
    ('admin',      'Administrador con acceso total'),
    ('comercial',  'Comercial con acceso a sus clientes y estudios'),
    ('supervisor', 'Supervisor con visión de equipo');

-- ------------------------------------------------------------
-- Tabla: usuarios
-- Usuarios del sistema (backoffice interno)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `rol_id`        INT UNSIGNED  NOT NULL,
    `nombre`        VARCHAR(100)  NOT NULL,
    `apellidos`     VARCHAR(150)  NULL,
    `email`         VARCHAR(180)  NOT NULL,
    `password_hash` VARCHAR(255)  NOT NULL,
    `activo`        TINYINT(1)    NOT NULL DEFAULT 1,
    `ultimo_acceso` DATETIME      NULL,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_usuarios_email` (`email`),
    CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password: admin1234 (bcrypt) — cambiar en producción
INSERT INTO `usuarios` (`rol_id`, `nombre`, `apellidos`, `email`, `password_hash`) VALUES
    (1, 'Administrador', 'OSCISA', 'admin@oscisa.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ------------------------------------------------------------
-- Tabla: estados_comerciales
-- Estados configurables para clientes y estudios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `estados_comerciales` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`      VARCHAR(80)  NOT NULL,
    `color_hex`   VARCHAR(7)   NOT NULL DEFAULT '#6c757d',
    `descripcion` VARCHAR(255) NULL,
    `orden`       TINYINT      NOT NULL DEFAULT 0,
    `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_estado_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `estados_comerciales` (`nombre`, `color_hex`, `descripcion`, `orden`) VALUES
    ('En estudio',         '#0d6efd', 'Se está analizando la factura del cliente',         1),
    ('Oferta presentada',  '#fd7e14', 'Se ha enviado la oferta al cliente',                 2),
    ('Aceptado',           '#198754', 'El cliente ha aceptado la oferta',                   3),
    ('Rechazado',          '#dc3545', 'El cliente ha rechazado la oferta',                  4),
    ('En seguimiento',     '#6f42c1', 'Se está haciendo seguimiento posterior',             5),
    ('Cerrado ganado',     '#20c997', 'Oportunidad cerrada con éxito',                      6),
    ('Cerrado perdido',    '#adb5bd', 'Oportunidad cerrada sin éxito',                      7);

-- ------------------------------------------------------------
-- Tabla: clientes
-- Datos de clientes gestionados por el CRM
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clientes` (
    `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `comercial_id`    INT UNSIGNED  NOT NULL,
    `estado_id`       INT UNSIGNED  NOT NULL DEFAULT 1,
    `nombre`          VARCHAR(150)  NOT NULL,
    `apellidos`       VARCHAR(150)  NULL,
    `empresa`         VARCHAR(200)  NULL,
    `cif_nif`         VARCHAR(20)   NULL,
    `email`           VARCHAR(180)  NULL,
    `telefono`        VARCHAR(20)   NULL,
    `telefono2`       VARCHAR(20)   NULL,
    `direccion`       VARCHAR(255)  NULL,
    `poblacion`       VARCHAR(100)  NULL,
    `provincia`       VARCHAR(100)  NULL,
    `codigo_postal`   VARCHAR(10)   NULL,
    `notas`           TEXT          NULL,
    `activo`          TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_clientes_comercial`  (`comercial_id`),
    KEY `idx_clientes_estado`     (`estado_id`),
    KEY `idx_clientes_cif`        (`cif_nif`),
    CONSTRAINT `fk_clientes_comercial` FOREIGN KEY (`comercial_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `fk_clientes_estado`    FOREIGN KEY (`estado_id`)    REFERENCES `estados_comerciales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: facturas
-- Archivos de factura subidos por el comercial
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `facturas` (
    `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `cliente_id`      INT UNSIGNED  NOT NULL,
    `subida_por`      INT UNSIGNED  NOT NULL,
    `nombre_original` VARCHAR(255)  NOT NULL,
    `nombre_fichero`  VARCHAR(255)  NOT NULL,
    `ruta_almacen`    VARCHAR(500)  NOT NULL,
    `mime_type`       VARCHAR(100)  NOT NULL,
    `tamanio_bytes`   INT UNSIGNED  NOT NULL,
    `estado_extraccion` ENUM('pendiente','procesando','completada','error') NOT NULL DEFAULT 'pendiente',
    `intentos_extraccion` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `notas`           TEXT          NULL,
    `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_facturas_cliente`   (`cliente_id`),
    KEY `idx_facturas_estado`    (`estado_extraccion`),
    CONSTRAINT `fk_facturas_cliente`   FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_facturas_subida`    FOREIGN KEY (`subida_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: datos_extraidos_factura
-- Resultado estructurado del análisis por OpenAI
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `datos_extraidos_factura` (
    `id`                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `factura_id`           INT UNSIGNED     NOT NULL,
    -- Titular y suministro
    `titular_nombre`       VARCHAR(200)     NULL,
    `titular_cif_nif`      VARCHAR(20)      NULL,
    `direccion_suministro` VARCHAR(300)     NULL,
    `cups`                 VARCHAR(30)      NULL,
    -- Comercializadora y contrato
    `comercializadora`     VARCHAR(150)     NULL,
    `tipo_suministro`      VARCHAR(50)      NULL  COMMENT 'electricidad, gas, dual...',
    `tarifa_acceso`        VARCHAR(50)      NULL  COMMENT 'p.ej. 2.0TD, 3.0TD, 6.1TD',
    -- Potencias contratadas (hasta 6 periodos)
    `potencia_p1_kw`       DECIMAL(8,3)     NULL,
    `potencia_p2_kw`       DECIMAL(8,3)     NULL,
    `potencia_p3_kw`       DECIMAL(8,3)     NULL,
    `potencia_p4_kw`       DECIMAL(8,3)     NULL,
    `potencia_p5_kw`       DECIMAL(8,3)     NULL,
    `potencia_p6_kw`       DECIMAL(8,3)     NULL,
    -- Consumos por periodo (kWh)
    `consumo_p1_kwh`       DECIMAL(10,3)    NULL,
    `consumo_p2_kwh`       DECIMAL(10,3)    NULL,
    `consumo_p3_kwh`       DECIMAL(10,3)    NULL,
    `consumo_p4_kwh`       DECIMAL(10,3)    NULL,
    `consumo_p5_kwh`       DECIMAL(10,3)    NULL,
    `consumo_p6_kwh`       DECIMAL(10,3)    NULL,
    `consumo_total_kwh`    DECIMAL(10,3)    NULL,
    -- Periodo facturado
    `periodo_inicio`       DATE             NULL,
    `periodo_fin`          DATE             NULL,
    `dias_facturados`      SMALLINT         NULL,
    -- Importes
    `importe_potencia`     DECIMAL(10,2)    NULL,
    `importe_energia`      DECIMAL(10,2)    NULL,
    `importe_impuestos`    DECIMAL(10,2)    NULL,
    `importe_total`        DECIMAL(10,2)    NULL,
    -- Gas (si aplica)
    `consumo_gas_kwh`      DECIMAL(10,3)    NULL,
    `importe_gas`          DECIMAL(10,2)    NULL,
    -- Datos adicionales en JSON (flexibilidad futura)
    `datos_extra`          JSON             NULL,
    -- Auditoría de extracción
    `revisado_manual`      TINYINT(1)       NOT NULL DEFAULT 0,
    `revisado_por`         INT UNSIGNED     NULL,
    `tokens_usados`        INT UNSIGNED     NULL,
    `modelo_ia`            VARCHAR(50)      NULL,
    `created_at`           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_datos_factura` (`factura_id`),
    CONSTRAINT `fk_datos_factura`    FOREIGN KEY (`factura_id`)   REFERENCES `facturas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_datos_revisor`    FOREIGN KEY (`revisado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: comercializadoras
-- Catálogo de comercializadoras disponibles para comparar
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comercializadoras` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre`      VARCHAR(150) NOT NULL,
    `nombre_corto` VARCHAR(50) NULL,
    `logo_path`   VARCHAR(255) NULL,
    `activa`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `comercializadoras` (`nombre`, `nombre_corto`) VALUES
    ('Iberdrola',              'iberdrola'),
    ('Endesa',                 'endesa'),
    ('Naturgy',                'naturgy'),
    ('Repsol',                 'repsol'),
    ('EDP',                    'edp'),
    ('Holaluz',                'holaluz'),
    ('Plenitude (Eni)',        'plenitude'),
    ('Octopus Energy',         'octopus');

-- ------------------------------------------------------------
-- Tabla: tarifas_oferta
-- Tarifas de cada comercializadora para el comparador
-- (sin IA, lógica pura de programación)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tarifas_oferta` (
    `id`                   INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `comercializadora_id`  INT UNSIGNED   NOT NULL,
    `nombre_oferta`        VARCHAR(200)   NOT NULL,
    `tipo_suministro`      VARCHAR(50)    NOT NULL DEFAULT 'electricidad',
    `tarifa_acceso`        VARCHAR(50)    NOT NULL COMMENT '2.0TD, 3.0TD, 6.1TD...',
    -- Precio energía por periodo (€/kWh)
    `precio_energia_p1`    DECIMAL(8,6)   NOT NULL DEFAULT 0,
    `precio_energia_p2`    DECIMAL(8,6)   NOT NULL DEFAULT 0,
    `precio_energia_p3`    DECIMAL(8,6)   NOT NULL DEFAULT 0,
    `precio_energia_p4`    DECIMAL(8,6)   NOT NULL DEFAULT 0,
    `precio_energia_p5`    DECIMAL(8,6)   NOT NULL DEFAULT 0,
    `precio_energia_p6`    DECIMAL(8,6)   NOT NULL DEFAULT 0,
    -- Precio potencia por periodo (€/kW/año)
    `precio_potencia_p1`   DECIMAL(8,6)   NOT NULL DEFAULT 0,
    `precio_potencia_p2`   DECIMAL(8,6)   NOT NULL DEFAULT 0,
    `precio_potencia_p3`   DECIMAL(8,6)   NOT NULL DEFAULT 0,
    `precio_potencia_p4`   DECIMAL(8,6)   NOT NULL DEFAULT 0,
    `precio_potencia_p5`   DECIMAL(8,6)   NOT NULL DEFAULT 0,
    `precio_potencia_p6`   DECIMAL(8,6)   NOT NULL DEFAULT 0,
    -- Descuento fijo sobre la factura (%)
    `descuento_pct`        DECIMAL(5,2)   NOT NULL DEFAULT 0,
    -- Comisión para OSCISA (€ o %)
    `comision_tipo`        ENUM('fija','porcentaje') NOT NULL DEFAULT 'fija',
    `comision_valor`       DECIMAL(8,2)   NOT NULL DEFAULT 0,
    `comision_periodicidad` ENUM('unica','anual','mensual') NOT NULL DEFAULT 'anual',
    -- Vigencia
    `vigente_desde`        DATE           NOT NULL,
    `vigente_hasta`        DATE           NULL,
    `activa`               TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`           DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tarifas_comercializadora` (`comercializadora_id`),
    KEY `idx_tarifas_acceso`           (`tarifa_acceso`),
    CONSTRAINT `fk_tarifas_comercializadora` FOREIGN KEY (`comercializadora_id`) REFERENCES `comercializadoras` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarifas de ejemplo para desarrollo
INSERT INTO `tarifas_oferta`
    (`comercializadora_id`, `nombre_oferta`, `tarifa_acceso`, `vigente_desde`,
     `precio_energia_p1`, `precio_energia_p2`, `precio_energia_p3`,
     `precio_potencia_p1`, `precio_potencia_p2`,
     `comision_tipo`, `comision_valor`, `comision_periodicidad`) VALUES
    (1, 'Iberdrola Pyme 2.0TD', '2.0TD', '2025-01-01',
     0.145000, 0.110000, 0.085000,
     38.043426, 2.098856,
     'fija', 120.00, 'anual'),
    (2, 'Endesa Uno 2.0TD',    '2.0TD', '2025-01-01',
     0.141000, 0.108000, 0.082000,
     37.500000, 2.000000,
     'fija', 130.00, 'anual'),
    (3, 'Naturgy Tempo 2.0TD', '2.0TD', '2025-01-01',
     0.148000, 0.112000, 0.088000,
     38.200000, 2.100000,
     'porcentaje', 5.00, 'anual');

-- ------------------------------------------------------------
-- Tabla: estudios
-- Cada comparativa realizada para un cliente
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `estudios` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `cliente_id`    INT UNSIGNED  NOT NULL,
    `factura_id`    INT UNSIGNED  NOT NULL,
    `comercial_id`  INT UNSIGNED  NOT NULL,
    `estado_id`     INT UNSIGNED  NOT NULL DEFAULT 1,
    `titulo`        VARCHAR(200)  NULL,
    `notas`         TEXT          NULL,
    `ahorro_anual_estimado` DECIMAL(10,2) NULL COMMENT 'Mejor oferta vs actual',
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_estudios_cliente`   (`cliente_id`),
    KEY `idx_estudios_comercial` (`comercial_id`),
    KEY `idx_estudios_estado`    (`estado_id`),
    CONSTRAINT `fk_estudios_cliente`   FOREIGN KEY (`cliente_id`)   REFERENCES `clientes` (`id`),
    CONSTRAINT `fk_estudios_factura`   FOREIGN KEY (`factura_id`)   REFERENCES `facturas` (`id`),
    CONSTRAINT `fk_estudios_comercial` FOREIGN KEY (`comercial_id`) REFERENCES `usuarios` (`id`),
    CONSTRAINT `fk_estudios_estado`    FOREIGN KEY (`estado_id`)    REFERENCES `estados_comerciales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: resultados_comparativa
-- Resultado por oferta de cada estudio realizado
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resultados_comparativa` (
    `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `estudio_id`           INT UNSIGNED  NOT NULL,
    `tarifa_id`            INT UNSIGNED  NOT NULL,
    `coste_calculado`      DECIMAL(10,2) NOT NULL COMMENT 'Coste con esta tarifa para el mismo consumo',
    `coste_actual`         DECIMAL(10,2) NOT NULL COMMENT 'Lo que paga ahora el cliente',
    `ahorro_estimado`      DECIMAL(10,2) NOT NULL COMMENT 'coste_actual - coste_calculado',
    `ahorro_pct`           DECIMAL(5,2)  NOT NULL,
    `comision_estimada`    DECIMAL(10,2) NOT NULL,
    `ranking`              TINYINT       NOT NULL DEFAULT 0,
    `detalle_calculo`      JSON          NULL COMMENT 'Desglose del cálculo para auditoría',
    `created_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_resultados_estudio` (`estudio_id`),
    CONSTRAINT `fk_resultados_estudio` FOREIGN KEY (`estudio_id`) REFERENCES `estudios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_resultados_tarifa`  FOREIGN KEY (`tarifa_id`)  REFERENCES `tarifas_oferta` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: seguimiento_comercial
-- Registro de acciones/contactos con cada cliente
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seguimiento_comercial` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `cliente_id`    INT UNSIGNED  NOT NULL,
    `estudio_id`    INT UNSIGNED  NULL,
    `usuario_id`    INT UNSIGNED  NOT NULL,
    `tipo`          ENUM('llamada','email','reunion','whatsapp','nota','otro') NOT NULL DEFAULT 'nota',
    `descripcion`   TEXT          NOT NULL,
    `proxima_accion` TEXT         NULL,
    `fecha_proxima`  DATE         NULL,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seguimiento_cliente` (`cliente_id`),
    KEY `idx_seguimiento_estudio` (`estudio_id`),
    CONSTRAINT `fk_seguimiento_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_seguimiento_estudio` FOREIGN KEY (`estudio_id`) REFERENCES `estudios` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_seguimiento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: logs_sistema
-- Trazabilidad de operaciones y errores
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `logs_sistema` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`    INT UNSIGNED    NULL,
    `nivel`         ENUM('debug','info','warning','error','critical') NOT NULL DEFAULT 'info',
    `contexto`      VARCHAR(100)    NULL COMMENT 'módulo o servicio que genera el log',
    `mensaje`       TEXT            NOT NULL,
    `datos_extra`   JSON            NULL,
    `ip`            VARCHAR(45)     NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_logs_nivel`    (`nivel`),
    KEY `idx_logs_contexto` (`contexto`),
    KEY `idx_logs_fecha`    (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
