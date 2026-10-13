<?php
/* =========================================================
   Event.php
   Saves an event registration (sent by events.html) into the
   "registrations" table of the "event" database.
   Replies in JSON so the page can show a message without reloading.
   Put this file in the StudentHub/ folder next to events.html.
   ========================================================= */

mysqli_report(MYSQLI_REPORT_OFF);          // return false on errors instead of throwing exceptions
header("Content-Type: application/json");

/* Sends the JSON answer and stops the script */
function reply($ok, $message, $already = false) {
    echo json_encode(["ok" => $ok, "message" => $message, "already" => $already]);
    exit;
}

// 1. Only accept a form submission (POST)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: events.html");
    exit;
}

// 2. Read the values
$name        = trim($_POST["Student_Name"] ?? "");
$email       = trim($_POST["Student_Email"] ?? "");
$eventId     = trim($_POST["Event_ID"] ?? "");
$eventName   = trim($_POST["Event_Name"] ?? "");
$eventDate   = trim($_POST["Event_Date"] ?? "");
$eventVenue  = trim($_POST["Event_Venue"] ?? "");
$eventType   = trim($_POST["Event_Type"] ?? "");
$coordinator = trim($_POST["Event_coordinator"] ?? "");

// 3. Validation (never trust only the browser)
if ($name === "" || $email === "" || $eventId === "" || $eventName === "") {
    reply(false, "Please fill in all the fields.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    reply(false, "Please enter a valid email address.");
}
if (!strtotime($eventDate)) {
    reply(false, "Invalid event date.");
}

// 4. Connect to the "event" database
$conn = @mysqli_connect("localhost", "root", "", "event");
if (!$conn) {
    error_log(mysqli_connect_error());
    reply(false, "Could not connect to the database. Is MySQL running?");
}

// 5. Save the registration (prepared statement = safe from SQL injection)
$stmt = mysqli_prepare($conn,
    "INSERT INTO registrations
     (student_name, student_email, event_id, event_name, event_date, event_venue, event_type, coordinator)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    error_log(mysqli_error($conn));
    reply(false, "Database table not found. Run database.sql in phpMyAdmin first.");
}
mysqli_stmt_bind_param($stmt, "ssssssss",
    $name, $email, $eventId, $eventName, $eventDate, $eventVenue, $eventType, $coordinator);

if (mysqli_stmt_execute($stmt)) {
    reply(true, "You are registered for " . $eventName . "!");
}
// Error 1062 = duplicate (same email + same event, see UNIQUE key in database.sql)
if (mysqli_stmt_errno($stmt) === 1062) {
    reply(false, "You are already registered for this event.", true);
}
error_log(mysqli_stmt_error($stmt));
reply(false, "Registration failed. Please try again.");
?>