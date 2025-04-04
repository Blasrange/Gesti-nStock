--- reabastecimiento
------------------------------------------------------------------------------------
CREATE TABLE `ciudades` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`nombre` VARCHAR(100) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`estado` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`pais` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`codigo` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=8
;

------------------------------------------------------------------------------------
CREATE TABLE `clientes` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`nombre` VARCHAR(100) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`email` VARCHAR(100) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`telefono` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`ciudad_id` INT(10) NULL DEFAULT NULL,
	`created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `email` (`email`) USING BTREE,
	INDEX `ciudad_id` (`ciudad_id`) USING BTREE,
	CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`ciudad_id`) REFERENCES `ciudades` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=5
;

------------------------------------------------------------------------------------
CREATE TABLE `estado_cliente` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`cliente_id` INT(10) NOT NULL,
	`estado` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`descripcion` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`created_at` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	INDEX `cliente_id` (`cliente_id`) USING BTREE,
	CONSTRAINT `estado_cliente_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=4
;

------------------------------------------------------------------------------------
CREATE TABLE `historial` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`fecha_hora` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
	`sku` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`unidades` INT(10) NULL DEFAULT NULL,
	`cajas` INT(10) NULL DEFAULT NULL,
	`turno` INT(10) NULL DEFAULT NULL,
	`created_at` DATETIME NULL DEFAULT NULL,
	`cliente_id` INT(10) NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	INDEX `cliente_id` (`cliente_id`) USING BTREE,
	CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=87
;

------------------------------------------------------------------------------------
CREATE TABLE `inventarios` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`codigo` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`lpn` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`localizacion` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`area_picking` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`sku` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`sku2` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`descripcion` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`precio` DECIMAL(10,2) NULL DEFAULT NULL,
	`tipo_material` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`categoria_material` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`unidades` INT(10) NULL DEFAULT NULL,
	`cajas` INT(10) NULL DEFAULT NULL,
	`reserva` INT(10) NULL DEFAULT NULL,
	`disponible` INT(10) NULL DEFAULT NULL,
	`udm` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`embalaje` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`fecha_entrada` DATE NULL DEFAULT NULL,
	`estado` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`lote` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`fecha_fabricacion` DATE NULL DEFAULT NULL,
	`fecha_vencimiento` DATE NULL DEFAULT NULL,
	`fpc` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`peso` DECIMAL(10,2) NULL DEFAULT NULL,
	`serial` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`cliente_id` INT(10) NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	INDEX `cliente_id` (`cliente_id`) USING BTREE,
	CONSTRAINT `inventarios_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=16753
;

------------------------------------------------------------------------------------
CREATE TABLE `maestra_materiales` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`sku` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`lpn` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`localizacion` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`descripcion` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`stock_minimo` INT(10) NULL DEFAULT NULL,
	`stock_maximo` INT(10) NULL DEFAULT NULL,
	`embalaje` INT(10) NULL DEFAULT NULL,
	`created_at` DATETIME NULL DEFAULT NULL,
	`cliente_id` INT(10) NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	INDEX `cliente_id` (`cliente_id`) USING BTREE,
	CONSTRAINT `maestra_materiales_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=259
;

------------------------------------------------------------------------------------
CREATE TABLE `reabastecimientos` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`sku` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`descripcion` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`lpn_inventario` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`localizacion_origen` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`unidades_reabastecer` INT(10) NULL DEFAULT NULL,
	`lote` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`fecha_vencimiento` DATE NULL DEFAULT NULL,
	`lpn_max_min` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`localizacion_destino` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`estado` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`cliente_id` INT(10) NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `unique_reabastecimiento` (`sku`, `lpn_inventario`, `localizacion_origen`, `cliente_id`) USING BTREE,
	INDEX `cliente_id` (`cliente_id`) USING BTREE,
	CONSTRAINT `reabastecimientos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=1598
;

------------------------------------------------------------------------------------
CREATE TABLE `reportes` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`sku` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`descripcion` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`lpn_inventario` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`localizacion_origen` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`lpn_max_min` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`localizacion_destino` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`estado` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`unidades_reabastecer` INT(10) NULL DEFAULT NULL,
	`cajas_reabastecer` INT(10) NULL DEFAULT NULL,
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`cliente_id` INT(10) NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `unique_sku_lpn` (`sku`, `lpn_inventario`) USING BTREE,
	INDEX `cliente_id` (`cliente_id`) USING BTREE,
	CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=6151
;

------------------------------------------------------------------------------------
CREATE TABLE `users` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`username` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`password` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`created_at` DATETIME NULL DEFAULT NULL,
	`cliente_id` INT(10) NOT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `username` (`username`) USING BTREE,
	INDEX `cliente_id` (`cliente_id`) USING BTREE,
	CONSTRAINT `users_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=7
;

------------------------------------------------------------------------------------
CREATE TABLE `usuario_clientes` (
	`user_id` INT(10) NOT NULL,
	`cliente_id` INT(10) NOT NULL,
	`created_at` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`user_id`, `cliente_id`) USING BTREE,
	INDEX `cliente_id` (`cliente_id`) USING BTREE,
	CONSTRAINT `usuario_clientes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `usuario_clientes_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
;

------------------------------------------------------------------------------------
-- Insertar clientes
INSERT INTO `clientes` (`nombre`, `email`, `telefono`, `ciudad_id`, `created_at`)
VALUES 
    ('RECAMIER S.A.', 'ecastano@recamier.com', '3164782401', 8, NOW()),
    ('CATALYST', 'admin@mdlz.com', '(57)0000000', 8, NOW()),
    ('(LEBON) MONDELEZ TAT MED', 'adminmed@mdlz.com', '4310400', 11, NOW()),
    ('SABAMA LTDA', 'ssalinas@alfaparf.com.co', '3183366034', 12, NOW());
------------------------------------------------------------------------------------
-- Insertar ciudades
INSERT INTO `ciudades` (`nombre`, `estado`, `pais`, `codigo`) 
VALUES
('CALI', 'VALLE', 'COLOMBIA', 77076001),
('FUNZA', 'CUNDINAMARCA', 'COLOMBIA', 77025286),
('BOGOTA D.C.', 'BOGOTA', 'COLOMBIA', 77011001),
('MEDELLIN', 'ANTIOQUIA', 'COLOMBIA', 77005001);
------------------------------------------------------------------------------------
-- Insertar Usuario
INSERT INTO users (
    username, password, cliente_id, created_at
) VALUES 
('Blas Rangel', '$2y$10$RqT.xKdtmVGf0ixzgC24ae8fySeh68uLshDaWHyQsMAvvxTGQBIje', 13);
------------------------------------------------------------------------------------
-- Insertar usuario_clientes
INSERT INTO `usuario_clientes` (`user_id`, `cliente_id`, `created_at`)
VALUES 
    (9, 27, NOW()),
    (9, 29, NOW()),
    (9, 30, NOW()),
    (9, 33, NOW());
------------------------------------------------------------------------------------
-- Insertar estados cliente con ID 1
INSERT INTO estado_cliente (cliente_id, estado, descripcion, created_at) VALUES
(33, 'DSP (Disponible)', 'Disponible para reabastecimiento'),
(33, 'DISPONIBLE', 'Disponible para reabastecimiento')
(27, '13 - CCL  DISPONIBLE', 'Disponible para reabastecimiento'),
(29, 'DSP  (Disponible)', 'Disponible para reabastecimiento');
------------------------------------------------------------------------------------