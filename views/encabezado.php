<?php
// views/encabezado.php
declare(strict_types=1);
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina ?? 'Sistema de Gestión de Biblioteca') ?></title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="topbar">
    <div class="container topbar-inner">
        <a class="brand" href="index.php">📚 Biblioteca UNIANDES</a>
        <nav class="nav">
            <a href="index.php" class="<?= $paginaActual === 'index.php' ? 'activo' : '' ?>">Inicio</a>
            <a href="libros.php" class="<?= $paginaActual === 'libros.php' ? 'activo' : '' ?>">Libros</a>
            <a href="autores.php" class="<?= $paginaActual === 'autores.php' ? 'activo' : '' ?>">Autores</a>
        </nav>
    </div>
</header>
<main class="container">