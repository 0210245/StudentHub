<?php
/**
 * Contact.php - Practical 7: Form Processing with CSV, JSON file storage & MySQL
 */
ob_start();
require_once __DIR__ . '/db.php';
$folder = __DIR__ . "/storage";
$csvFile = $folder . "/contacts.csv";
$jsonFile = $folder . "/contacts.json";

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

function clean($val) {
    if (!is_string($val)) return "";
    return trim(strip_tags($val));
}

function safeCell($text) {
    if ($text !== "" && in_array($text[0], ['=', '+', '-', '@'])) {
        return "'" . $text;
    }
    return $text;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Contact.html");
    exit;
}

$name    = clean($_POST["name"] ?? "");
$email   = filter_var(clean($_POST["email"] ?? ""), FILTER_SANITIZE_EMAIL);
$message = clean($_POST["message"] ?? "");

$errors = [];
if ($name === "" || strlen($name) < 3) $errors[] = "Name must be at least 3 letters.";
if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
if ($message === "" || strlen($message) < 10) $errors[] = "Message must be at least 10 characters.";

if (!empty($errors)) {
    $list = "<ul style='text-align:left; color:#b91c1c; margin:15px 0 20px 25px;'>";
    foreach ($errors as $e) $list .= "<li>" . htmlspecialchars($e) . "</li>";
    $list .= "</ul><a class='btn' href='Contact.html'>Go back and fix</a>";
    showPage("Please fix these errors", $list);
}

if (!is_dir($folder)) mkdir($folder, 0755, true);
if (!file_exists($folder . "/.htaccess")) file_put_contents($folder . "/.htaccess", "Require all denied\n");

$timestamp = date("Y-m-d H:i:s");
$cleanMessage = preg_replace("/\s+/", " ", $message);

// 1. Append to CSV safely
$isNew = !file_exists($csvFile) || filesize($csvFile) === 0;
$h = fopen($csvFile, "a");
if ($h) {
    flock($h, LOCK_EX);
    if ($isNew) fputcsv($h, ["Date", "Name", "Email", "Message"]);
    fputcsv($h, [$timestamp, safeCell($name), safeCell($email), safeCell($cleanMessage)]);
    flock($h, LOCK_UN);
    fclose($h);
}

// 2. Append to JSON storage
$jsonRecords = [];
if (file_exists($jsonFile) && filesize($jsonFile) > 0) {
    $decoded = json_decode(file_get_contents($jsonFile), true);
    if (is_array($decoded)) $jsonRecords = $decoded;
}
$jsonRecords[] = [
    "timestamp" => $timestamp,
    "name" => $name,
    "email" => $email,
    "message" => $cleanMessage
];
file_put_contents($jsonFile, json_encode($jsonRecords, JSON_PRETTY_PRINT), LOCK_EX);

showPage("Thank you!", "<p>Your message has been safely saved in server storage (CSV & JSON).</p><br><a class='btn' href='contact-records.php'>View Saved Messages</a> <a class='btn' href='Contact.html' style='margin-left:8px;'>Back</a>");
?>
