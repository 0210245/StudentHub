<?php
    $dbLocalhost = mysqli_connect("localhost","root","")
    or die("Could not connect:".mysqli_connect_error());
    mysqli_select_db($dbLocalhost,"event")
    or die("could not find database:".mysqli_connect_error());
    echo"<h1>Connected to database</h1>";
?>
<?php
$conn = mysqli_connect("localhost", "root", "", "event");
if (!$conn) {
    error_log(mysqli_connect_error());
    die("Sorry, something went wrong. Please try again later.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Register.html");
    exit;
}

$stmt = mysqli_prepare($conn,
    "INSERT INTO registrations
     (student_name, student_email, event_id, event_name, event_date, event_venue, event_type, coordinator)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

mysqli_stmt_bind_param($stmt, "ssssssss",
    $_POST["Student_Name"], $_POST["Student_Email"], $_POST["Event_ID"],
    $_POST["Event_Name"], $_POST["Event_Date"], $_POST["Event_Venue"],
    $_POST["Event_Type"], $_POST["Event_coordinator"]);

if (mysqli_stmt_execute($stmt)) {
    echo "<h1>Registration successful!</h1><a href='events.html'>Back to events</a>";
} else {
    error_log(mysqli_stmt_error($stmt));
    echo "<h1>Registration failed. Please try again.</h1>";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>