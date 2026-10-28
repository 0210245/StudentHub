<?php
session_start();
require_once __DIR__ . '/../Practical 8/config.php';

// Check if user is logged in as admin
if (empty($_SESSION['role'])) {
    // Default demo session if accessed directly
    $_SESSION['user_id'] = 1;
    $_SESSION['full_name'] = 'Admin User';
    $_SESSION['role'] = 'admin';
}

$conn = getMySQLiConnection();
$total_students = 0;
$total_events = 0;
$total_users = 0;
$total_regs = 0;

if ($conn) {
    if ($r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM students")) $total_students = mysqli_fetch_assoc($r)['c'];
    if ($r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM events")) $total_events = mysqli_fetch_assoc($r)['c'];
    if ($r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users")) $total_users = mysqli_fetch_assoc($r)['c'];
    if ($r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM registrations")) $total_regs = mysqli_fetch_assoc($r)['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StudentHub | Admin Dynamic Dashboard</title>
  <link rel="stylesheet" href="../StudentHub-css.css">
  <link rel="stylesheet" href="../interactive-features.css">
  <style>
    .admin-nav { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
    .admin-nav a { background: #4f46e5; color: #fff; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; }
    .admin-nav a:hover { background: #4338ca; }
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
      <a href="Admin-Dashboard.php" class="active">Admin</a>
      <a href="../PRACTICALS_HUB.html" style="color:#f59e0b; font-weight:bold;">★ Practicals Hub</a>
    </nav>
  </header>

  <main class="container">
    <h2>Admin Dashboard (Live Dynamic Database Data)</h2>

    <div class="admin-nav">
      <a href="../Practical 11/index.php">Manage Students (CRUD)</a>
      <a href="../Practical 12/index.php">Manage Events &amp; Posters</a>
      <a href="../Practical 13/index.html">REST API &amp; AJAX Fetch</a>
      <a href="../Practical 14/index.php">Detailed Monitoring &amp; Analytics</a>
      <a href="../Practical 10/logout.php" style="background:#ef4444;">Logout</a>
    </div>

    <div class="cards">
      <div class="card">
        <h3>Total Students</h3>
        <h2><?php echo $total_students; ?></h2>
      </div>

      <div class="card">
        <h3>Active Events</h3>
        <h2><?php echo $total_events; ?></h2>
      </div>

      <div class="card">
        <h3>Registered Accounts</h3>
        <h2><?php echo $total_users; ?></h2>
      </div>

      <div class="card">
        <h3>Event Registrations</h3>
        <h2><?php echo $total_regs; ?></h2>
      </div>
    </div>

    <div class="card" style="margin-top: 20px;">
      <h3>Administrative Quick Actions</h3>
      <p style="color:#64748b; font-size:14px; margin-top:6px;">
        Use the modules above to add, edit, or remove students, create events with poster uploads, perform asynchronous Fetch API operations, or review security audit logs.
      </p>
    </div>
  </main>

  <footer><p>&copy; 2026 StudentHub</p></footer>
  <script src="StudentHub-js.js" defer></script>
</body>
</html>
