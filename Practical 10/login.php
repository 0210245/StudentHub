<?php
/**
 * Practical 10: Secure Login Processing with Sessions, RBAC & Remember-Me
 */
session_start();
require_once __DIR__ . '/../Practical 8/config.php';

$error = '';
$message = '';

if (isset($_GET['logout'])) {
    $message = 'You have been successfully logged out.';
} elseif (isset($_GET['timeout'])) {
    $error = 'Your session expired due to 15 minutes of inactivity. Please log in again.';
} elseif (isset($_GET['unauthorized'])) {
    $error = 'Please log in to access this page.';
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $conn = getMySQLiConnection();
            if (!$conn) {
                $error = 'Database connection failed. Please ensure MySQL is running.';
            } else {
                $stmt = mysqli_prepare($conn, 
                    "SELECT id, full_name, email, role, department, password, status FROM users WHERE email = ? LIMIT 1"
                );
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);

                // Verify password with password_verify()
                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $error = 'This account has been deactivated. Please contact the administrator.';
                    } else {
                        // Success: Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['department'] = $user['department'];
                        $_SESSION['last_activity'] = time();

                        // Intermediate Extension: Update last_login timestamp
                        $upd_stmt = mysqli_prepare($conn, "UPDATE users SET last_login = NOW() WHERE id = ?");
                        mysqli_stmt_bind_param($upd_stmt, "i", $user['id']);
                        mysqli_stmt_execute($upd_stmt);
                        mysqli_stmt_close($upd_stmt);

                        // Advanced Extension: Secure Remember-Me Token
                        if ($remember) {
                            $selector = bin2hex(random_bytes(9)); // 18 chars
                            $validator = bin2hex(random_bytes(32));
                            $token_hash = hash('sha256', $validator);
                            $expires_at = date('Y-m-d H:i:s', time() + (30 * 24 * 3600)); // 30 days

                            $tok_stmt = mysqli_prepare($conn, 
                                "INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)"
                            );
                            mysqli_stmt_bind_param($tok_stmt, "isss", $user['id'], $selector, $token_hash, $expires_at);
                            mysqli_stmt_execute($tok_stmt);
                            mysqli_stmt_close($tok_stmt);

                            setcookie('remember_me', $selector . ':' . $validator, [
                                'expires' => time() + (30 * 24 * 3600),
                                'path' => '/',
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]);
                        }

                        // Audit Log login event
                        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                        $audit_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'USER_LOGIN', 'User logged in', ?)");
                        if ($audit_stmt) {
                            mysqli_stmt_bind_param($audit_stmt, "is", $user['id'], $ip);
                            mysqli_stmt_execute($audit_stmt);
                            mysqli_stmt_close($audit_stmt);
                        }

                        mysqli_close($conn);

                        // Role-Based Redirection (RBAC)
                        if ($user['role'] === 'admin') {
                            header('Location: admin_dashboard.php');
                        } else {
                            header('Location: student_dashboard.php');
                        }
                        exit;
                    }
                } else {
                    $error = 'Invalid email address or password.';
                }
                mysqli_close($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Practical 10 | Secure Login &amp; RBAC</title>
  <link rel="stylesheet" href="../Practical 9/style.css">
</head>
<body>
  <div class="container" style="max-width: 480px;">
    <div class="header-box">
      <h1>StudentHub Portal | Login</h1>
      <p>Practical 10: Secure Login/Logout, RBAC, Timeout &amp; Remember-Me</p>
    </div>

    <div style="margin-bottom: 15px; font-size:13px;">
      <a href="../Practical 9/index.php" style="color:var(--primary); font-weight:600; text-decoration:none;">&larr; Practical 9 (Register)</a>
      <span style="margin: 0 8px; color:#cbd5e1;">|</span>
      <a href="../Practical 11/index.php" style="color:var(--primary); font-weight:600; text-decoration:none;">Practical 11 (CRUD) &rarr;</a>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
      <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card">
      <form action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div class="form-group">
          <label for="email">Institutional Email</label>
          <input type="email" id="email" name="email" placeholder="e.g. admin@studenthub.com" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; font-size:13px;">
          <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
            <input type="checkbox" name="remember" value="1"> Remember Me (30 Days)
          </label>
          <a href="../Practical 9/index.php" style="color:var(--primary); text-decoration:none;">Register</a>
        </div>

        <button type="submit" class="btn-submit">Login to Account &rarr;</button>
      </form>

      <div style="margin-top: 20px; background:#f1f5f9; padding:12px; border-radius:6px; font-size:12px; color:#475569;">
        <strong>Demo Accounts from DB.sql:</strong><br>
        &bull; <strong>Admin:</strong> admin@studenthub.com / <code>Admin@123</code><br>
        &bull; <strong>Student:</strong> student@studenthub.com / <code>Student@123</code><br>
        &bull; <strong>Faculty:</strong> faculty@studenthub.com / <code>Faculty@123</code>
      </div>
    </div>
  </div>
</body>
</html>
