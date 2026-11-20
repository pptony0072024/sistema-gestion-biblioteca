<?php
// public/autores.php
// Implementa el CRUD completo de la tabla Autores usando GET para consultar/filtrar y POST para crear, actualizar y eliminar, junto con el patrón PRG. Además permite ver los libros vinculados a cada autor.
declare(strict_types=1);

require_once __DIR__ . '/../src/Conexion.php';
require_once __DIR__ . '/../src/Autor.php';
require_once __DIR__ . '/../src/validaciones.php';

$tituloPagina = 'Gestión de Autores | Biblioteca';
$errores = [];
$mensaje = $_GET['msg'] ?? '';
$busqueda = trim((string) ($_GET['q'] ?? ''));

$valores = [
    'autor_id'         => '',
    'nombre'           => '',
    'apellido'         => '',
    'fecha_nacimiento' => '',
    'nacionalidad'     => '',
];

try {
    $pdo = Conexion::obtener();
    $repo = new AutorRepositorio($pdo);

    // Procesamiento de operaciones de escritura por POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'eliminar') {
            $id = filter_input(INPUT_POST, 'autor_id', FILTER_VALIDATE_INT);
            if ($id !== false && $id !== null) {
                $repo->eliminar($id);
                header('Location: autores.php?msg=Autor+eliminado+correctamente');
                exit;
            }
        }

        if ($accion === 'guardar') {
            $id = filter_input(INPUT_POST, 'autor_id', FILTER_VALIDATE_INT);
            $valores = [
                'autor_id'         => $id ?: '',
                'nombre'           => trim((string) ($_POST['nombre'] ?? '')),
                'apellido'         => trim((string) ($_POST['apellido'] ?? '')),
                'fecha_nacimiento' => trim((string) ($_POST['fecha_nacimiento'] ?? '')),
                'nacionalidad'     => trim((string) ($_POST['nacionalidad'] ?? '')),
            ];

            $errores = validarAutor($valores);

            if (!$errores) {
                $autor = new Autor(
                    $id ?: null,
                    $valores['nombre'],
                    $valores['apellido'],
                    $valores['fecha_nacimiento'],
                    $valores['nacionalidad']
                );

                if ($autor->autor_id !== null) {
                    $repo->actualizar($autor);
                    header('Location: autores.php?msg=Autor+actualizado+correctamente');
                } else {
                    $repo->crear($autor);
                    header('Location: autores.php?msg=Autor+registrado+correctamente');
                }
                exit;
            }
        }
    }

    // Cargar autor para edición mediante parámetro GET
    $editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($editarId && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $autorEditar = $repo->obtenerPorId($editarId);
        if ($autorEditar !== null) {
            $valores = [
                'autor_id'         => $autorEditar->autor_id,
                'nombre'           => $autorEditar->nombre,
                'apellido'         => $autorEditar->apellido,
                'fecha_nacimiento' => $autorEditar->fecha_nacimiento,
                'nacionalidad'     => $autorEditar->nacionalidad,
            ];
        }
    }

    // Ver libros de un autor específico
    $verLibrosId = filter_input(INPUT_GET, 'ver_libros', FILTER_VALIDATE_INT);
    $autorSeleccionado = $verLibrosId ? $repo->obtenerPorId($verLibrosId) : null;
    $librosDelAutor = $autorSeleccionado ? $repo->obtenerLibrosPorAutor((int) $autorSeleccionado->autor_id) : [];

    $autores = $repo->listar($busqueda);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errores['db'] = 'Ocurrió un error al comunicarse con la base de datos.';
    $autores = [];
    $autorSeleccionado = null;
    $librosDelAutor = [];
}

require __DIR__ . '/../views/encabezado.php';
?>

<h1>Gestión de Autores</h1>

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
    <h2><?= $valores['autor_id'] !== '' ? 'Editar Autor #' . (int) $valores['autor_id'] : 'Registrar Nuevo Autor' ?></h2>

    <form method="post" action="autores.php">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="autor_id" value="<?= e((string) $valores['autor_id']) ?>">

        <div class="form-grid">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="80" required value="<?= e($valores['nombre']) ?>">
            </div>

            <div class="campo">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" maxlength="80" required value="<?= e($valores['apellido']) ?>">
            </div>

            <div class="campo">
                <label for="fecha_nacimiento">Fecha de nacimiento</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required value="<?= e($valores['fecha_nacimiento']) ?>">
            </div>

            <div class="campo">
                <label for="nacionalidad">Nacionalidad</label>
                <input type="text" id="nacionalidad" name="nacionalidad" maxlength="60" required value="<?= e($valores['nacionalidad']) ?>">
            </div>
        </div>

        <div class="acciones-form">
            <button type="submit" class="btn btn-primario">
                <?= $valores['autor_id'] !== '' ? 'Actualizar Autor' : 'Guardar Autor' ?>
            </button>
            <?php if ($valores['autor_id'] !== ''): ?>
                <a class="btn btn-secundario" href="autores.php">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php if ($autorSeleccionado !== null): ?>
    <section class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
            <h2 style="margin:0;">Obras de <?= e($autorSeleccionado->nombreCompleto()) ?></h2>
            <a class="btn btn-secundario btn-sm" href="autores.php">Cerrar detalle</a>
        </div>
        <?php if ($librosDelAutor): ?>
            <ul>
                <?php foreach ($librosDelAutor as $lib): ?>
                    <li>
                        <strong><?= e($lib['titulo']) ?></strong>
                        (<?= e($lib['genero']) ?>, <?= e(date('Y', strtotime($lib['fecha_publicacion']))) ?>)
                        — ISBN: <code><?= e($lib['isbn']) ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Este autor aún no tiene libros asociados.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card">
    <h2>Listado de Autores</h2>

    <form method="get" action="autores.php" class="buscador">
        <input type="search" name="q" placeholder="Buscar por nombre, apellido o nacionalidad..." value="<?= e($busqueda) ?>">
        <button type="submit" class="btn btn-primario">Buscar</button>
        <?php if ($busqueda !== ''): ?>
            <a class="btn btn-secundario" href="autores.php">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="tabla-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre completo</th>
                    <th>Fecha nacimiento</th>
                    <th>Nacionalidad</th>
                    <th>Libros</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($autores as $a): ?>
                    <tr>
                        <td><?= (int) $a['autor_id'] ?></td>
                        <td><strong><?= e($a['nombre'] . ' ' . $a['apellido']) ?></strong></td>
                        <td><?= e(date('d/m/Y', strtotime($a['fecha_nacimiento']))) ?></td>
                        <td><?= e($a['nacionalidad']) ?></td>
                        <td><span class="badge"><?= (int) $a['total_libros'] ?> libro(s)</span></td>
                        <td class="acciones-tabla">
                            <a class="btn btn-acento btn-sm" href="autores.php?ver_libros=<?= (int) $a['autor_id'] ?>">Ver libros</a>
                            <a class="btn btn-secundario btn-sm" href="autores.php?editar=<?= (int) $a['autor_id'] ?>">Editar</a>
                            <form method="post" action="autores.php" onsubmit="return confirm('¿Eliminar este autor? También se eliminarán sus relaciones con libros.');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="autor_id" value="<?= (int) $a['autor_id'] ?>">
                                <button type="submit" class="btn btn-peligro btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$autores): ?>
                    <tr><td colspan="6">No se encontraron autores registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../views/pie.php'; ?>