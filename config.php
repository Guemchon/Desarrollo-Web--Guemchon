<?php
/* ==========================================================================
   Ecosistema Digital Guemchon Altime — config.php
   Dominio: guemchonaltime.com | Operaciones: Altimeshoper SpA
   Configuración Global & Conexión Segura PDO a Base de Datos (MySQL / PostgreSQL)
   ========================================================================== */

// 1. Entorno de Ejecución ('development' o 'production')
define('ENVIRONMENT', 'development');

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 2. Definición de Constantes Corporativas y Rutas
define('SITE_NAME', 'Guemchon Altime');
define('SITE_DOMAIN', 'guemchonaltime.com');
define('COMPANY_NAME', 'Altimeshoper SpA');
define('HOLDING_NAME', 'Holding Altime');
define('SITE_URL', 'https://guemchonaltime.com/');
define('ADMIN_EMAIL', 'contacto@guemchonaltime.com');

// 3. Credenciales de la Base de Datos (Ajustar según entorno XAMPP / Hostinger)
define('DB_DRIVER', 'mysql'); // 'mysql' o 'pgsql'
define('DB_HOST', 'localhost');
define('DB_NAME', 'guemchon_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 4. Función de Conexión Centralizada PDO (Singleton / Conexión Segura)
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = DB_DRIVER . ":host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (ENVIRONMENT === 'development') {
                die("Error de Conexión a la Base de Datos: " . $e->getMessage());
            } else {
                error_log("DB Connection Error: " . $e->getMessage());
                die("Error interno del servidor. Por favor, intente más tarde.");
            }
        }
    }

    return $pdo;
}

// 5. Configuración de Sesiones Securas
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
?>
