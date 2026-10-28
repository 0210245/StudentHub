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
  <title>Practical 9 | Secure User Registration</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <div class="header-box">
      <h1>StudentHub | User Registration</h1>
      <p>Practical 9: Secure Registration with Password Hashing &amp; Duplicate Checks</p>
    </div>

    <div style="margin-bottom: 15px;">
      <a href="../Practical 8/Connectivity.php" style="color:var(--primary); text-decoration:none; font-weight:600;">&larr; Practical 8</a>
      <span style="margin: 0 10px; color:#cbd5e1;">|</span>
      <a href="../Practical 10/login.php" style="color:var(--primary); text-decoration:none; font-weight:600;">Practical 10 (Login) &rarr;</a>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
      <div class="alert-success">
        <strong>Registration Successful!</strong> Your account has been securely created with BCrypt password hashing.
        <a href="../Practical 10/login.php" style="font-weight:bold; color:#15803d; margin-left:8px;">Go to Login &rarr;</a>
      </div>
    <?php elseif (!empty($_SESSION['reg_errors'])): ?>
      <div class="alert-error">
        <strong>Please fix these errors:</strong>
        <ul style="margin: 8px 0 0 20px;">
          <?php 
            foreach ($_SESSION['reg_errors'] as $err) {
                echo "<li>" . htmlspecialchars($err) . "</li>";
            }
            unset($_SESSION['reg_errors']);
          ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card">
      <form action="register_process.php" method="POST" id="regForm" novalidate>
        <!-- CSRF Token (Advanced Extension) -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="form-group">
          <label for="full_name">Full Name *</label>
          <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
        </div>

        <div class="form-group">
          <label for="email">Institutional Email *</label>
          <input type="email" id="email" name="email" placeholder="e.g. yourname@studenthub.com" required>
        </div>

        <div class="form-group">
          <label for="user_id">User ID / Enrollment No. *</label>
          <input type="text" id="user_id" name="user_id" placeholder="e.g. D26CE153" required>
        </div>

        <div class="form-group">
          <label for="role">User Role *</label>
          <select id="role" name="role" required>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
          </select>
        </div>

        <div class="form-group">
          <label for="department">Department *</label>
          <select id="department" name="department" required>
            <option value="Computer Engineering">Computer Engineering</option>
            <option value="Information Technology">Information Technology</option>
            <option value="Electronics & Communication">Electronics & Communication</option>
            <option value="Mechanical Engineering">Mechanical Engineering</option>
            <option value="Civil Engineering">Civil Engineering</option>
            <option value="Computer Applications">Computer Applications</option>
          </select>
        </div>

        <div class="form-group">
          <label for="password">Password * (Min 8 characters)</label>
          <input type="password" id="password" name="password" placeholder="Create a strong password" required>
          <div class="strength-meter"><div class="strength-bar" id="strengthBar"></div></div>
          <small id="strengthText" style="font-size:11px; color:#64748b; margin-top:2px;">Password strength: Empty</small>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password *</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
        </div>

        <button type="submit" class="btn-submit">Secure Register Account &rarr;</button>
      </form>
    </div>
  </div>

  <script>
    // Frontend Password Strength Meter
    const pwdInput = document.getElementById('password');
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');

    pwdInput.addEventListener('input', () => {
      const val = pwdInput.value;
      let score = 0;
      if (val.length >= 8) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;

      if (val.length === 0) {
        bar.style.width = '0%';
        text.textContent = 'Password strength: Empty';
      } else if (score <= 1) {
        bar.style.width = '25%';
        bar.style.background = '#ef4444';
        text.textContent = 'Password strength: Weak';
      } else if (score === 2 || score === 3) {
        bar.style.width = '60%';
        bar.style.background = '#f59e0b';
        text.textContent = 'Password strength: Medium';
      } else {
        bar.style.width = '100%';
        bar.style.background = '#10b981';
        text.textContent = 'Password strength: Strong';
      }
    });
  </script>
</body>
</html>
