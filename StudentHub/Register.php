<?php
/**
 * register.php - Practical 9: Secure Registration with Password Hashing & Duplicate Checks
 */
require_once __DIR__ . '/db.php';

function showMessage($title, $message, $linkText, $linkUrl) {
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>
          <meta name='viewport' content='width=device-width, initial-scale=1.0'>
          <title>StudentHub | " . htmlspecialchars($title) . "</title>
          <link rel='stylesheet' href='../StudentHub-css.css'></head><body>
          <main class='container'><div class='card' style='text-align:center'>
          <h2>" . htmlspecialchars($title) . "</h2>
          <p>" . htmlspecialchars($message) . "</p><br>
          <a class='btn' href='" . $linkUrl . "'>" . htmlspecialchars($linkText) . "</a>
          </div></main></body></html>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Register.html");
    exit;
}

$fullName   = trim($_POST["full_name"] ?? "");
$email      = filter_var(trim($_POST["email"] ?? ""), FILTER_SANITIZE_EMAIL);
$role       = $_POST["role"] ?? "student";
$userId     = trim($_POST["user_id"] ?? "");
$department = trim($_POST["department"] ?? "");
$password   = $_POST["password"] ?? "";
$confirm    = $_POST["confirm_password"] ?? "";

if ($fullName === "" || $email === "" || $userId === "" || $department === "") {
    showMessage("Missing details", "Please fill in all the required fields.", "Back", "Register.html");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    showMessage("Invalid email", "Please enter a valid institutional email address.", "Back", "Register.html");
}

if (!in_array($role, ["student", "faculty"])) {
    showMessage("Invalid role", "Please choose Student or Faculty.", "Back", "Register.html");
}

if (strlen($password) < 8) {
    showMessage("Weak password", "Password must be at least 8 characters.", "Back", "Register.html");
}

if ($password !== $confirm) {
    showMessage("Passwords do not match", "Please re-enter your password.", "Back", "Register.html");
}

$conn = getDBConnection();
if (!$conn) {
    showMessage("Server problem", "Could not connect to database. Ensure MySQL is running on WAMP.", "Back", "Register.html");
}

// 1. Check for Duplicate Email or User ID (Prepared Statement)
$check = mysqli_prepare($conn, "SELECT id, email, user_id FROM users WHERE email = ? OR user_id = ? LIMIT 1");
if ($check) {
    mysqli_stmt_bind_param($check, "ss", $email, $userId);
    mysqli_stmt_execute($check);
    $res = mysqli_stmt_get_result($check);

    if ($existing = mysqli_fetch_assoc($res)) {
        mysqli_stmt_close($check);
        mysqli_close($conn);
        if (strtolower($existing['email']) === strtolower($email)) {
            showMessage("Already registered", "This email is already in use.", "Login instead", "Login.html");
        } else {
            showMessage("ID already exists", "This Student/Faculty ID is already registered.", "Back", "Register.html");
        }
    }
    mysqli_stmt_close($check);
}

// 2. Hash Password with BCrypt (password_hash)
$hash = password_hash($password, PASSWORD_DEFAULT);

// 3. Prepared Statement INSERT (safe across old and new schemas)
$stmt = mysqli_prepare($conn,
    "INSERT INTO users (full_name, email, role, user_id, department, password)
     VALUES (?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    $err = mysqli_error($conn);
    mysqli_close($conn);
    showMessage("Registration Error", "Could not prepare insert statement: " . $err, "Back", "Register.html");
}

mysqli_stmt_bind_param($stmt, "ssssss", $fullName, $email, $role, $userId, $department, $hash);

if (mysqli_stmt_execute($stmt)) {
    $newId = mysqli_insert_id($conn);
    recordAuditLog($conn, $newId, 'USER_REGISTER', "Registered {$role} account ({$email})");
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    showMessage("Registration successful!", "Welcome to StudentHub, " . htmlspecialchars($fullName) . ".", "Go to Login", "Login.html");
} else {
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    showMessage("Registration failed", "Database error: " . $err, "Back", "Register.html");
}
?>
