<?php
/**
 * Practical 7: Intermediate Extension - Display Stored CSV/JSON Records
 */
$storage_dir = __DIR__ . '/storage';
$csv_file = $storage_dir . '/registrations.csv';
$json_file = $storage_dir . '/registrations.json';

// Read CSV rows
$csv_rows = [];
if (file_exists($csv_file) && ($h = fopen($csv_file, 'r')) !== false) {
    $header = fgetcsv($h); // Read header
    while (($row = fgetcsv($h)) !== false) {
        if (!empty($row)) $csv_rows[] = $row;
    }
    fclose($h);
}

// Read JSON records
$json_rows = [];
if (file_exists($json_file) && filesize($json_file) > 0) {
    $decoded = json_decode(file_get_contents($json_file), true);
    if (is_array($decoded)) {
        $json_rows = $decoded;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Practical 7 | View Saved CSV & JSON Records</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container" style="max-width: 1050px;">
    <header class="header-box">
      <div>
        <h1>CHARUSAT StudentHub Portal</h1>
        <p>Practical 7: Server File Storage Viewer (CSV &amp; JSON Storage)</p>
      </div>
      <div>
        <a href="index.php" class="nav-link-btn" style="background: rgba(255,255,255,0.2); color:#fff; border:none;">
          + New Registration
        </a>
      </div>
    </header>

    <nav class="nav-links">
      <a href="index.php" class="nav-link-btn">&larr; Back to Form</a>
      <a href="#csvSection" class="nav-link-btn">📄 CSV Records (<?php echo count($csv_rows); ?>)</a>
      <a href="#jsonSection" class="nav-link-btn">📋 JSON Records (<?php echo count($json_rows); ?>)</a>
    </nav>

    <!-- CSV Section -->
    <div class="card" id="csvSection">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3>📄 CSV File Records (<span class="badge badge-csv"><?php echo count($csv_rows); ?> Total</span>)</h3>
        <small style="color:var(--text-muted);">Source: <code>storage/registrations.csv</code></small>
      </div>

      <?php if (empty($csv_rows)): ?>
        <p style="color:var(--text-muted); padding: 15px 0;">No records saved in CSV format yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th><th>Timestamp</th><th>Name</th><th>Email</th><th>Phone</th><th>Course</th><th>Yr</th><th>Gender</th><th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_reverse($csv_rows) as $r): ?>
                <tr>
                  <td><code><?php echo htmlspecialchars($r[0] ?? ''); ?></code></td>
                  <td><?php echo htmlspecialchars($r[1] ?? ''); ?></td>
                  <td><strong><?php echo htmlspecialchars($r[2] ?? ''); ?></strong></td>
                  <td><?php echo htmlspecialchars($r[3] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r[4] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r[5] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r[6] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r[7] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r[8] ?? ''); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- JSON Section -->
    <div class="card" id="jsonSection">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3>📋 JSON File Records (<span class="badge badge-json"><?php echo count($json_rows); ?> Total</span>)</h3>
        <small style="color:var(--text-muted);">Source: <code>storage/registrations.json</code></small>
      </div>

      <?php if (empty($json_rows)): ?>
        <p style="color:var(--text-muted); padding: 15px 0;">No records saved in JSON format yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th><th>Timestamp</th><th>Name</th><th>Email</th><th>Phone</th><th>Course</th><th>Year</th><th>Gender</th><th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_reverse($json_rows) as $item): ?>
                <tr>
                  <td><code><?php echo htmlspecialchars($item['id'] ?? ''); ?></code></td>
                  <td><?php echo htmlspecialchars($item['timestamp'] ?? ''); ?></td>
                  <td><strong><?php echo htmlspecialchars($item['full_name'] ?? ''); ?></strong></td>
                  <td><?php echo htmlspecialchars($item['email'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($item['phone'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($item['course'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($item['year'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($item['gender'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($item['message'] ?? ''); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
