<?php
/**
 * Practical 11: Intermediate Extension - Export Student List to CSV
 */
require_once __DIR__ . '/../Practical 8/config.php';

$conn = getMySQLiConnection();
if (!$conn) die("Database connection error");

$search = trim($_GET['search'] ?? '');
$dept   = trim($_GET['department'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT enrollment_no, full_name, email, phone, course, department, year, status, created_at FROM students WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}

if (!empty($dept)) {
    $sql .= " AND department = ?";
    $params[] = $dept;
    $types .= "s";
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY enrollment_no ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Output HTTP headers to force CSV file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=StudentHub_Students_' . date('Ymd_His') . '.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Enrollment No', 'Full Name', 'Email Address', 'Phone', 'Course', 'Department', 'Year', 'Status', 'Registered Date']);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($out, [
        $row['enrollment_no'],
        $row['full_name'],
        $row['email'],
        $row['phone'],
        $row['course'],
        $row['department'],
        $row['year'],
        strtoupper($row['status']),
        $row['created_at']
    ]);
}

fclose($out);
mysqli_stmt_close($stmt);
mysqli_close($conn);
exit;
