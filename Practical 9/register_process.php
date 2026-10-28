<?php
/**
 * Practical 9: Secure Registration Processor
 * Uses MySQLi Prepared Statements, password_hash(), Duplicate Checks, and Audit Logging
 */
session_start();
require_once __DIR__ . '/../Practical 8/config.php';

// 1. Enforce POST submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 2. CSRF Token Validation
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['reg_errors'] = ['Security check failed: Invalid CSRF token.'];
    header('Location: index.php');
    exit;
}

// 3. Read and sanitize inputs
$full_name   = trim(strip_tags($_POST['full_name'] ?? ''));
$email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$user_id     = trim(strip_tags($_POST['user_id'] ?? ''));
$role        = trim($_POST['role'] ?? 'student');
$department  = trim(strip_tags($_POST['department'] ?? ''));
$password    = $_POST['password'] ?? '';
$confirm_pwd = $_POST['confirm_password'] ?? '';

// 4. Server-Side Validation
$errors = [];

if (empty($full_name) || strlen($full_name) < 3) {
    $errors[] = 'Full name is required and must be at least 3 characters.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (empty($user_id)) {
    $errors[] = 'User ID / Enrollment number is required.';
}

if (!in_array($role, ['student', 'faculty'])) {
    $errors[] = 'Invalid user role specified.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
}

if ($password !== $confirm_pwd) {
    $errors[] = 'Passwords do not match.';
}

// If basic validation fails, return early
if (!empty($errors)) {
    $_SESSION['reg_errors'] = $errors;
    header('Location: index.php');
    exit;
}

// 5. Connect to Database using MySQLi
$conn = getMySQLiConnection();
if (!$conn) {
    $_SESSION['reg_errors'] = ['Database connection failure. Please ensure MySQL is running on WAMP.'];
    header('Location: index.php');
    exit;
}

// 6. Duplicate Email and User ID Check (Prepared Statement)
$check_stmt = mysqli_prepare($conn, "SELECT id, email, user_id FROM users WHERE email = ? OR user_id = ? LIMIT 1");
mysqli_stmt_bind_param($check_stmt, "ss", $email, $user_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

if ($row = mysqli_fetch_assoc($result)) {
    if (strtolower($row['email']) === strtolower($email)) {
        $errors[] = 'This email address is already registered. Please login or use another email.';
    }
    if (strtoupper($row['user_id']) === strtoupper($user_id)) {
        $errors[] = 'This User ID / Enrollment number is already registered.';
    }
}
mysqli_stmt_close($check_stmt);

if (!empty($errors)) {
    mysqli_close($conn);
    $_SESSION['reg_errors'] = $errors;
    header('Location: index.php');
    exit;
}

// 7. Secure Password Hashing (BCrypt algorithm)
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// 8. Prepared Statement INSERT
$insert_stmt = mysqli_prepare($conn, 
    "INSERT INTO users (full_name, email, role, user_id, department, password, status) VALUES (?, ?, ?, ?, ?, ?, 'active')"
);
mysqli_stmt_bind_param($insert_stmt, "ssssss", $full_name, $email, $role, $user_id, $department, $hashed_password);

if (mysqli_stmt_execute($insert_stmt)) {
    $new_user_id = mysqli_insert_id($conn);
    mysqli_stmt_close($insert_stmt);

    // 9. Audit Log Entry (Advanced Extension)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $action = 'USER_REGISTER';
    $details = "Registered user {$email} with role {$role}";
    $audit_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($audit_stmt) {
        mysqli_stmt_bind_param($audit_stmt, "isss", $new_user_id, $action, $details, $ip);
        mysqli_stmt_execute($audit_stmt);
        mysqli_stmt_close($audit_stmt);
    }

    mysqli_close($conn);

    // Rotate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: index.php?status=success');
    exit;
} else {
    $errors[] = 'Database error during insertion: ' . mysqli_error($conn);
    mysqli_stmt_close($insert_stmt);
    mysqli_close($conn);
    $_SESSION['reg_errors'] = $errors;
    header('Location: index.php');
    exit;
}
