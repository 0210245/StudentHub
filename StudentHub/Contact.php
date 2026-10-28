<?php
/* =========================================================
   contact.php
   Receives the Contact form (Contact.html), checks it on the
   SERVER, cleans it, and saves it as a new row in a CSV file.
   Put this file in the StudentHub/ folder next to Contact.html.
   ========================================================= */

// Where the messages are saved: StudentHub/storage/contacts.csv
$folder = __DIR__ . "/storage";
$file   = $folder . "/contacts.csv";

/* Shows a styled result page and stops the script */
function showPage($title, $bodyHtml) {
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>
          <meta name='viewport' content='width=device-width, initial-scale=1.0'>
          <title>StudentHub | " . htmlspecialchars($title) . "</title>
          <link rel='stylesheet' href='../StudentHub-css.css'>
          <link rel='stylesheet' href='../interactive-features.css'></head><body>
          <main class='container'><div class='card' style='text-align:center'>
          <h2>" . htmlspecialchars($title) . "</h2>" . $bodyHtml . "
          </div></main></body></html>";
    exit;
}

/* Sanitize: remove HTML tags and extra spaces from one value */
function clean($value) {
    if (!is_string($value)) return "";          // ignore tampered input such as arrays
    return trim(strip_tags($value));
}

/* A cell that starts with = + - @ could run as a formula when the CSV is opened in Excel.
   A leading ' makes it plain text. */
function safeCell($text) {
    if ($text !== "" && strpos("=+-@", $text[0]) !== false) {
        return "'" . $text;
    }
    return $text;
}

// 1. The form must be sent with POST. Anything else goes back to the form.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Contact.html");
    exit;
}

// 2. Read and SANITIZE the input
$name    = clean($_POST["name"] ?? "");
$email   = filter_var(clean($_POST["email"] ?? ""), FILTER_SANITIZE_EMAIL);
$message = clean($_POST["message"] ?? "");

// 3. VALIDATE the input. Every problem is added to the $errors list.
$errors = [];

if ($name === "") {
    $errors[] = "Name is required.";
} elseif (!preg_match("/^[A-Za-z][A-Za-z .'-]{2,49}$/", $name)) {
    $errors[] = "Name must be 3 to 50 letters (spaces, dot, apostrophe and hyphen are allowed).";
}

if ($email === "") {
    $errors[] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if ($message === "") {
    $errors[] = "Message is required.";
} elseif (strlen($message) < 10) {
    $errors[] = "Message must be at least 10 characters.";
} elseif (strlen($message) > 500) {
    $errors[] = "Message must be at most 500 characters.";
}

// 4. If there are errors: show them and do NOT save anything
if (count($errors) > 0) {
    $list = "<ul style='text-align:left; color:#b91c1c; margin:15px 0 20px 25px;'>";
    foreach ($errors as $error) {
        $list .= "<li>" . htmlspecialchars($error) . "</li>";
    }
    $list .= "</ul><a class='btn' href='Contact.html'>Go back and fix</a>";
    showPage("Please fix these errors", $list);
}

// 5. Prepare the values for the file
$message = preg_replace("/\s+/", " ", $message);   // keep every message on ONE line of the file
$row = [date("Y-m-d H:i:s"), safeCell($name), safeCell($email), safeCell($message)];

// 6. Save to the CSV file safely
if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
    showPage("Could not save", "<p>The storage folder could not be created.</p><a class='btn' href='Contact.html'>Back</a>");
}
// Block direct web access to the saved messages (Apache 2.4)
if (!file_exists($folder . "/.htaccess")) {
    file_put_contents($folder . "/.htaccess", "Require all denied\n");
}

$isNew = !file_exists($file) || filesize($file) === 0;   // write the column names only once
$handle = fopen($file, "a");                              // "a" = append, old rows are never overwritten
if (!$handle) {
    error_log("Cannot open " . $file);
    showPage("Could not save", "<p>The file could not be opened.</p><a class='btn' href='Contact.html'>Back</a>");
}

if (flock($handle, LOCK_EX)) {                            // lock: two visitors cannot write at the same moment
    if ($isNew) {
        fputcsv($handle, ["date_time", "name", "email", "message"], ",", "\"", "\\");
    }
    fputcsv($handle, $row, ",", "\"", "\\");              // writes one properly quoted CSV row
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
} else {
    fclose($handle);
    showPage("Could not save", "<p>The file is busy. Please try again.</p><a class='btn' href='Contact.html'>Back</a>");
}

// 7. Success message
showPage("Message sent!",
    "<p>Thank you, " . htmlspecialchars($name) . ". Your message has been saved.</p><br>" .
    "<a class='btn' href='Contact.html'>Send another</a> " .
    "<a class='btn' href='contact-records.php' style='margin-left:8px'>View saved messages</a>");
?>