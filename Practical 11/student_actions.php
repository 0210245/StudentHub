<?php
/**
 * Practical 11: Student CRUD Action Handler (MySQLi Prepared Statements)
 */
session_start();
require_once __DIR__ . '/../Practical 8/config.php';

$conn = getMySQLiConnection();
if (!$conn) {
    die("Database connection failed.");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper to record audit log
function record_audit($conn, $act, $details) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $uid = $_SESSION['user_id'] ?? null;
    $stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isss", $uid, $act, $details, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// 1. ADD STUDENT
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment = trim($_POST['enrollment_no'] ?? '');
    $name       = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $course     = trim($_POST['course'] ?? '');
    $dept       = trim($_POST['department'] ?? '');
    $year       = intval($_POST['year'] ?? 1);

    if (empty($enrollment) || empty($name) || empty($email)) {
        $_SESSION['msg_error'] = 'Enrollment No, Name, and Email are required.';
        header('Location: index.php');
        exit;
    }

    $stmt = mysqli_prepare($conn, 
        "INSERT INTO students (enrollment_no, full_name, email, phone, course, department, year, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
    );
    mysqli_stmt_bind_param($stmt, "ssssssi", $enrollment, $name, $email, $phone, $course, $dept, $year);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['msg_success'] = "Student {$name} ({$enrollment}) added successfully!";
        record_audit($conn, 'STUDENT_ADD', "Added student {$enrollment} ({$name})");
    } else {
        $_SESSION['msg_error'] = "Failed to add student: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
    header('Location: index.php');
    exit;
}

// 2. EDIT STUDENT
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = intval($_POST['student_id'] ?? 0);
    $enrollment = trim($_POST['enrollment_no'] ?? '');
    $name       = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $course     = trim($_POST['course'] ?? '');
    $dept       = trim($_POST['department'] ?? '');
    $year       = intval($_POST['year'] ?? 1);

    $stmt = mysqli_prepare($conn, 
        "UPDATE students SET enrollment_no = ?, full_name = ?, email = ?, phone = ?, course = ?, department = ?, year = ? WHERE student_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ssssssii", $enrollment, $name, $email, $phone, $course, $dept, $year, $id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['msg_success'] = "Student records updated successfully!";
        record_audit($conn, 'STUDENT_EDIT', "Updated student ID {$id} ({$enrollment})");
    } else {
        $_SESSION['msg_error'] = "Update failed: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
    header('Location: index.php');
    exit;
}

// 3. SOFT DELETE / TOGGLE STATUS (Advanced Extension)
if ($action === 'toggle_status') {
    $id = intval($_GET['id'] ?? 0);
    $new_status = ($_GET['status'] === 'active') ? 'active' : 'inactive';

    $stmt = mysqli_prepare($conn, "UPDATE students SET status = ? WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['msg_success'] = "Student status updated to " . strtoupper($new_status);
        record_audit($conn, 'STUDENT_STATUS_TOGGLE', "Toggled student ID {$id} status to {$new_status}");
    }
    mysqli_stmt_close($stmt);
    header('Location: index.php');
    exit;
}

// 4. PERMANENT DELETE (with confirmation)
if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = mysqli_prepare($conn, "DELETE FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['msg_success'] = "Student permanently removed.";
        record_audit($conn, 'STUDENT_DELETE', "Permanently deleted student ID {$id}");
    }
    mysqli_stmt_close($stmt);
    header('Location: index.php');
    exit;
}

mysqli_close($conn);
header('Location: index.php');
exit;
