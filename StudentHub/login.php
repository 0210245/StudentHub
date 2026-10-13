<?php
/* =========================================================
   login.php
   Receives the form from Login.html (email, password, role),
   checks it against the "users" table in the studenthub
   database, and starts a session if the details are correct.
   Put this file in the StudentHub/ folder next to Login.html.
   ========================================================= */

session_start();   // lets us remember the logged-in user across pages

// Database settings (same database as register.php)
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
    header("Location: Login.html");
    exit;
}

// 2. Read the values
$email    = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$role     = $_POST["role"] ?? "";

// 3. Basic validation
if ($email === "" || $password === "" || $role === "") {
    showMessage("Missing details", "Please fill in all the fields.", "Back", "Login.html");
}
if ($role !== "student" && $role !== "faculty" && $role !== "admin") {
    showMessage("Invalid role", "Please choose a valid role.", "Back", "Login.html");
}

// 4. Connect to MySQL (@ hides the raw PHP warning; we show our own message)
$conn = @mysqli_connect($host, $dbUser, $dbPass, $dbName);
if (!$conn) {
    error_log(mysqli_connect_error());
    showMessage("Server problem", "Could not connect to the database. Is MySQL running?", "Back", "Login.html");
}

// 5. Find the user by email AND role (prepared statement = safe from SQL injection)
$stmt = mysqli_prepare($conn,
    "SELECT id, full_name, email, role, user_id, department, password
     FROM users WHERE email = ? AND role = ?");
mysqli_stmt_bind_param($stmt, "ss", $email, $role);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
mysqli_close($conn);

// 6. Compare the typed password with the stored hash.
//    One generic message for "no such user" and "wrong password",
//    so nobody can find out which emails are registered.
if (!$user || !password_verify($password, $user["password"])) {
    showMessage("Login failed", "Incorrect email, password or role.", "Try again", "Login.html");
}

// 7. Success: start a fresh session and store the user's details
session_regenerate_id(true);   // new session id (prevents session fixation)
$_SESSION["user_id"]    = $user["id"];
$_SESSION["full_name"]  = $user["full_name"];
$_SESSION["email"]      = $user["email"];
$_SESSION["role"]       = $user["role"];
$_SESSION["student_id"] = $user["user_id"];
$_SESSION["department"] = $user["department"];

// 8. Send the user to the right page for their role
if ($user["role"] === "admin") {
    header("Location: Admin-Dashboard.html");
} else {
    header("Location: Profile.html");
}
exit;
?>