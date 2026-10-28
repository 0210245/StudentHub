<?php
/**
 * Practical 10: Secure Logout Handler
 * Revokes Remember-Me Token from DB, Clears Cookies, Destroys Session
 */
session_start();
require_once __DIR__ . '/../Practical 8/config.php';

// Revoke DB Remember-Me Token if cookie exists
if (isset($_COOKIE['remember_me'])) {
    list($selector, $validator) = explode(':', $_COOKIE['remember_me'] . ':', 2);
    if (!empty($selector)) {
        $conn = getMySQLiConnection();
        if ($conn) {
            $stmt = mysqli_prepare($conn, "DELETE FROM remember_tokens WHERE selector = ?");
            mysqli_stmt_bind_param($stmt, "s", $selector);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
        }
    }
    // Delete the cookie
    setcookie('remember_me', '', time() - 3600, '/');
}

// Log audit entry if user was logged in
if (!empty($_SESSION['user_id'])) {
    $conn = getMySQLiConnection();
    if ($conn) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $audit_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'USER_LOGOUT', 'User logged out', ?)");
        if ($audit_stmt) {
            mysqli_stmt_bind_param($audit_stmt, "is", $_SESSION['user_id'], $ip);
            mysqli_stmt_execute($audit_stmt);
            mysqli_stmt_close($audit_stmt);
        }
        mysqli_close($conn);
    }
}

// Unset all session variables & destroy
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header('Location: login.php?logout=1');
exit;
