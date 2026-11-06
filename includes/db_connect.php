<?php
/**
 * db_connect.php
 * ---------------------------------------------------------------
 * Connects PHP to the MySQL database using MySQLi.
 * Every page includes this file first (through header.php).
 *
 * >>> When you deploy to InfinityFree, change the 4 settings below
 *     using the values from Control Panel -> MySQL Databases. <<<
 */

// Sessions are used to remember the shopping cart and flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== DATABASE SETTINGS =====
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pos_db';
// =============================

// Make MySQLi throw errors we can catch (instead of silent failures)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');          // supports all characters
    $conn->query("SET time_zone = '+08:00'"); // Philippine time for NOW()/CURDATE()
} catch (mysqli_sql_exception $e) {
    // Friendly message instead of a scary PHP error
    http_response_code(500);
    die('<h3>Cannot connect to the database.</h3>'
      . '<p>Please check the settings in <code>includes/db_connect.php</code> '
      . 'and make sure the database was imported.</p>');
}

date_default_timezone_set('Asia/Manila');

// Shared helper functions (escaping, peso format, flash messages, ...)
require_once __DIR__ . '/functions.php';
