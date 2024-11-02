-- Crear tabla de Usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    cliente_id INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

-- Crear tabla de Ciudades
CREATE TABLE ciudades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    estado VARCHAR(100),
    pais VARCHAR(100),
    codigo INT(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de Clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefono VARCHAR(15),
    ciudad_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ciudad_id) REFERENCES ciudades(id) ON DELETE SET NULL
);

-- Crear tabla de Inventarios
CREATE TABLE inventarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    lpn VARCHAR(50),
    localizacion VARCHAR(100),
    area_picking VARCHAR(100),
    sku VARCHAR(50),
    sku2 VARCHAR(50),
    descripcion TEXT,
    precio DECIMAL(10,2),
    tipo_material VARCHAR(100),
    categoria_material VARCHAR(100),
    unidades INT,
    cajas INT,
    reserva INT,
    disponible INT,
    udm VARCHAR(50),
    fecha_entrada DATE,
    estado VARCHAR(50),
    lote VARCHAR(50),
    fecha_fabricacion DATE,
    fecha_vencimiento DATE,
    fpc VARCHAR(50),
    peso DECIMAL(10,2),
    serial VARCHAR(100),
    cliente_id INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

--- Crear tabla de Maestra_Materiales
CREATE TABLE maestra_materiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    lpn VARCHAR(50),
    localizacion VARCHAR(100),
    descripcion TEXT,
    stock_minimo INT,
    stock_maximo INT,
    embalaje VARCHAR(50),
    cliente_id INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

--- Crear tabla de Reabastecimientos 
CREATE TABLE reabastecimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50),
    descripcion TEXT,
    lpn_inventario VARCHAR(50),
    localizacion_origen VARCHAR(100),
    unidades_reabastecer INT,
    lote VARCHAR(50),
    fecha_vencimiento DATE,
    lpn_max_min VARCHAR(100),
    localizacion_destino VARCHAR(100),
    estado VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cliente_id INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

--- Crear tabla de Reportes 
CREATE TABLE reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50),
    descripcion TEXT,
    lpn_inventario VARCHAR(50),
    localizacion_origen VARCHAR(100),
    lpn_max_min VARCHAR(100),
    localizacion_destino VARCHAR(100),
    estado VARCHAR(50),
    unidades_reabastecer INT,
    cajas_reabastecer INT,
    cliente_id INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

--- Crear tabla de historial 
CREATE TABLE historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    sku INT,
    unidades INT,
    cajas INT,
    turno INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cliente_id INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

--- Crear tabla de usuario_clientes
CREATE TABLE usuario_clientes (
    user_id INT,
    cliente_id INT,
    PRIMARY KEY (user_id, cliente_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

--- Crear tabla de estado_cliente
CREATE TABLE estado_cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    estado VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

--- Crear tabla de Insertar Inevnatrio
INSERT INTO inventarios (
    codigo, lpn, localizacion, area_picking, sku, sku2, descripcion, 
    precio, tipo_material, categoria_material, unidades, cajas, reserva, 
    disponible, udm,embalaje, fecha_entrada, estado, lote, fecha_fabricacion, 
    fecha_vencimiento, fpc, peso, serial, cliente_id
) VALUES 
('1566266', 'PL2999342', 'P1-20-50-2', '', '93384', '', 'GRATIS BANDEJA BUBBA-SPARKIES', 
  25.50, '10', '', 160, 11.43, 0, 160, 'INNER BOX','14', '2022-04-15', 
  'DSP (Disponible)','MDLZ00506', '2023-01-01', '2025-12-12', '442', 0, '', 1);

-- Crear tabla de Usuario
INSERT INTO users (
    username, password, cliente_id
) VALUES 
('Blas Rangel', '$2y$10$BxvBq25E3z7hu/c/1qKlyeJIRZIWKNWuxyCcApg1yXM67QoFtMT.G', 2);

-- Insertar estados permitidos para el cliente con ID 1
INSERT INTO estado_cliente (cliente_id, estado, descripcion) VALUES
(1, 'DSP (Disponible)', 'Disponible para reabastecimiento'),
(1, 'RETIRADO', 'Material retirado del inventario');

-- Insertar estados permitidos para el cliente con ID 2
INSERT INTO estado_cliente (cliente_id, estado, descripcion) VALUES
(2, 'DSP (Disponible)', 'Disponible para reabastecimiento'),
(2, 'EN TRANSITO', 'Material en tránsito entre ubicaciones');

-- Insertar estados permitidos para el cliente con ID 3
INSERT INTO estado_cliente (cliente_id, estado, descripcion) VALUES
(3, 'DSP (Disponible)', 'Disponible para reabastecimiento'),
(3, 'RETIRADO', 'Material retirado del inventario'),
(3, 'EN TRANSITO', 'Material en tránsito entre ubicaciones');

-- Insertar ciudades
INSERT INTO `ciudades` (`nombre`, `estado`, `pais`, `codigo`) 
VALUES
('CALI', 'VALLE', 'COLOMBIA', 77076001),
('FUNZA', 'CUNDINAMARCA', 'COLOMBIA', 77025286),
('BOGOTA D.C.', 'BOGOTA', 'COLOMBIA', 77011001),
('MEDELLIN', 'ANTIOQUIA', 'COLOMBIA', 77005001);
