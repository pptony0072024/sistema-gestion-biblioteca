--Creación de la base de datos con codificación utf8mb4, las dos tablas solicitadas, la tabla intermedia de relación y datos iniciales
-- database/biblioteca.sql
CREATE DATABASE IF NOT EXISTS biblioteca
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE biblioteca;

DROP TABLE IF EXISTS Libros_Autores;
DROP TABLE IF EXISTS Libros;
DROP TABLE IF EXISTS Autores;

CREATE TABLE Autores (
    autor_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    nacionalidad VARCHAR(60) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Libros (
    libro_id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    genero VARCHAR(60) NOT NULL,
    fecha_publicacion DATE NOT NULL,
    isbn VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Tabla intermedia para la relación N:M entre Libros y Autores
CREATE TABLE Libros_Autores (
    libro_id INT NOT NULL,
    autor_id INT NOT NULL,
    PRIMARY KEY (libro_id, autor_id),
    CONSTRAINT fk_la_libro
        FOREIGN KEY (libro_id) REFERENCES Libros(libro_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_la_autor
        FOREIGN KEY (autor_id) REFERENCES Autores(autor_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Datos de prueba iniciales
INSERT INTO Autores (nombre, apellido, fecha_nacimiento, nacionalidad) VALUES
('Jorge', 'Icaza', '1906-06-10', 'Ecuatoriana'),
('Gabriel', 'García Márquez', '1927-03-06', 'Colombiana'),
('Isabel', 'Allende', '1942-08-02', 'Chilena'),
('Jorge Luis', 'Borges', '1899-08-24', 'Argentina'),
('Adolfo', 'Bioy Casares', '1914-09-15', 'Argentina');

INSERT INTO Libros (titulo, genero, fecha_publicacion, isbn) VALUES
('Huasipungo', 'Novela social', '1934-01-01', '978-9978-80-123-4'),
('Cien años de soledad', 'Realismo mágico', '1967-05-30', '978-0307-47472-8'),
('La casa de los espíritus', 'Novela histórica', '1982-10-01', '978-8401-33720-8'),
('Seis problemas para don Isidro Parodi', 'Policial', '1942-01-01', '978-9500-40512-6');

-- Relaciones (el libro 4 demuestra coautoría: 2 autores para un mismo libro)
INSERT INTO Libros_Autores (libro_id, autor_id) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(4, 5);