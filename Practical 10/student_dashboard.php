<?php
require_once __DIR__ . '/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StudentHub | Student Dashboard</title>
  <link rel="stylesheet" href="../Practical 9/style.css">
</head>
<body>
  <div class="container" style="max-width: 800px;">
    <div class="header-box" style="display:flex; justify-content:space-between; align-items:center;">
      <div>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <p>Role: <strong><?php echo strtoupper($_SESSION['role']); ?></strong> &bull; Dept: <?php echo htmlspecialchars($_SESSION['department']); ?></p>
      </div>
      <div>
        <a href="logout.php" style="background:#ef4444; color:#fff; text-decoration:none; padding:8px 14px; border-radius:6px; font-weight:600; font-size:13px;">Logout</a>
      </div>
    </div>

    <div class="card" style="margin-bottom: 20px;">
      <h3>Session &amp; Security Status</h3>
      <p style="margin: 8px 0; color:#475569;">
        Logged in as: <code><?php echo htmlspecialchars($_SESSION['email']); ?></code>
      </p>
      <div style="background:#e0e7ff; color:#3730a3; padding:10px 14px; border-radius:6px; font-size:13px;">
        ⏳ Session timeout is set to 15 minutes of inactivity. Session ID is regenerated on every login.
      </div>
    </div>

    <div class="card">
      <h3>Quick Navigation</h3>
      <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
        <a href="../Practical 6/index.html" class="btn-submit" style="display:inline-block; text-decoration:none; text-align:center; width:auto; padding:10px 16px;">View Events (P6)</a>
        <a href="../Practical 7/index.php" class="btn-submit" style="display:inline-block; text-decoration:none; text-align:center; width:auto; padding:10px 16px; background:#059669;">Form Submission (P7)</a>
        <a href="../StudentHub/Profile.html" class="btn-submit" style="display:inline-block; text-decoration:none; text-align:center; width:auto; padding:10px 16px; background:#7c3aed;">Student Profile</a>
      </div>
    </div>
  </div>
</body>
</html>
