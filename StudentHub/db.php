<?php
/**
 * StudentHub Centralized Database Connection
 * Practical 8: MySQLi and PDO Connection with Prepared Statements
 */

define('SH_DB_HOST', 'localhost');
define('SH_DB_USER', 'root');
define('SH_DB_PASS', '');
define('SH_DB_NAME', 'studenthub');
define('SH_DB_CHARSET', 'utf8mb4');

/**
 * Returns MySQLi connection
 */
function getDBConnection() {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @mysqli_connect(SH_DB_HOST, SH_DB_USER, SH_DB_PASS, SH_DB_NAME);
    if (!$conn) {
        error_log("Database connection failed: " . mysqli_connect_error());
        return false;
    }
    mysqli_set_charset($conn, SH_DB_CHARSET);
    return $conn;
}

/**
 * Returns PDO connection (Practical 8 Intermediate extension)
 */
function getPDOConnection() {
    $dsn = "mysql:host=" . SH_DB_HOST . ";dbname=" . SH_DB_NAME . ";charset=" . SH_DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        return new PDO($dsn, SH_DB_USER, SH_DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("PDO Connection failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Logs an administrative or security action into audit_logs (Practical 14)
 */
function recordAuditLog($conn, $userId, $action, $details) {
    if (!$conn) return;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isss", $userId, $action, $details, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
