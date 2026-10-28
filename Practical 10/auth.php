<?php
/**
 * Practical 10: Authentication Middleware & Session Security Manager
 * Features:
 *   1. Session security & Inactivity Timeout (15 minutes).
 *   2. Remember-Me Token verification via Database SHA-256 Hash.
 *   3. Role-Based Access Control (RBAC) helpers.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Practical 8/config.php';

// Inactivity timeout configuration (15 minutes = 900 seconds)
define('SESSION_TIMEOUT_SECONDS', 900);

// Check Session Inactivity Timeout
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) {
        // Session expired due to inactivity
        session_unset();
        session_destroy();
        header('Location: ../Practical 10/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
} elseif (isset($_COOKIE['remember_me'])) {
    // Check Remember-Me Token Auto-Login (Advanced Extension)
    attempt_remember_me_login();
}

function attempt_remember_me_login() {
    $cookie = $_COOKIE['remember_me'] ?? '';
    if (empty($cookie) || strpos($cookie, ':') === false) return;

    list($selector, $validator) = explode(':', $cookie, 2);
    if (empty($selector) || empty($validator)) return;

    $conn = getMySQLiConnection();
    if (!$conn) return;

    $stmt = mysqli_prepare($conn, 
        "SELECT r.id, r.user_id, r.token_hash, r.expires_at, u.full_name, u.email, u.role, u.department 
         FROM remember_tokens r
         JOIN users u ON u.id = r.user_id
         WHERE r.selector = ? AND r.expires_at > NOW() AND u.status = 'active' LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "s", $selector);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        $calc_hash = hash('sha256', $validator);
        if (hash_equals($row['token_hash'], $calc_hash)) {
            // Valid token! Restore session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['department'] = $row['department'];
            $_SESSION['last_activity'] = time();
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ../Practical 10/login.php?unauthorized=1');
        exit;
    }
}

function require_role($required_role) {
    require_login();
    if (($_SESSION['role'] ?? '') !== $required_role) {
        http_response_code(403);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                <h2>403 - Forbidden: Access Denied</h2>
                <p>This module requires <strong>" . htmlspecialchars($required_role) . "</strong> privileges.</p>
                <a href='../Practical 10/login.php'>Return to Login</a>
              </div>";
        exit;
    }
}
