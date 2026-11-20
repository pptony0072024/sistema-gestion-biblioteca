<?php
// config/config.php
//Se ubica fuera de public/ para no exponer credenciales desde el navegador
declare(strict_types=1);

return [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'biblioteca',
    'db_user' => 'root',
    'db_pass' => 'root',
];