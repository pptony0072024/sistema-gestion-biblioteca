<?php
// public/libros.php
// Implementa el CRUD completo de Libros, la validación de ISBN único y la relación N:M mediante casillas name="autores[]" que llegan a PHP como un arreglo.
declare(strict_types=1);

require_once __DIR__ . '/../src/Conexion.php';
require_once __DIR__ . '/../src/Autor.php';
require_once __DIR__ . '/../src/Libro.php';
require_once __DIR__ . '/../src/validaciones.php';

$tituloPagina = 'Gestión de Libros | Biblioteca';
$errores = [];
$mensaje = $_GET['msg'] ?? '';
$busqueda = trim((string) ($_GET['q'] ?? ''));

$valores = [
    'libro_id'          => '',
    'titulo'            => '',
    'genero'            => '',
    'fecha_publicacion' => '',
    'isbn'              => '',
    'autores'           => [],
];

try {
    $pdo = Conexion::obtener();
    $libroRepo = new LibroRepositorio($pdo);
    $autorRepo = new AutorRepositorio($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'eliminar') {
            $id = filter_input(INPUT_POST, 'libro_id', FILTER_VALIDATE_INT);
            if ($id !== false && $id !== null) {
                $libroRepo->eliminar($id);
                header('Location: libros.php?msg=Libro+eliminado+correctamente');
                exit;
            }
        }

        if ($accion === 'guardar') {
            $id = filter_input(INPUT_POST, 'libro_id', FILTER_VALIDATE_INT);
            $autoresPost = $_POST['autores'] ?? [];
            $autoresIds = is_array($autoresPost) ? array_map('intval', $autoresPost) : [];

            $valores = [
                'libro_id'          => $id ?: '',
                'titulo'            => trim((string) ($_POST['titulo'] ?? '')),
                'genero'            => trim((string) ($_POST['genero'] ?? '')),
                'fecha_publicacion' => trim((string) ($_POST['fecha_publicacion'] ?? '')),
                'isbn'              => trim((string) ($_POST['isbn'] ?? '')),
                'autores'           => $autoresIds,
            ];

            $errores = validarLibro($valores);

            if (!isset($errores['isbn']) && $libroRepo->existeIsbn($valores['isbn'], $id ?: null)) {
                $errores['isbn'] = 'El ISBN ingresado ya pertenece a otro libro.';
            }

            if (!$errores) {
                $libro = new Libro(
                    $id ?: null,
                    $valores['titulo'],
                    $valores['genero'],
                    $valores['fecha_publicacion'],
                    $valores['isbn'],
                    $autoresIds
                );

                if ($libro->libro_id !== null) {
                    $libroRepo->actualizar($libro);
                    header('Location: libros.php?msg=Libro+actualizado+correctamente');
                } else {
                    $libroRepo->crear($libro);
                    header('Location: libros.php?msg=Libro+registrado+correctamente');
                }
                exit;
            }
        }
    }

    // Cargar datos para edición
    $editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($editarId && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $libroEditar = $libroRepo->obtenerPorId($editarId);
        if ($libroEditar !== null) {
            $valores = [
                'libro_id'          => $libroEditar->libro_id,
                'titulo'            => $libroEditar->titulo,
                'genero'            => $libroEditar->genero,
                'fecha_publicacion' => $libroEditar->fecha_publicacion,
                'isbn'              => $libroEditar->isbn,
                'autores'           => $libroEditar->autoresIds,
            ];
        }
    }

    $todosAutores = $autorRepo->listar();
    $libros = $libroRepo->listar($busqueda);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errores['db'] = 'Ocurrió un error al comunicarse con la base de datos.';
    $todosAutores = [];
    $libros = [];
}

require __DIR__ . '/../views/encabezado.php';
?>

<h1>Gestión de Libros</h1>

<?php if ($mensaje !== ''): ?>
    <div class="alerta alerta-ok"><?= e($mensaje) ?></div>
