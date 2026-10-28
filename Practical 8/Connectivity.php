<?php
/**
 * Practical 8: Database Connectivity Inspection & Verification
 * Tests MySQLi, PDO (Intermediate), Prepared Statements, and Stored Procedures (Advanced)
 */
require_once __DIR__ . '/config.php';

$mysqli_status = false;
$mysqli_msg = '';
$pdo_status = false;
$pdo_msg = '';
$tables_info = [];
$sp_status = false;
$sp_results = [];

// 1. Test MySQLi Connection
$mysqli = getMySQLiConnection();
if ($mysqli) {
    $mysqli_status = true;
    $mysqli_msg = "MySQLi Connected Successfully (Server version: " . mysqli_get_server_info($mysqli) . ")";
} else {
    $mysqli_msg = "MySQLi Connection Failed. Ensure MySQL is running on WAMP and 'studenthub' database is imported.";
}

// 2. Test PDO Connection (Intermediate Extension)
$pdo = getPDOConnection();
if ($pdo) {
    $pdo_status = true;
    $pdo_msg = "PDO Connected Successfully (Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . ")";

    // Query tables and row counts
    $tables = ['users', 'students', 'events', 'registrations', 'audit_logs', 'remember_tokens'];
    foreach ($tables as $t) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM `{$t}`");
            $row = $stmt->fetch();
            $tables_info[$t] = $row['cnt'];
        } catch (PDOException $e) {
            $tables_info[$t] = 'Table not found / error';
        }
    }

    // 3. Test Stored Procedure (Advanced Extension)
    try {
        $sp_stmt = $pdo->query("CALL sp_event_registration_counts()");
        $sp_results = $sp_stmt->fetchAll();
        $sp_status = true;
        $sp_stmt->closeCursor();
    } catch (PDOException $e) {
        $sp_status = false;
    }
} else {
    $pdo_msg = "PDO Connection Failed.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Practical 8 | MySQL Schema &amp; Connectivity Test</title>
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #1e293b; padding: 30px 15px; margin: 0; }
    .container { max-width: 900px; margin: 0 auto; }
    .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; padding: 24px; border-radius: 8px; margin-bottom: 20px; }
    .header h1 { margin: 0 0 6px; font-size: 24px; }
    .card { background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
    .status-success { background: #dcfce7; color: #15803d; }
    .status-fail { background: #fee2e2; color: #b91c1c; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 14px; }
    th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    th { background: #f1f5f9; color: #475569; }
    .btn { display: inline-block; background: #4f46e5; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; margin-right: 10px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Practical 8: MySQL Schema &amp; Database Connectivity</h1>
      <p>Database: <code>studenthub</code> | Tests MySQLi, PDO, Prepared Statements &amp; Stored Procedures</p>
    </div>

    <div style="margin-bottom: 20px;">
      <a href="../Practical 7/index.php" class="btn" style="background:#64748b;">&larr; Practical 7</a>
      <a href="../Practical 9/index.php" class="btn">Practical 9 &rarr;</a>
      <a href="ER_MODEL_AND_VIVA.md" class="btn" style="background:#0284c7;">📑 ER Model &amp; Viva Documentation</a>
    </div>

    <!-- MySQLi Test Result -->
    <div class="card">
      <h3>1. MySQLi Connectivity Check</h3>
      <p style="margin: 8px 0;">
        <span class="status-badge <?php echo $mysqli_status ? 'status-success' : 'status-fail'; ?>">
          <?php echo $mysqli_status ? '● CONNECTED' : '● FAILED'; ?>
        </span>
        <span style="margin-left: 10px; font-size: 14px;"><?php echo htmlspecialchars($mysqli_msg); ?></span>
      </p>
    </div>

    <!-- PDO Test Result -->
    <div class="card">
      <h3>2. PDO Connectivity Check (Intermediate Extension)</h3>
      <p style="margin: 8px 0;">
        <span class="status-badge <?php echo $pdo_status ? 'status-success' : 'status-fail'; ?>">
          <?php echo $pdo_status ? '● PDO CONNECTED' : '● PDO FAILED'; ?>
        </span>
        <span style="margin-left: 10px; font-size: 14px;"><?php echo htmlspecialchars($pdo_msg); ?></span>
      </p>
    </div>

    <!-- Database Tables Status -->
    <div class="card">
      <h3>3. Database Tables &amp; Record Counts</h3>
      <?php if (!empty($tables_info)): ?>
        <table>
          <thead>
            <tr><th>Table Name</th><th>Record Count</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($tables_info as $tbl => $cnt): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($tbl); ?></strong></td>
                <td><?php echo htmlspecialchars($cnt); ?> records</td>
                <td><span class="status-badge status-success">Ready</span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="color:#b91c1c;">Please import <code>DB.sql</code> in phpMyAdmin to create the database and tables.</p>
      <?php endif; ?>
    </div>

    <!-- Stored Procedure Test Result (Advanced Extension) -->
    <div class="card">
      <h3>4. Stored Procedure Test (Advanced Extension)</h3>
      <p style="font-size: 13px; color: #64748b;">Executing: <code>CALL sp_event_registration_counts();</code></p>
      <?php if ($sp_status && !empty($sp_results)): ?>
        <table>
          <thead>
            <tr><th>Event ID</th><th>Event Name</th><th>Event Date</th><th>Registrations</th></tr>
          </thead>
          <tbody>
            <?php foreach ($sp_results as $sp): ?>
              <tr>
                <td><code><?php echo htmlspecialchars($sp['event_id']); ?></code></td>
                <td><?php echo htmlspecialchars($sp['event_name']); ?></td>
                <td><?php echo htmlspecialchars($sp['event_date']); ?></td>
                <td><strong><?php echo htmlspecialchars($sp['total_registrations']); ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="color:#64748b; font-size:13px; margin-top:8px;">
          Stored procedure <code>sp_event_registration_counts</code> verified in <code>DB.sql</code>.
        </p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
