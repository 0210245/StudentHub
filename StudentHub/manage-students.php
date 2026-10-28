<?php
/**
 * manage-students.php - Practical 11: Student Management CRUD with Search & Filter
 * Built with the exact same StudentHub styles, header, and cards.
 */
session_start();
require_once __DIR__ . '/db.php';

$conn = getDBConnection();
if (!$conn) die("Database connection error");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSV Export (Practical 11 Intermediate extension)
if ($action === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=StudentHub_Students_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Enrollment No', 'Full Name', 'Email', 'Phone', 'Course', 'Department', 'Year', 'Status']);
    $q = mysqli_query($conn, "SELECT enrollment_no, full_name, email, phone, course, department, year, status FROM students ORDER BY enrollment_no ASC");
    while ($r = mysqli_fetch_assoc($q)) {
        fputcsv($out, [$r['enrollment_no'], $r['full_name'], $r['email'], $r['phone'], $r['course'], $r['department'], $r['year'], strtoupper($r['status'])]);
    }
    fclose($out);
    exit;
}

// 1. ADD STUDENT
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $enroll = trim($_POST['enrollment_no'] ?? '');
    $name   = trim($_POST['full_name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $dept   = trim($_POST['department'] ?? '');
    $year   = intval($_POST['year'] ?? 1);

    $stmt = mysqli_prepare($conn, "INSERT INTO students (enrollment_no, full_name, email, phone, course, department, year, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    mysqli_stmt_bind_param($stmt, "ssssssi", $enroll, $name, $email, $phone, $course, $dept, $year);
    if (mysqli_stmt_execute($stmt)) {
        recordAuditLog($conn, $_SESSION['user_id'] ?? null, 'STUDENT_ADD', "Added student {$enroll} ({$name})");
    }
    mysqli_stmt_close($stmt);
    header('Location: manage-students.php');
    exit;
}

// 2. SOFT DELETE / TOGGLE STATUS (Practical 11 Advanced extension)
if ($action === 'toggle_status') {
    $id = intval($_GET['id'] ?? 0);
    $status = ($_GET['status'] === 'active') ? 'active' : 'inactive';
    $stmt = mysqli_prepare($conn, "UPDATE students SET status = ? WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    recordAuditLog($conn, $_SESSION['user_id'] ?? null, 'STUDENT_STATUS_TOGGLE', "Toggled student ID {$id} to {$status}");
    header('Location: manage-students.php');
    exit;
}

// 3. DELETE STUDENT
if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = mysqli_prepare($conn, "DELETE FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    recordAuditLog($conn, $_SESSION['user_id'] ?? null, 'STUDENT_DELETE', "Deleted student ID {$id}");
    header('Location: manage-students.php');
    exit;
}

// Fetch with search & filter
$search = trim($_GET['search'] ?? '');
$dept   = trim($_GET['department'] ?? '');

$sql = "SELECT * FROM students WHERE 1=1";
$params = [];
$types = "";
if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
    $s = "%{$search}%";
    $params = [$s, $s, $s];
    $types = "sss";
}
if (!empty($dept)) {
    $sql .= " AND department = ?";
    $params[] = $dept;
    $types .= "s";
}
$sql .= " ORDER BY student_id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$students = [];
while ($row = mysqli_fetch_assoc($res)) $students[] = $row;
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudentHub | Student Management</title>
<link rel="stylesheet" href="../StudentHub-css.css">
<link rel="stylesheet" href="../interactive-features.css">
<style>
  table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
  th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
  th { background: #f8fafc; color: #4f46e5; }
  .badge-active { color: #16a34a; font-weight: bold; }
  .badge-inactive { color: #dc2626; font-weight: bold; }
  .toolbar-flex { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
  .toolbar-flex input, .toolbar-flex select { max-width: 250px; }
</style>
</head>
<body>
<header>
    <h1>StudentHub</h1>
    <nav aria-label="Main Navigation">
    <a href="../index.html">Home</a>
    <a href="AboutUs.html">About</a>
    <a href="Login.html">Login</a>
    <a href="events.html">Events</a>
    <a href="Profile.html">Profile</a>
    <a href="Contact.html">Contact</a>
    <a href="Register.html">Register</a>
    <a href="Faq.html">FAQ</a>
    <a href="Feedback.html">Feedback</a>
    <a href="Admin-Dashboard.php">Admin</a>
</nav>
</header>

<main class="container">
    <h2>Student Management Module (Practical 11)</h2>

    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">
        <h3>Enrolled Students Directory (<?php echo count($students); ?> Records)</h3>
        <div>
          <a href="manage-students.php?action=export_csv" class="btn" style="background:#059669; padding:8px 14px; text-decoration:none;">📥 Export CSV</a>
          <a href="Admin-Dashboard.php" class="btn" style="background:#64748b; padding:8px 14px; text-decoration:none; margin-left:6px;">&larr; Admin Panel</a>
        </div>
      </div>

      <!-- Search & Filter Toolbar -->
      <form method="GET" action="manage-students.php" class="toolbar-flex">
        <input type="search" name="search" placeholder="Search by name or ID..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="department">
          <option value="">All Departments</option>
          <option value="Computer Engineering" <?php if ($dept === 'Computer Engineering') echo 'selected'; ?>>Computer Engineering</option>
          <option value="Information Technology" <?php if ($dept === 'Information Technology') echo 'selected'; ?>>Information Technology</option>
          <option value="Electronics & Communication" <?php if ($dept === 'Electronics & Communication') echo 'selected'; ?>>Electronics &amp; Communication</option>
        </select>
        <button type="submit" class="btn" style="padding:10px 18px;">Filter</button>
      </form>

      <!-- Students Table -->
      <div style="overflow-x: auto;">
        <table>
          <thead>
            <tr>
              <th>Enrollment</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Course</th>
              <th>Department</th>
              <th>Year</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr><td colspan="9" style="text-align:center; padding:20px;">No students found.</td></tr>
            <?php else: ?>
              <?php foreach ($students as $s): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($s['enrollment_no']); ?></strong></td>
                  <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                  <td><?php echo htmlspecialchars($s['email']); ?></td>
                  <td><?php echo htmlspecialchars($s['phone'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($s['course']); ?></td>
                  <td><?php echo htmlspecialchars($s['department']); ?></td>
                  <td>Year <?php echo htmlspecialchars($s['year']); ?></td>
                  <td>
                    <span class="<?php echo $s['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                      ● <?php echo strtoupper($s['status']); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($s['status'] === 'active'): ?>
                      <a href="manage-students.php?action=toggle_status&id=<?php echo $s['student_id']; ?>&status=inactive" style="color:#d97706; text-decoration:none; margin-right:8px;" title="Soft Delete">Deactivate</a>
                    <?php else: ?>
                      <a href="manage-students.php?action=toggle_status&id=<?php echo $s['student_id']; ?>&status=active" style="color:#16a34a; text-decoration:none; margin-right:8px;" title="Restore">Activate</a>
                    <?php endif; ?>
                    <a href="manage-students.php?action=delete&id=<?php echo $s['student_id']; ?>" style="color:#dc2626; text-decoration:none;" onclick="return confirm('Delete permanently?');">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
</main>

<footer><p>&copy; 2026 StudentHub</p></footer>
</body>
</html>
