<?php
/* =========================================================
   register.php
   Receives the form from Register.html, validates it,
   and saves the new user in the "users" table (database: studenthub).
   Put this file in the StudentHub/ folder next to Register.html.
   =========================================================
*/

// Database settings (accounts database, default XAMPP login)
mysqli_report(MYSQLI_REPORT_OFF);   // return false on DB errors instead of throwing exceptions
$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "studenthub";

/* Shows a small styled result page and stops the script */
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

// 1. Only accept a form submission (POST). Otherwise go back to the form.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Register.html");
    exit;
}

// 2. Read the values (trim removes extra spaces)
$fullName   = trim($_POST["full_name"] ?? "");
$email      = trim($_POST["email"] ?? "");
$role       = $_POST["role"] ?? "";
$userId     = trim($_POST["user_id"] ?? "");
$department = trim($_POST["department"] ?? "");
$password   = $_POST["password"] ?? "";
$confirm    = $_POST["confirm_password"] ?? "";

// 3. Server-side validation (never trust only the browser/JavaScript check)
if ($fullName === "" || $email === "" || $userId === "" || $department === "") {
    showMessage("Missing details", "Please fill in all the fields.", "Back", "Register.html");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    showMessage("Invalid email", "Please enter a valid email address.", "Back", "Register.html");
}
if ($role !== "student" && $role !== "faculty") {
    showMessage("Invalid role", "Please choose Student or Faculty.", "Back", "Register.html");
}
if (strlen($password) < 8) {
    showMessage("Weak password", "Password must be at least 8 characters.", "Back", "Register.html");
}
if ($password !== $confirm) {
    showMessage("Passwords do not match", "Please re-enter your password.", "Back", "Register.html");
}

// 4. Connect to MySQL (@ hides the raw PHP warning; we show our own message)
$conn = @mysqli_connect($host, $dbUser, $dbPass, $dbName);
if (!$conn) {
    error_log(mysqli_connect_error());
    showMessage("Server problem", "Could not connect to the database. Is MySQL running?", "Back", "Register.html");
}

// 5. Check if this email or ID is already registered
$check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? OR user_id = ?");
mysqli_stmt_bind_param($check, "ss", $email, $userId);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) {
    mysqli_stmt_close($check);
    mysqli_close($conn);
    showMessage("Already registered", "This email or ID is already in use.", "Login instead", "Login.html");
}
mysqli_stmt_close($check);

// 6. Hash the password (never store a plain-text password!)
$hash = password_hash($password, PASSWORD_DEFAULT);

// 7. Insert using a prepared statement (protects against SQL injection)
$stmt = mysqli_prepare($conn,
    "INSERT INTO users (full_name, email, role, user_id, department, password)
     VALUES (?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssssss", $fullName, $email, $role, $userId, $department, $hash);

if (mysqli_stmt_execute($stmt)) {
    showMessage("Registration successful!", "Welcome to StudentHub, " . $fullName . ".", "Go to Login", "Login.html");
} else {
    error_log(mysqli_stmt_error($stmt));
    showMessage("Registration failed", "Something went wrong. Please try again.", "Back", "Register.html");
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>