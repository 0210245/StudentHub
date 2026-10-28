<?php
/**
 * Practical 7: Form Processor with Server-Side Validation, CSRF Check & Safe CSV/JSON Storage
 */
session_start();

// 1. Enforce POST submission only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 2. CSRF Token Validation (Advanced Extension)
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['errors'] = ['Security check failed: Invalid or expired CSRF token. Please resubmit the form.'];
    header('Location: index.php');
    exit;
}

// 3. Sanitization helper functions
function sanitize_text($val) {
    if (!is_string($val)) return '';
    return trim(strip_tags($val));
}

// Prevent CSV formula injection by prepending single quote if first character is =, +, -, @
function escape_csv_cell($val) {
    if (!empty($val) && in_array($val[0], ['=', '+', '-', '@'])) {
        return "'" . $val;
    }
    return $val;
}

// 4. Capture & Sanitize inputs
$full_name    = sanitize_text($_POST['full_name'] ?? '');
$email        = filter_var(sanitize_text($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone        = sanitize_text($_POST['phone'] ?? '');
$course       = sanitize_text($_POST['course'] ?? '');
$year         = sanitize_text($_POST['year'] ?? '');
$gender       = sanitize_text($_POST['gender'] ?? 'Other');
$storage_mode = sanitize_text($_POST['storage_mode'] ?? 'both');
$message      = sanitize_text($_POST['message'] ?? '');

// 5. Server-Side Validation
$errors = [];

// Name validation
if (empty($full_name)) {
    $errors[] = 'Full name is required.';
} elseif (!preg_match("/^[A-Za-z\s.]{3,50}$/", $full_name)) {
    $errors[] = 'Full name must be between 3 and 50 alphabetic characters.';
}

// Email validation
if (empty($email)) {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

// Phone validation (10-digit Indian standard)
if (empty($phone)) {
    $errors[] = 'Phone number is required.';
} elseif (!preg_match("/^[6-9]\d{9}$/", $phone)) {
    $errors[] = 'Phone number must be a valid 10-digit mobile number starting with 6-9.';
}

// Course validation
if (empty($course)) {
    $errors[] = 'Please select a valid course.';
}

// Year validation
if (!in_array($year, ['1', '2', '3', '4'])) {
    $errors[] = 'Year must be between 1 and 4.';
}

// Message validation (if provided)
if (!empty($message) && strlen($message) < 10) {
    $errors[] = 'Remarks/Message must be at least 10 characters long.';
}

// If validation errors exist, redirect back with error list
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: index.php');
    exit;
}

// 6. Safe File Storage (CSV & JSON)
$storage_dir = __DIR__ . '/storage';
if (!is_dir($storage_dir)) {
    mkdir($storage_dir, 0755, true);
}

// Protect storage directory with .htaccess against direct web browsing
$htaccess_path = $storage_dir . '/.htaccess';
if (!file_exists($htaccess_path)) {
    file_put_contents($htaccess_path, "Require all denied\n");
}

$timestamp = date('Y-m-d H:i:s');
$record_id = 'REG' . time() . rand(100, 999);

// Data structure
$record = [
    'id'        => $record_id,
    'timestamp' => $timestamp,
    'full_name' => $full_name,
    'email'     => $email,
    'phone'     => $phone,
    'course'    => $course,
    'year'      => $year,
    'gender'    => $gender,
    'message'   => str_replace(["\r", "\n"], ' ', $message)
];

// Write to CSV (Safe handling with flock / LOCK_EX)
if ($storage_mode === 'both' || $storage_mode === 'csv') {
    $csv_file = $storage_dir . '/registrations.csv';
    $is_new_csv = !file_exists($csv_file) || filesize($csv_file) === 0;

    $handle = fopen($csv_file, 'a');
    if ($handle) {
        if (flock($handle, LOCK_EX)) {
            if ($is_new_csv) {
                // Write header row
                fputcsv($handle, ['ID', 'Timestamp', 'Full Name', 'Email', 'Phone', 'Course', 'Year', 'Gender', 'Message']);
            }
            fputcsv($handle, [
                $record['id'],
                $record['timestamp'],
                escape_csv_cell($record['full_name']),
                escape_csv_cell($record['email']),
                escape_csv_cell($record['phone']),
                escape_csv_cell($record['course']),
                $record['year'],
                $record['gender'],
                escape_csv_cell($record['message'])
            ]);
            flock($handle, LOCK_UN);
        }
        fclose($handle);
    }
}

// Write to JSON
if ($storage_mode === 'both' || $storage_mode === 'json') {
    $json_file = $storage_dir . '/registrations.json';
    $existing_json_data = [];

    if (file_exists($json_file) && filesize($json_file) > 0) {
        $content = file_get_contents($json_file);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $existing_json_data = $decoded;
        }
    }

    $existing_json_data[] = $record;
    file_put_contents($json_file, json_encode($existing_json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

// Rotate CSRF token after successful submission
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: index.php?status=success');
exit;
