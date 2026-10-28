<?php
/**
 * Practical 8: Database Connection Configuration (MySQLi & PDO)
 * Subject: ITUE203 Web Development Frameworks - CHARUSAT
 */

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'studenthub');
define('DB_CHARSET', 'utf8mb4');

/**
 * 1. MySQLi Connection Function (Prepared statements ready)
 */
function getMySQLiConnection() {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        error_log("MySQLi Connection Failed: " . mysqli_connect_error());
        return false;
    }
    mysqli_set_charset($conn, DB_CHARSET);
    return $conn;
}

/**
 * 2. PDO Connection Function (Intermediate Extension)
 * Uses PDO with Exception error mode & UTF-8 character set
 */
function getPDOConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("PDO Connection Failed: " . $e->getMessage());
        return false;
    }
}
