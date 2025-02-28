<?php
// Application configuration
define('APP_NAME', 'Sistema de Gestión de Pedidos');
define('APP_VERSION', '1.0.0');

// Database configuration


// Uncomment for local development
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'gestion_pedidos');
// define('DB_USER', 'root');
// define('DB_PASS', '');

// Path configurations
define('BASE_PATH', __DIR__);
define('INVOICE_PATH', BASE_PATH . '/facturas/');

// Create necessary directories
if (!is_dir(INVOICE_PATH)) {
    mkdir(INVOICE_PATH, 0755, true);
}
?>