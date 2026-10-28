<?php
session_start();
require_once __DIR__ . '/db.php';

$name = $_SESSION['full_name'] ?? 'Radhika';
$studentId = $_SESSION['student_id'] ?? 'D26CE153';
$email = $_SESSION['email'] ?? 'radhika@charusat.edu.in';
$department = $_SESSION['department'] ?? 'Computer Engineering';
$role = $_SESSION['role'] ?? 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="../StudentHub-css.css">
    <link rel="stylesheet" href="../interactive-features.css">
</head>
<body>
<header>
    <h1>StudentHub</h1>
    <nav aria-label="Main Navigation">
        <a href="../index.html">Home</a>
        <a href="AboutUs.html">About</a>
        <a href="Login.html">Login</a>
        <a href="events.html">Events</a>
        <a href="Profile.php" class="active">Profile</a>
        <a href="Contact.html">Contact</a>
        <a href="Register.html">Register</a>
        <a href="Faq.html">FAQ</a>
        <a href="Feedback.html">Feedback</a>
        <a href="Admin-Dashboard.php">Admin</a>
    </nav>
</header>

<div class="profile-card">
    <div class="profile-photo">
        <img src="images.webp" alt="Profile Photo">
    </div>

    <h2><?php echo htmlspecialchars($name); ?></h2>

    <div class="student-info">
        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($studentId); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Role:</strong> <?php echo strtoupper(htmlspecialchars($role)); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($department); ?></p>
        <p><strong>Year:</strong> 2nd Year</p>
    </div>
    <div class="Skills">
        <h2>Achievements</h2>
        <ol>
            <li>Coding competition</li>
            <li>Web development certificate</li>
        </ol>
    </div>
    <div style="display:flex; justify-content:center; gap:10px; margin-top:15px;">
      <button class="edit-btn" type="button" data-modal-open>Edit Profile</button>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="logout.php" class="btn" style="background:#ef4444; color:#fff; text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:600;">Logout</a>
      <?php endif; ?>
    </div>
</div>

<footer><p>&copy; 2026 StudentHub</p></footer>
<script src="StudentHub-js.js" defer></script>
</body>
</html>