<?php endif; ?>

<?php if ($errores): ?>
    <div class="alerta alerta-error">
        <strong>Revise los siguientes campos:</strong>
        <ul>
            <?php foreach ($errores as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="card">
    <h2><?= $valores['libro_id'] !== '' ? 'Editar Libro #' . (int) $valores['libro_id'] : 'Registrar Nuevo Libro' ?></h2>

    <form method="post" action="libros.php">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="libro_id" value="<?= e((string) $valores['libro_id']) ?>">

        <div class="form-grid">
            <div class="campo">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" maxlength="150" required value="<?= e($valores['titulo']) ?>">
            </div>

            <div class="campo">
                <label for="genero">Género</label>
                <input type="text" id="genero" name="genero" maxlength="60" required value="<?= e($valores['genero']) ?>">
            </div>

            <div class="campo">
                <label for="fecha_publicacion">Fecha de publicación</label>
                <input type="date" id="fecha_publicacion" name="fecha_publicacion" required value="<?= e($valores['fecha_publicacion']) ?>">
            </div>

            <div class="campo">
                <label for="isbn">ISBN</label>
                <input type="text" id="isbn" name="isbn" maxlength="20" required placeholder="Ej: 978-9978-80-123-4" value="<?= e($valores['isbn']) ?>">
            </div>

            <div class="campo campo-full">
                <label>Autor(es) asociados (seleccione uno o varios)</label>
                <?php if ($todosAutores): ?>
                    <div class="checkbox-grid">
                        <?php foreach ($todosAutores as $autor): ?>
                            <?php $aid = (int) $autor['autor_id']; ?>
                            <label class="checkbox-item">
                                <input
                                    type="checkbox"
                                    name="autores[]"
                                    value="<?= $aid ?>"
                                    <?= in_array($aid, $valores['autores'], true) ? 'checked' : '' ?>
                                >
                                <?= e($autor['nombre'] . ' ' . $autor['apellido']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No existen autores registrados. <a href="autores.php">Cree un autor primero</a>.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="acciones-form">
            <button type="submit" class="btn btn-primario" <?= !$todosAutores ? 'disabled' : '' ?>>
                <?= $valores['libro_id'] !== '' ? 'Actualizar Libro' : 'Guardar Libro' ?>
            </button>
            <?php if ($valores['libro_id'] !== ''): ?>
                <a class="btn btn-secundario" href="libros.php">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="card">
    <h2>Catálogo de Libros</h2>

    <form method="get" action="libros.php" class="buscador">
        <input type="search" name="q" placeholder="Buscar por título, género, ISBN o autor..." value="<?= e($busqueda) ?>">
        <button type="submit" class="btn btn-primario">Buscar</button>
        <?php if ($busqueda !== ''): ?>
            <a class="btn btn-secundario" href="libros.php">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="tabla-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Autor(es)</th>
                    <th>Género</th>
                    <th>Publicación</th>
                    <th>ISBN</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($libros as $l): ?>
                    <tr>
                        <td><?= (int) $l['libro_id'] ?></td>
                        <td><strong><?= e($l['titulo']) ?></strong></td>
                        <td><?= e($l['autores']) ?></td>
                        <td><span class="badge"><?= e($l['genero']) ?></span></td>
                        <td><?= e(date('d/m/Y', strtotime($l['fecha_publicacion']))) ?></td>
                        <td><code><?= e($l['isbn']) ?></code></td>
                        <td class="acciones-tabla">
                            <a class="btn btn-secundario btn-sm" href="libros.php?editar=<?= (int) $l['libro_id'] ?>">Editar</a>
                            <form method="post" action="libros.php" onsubmit="return confirm('¿Eliminar este libro del catálogo?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="libro_id" value="<?= (int) $l['libro_id'] ?>">
                                <button type="submit" class="btn btn-peligro btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$libros): ?>
                    <tr><td colspan="7">No se encontraron libros registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../views/pie.php'; ?>