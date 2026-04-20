CREATE DATABASE IF NOT EXISTS agencia_autos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agencia_autos;

DROP TABLE IF EXISTS vehiculos;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('empleado', 'administrador') NOT NULL DEFAULT 'empleado',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(100) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    anio INT NOT NULL,
    precio DECIMAL(12,2) NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_anio CHECK (anio >= 1900),
    CONSTRAINT chk_precio CHECK (precio > 0)
);

-- password para ambos usuarios: password
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador General', 'admin@agencia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador'),
('Empleado Demo', 'empleado@agencia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado');

INSERT INTO vehiculos (marca, modelo, anio, precio, imagen) VALUES
('Toyota', 'Corolla', 2021, 18500.00, NULL),
('Volkswagen', 'Vento', 2020, 17300.00, NULL),
('Ford', 'Ranger', 2022, 31800.00, NULL),
('Chevrolet', 'Onix', 2019, 12900.00, NULL);