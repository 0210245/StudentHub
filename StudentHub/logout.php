<?php
/**
 * logout.php - Practical 10: Secure Session Termination
 */
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['user_id'])) {
    $conn = getDBConnection();
    if ($conn) {
        recordAuditLog($conn, $_SESSION['user_id'], 'USER_LOGOUT', "User logged out");
        mysqli_close($conn);
    }
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: Login.html");
exit;
