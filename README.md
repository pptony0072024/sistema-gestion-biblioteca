# Sistema de Gestión de Biblioteca (PHP + MySQL / PDO)

Proyecto práctico de la asignatura Aplicaciones Web — Carrera de Ingeniería de Software (Sexto Nivel), Universidad Regional Autónoma de los Andes (UNIANDES).

## Características principales
- Gestión de Autores (`Autores`): Crear, listar, buscar, editar, eliminar y consultar las obras asociadas a cada autor.
- Gestión de Libros (`Libros`): Crear, listar, buscar por título/género/ISBN/autor, editar y eliminar con validación de ISBN único.
- Relación Muchos a Muchos (N:M): Tabla intermedia `Libros_Autores` con claves foráneas y `ON DELETE CASCADE`, permitiendo asignar uno o varios autores por libro dentro de transacciones PDO.
- Buenas prácticas: Arquitectura modular (`public/`, `src/`, `views/`, `config/`), POO con tipado estricto (`strict_types=1`), sentencias preparadas PDO, validación en servidor, escape contra XSS (`htmlspecialchars`) y patrón Post/Redirect/Get (PRG).

## Modelo de datos
- `Autores`: `autor_id`, `nombre`, `apellido`, `fecha_nacimiento`, `nacionalidad`
- `Libros`: `libro_id`, `titulo`, `genero`, `fecha_publicacion`, `isbn`
- `Libros_Autores`: `libro_id` (FK), `autor_id` (FK)

## Instalación y ejecución local
1. Clonar el repositorio:
   ```bash
   git clone [https://github.com/pptony0072024/sistema-gestion-biblioteca.git](https://github.com/pptony0072024/sistema-gestion-biblioteca.git)