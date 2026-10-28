<?php
/**
 * Admin-Dashboard.php - Practical 14: Dynamic Admin Dashboard with RBAC & Audit Log
 * Keeps the EXACT UI layout of Admin-Dashboard.html with live database data.
 */
session_start();
require_once __DIR__ . '/db.php';

$conn = getDBConnection();
$totalStudents = 0;
$totalEvents = 0;
$totalUsers = 0;
$recentActivities = [];

if ($conn) {
    if ($r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM students")) {
        $totalStudents = mysqli_fetch_assoc($r)['c'];
    }
    if ($r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM events")) {
        $totalEvents = mysqli_fetch_assoc($r)['c'];
    }
    if ($r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users")) {
        $totalUsers = mysqli_fetch_assoc($r)['c'];
    }
    if ($r = mysqli_query($conn, "SELECT action, details, created_at FROM audit_logs ORDER BY id DESC LIMIT 5")) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recentActivities[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudentHub | Admin</title>
<link rel="stylesheet" href="../StudentHub-css.css">
<link rel="stylesheet" href="../interactive-features.css"> 
<style>
  .admin-shortcuts { display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap; }
  .admin-shortcuts a { text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; }
  .btn-students { background: #4f46e5; color: #fff; }
  .btn-events { background: #0891b2; color: #fff; }
  .btn-logout { background: #ef4444; color: #fff; margin-left: auto; }
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
        <h2>Admin Dashboard</h2>

        <!-- Module Shortcuts -->
        <div class="admin-shortcuts">
          <a href="manage-students.php" class="admin-shortcuts a btn-students">Manage Students (CRUD)</a>
          <a href="manage-events.php" class="admin-shortcuts a btn-events">Manage Events &amp; Posters</a>
          <a href="logout.php" class="admin-shortcuts a btn-logout">Logout</a>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Students</h3>
                <h2><?php echo $totalStudents; ?></h2>
            </div>

            <div class="card">
                <h3>Events</h3>
                <h2><?php echo $totalEvents; ?></h2>
            </div>

            <div class="card">
                <h3>Faculty / Users</h3>
                <h2><?php echo $totalUsers; ?></h2>
            </div>
        </div>

        <div class="card">
            <h3>Recent Activities (Audit Trail)</h3>
            <ul>
                <?php if (empty($recentActivities)): ?>
                    <li>New Student Registered</li>
                    <li>Faculty Added</li>
                    <li>Workshop Updated</li>
                <?php else: ?>
                    <?php foreach ($recentActivities as $act): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($act['action']); ?>:</strong> 
                            <?php echo htmlspecialchars($act['details']); ?> 
                            <small style="color:#64748b;">(<?php echo date('M d, H:i', strtotime($act['created_at'])); ?>)</small>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </main>
    <footer><p>&copy; 2026 StudentHub</p></footer>
    <script src="StudentHub-js.js" defer></script>
</body>
</html>
