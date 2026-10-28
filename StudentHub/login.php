<?php
/**
 * login.php - Practical 10: Secure Authentication, Session Management, RBAC & Timeout
 */
session_start();
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
    header("Location: Login.html");
    exit;
}

$email    = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$role     = $_POST["role"] ?? "";

if ($email === "" || $password === "" || $role === "") {
    showMessage("Missing details", "Please fill in email, password, and select your role.", "Back", "Login.html");
}

$conn = getDBConnection();
if (!$conn) {
    showMessage("Server problem", "Could not connect to database. Is MySQL running on WAMP?", "Back", "Login.html");
}

// 1. Prepared Statement user lookup
$stmt = mysqli_prepare($conn,
    "SELECT id, full_name, email, role, user_id, department, password, status
     FROM users WHERE email = ? AND role = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "ss", $email, $role);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

// 2. Verify password with password_verify()
if (!$user || !password_verify($password, $user["password"])) {
    mysqli_close($conn);
    showMessage("Login failed", "Incorrect email, password or role.", "Try again", "Login.html");
}

if ($user['status'] !== 'active') {
    mysqli_close($conn);
    showMessage("Account Inactive", "Your account has been deactivated. Contact an administrator.", "Back", "Login.html");
}

// 3. Regenerate session ID (prevents session fixation)
session_regenerate_id(true);
$_SESSION["user_id"]       = $user["id"];
$_SESSION["full_name"]     = $user["full_name"];
$_SESSION["email"]         = $user["email"];
$_SESSION["role"]          = $user["role"];
$_SESSION["student_id"]    = $user["user_id"];
$_SESSION["department"]    = $user["department"];
$_SESSION["last_activity"] = time();

// 4. Update last_login timestamp & record audit
$upd = mysqli_prepare($conn, "UPDATE users SET last_login = NOW() WHERE id = ?");
mysqli_stmt_bind_param($upd, "i", $user["id"]);
mysqli_stmt_execute($upd);
mysqli_stmt_close($upd);

recordAuditLog($conn, $user["id"], 'USER_LOGIN', "{$user['email']} logged in as {$user['role']}");
mysqli_close($conn);

// 5. Role-Based Access Control Redirection
if ($user["role"] === "admin") {
    header("Location: Admin-Dashboard.php");
} else {
    header("Location: Profile.html");
}
exit;
?>
