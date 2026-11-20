<?php
// src/Libro.php
// Gestiona el CRUD de Libros y sincroniza las relaciones en Libros_Autores dentro de transacciones para garantizar consistencia de datos.
declare(strict_types=1);

class Libro
{
    /**
     * @param int[] $autoresIds
     */
    public function __construct(
        public ?int $libro_id,
        public string $titulo,
        public string $genero,
        public string $fecha_publicacion,
        public string $isbn,
        public array $autoresIds = [],
    ) {}
}

class LibroRepositorio
{
    public function __construct(private PDO $pdo) {}

    /**
     * Lista libros con sus autores concatenados mediante LEFT JOIN.
     */
    public function listar(string $busqueda = ''): array
    {
        $sql = '
            SELECT l.libro_id, l.titulo, l.genero, l.fecha_publicacion, l.isbn,
                   COALESCE(
                       GROUP_CONCAT(CONCAT(a.nombre, " ", a.apellido)
                       ORDER BY a.apellido SEPARATOR ", "),
                       "Sin autor asignado"
                   ) AS autores
            FROM Libros l
            LEFT JOIN Libros_Autores la ON la.libro_id = l.libro_id
            LEFT JOIN Autores a ON a.autor_id = la.autor_id
        ';

        $params = [];
        if ($busqueda !== '') {
            $sql .= '
                WHERE l.titulo LIKE :q1
                   OR l.genero LIKE :q2
                   OR l.isbn LIKE :q3
                   OR EXISTS (
                       SELECT 1
                       FROM Libros_Autores la2
                       INNER JOIN Autores a2 ON a2.autor_id = la2.autor_id
                       WHERE la2.libro_id = l.libro_id
                         AND CONCAT(a2.nombre, " ", a2.apellido) LIKE :q4
                   )
            ';
            $like = '%' . $busqueda . '%';
            $params = [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
        }

        $sql .= '
            GROUP BY l.libro_id, l.titulo, l.genero, l.fecha_publicacion, l.isbn
            ORDER BY l.libro_id DESC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?Libro
    {
        $stmt = $this->pdo->prepare(
            'SELECT libro_id, titulo, genero, fecha_publicacion, isbn
             FROM Libros WHERE libro_id = :id'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();

        if (!$fila) {
            return null;
        }

        $stmtAutores = $this->pdo->prepare(
            'SELECT autor_id FROM Libros_Autores WHERE libro_id = :id'
        );
        $stmtAutores->execute([':id' => $id]);
        $autoresIds = array_map('intval', $stmtAutores->fetchAll(PDO::FETCH_COLUMN));

        return new Libro(
            (int) $fila['libro_id'],
            $fila['titulo'],
            $fila['genero'],
            $fila['fecha_publicacion'],
            $fila['isbn'],
            $autoresIds
        );
    }

    public function existeIsbn(string $isbn, ?int $excluirId = null): bool
    {
        if ($excluirId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM Libros WHERE isbn = :isbn AND libro_id <> :id'
            );
            $stmt->execute([':isbn' => $isbn, ':id' => $excluirId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM Libros WHERE isbn = :isbn');
            $stmt->execute([':isbn' => $isbn]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear(Libro $libro): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO Libros (titulo, genero, fecha_publicacion, isbn)
                 VALUES (:titulo, :genero, :fecha_publicacion, :isbn)'
            );
            $stmt->execute([
                ':titulo'            => $libro->titulo,
                ':genero'            => $libro->genero,
                ':fecha_publicacion' => $libro->fecha_publicacion,
                ':isbn'              => $libro->isbn,
            ]);

            $libroId = (int) $this->pdo->lastInsertId();
            $this->sincronizarAutores($libroId, $libro->autoresIds);

            $this->pdo->commit();
            return $libroId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function actualizar(Libro $libro): bool
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE Libros
                 SET titulo = :titulo,
                     genero = :genero,
                     fecha_publicacion = :fecha_publicacion,
                     isbn = :isbn
                 WHERE libro_id = :id'
            );
            $stmt->execute([
                ':titulo'            => $libro->titulo,
                ':genero'            => $libro->genero,
                ':fecha_publicacion' => $libro->fecha_publicacion,
                ':isbn'              => $libro->isbn,
                ':id'                => $libro->libro_id,
            ]);

            $this->sincronizarAutores((int) $libro->libro_id, $libro->autoresIds);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM Libros WHERE libro_id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Reemplaza las relaciones del libro en la tabla intermedia Libros_Autores.
     * @param int[] $autoresIds
     */
    private function sincronizarAutores(int $libroId, array $autoresIds): void
    {
        $del = $this->pdo->prepare('DELETE FROM Libros_Autores WHERE libro_id = :libro_id');
        $del->execute([':libro_id' => $libroId]);

        $ins = $this->pdo->prepare(
            'INSERT INTO Libros_Autores (libro_id, autor_id) VALUES (:libro_id, :autor_id)'
        );

        foreach (array_unique($autoresIds) as $autorId) {
            if ($autorId > 0) {
                $ins->execute([
                    ':libro_id' => $libroId,
                    ':autor_id' => $autorId,
                ]);
            }
        }
    }
}