<?php
/**
 * Practical 14: Intermediate Extension - User Status Toggle
 */
session_start();
require_once __DIR__ . '/../Practical 8/config.php';

$conn = getMySQLiConnection();
if (!$conn) die("Database error");

$id = intval($_GET['id'] ?? 0);
$status = ($_GET['status'] === 'active') ? 'active' : 'inactive';

if ($id > 0) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Record audit log
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $uid = $_SESSION['user_id'] ?? null;
    $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'USER_STATUS_CHANGE', ?, ?)");
    if ($log_stmt) {
        $details = "Changed user ID {$id} status to {$status}";
        mysqli_stmt_bind_param($log_stmt, "iss", $uid, $details, $ip);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }
}

mysqli_close($conn);
header('Location: index.php');
exit;
