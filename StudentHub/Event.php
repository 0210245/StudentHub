<?php
/**
 * Event.php - Practical 13: Asynchronous Event Registration API
 * Saves registration to "studenthub.registrations" table and returns JSON response
 */
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/db.php';

function reply($ok, $message, $already = false) {
    echo json_encode(["ok" => $ok, "message" => $message, "already" => $already]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: events.html");
    exit;
}

$name        = trim($_POST["Student_Name"] ?? "");
$email       = trim($_POST["Student_Email"] ?? "");
$eventId     = trim($_POST["Event_ID"] ?? "");
$eventName   = trim($_POST["Event_Name"] ?? "");
$eventDate   = trim($_POST["Event_Date"] ?? "");
$eventVenue  = trim($_POST["Event_Venue"] ?? "");
$eventType   = trim($_POST["Event_Type"] ?? "");
$coordinator = trim($_POST["Event_coordinator"] ?? "");

if (empty($name) || empty($email) || empty($eventId)) {
    reply(false, "Please provide your name and institutional email.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    reply(false, "Please enter a valid email address.");
}

$conn = getDBConnection();
if (!$conn) {
    reply(false, "Database connection failed. Please ensure MySQL is running on WAMP.");
}

// 1. Check if already registered
$check = mysqli_prepare($conn, "SELECT id FROM registrations WHERE student_email = ? AND event_id = ? LIMIT 1");
mysqli_stmt_bind_param($check, "ss", $email, $eventId);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) {
    mysqli_stmt_close($check);
    mysqli_close($conn);
    reply(false, "You are already registered for this event.", true);
}
mysqli_stmt_close($check);

// 2. Insert registration
$stmt = mysqli_prepare($conn, 
    "INSERT INTO registrations (event_id, student_name, student_email) VALUES (?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, "sss", $eventId, $name, $email);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    recordAuditLog($conn, null, 'EVENT_REGISTRATION', "{$name} registered for {$eventId}");
    mysqli_close($conn);
    reply(true, "Registration confirmed for " . htmlspecialchars($eventName) . "!");
} else {
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    reply(false, "Registration could not be saved: " . $err);
}
?>
