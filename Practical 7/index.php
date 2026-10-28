<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Practical 7 | PHP Form Processing (CSV & JSON)</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <header class="header-box">
      <div>
        <h1>CHARUSAT StudentHub Portal</h1>
        <p>Practical 7: PHP Form Processing with Server-Side Validation, CSRF Protection & CSV/JSON Storage</p>
      </div>
      <div>
        <a href="view_records.php" class="nav-link-btn" style="background: rgba(255,255,255,0.2); color:#fff; border:none;">
          📂 View Saved Records
        </a>
      </div>
    </header>

    <nav class="nav-links">
      <a href="../Practical 6/index.html" class="nav-link-btn">&larr; Practical 6</a>
      <a href="view_records.php" class="nav-link-btn">📊 Stored CSV / JSON Records</a>
      <a href="../Practical 8/Connectivity.php" class="nav-link-btn">Practical 8 &rarr;</a>
    </nav>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
      <div class="alert-success">
        <strong>Success!</strong> Your registration has been validated and safely stored in the server file storage.
        <a href="view_records.php" style="color:#15803d; font-weight:bold; margin-left:8px;">View Records &rarr;</a>
      </div>
    <?php elseif (!empty($_SESSION['errors'])): ?>
      <div class="alert-error">
        <strong>Please correct the following errors:</strong>
        <ul style="margin: 8px 0 0 20px;">
          <?php 
            foreach ($_SESSION['errors'] as $err) {
                echo "<li>" . htmlspecialchars($err) . "</li>";
            }
            unset($_SESSION['errors']);
          ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2 style="font-size: 18px; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
        Student Application & Contact Form
      </h2>

      <form action="process.php" method="POST" novalidate>
        <!-- CSRF Token (Advanced Extension) -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="form-row">
          <div class="form-group">
            <label for="full_name">Full Name *</label>
            <input type="text" id="full_name" name="full_name" placeholder="e.g. Radhika Patel" required>
          </div>
          <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" placeholder="e.g. radhika@charusat.edu.in" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="phone">Mobile Number *</label>
            <input type="tel" id="phone" name="phone" placeholder="10-digit mobile (e.g. 9876543210)" required>
          </div>
          <div class="form-group">
            <label for="course">Academic Course *</label>
            <select id="course" name="course" required>
              <option value="">-- Select Course --</option>
              <option value="B.Tech Computer Engineering">B.Tech Computer Engineering</option>
              <option value="B.Tech Information Technology">B.Tech Information Technology</option>
              <option value="B.Tech EC">B.Tech Electronics & Communication</option>
              <option value="M.Tech CE">M.Tech Computer Engineering</option>
              <option value="BCA">BCA Computer Applications</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="year">Year of Study *</label>
            <select id="year" name="year" required>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
            </select>
          </div>
          <div class="form-group">
            <label>Gender *</label>
            <div class="radio-group">
              <label class="radio-label"><input type="radio" name="gender" value="Female" checked> Female</label>
              <label class="radio-label"><input type="radio" name="gender" value="Male"> Male</label>
              <label class="radio-label"><input type="radio" name="gender" value="Other"> Other</label>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Storage Format *</label>
          <div class="radio-group">
            <label class="radio-label"><input type="radio" name="storage_mode" value="both" checked> Store in Both CSV and JSON</label>
            <label class="radio-label"><input type="radio" name="storage_mode" value="csv"> CSV Only</label>
            <label class="radio-label"><input type="radio" name="storage_mode" value="json"> JSON Only</label>
          </div>
        </div>

        <div class="form-group">
          <label for="message">Remarks / Message (Min 10 characters)</label>
          <textarea id="message" name="message" rows="3" placeholder="Enter remarks or inquiry details..."></textarea>
        </div>

        <button type="submit" class="btn-submit">Submit & Save to File Storage &rarr;</button>
      </form>
    </div>
  </div>
</body>
</html>
