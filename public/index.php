<?php
// public/index.php
// Muestra el resumen general y el catálogo combinado de libros con sus autores, manejando errores de conexión sin revelar credenciales al usuario final.
declare(strict_types=1);

require_once __DIR__ . '/../src/Conexion.php';
require_once __DIR__ . '/../src/Libro.php';
require_once __DIR__ . '/../src/validaciones.php';

$tituloPagina = 'Inicio | Sistema de Gestión de Biblioteca';
$errorDb = null;
$totalLibros = 0;
$totalAutores = 0;
$totalRelaciones = 0;
$libros = [];

try {
    $pdo = Conexion::obtener();
    $totalLibros = (int) $pdo->query('SELECT COUNT(*) FROM Libros')->fetchColumn();
    $totalAutores = (int) $pdo->query('SELECT COUNT(*) FROM Autores')->fetchColumn();
    $totalRelaciones = (int) $pdo->query('SELECT COUNT(*) FROM Libros_Autores')->fetchColumn();

    $libroRepo = new LibroRepositorio($pdo);
    $libros = $libroRepo->listar();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorDb = 'No fue posible conectar con la base de datos. Verifique config/config.php y que el script biblioteca.sql haya sido ejecutado.';
}

require __DIR__ . '/../views/encabezado.php';
?>

<h1>Panel de Gestión de Biblioteca</h1>

<?php if ($errorDb !== null): ?>
    <div class="alerta alerta-error"><?= e($errorDb) ?></div>
<?php else: ?>
    <section class="grid-stats">
        <div class="stat-card">
            <h3>Libros registrados</h3>
            <p class="numero"><?= $totalLibros ?></p>
        </div>
        <div class="stat-card">
            <h3>Autores registrados</h3>
            <p class="numero"><?= $totalAutores ?></p>
        </div>
        <div class="stat-card">
            <h3>Relaciones Libro–Autor</h3>
            <p class="numero"><?= $totalRelaciones ?></p>
        </div>
    </section>

    <section class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
            <h2 style="margin:0;">Catálogo General (Libros y Autores)</h2>
            <div style="display:flex; gap:0.5rem;">
                <a class="btn btn-primario" href="libros.php">Gestionar Libros</a>
                <a class="btn btn-acento" href="autores.php">Gestionar Autores</a>
            </div>
        </div>

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
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$libros): ?>
                        <tr><td colspan="6">No existen libros registrados todavía.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../views/pie.php'; ?>