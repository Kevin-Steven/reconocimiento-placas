CREATE DATABASE placas_db;
USE placas_db;
#DROP DATABASE placas_db;

-- ROLES 'user' y 'admin'
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL DEFAULT 'user',
    creado_el TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE registro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(255) NOT NULL,
    apellidos VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    creado_el TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE placas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(10) NOT NULL,
    propietario VARCHAR(255) NOT NULL,
    marca VARCHAR(255) NOT NULL,
    modelo VARCHAR(255) NOT NULL
);

CREATE TABLE ingresos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa_id INT,
    fecha_ingreso DATETIME,
    FOREIGN KEY (placa_id) REFERENCES placas(id)
);

CREATE TABLE salidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingreso_id INT,
    fecha_salida DATETIME,
    FOREIGN KEY (ingreso_id) REFERENCES ingresos(id)
);

CREATE TABLE placas_no_registradas (
id INT AUTO_INCREMENT PRIMARY KEY,
placa VARCHAR(10) NOT NULL,
fecha_denegado DATETIME
);

DELIMITER //

CREATE TRIGGER after_registro_insert
AFTER INSERT ON registro
FOR EACH ROW
BEGIN
    INSERT INTO usuarios (email, password, creado_el) 
    VALUES (NEW.email, NEW.password, NEW.creado_el);
END;

//

DELIMITER ;
