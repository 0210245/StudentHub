<?php
/**
 * Practical 12: Event Management CRUD & Secure Poster Upload Handler
 * Features:
 *   1. Mime-type & file size validation (max 2MB, jpg/png/webp only).
 *   2. Safe renaming (evt_uniqid.ext) and upload directory protection.
 *   3. Audit log tracking for all admin operations.
 */
session_start();
require_once __DIR__ . '/../Practical 8/config.php';

$conn = getMySQLiConnection();
if (!$conn) die("Database connection failed.");

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$upload_dir = __DIR__ . '/uploads';

// Helper to record audit log
function record_event_audit($conn, $act, $details) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $uid = $_SESSION['user_id'] ?? null;
    $stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isss", $uid, $act, $details, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/**
 * Handle and validate poster image upload
 */
function handle_poster_upload($file_input, &$error) {
    global $upload_dir;

    if (!isset($_FILES[$file_input]) || $_FILES[$file_input]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded
    }

    $file = $_FILES[$file_input];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload error code: " . $file['error'];
        return false;
    }

    // 1. File size check (Max 2MB)
    $max_size = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $max_size) {
        $error = "Poster file size exceeds the 2MB limit.";
        return false;
    }

    // 2. MIME type check using finfo
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mimes)) {
        $error = "Invalid file type ({$mime}). Only JPG, PNG, and WebP images are allowed.";
        return false;
    }

    // 3. Unique renaming (Advanced Extension)
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe_ext = strtolower($ext) === 'jpeg' ? 'jpg' : strtolower($ext);
    $new_filename = 'evt_' . uniqid() . '.' . $safe_ext;
    $destination = $upload_dir . '/' . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $new_filename;
    } else {
        $error = "Failed to save the uploaded poster file.";
        return false;
    }
}

// 1. ADD EVENT
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id    = trim($_POST['event_id'] ?? '');
    $event_name  = trim($_POST['event_name'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $event_venue = trim($_POST['event_venue'] ?? 'Campus Audi');
    $event_type  = trim($_POST['event_type'] ?? 'Technical');
    $coordinator = trim($_POST['coordinator'] ?? 'Faculty Coordinator');
    $description = trim($_POST['description'] ?? '');

    $upload_err = '';
    $poster = handle_poster_upload('poster', $upload_err);

    if ($poster === false) {
        $_SESSION['msg_error'] = $upload_err;
        header('Location: index.php');
        exit;
    }

    $stmt = mysqli_prepare($conn, 
        "INSERT INTO events (event_id, event_name, event_date, event_venue, event_type, coordinator, description, poster, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')"
    );
    mysqli_stmt_bind_param($stmt, "ssssssss", $event_id, $event_name, $event_date, $event_venue, $event_type, $coordinator, $description, $poster);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['msg_success'] = "Event '{$event_name}' added successfully!";
        record_event_audit($conn, 'EVENT_CREATE', "Created event {$event_id} ({$event_name})");
    } else {
        $_SESSION['msg_error'] = "Database error: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
    header('Location: index.php');
    exit;
}

// 2. EDIT EVENT
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id    = trim($_POST['event_id'] ?? '');
    $event_name  = trim($_POST['event_name'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $event_venue = trim($_POST['event_venue'] ?? '');
    $event_type  = trim($_POST['event_type'] ?? '');
    $coordinator = trim($_POST['coordinator'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $upload_err = '';
    $poster = handle_poster_upload('poster', $upload_err);

    if ($poster === false) {
        $_SESSION['msg_error'] = $upload_err;
        header('Location: index.php');
        exit;
    }

    if ($poster !== null) {
        // Update with new poster
        $stmt = mysqli_prepare($conn, 
            "UPDATE events SET event_name = ?, event_date = ?, event_venue = ?, event_type = ?, coordinator = ?, description = ?, poster = ? WHERE event_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ssssssss", $event_name, $event_date, $event_venue, $event_type, $coordinator, $description, $poster, $event_id);
    } else {
        // Keep existing poster
        $stmt = mysqli_prepare($conn, 
            "UPDATE events SET event_name = ?, event_date = ?, event_venue = ?, event_type = ?, coordinator = ?, description = ? WHERE event_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "sssssss", $event_name, $event_date, $event_venue, $event_type, $coordinator, $description, $event_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['msg_success'] = "Event '{$event_id}' updated successfully!";
        record_event_audit($conn, 'EVENT_UPDATE', "Updated event details for {$event_id}");
    } else {
        $_SESSION['msg_error'] = "Update failed: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
    header('Location: index.php');
    exit;
}

// 3. TOGGLE EVENT STATUS (Open / Closed)
if ($action === 'toggle_status') {
    $id = trim($_GET['id'] ?? '');
    $new_status = ($_GET['status'] === 'open') ? 'open' : 'closed';

    $stmt = mysqli_prepare($conn, "UPDATE events SET status = ? WHERE event_id = ?");
    mysqli_stmt_bind_param($stmt, "ss", $new_status, $id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['msg_success'] = "Event {$id} status set to " . strtoupper($new_status);
        record_event_audit($conn, 'EVENT_STATUS_TOGGLE', "Changed status of {$id} to {$new_status}");
    }
    mysqli_stmt_close($stmt);
    header('Location: index.php');
    exit;
}

// 4. DELETE EVENT
if ($action === 'delete') {
    $id = trim($_GET['id'] ?? '');

    // Retrieve poster filename to clean up file from storage
    $q = mysqli_prepare($conn, "SELECT poster FROM events WHERE event_id = ?");
    mysqli_stmt_bind_param($q, "s", $id);
    mysqli_stmt_execute($q);
    $r = mysqli_stmt_get_result($q);
    if ($ev = mysqli_fetch_assoc($r)) {
        if (!empty($ev['poster']) && file_exists($upload_dir . '/' . $ev['poster'])) {
            unlink($upload_dir . '/' . $ev['poster']);
        }
    }
    mysqli_stmt_close($q);

    $stmt = mysqli_prepare($conn, "DELETE FROM events WHERE event_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['msg_success'] = "Event {$id} deleted successfully!";
        record_event_audit($conn, 'EVENT_DELETE', "Deleted event {$id}");
    }
    mysqli_stmt_close($stmt);
    header('Location: index.php');
    exit;
}

mysqli_close($conn);
header('Location: index.php');
exit;
