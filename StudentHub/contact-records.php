<?php
/* =========================================================
   contact-records.php
   Reads StudentHub/storage/contacts.csv and shows every saved
   message in a table (newest first).
   Put this file in the StudentHub/ folder.
   ========================================================= */

$file = __DIR__ . "/storage/contacts.csv";
$rows = [];

if (file_exists($file) && ($handle = fopen($file, "r")) !== false) {
    fgetcsv($handle, 0, ",", "\"", "\\");                    // skip the first line (column names)
    while (($row = fgetcsv($handle, 0, ",", "\"", "\\")) !== false) {
        if (count($row) === 4) {                              // ignore empty or broken lines
            $rows[] = $row;
        }
    }
    fclose($handle);
}
$rows = array_reverse($rows);                                 // newest message first

/* Makes text safe to print inside HTML */
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
        .table-wrap { overflow-x: auto; }
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
        <a href="Admin-Dashboard.html">Admin</a>
    </nav>
</header>

<main class="container">
    <h2>Saved Messages</h2>
    <div class="card">
        <?php if (count($rows) === 0) { ?>
            <p>No messages yet. <a href="Contact.html">Send the first one.</a></p>
        <?php } else { ?>
            <p>Total messages: <strong><?php echo count($rows); ?></strong></p><br>
            <div class="table-wrap">
                <table>
                    <tr><th>Date and time</th><th>Name</th><th>Email</th><th>Message</th></tr>
                    <?php foreach ($rows as $row) { ?>
                        <tr>
                            <td><?php echo esc($row[0]); ?></td>
                            <td><?php echo esc($row[1]); ?></td>
                            <td><?php echo esc($row[2]); ?></td>
                            <td><?php echo esc($row[3]); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } ?>
    </div>
</main>

<footer><p>&copy; 2026 StudentHub</p></footer>
<script src="StudentHub-js.js" defer></script>
</body>
</html>