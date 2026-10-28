<?php
/**
 * manage-events.php - Practical 12: Event Management CRUD with Poster Upload
 * Built with the exact same StudentHub layout and styles.
 */
session_start();
require_once __DIR__ . '/db.php';

$conn = getDBConnection();
if (!$conn) die("Database connection error");

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// 1. ADD EVENT WITH POSTER UPLOAD
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId     = trim($_POST['event_id'] ?? '');
    $eventName   = trim($_POST['event_name'] ?? '');
    $eventDate   = trim($_POST['event_date'] ?? '');
    $eventVenue  = trim($_POST['event_venue'] ?? 'TBA');
    $eventType   = trim($_POST['event_type'] ?? 'General');
    $coordinator = trim($_POST['coordinator'] ?? 'TBA');
    $desc        = trim($_POST['description'] ?? '');
    $posterName  = null;

    // Validate and process poster file (Max 2MB, JPG/PNG/WebP)
    if (!empty($_FILES['poster']['name']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['poster']['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed) && $_FILES['poster']['size'] <= 2 * 1024 * 1024) {
            $ext = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
            $posterName = 'evt_' . uniqid() . '.' . strtolower($ext);
            move_uploaded_file($_FILES['poster']['tmp_name'], $uploadDir . '/' . $posterName);
        }
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO events (event_id, event_name, event_date, event_venue, event_type, coordinator, description, poster, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')");
    mysqli_stmt_bind_param($stmt, "ssssssss", $eventId, $eventName, $eventDate, $eventVenue, $eventType, $coordinator, $desc, $posterName);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    recordAuditLog($conn, $_SESSION['user_id'] ?? null, 'EVENT_CREATE', "Created event {$eventId} ({$eventName})");
    header("Location: manage-events.php");
    exit;
}

// 2. TOGGLE STATUS (Open / Closed)
if ($action === 'toggle_status') {
    $id = trim($_GET['id'] ?? '');
    $status = ($_GET['status'] === 'open') ? 'open' : 'closed';
    $stmt = mysqli_prepare($conn, "UPDATE events SET status = ? WHERE event_id = ?");
    mysqli_stmt_bind_param($stmt, "ss", $status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    recordAuditLog($conn, $_SESSION['user_id'] ?? null, 'EVENT_STATUS_TOGGLE', "Toggled event {$id} to {$status}");
    header("Location: manage-events.php");
    exit;
}

// 3. DELETE EVENT
if ($action === 'delete') {
    $id = trim($_GET['id'] ?? '');
    $stmt = mysqli_prepare($conn, "DELETE FROM events WHERE event_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    recordAuditLog($conn, $_SESSION['user_id'] ?? null, 'EVENT_DELETE', "Deleted event {$id}");
    header("Location: manage-events.php");
    exit;
}

$events = [];
$res = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date ASC");
while ($r = mysqli_fetch_assoc($res)) $events[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudentHub | Event Management</title>
<link rel="stylesheet" href="../StudentHub-css.css">
<link rel="stylesheet" href="../interactive-features.css">
<style>
  table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
  th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
  th { background: #f8fafc; color: #4f46e5; }
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
    <h2>Event Management Module (Practical 12)</h2>

    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h3>Campus Events Administration</h3>
        <a href="Admin-Dashboard.php" class="btn" style="background:#64748b; padding:8px 14px; text-decoration:none;">&larr; Admin Panel</a>
      </div>

      <!-- Add Event Form -->
      <form action="manage-events.php" method="POST" enctype="multipart/form-data" style="margin-bottom:30px; border:1px solid #e5e7eb;">
        <input type="hidden" name="action" value="add">
        <h3 style="color:#4f46e5; margin-bottom:15px; text-align:center;">Add New Event</h3>
        
        <label for="event_id">Event ID</label>
        <input type="text" id="event_id" name="event_id" placeholder="e.g. EVT009" required>

        <label for="event_name">Event Name</label>
        <input type="text" id="event_name" name="event_name" placeholder="Event Name" required>

        <label for="event_date">Event Date</label>
        <input type="date" id="event_date" name="event_date" required>

        <label for="event_type">Category / Type</label>
        <select id="event_type" name="event_type" required>
          <option value="Technical">Technical</option>
          <option value="Cultural">Cultural</option>
          <option value="Workshop">Workshop</option>
          <option value="Sports">Sports</option>
        </select>

        <label for="poster">Event Poster Image (Max 2MB - JPG, PNG, WebP)</label>
        <input type="file" id="poster" name="poster" accept="image/*">

        <button type="submit" class="btn" style="margin-top:15px;">Upload &amp; Create Event</button>
      </form>

      <!-- Events List Table -->
      <div style="overflow-x: auto;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Date</th>
              <th>Type</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $ev): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($ev['event_id']); ?></strong></td>
                <td><?php echo htmlspecialchars($ev['event_name']); ?></td>
                <td><?php echo htmlspecialchars($ev['event_date']); ?></td>
                <td><?php echo htmlspecialchars($ev['event_type']); ?></td>
                <td>
                  <span style="font-weight:bold; color: <?php echo $ev['status'] === 'open' ? '#16a34a' : '#dc2626'; ?>;">
                    ● <?php echo strtoupper($ev['status']); ?>
                  </span>
                </td>
                <td>
                  <?php if ($ev['status'] === 'open'): ?>
                    <a href="manage-events.php?action=toggle_status&id=<?php echo $ev['event_id']; ?>&status=closed" style="color:#d97706; text-decoration:none; margin-right:8px;">Close</a>
                  <?php else: ?>
                    <a href="manage-events.php?action=toggle_status&id=<?php echo $ev['event_id']; ?>&status=open" style="color:#16a34a; text-decoration:none; margin-right:8px;">Open</a>
                  <?php endif; ?>
                  <a href="manage-events.php?action=delete&id=<?php echo $ev['event_id']; ?>" style="color:#dc2626; text-decoration:none;" onclick="return confirm('Delete event?');">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
</main>

<footer><p>&copy; 2026 StudentHub</p></footer>
</body>
</html>
