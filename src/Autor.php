<?php
// src/Autor.php
// Modela la clase Autor con propiedades tipadas en el constructor y el repositorio AutorRepositorio con sentencias preparadas.
declare(strict_types=1);

class Autor
{
    public function __construct(
        public ?int $autor_id,
        public string $nombre,
        public string $apellido,
        public string $fecha_nacimiento,
        public string $nacionalidad,
    ) {}

    public function nombreCompleto(): string
    {
        return trim($this->nombre . ' ' . $this->apellido);
    }
}

class AutorRepositorio
{
    public function __construct(private PDO $pdo) {}

    /**
     * Lista autores junto con el conteo de libros asociados.
     */
    public function listar(string $busqueda = ''): array
    {
        $sql = '
            SELECT a.autor_id, a.nombre, a.apellido, a.fecha_nacimiento, a.nacionalidad,
                   COUNT(la.libro_id) AS total_libros
            FROM Autores a
            LEFT JOIN Libros_Autores la ON la.autor_id = a.autor_id
        ';

        $params = [];
        if ($busqueda !== '') {
            $sql .= ' WHERE CONCAT(a.nombre, " ", a.apellido) LIKE :q1 OR a.nacionalidad LIKE :q2';
            $like = '%' . $busqueda . '%';
            $params = [':q1' => $like, ':q2' => $like];
        }

        $sql .= '
            GROUP BY a.autor_id, a.nombre, a.apellido, a.fecha_nacimiento, a.nacionalidad
            ORDER BY a.apellido ASC, a.nombre ASC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?Autor
    {
        $stmt = $this->pdo->prepare(
            'SELECT autor_id, nombre, apellido, fecha_nacimiento, nacionalidad
             FROM Autores WHERE autor_id = :id'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();

        if (!$fila) {
            return null;
        }

        return new Autor(
            (int) $fila['autor_id'],
            $fila['nombre'],
            $fila['apellido'],
            $fila['fecha_nacimiento'],
            $fila['nacionalidad']
        );
    }

    public function crear(Autor $autor): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO Autores (nombre, apellido, fecha_nacimiento, nacionalidad)
             VALUES (:nombre, :apellido, :fecha_nacimiento, :nacionalidad)'
        );
        $stmt->execute([
            ':nombre'           => $autor->nombre,
            ':apellido'         => $autor->apellido,
            ':fecha_nacimiento' => $autor->fecha_nacimiento,
            ':nacionalidad'     => $autor->nacionalidad,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(Autor $autor): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE Autores
             SET nombre = :nombre,
                 apellido = :apellido,
                 fecha_nacimiento = :fecha_nacimiento,
                 nacionalidad = :nacionalidad
             WHERE autor_id = :id'
        );

        return $stmt->execute([
            ':nombre'           => $autor->nombre,
            ':apellido'         => $autor->apellido,
            ':fecha_nacimiento' => $autor->fecha_nacimiento,
            ':nacionalidad'     => $autor->nacionalidad,
            ':id'               => $autor->autor_id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM Autores WHERE autor_id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function obtenerLibrosPorAutor(int $autorId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.libro_id, l.titulo, l.genero, l.fecha_publicacion, l.isbn
             FROM Libros l
             INNER JOIN Libros_Autores la ON la.libro_id = l.libro_id
             WHERE la.autor_id = :id
             ORDER BY l.fecha_publicacion DESC'
        );
        $stmt->execute([':id' => $autorId]);
        return $stmt->fetchAll();
    }
}