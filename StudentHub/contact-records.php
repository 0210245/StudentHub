<?php
/* =========================================================
   contact-records.php - Practical 7: View CSV & JSON Records
   Reads StudentHub/storage/contacts.csv & contacts.json
   ========================================================= */

$csvFile = __DIR__ . "/storage/contacts.csv";
$jsonFile = __DIR__ . "/storage/contacts.json";
$rows = [];
$jsonRows = [];

// 1. Read CSV
if (file_exists($csvFile) && ($handle = fopen($csvFile, "r")) !== false) {
    fgetcsv($handle, 0, ",", "\"", "\\"); // skip header
    while (($row = fgetcsv($handle, 0, ",", "\"", "\\")) !== false) {
        if (count($row) >= 4) {
            $rows[] = $row;
        }
    }
    fclose($handle);
}
$rows = array_reverse($rows);

// 2. Read JSON
if (file_exists($jsonFile) && filesize($jsonFile) > 0) {
    $decoded = json_decode(file_get_contents($jsonFile), true);
    if (is_array($decoded)) {
        $jsonRows = array_reverse($decoded);
    }
}

function esc($text) {
    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Saved Messages</title>
    <link rel="stylesheet" href="../StudentHub-css.css">
    <link rel="stylesheet" href="../interactive-features.css">
    <style>
        .table-wrap { overflow-x: auto; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; text-align: left; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        tr:nth-child(even) td { background: rgba(79,70,229,0.05); }
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
    <h2>Saved Messages (Practical 7 File Storage)</h2>

    <!-- CSV Table -->
    <div class="card">
        <h3>Saved in CSV File (<?php echo count($rows); ?> Total)</h3>
        <?php if (count($rows) === 0): ?>
            <p style="color:#64748b; margin-top:8px;">No CSV messages recorded yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr><th>Date &amp; Time</th><th>Name</th><th>Email</th><th>Message</th></tr>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo esc($row[0]); ?></td>
                            <td><strong><?php echo esc($row[1]); ?></strong></td>
                            <td><?php echo esc($row[2]); ?></td>
                            <td><?php echo esc($row[3]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- JSON Table -->
    <div class="card" style="margin-top:20px;">
        <h3>Saved in JSON File (<?php echo count($jsonRows); ?> Total)</h3>
        <?php if (count($jsonRows) === 0): ?>
            <p style="color:#64748b; margin-top:8px;">No JSON messages recorded yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr><th>Date &amp; Time</th><th>Name</th><th>Email</th><th>Message</th></tr>
                    <?php foreach ($jsonRows as $j): ?>
                        <tr>
                            <td><?php echo esc($j['timestamp'] ?? ''); ?></td>
                            <td><strong><?php echo esc($j['name'] ?? ''); ?></strong></td>
                            <td><?php echo esc($j['email'] ?? ''); ?></td>
                            <td><?php echo esc($j['message'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer><p>&copy; 2026 StudentHub</p></footer>
<script src="StudentHub-js.js" defer></script>
</body>
</html>
