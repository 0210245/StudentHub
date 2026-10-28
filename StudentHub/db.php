<?php
/**
 * StudentHub Centralized Database Connection
 * Practical 8: MySQLi and PDO Connection with Prepared Statements & Auto-Migration
 */

define('SH_DB_HOST', 'localhost');
define('SH_DB_USER', 'root');
define('SH_DB_PASS', '');
define('SH_DB_NAME', 'studenthub');
define('SH_DB_CHARSET', 'utf8mb4');

/**
 * Returns MySQLi connection and ensures all required tables and columns exist
 */
function getDBConnection() {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @mysqli_connect(SH_DB_HOST, SH_DB_USER, SH_DB_PASS, SH_DB_NAME);
    if (!$conn) {
        error_log("Database connection failed: " . mysqli_connect_error());
        return false;
    }
    mysqli_set_charset($conn, SH_DB_CHARSET);

    // 1. Auto-migrate users columns if missing
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN last_login DATETIME NULL");

    // 2. Auto-migrate events columns if missing
    @mysqli_query($conn, "ALTER TABLE events ADD COLUMN event_type VARCHAR(50) NOT NULL DEFAULT 'General'");
    @mysqli_query($conn, "ALTER TABLE events ADD COLUMN description TEXT NULL");
    @mysqli_query($conn, "ALTER TABLE events ADD COLUMN poster VARCHAR(255) NULL");
    @mysqli_query($conn, "ALTER TABLE events ADD COLUMN status ENUM('open','closed') NOT NULL DEFAULT 'open'");

    // 3. Ensure students table exists (Practical 11)
    $createStudents = "CREATE TABLE IF NOT EXISTS students (
        student_id    INT AUTO_INCREMENT PRIMARY KEY,
        enrollment_no VARCHAR(20)  NOT NULL UNIQUE,
        full_name     VARCHAR(100) NOT NULL,
        email         VARCHAR(100) NOT NULL UNIQUE,
        phone         VARCHAR(15)  NULL,
        course        VARCHAR(50)  NOT NULL,
        department    VARCHAR(100) NOT NULL,
        year          TINYINT      NOT NULL,
        status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    @mysqli_query($conn, $createStudents);

    // Seed students if table is empty
    $chkStudents = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM students");
    if ($chkStudents && ($row = mysqli_fetch_assoc($chkStudents)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT IGNORE INTO students (enrollment_no, full_name, email, phone, course, department, year) VALUES
         ('D26CE101', 'Aarav Patel',     'aarav.patel@example.com',     '9876500001', 'B.Tech', 'Computer Engineering',         2),
         ('D26CE102', 'Diya Shah',       'diya.shah@example.com',       '9876500002', 'B.Tech', 'Computer Engineering',         2),
         ('D26CE103', 'Rohan Mehta',     'rohan.mehta@example.com',     '9876500003', 'B.Tech', 'Computer Engineering',         3),
         ('D26CE104', 'Isha Desai',      'isha.desai@example.com',      '9876500004', 'B.Tech', 'Computer Engineering',         1),
         ('D26IT201', 'Kabir Joshi',     'kabir.joshi@example.com',     '9876500005', 'B.Tech', 'Information Technology',       2),
         ('D26IT202', 'Meera Trivedi',   'meera.trivedi@example.com',   '9876500006', 'B.Tech', 'Information Technology',       3),
         ('D26IT203', 'Neel Parmar',     'neel.parmar@example.com',     '9876500007', 'B.Tech', 'Information Technology',       4),
         ('D26IT204', 'Sara Vyas',       'sara.vyas@example.com',       '9876500008', 'B.Tech', 'Information Technology',       1),
         ('D26EC301', 'Yash Solanki',    'yash.solanki@example.com',    '9876500009', 'B.Tech', 'Electronics & Communication',  2),
         ('D26EC302', 'Anaya Bhatt',     'anaya.bhatt@example.com',     '9876500010', 'B.Tech', 'Electronics & Communication',  3)");
    }

    // 4. Ensure audit_logs table exists (Practical 14)
    $createAudit = "CREATE TABLE IF NOT EXISTS audit_logs (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NULL,
        action      VARCHAR(60)  NOT NULL,
        details     VARCHAR(255) NULL,
        ip_address  VARCHAR(45)  NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    @mysqli_query($conn, $createAudit);

    // 5. Ensure remember_tokens table exists (Practical 10)
    $createTokens = "CREATE TABLE IF NOT EXISTS remember_tokens (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        selector    CHAR(18) NOT NULL UNIQUE,
        token_hash  CHAR(64) NOT NULL,
        expires_at  DATETIME NOT NULL
    )";
    @mysqli_query($conn, $createTokens);

    return $conn;
}

/**
 * Returns PDO connection
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
    $stmt = @mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isss", $userId, $action, $details, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
