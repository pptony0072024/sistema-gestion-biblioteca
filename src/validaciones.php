<?php
// src/validaciones.php
// Separa las validaciones y el saneamiento en funciones reutilizables.
declare(strict_types=1);

/**
 * Escapa texto antes de imprimirlo en HTML para prevenir XSS.
 */
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Valida que una fecha tenga formato estricto YYYY-MM-DD y sea válida en calendario.
 */
function esFechaValida(string $fecha): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    return $dt !== false && $dt->format('Y-m-d') === $fecha;
}

/**
 * Valida los datos de un autor y devuelve un arreglo de errores.
 * @return array<string, string>
 */
function validarAutor(array $datos): array
{
    $errores = [];

    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $apellido = trim((string) ($datos['apellido'] ?? ''));
    $fecha = trim((string) ($datos['fecha_nacimiento'] ?? ''));
    $nacionalidad = trim((string) ($datos['nacionalidad'] ?? ''));

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $errores['nombre'] = 'El nombre es obligatorio (máximo 80 caracteres).';
    }
    if ($apellido === '' || mb_strlen($apellido) > 80) {
        $errores['apellido'] = 'El apellido es obligatorio (máximo 80 caracteres).';
    }
    if (!esFechaValida($fecha)) {
        $errores['fecha_nacimiento'] = 'Ingrese una fecha de nacimiento válida.';
    }
    if ($nacionalidad === '' || mb_strlen($nacionalidad) > 60) {
        $errores['nacionalidad'] = 'La nacionalidad es obligatoria (máximo 60 caracteres).';
    }

    return $errores;
}

/**
 * Valida los datos de un libro y al menos un autor asociado.
 * @return array<string, string>
 */
function validarLibro(array $datos): array
{
    $errores = [];

    $titulo = trim((string) ($datos['titulo'] ?? ''));
    $genero = trim((string) ($datos['genero'] ?? ''));
    $fecha = trim((string) ($datos['fecha_publicacion'] ?? ''));
    $isbn = trim((string) ($datos['isbn'] ?? ''));
    $autores = $datos['autores'] ?? [];

    if ($titulo === '' || mb_strlen($titulo) > 150) {
        $errores['titulo'] = 'El título es obligatorio (máximo 150 caracteres).';
    }
    if ($genero === '' || mb_strlen($genero) > 60) {
        $errores['genero'] = 'El género es obligatorio (máximo 60 caracteres).';
    }
    if (!esFechaValida($fecha)) {
        $errores['fecha_publicacion'] = 'Ingrese una fecha de publicación válida.';
    }
    if ($isbn === '' || mb_strlen($isbn) > 20 || !preg_match('/^[0-9Xx\-]+$/', $isbn)) {
        $errores['isbn'] = 'El ISBN es obligatorio (solo números, guiones o X; máx. 20 caracteres).';
    }
    if (!is_array($autores) || count($autores) === 0) {
        $errores['autores'] = 'Debe seleccionar al menos un autor para el libro.';
    }

    return $errores;
}