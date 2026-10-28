<?php
require_once __DIR__ . '/auth.php';
require_role('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StudentHub | Admin Security Panel</title>
  <link rel="stylesheet" href="../Practical 9/style.css">
</head>
<body>
  <div class="container" style="max-width: 850px;">
    <div class="header-box" style="display:flex; justify-content:space-between; align-items:center;">
      <div>
        <h1>Admin Control Center (RBAC Protected)</h1>
        <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong> (Admin)</p>
      </div>
      <div>
        <a href="logout.php" style="background:#ef4444; color:#fff; text-decoration:none; padding:8px 14px; border-radius:6px; font-weight:600; font-size:13px;">Logout</a>
      </div>
    </div>

    <div class="card" style="margin-bottom: 20px;">
      <span style="background:#dcfce7; color:#15803d; padding:4px 10px; border-radius:12px; font-size:12px; font-weight:600;">
        ✓ Authenticated with Role-Based Access Control (Admin)
      </span>
      <p style="margin-top: 10px; color:#475569; font-size:14px;">
        You have exclusive access to student management, event configuration, REST APIs, and system audit logs.
      </p>
    </div>

    <div class="card">
      <h3>Administrative Modules</h3>
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-top:15px;">
        <a href="../Practical 11/index.php" class="btn-submit" style="display:block; text-decoration:none; text-align:center; padding:12px;">Practical 11: Student CRUD</a>
        <a href="../Practical 12/index.php" class="btn-submit" style="display:block; text-decoration:none; text-align:center; padding:12px; background:#0891b2;">Practical 12: Event Poster CRUD</a>
        <a href="../Practical 13/index.html" class="btn-submit" style="display:block; text-decoration:none; text-align:center; padding:12px; background:#7c3aed;">Practical 13: Fetch AJAX API</a>
        <a href="../Practical 14/index.php" class="btn-submit" style="display:block; text-decoration:none; text-align:center; padding:12px; background:#059669;">Practical 14: Admin Dashboard &amp; Analytics</a>
      </div>
    </div>
  </div>
</body>
</html>
