<?php
session_start();
require_once __DIR__ . '/../Practical 8/config.php';

$conn = getMySQLiConnection();
$cat    = trim($_GET['category'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM events WHERE 1=1";
$params = [];
$types = "";

if (!empty($cat)) {
    $sql .= " AND event_type = ?";
    $params[] = $cat;
    $types .= "s";
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY event_date ASC";

$events = [];
if ($conn) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $events[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Practical 12 | Event Management CRUD &amp; Poster Upload</title>
  <link rel="stylesheet" href="../Practical 11/style.css">
</head>
<body>
  <div class="container">
    <div class="header-box" style="background: linear-gradient(135deg, #0284c7, #2563eb);">
      <div>
        <h1>StudentHub | Event Management CRUD</h1>
        <p>Practical 12: Event Administration with Secure File Upload &amp; Validation</p>
      </div>
      <div>
        <button class="btn btn-success" onclick="openAddEventModal()">+ Create New Event</button>
      </div>
    </div>

    <div style="margin-bottom: 15px; font-size:13px;">
      <a href="../Practical 11/index.php" style="color:var(--primary); font-weight:600; text-decoration:none;">&larr; Practical 11 (Students)</a>
      <span style="margin: 0 8px; color:#cbd5e1;">|</span>
      <a href="../Practical 13/index.html" style="color:var(--primary); font-weight:600; text-decoration:none;">Practical 13 (AJAX API) &rarr;</a>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['msg_success'])): ?>
      <div style="background:#dcfce7; color:#15803d; padding:12px; border-radius:6px; margin-bottom:15px; font-weight:600;">
        <?php echo htmlspecialchars($_SESSION['msg_success']); unset($_SESSION['msg_success']); ?>
      </div>
    <?php elseif (!empty($_SESSION['msg_error'])): ?>
      <div style="background:#fee2e2; color:#b91c1c; padding:12px; border-radius:6px; margin-bottom:15px; font-weight:600;">
        <?php echo htmlspecialchars($_SESSION['msg_error']); unset($_SESSION['msg_error']); ?>
      </div>
    <?php endif; ?>

    <!-- Toolbar & Filters (Intermediate Extension) -->
    <div class="card">
      <form method="GET" action="index.php" class="toolbar" style="grid-template-columns: 1fr 1fr auto;">
        <select name="category">
          <option value="">All Categories / Types</option>
          <option value="Technical" <?php if ($cat === 'Technical') echo 'selected'; ?>>Technical</option>
          <option value="Cultural" <?php if ($cat === 'Cultural') echo 'selected'; ?>>Cultural</option>
          <option value="Workshop" <?php if ($cat === 'Workshop') echo 'selected'; ?>>Workshop</option>
          <option value="Sports" <?php if ($cat === 'Sports') echo 'selected'; ?>>Sports</option>
          <option value="Seminar" <?php if ($cat === 'Seminar') echo 'selected'; ?>>Seminar</option>
        </select>

        <select name="status">
          <option value="">All Statuses (Open &amp; Closed)</option>
          <option value="open" <?php if ($status === 'open') echo 'selected'; ?>>Open Only</option>
          <option value="closed" <?php if ($status === 'closed') echo 'selected'; ?>>Closed Only</option>
        </select>

        <button type="submit" class="btn btn-primary">Filter Events</button>
      </form>
    </div>

    <!-- Events List Table -->
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3>Campus Events (<?php echo count($events); ?> Total)</h3>
        <small style="color:var(--text-muted);">Database: <code>studenthub.events</code></small>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Poster</th>
              <th>Event ID</th>
              <th>Title</th>
              <th>Date</th>
              <th>Category</th>
              <th>Venue</th>
              <th>Coordinator</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($events)): ?>
              <tr><td colspan="9" style="text-align:center; padding:20px; color:#64748b;">No events found.</td></tr>
            <?php else: ?>
              <?php foreach ($events as $ev): ?>
                <tr>
                  <td>
                    <?php if (!empty($ev['poster']) && file_exists(__DIR__ . '/uploads/' . $ev['poster'])): ?>
                      <img src="uploads/<?php echo htmlspecialchars($ev['poster']); ?>" alt="Poster" style="width:45px; height:45px; object-fit:cover; border-radius:4px; border:1px solid #cbd5e1;">
                    <?php else: ?>
                      <span style="display:inline-block; width:45px; height:45px; background:#f1f5f9; border-radius:4px; text-align:center; line-height:45px; font-size:18px;">📅</span>
                    <?php endif; ?>
                  </td>
                  <td><code><?php echo htmlspecialchars($ev['event_id']); ?></code></td>
                  <td><strong><?php echo htmlspecialchars($ev['event_name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($ev['event_date']); ?></td>
                  <td><span class="badge" style="background:#e0e7ff; color:#3730a3;"><?php echo htmlspecialchars($ev['event_type']); ?></span></td>
                  <td><?php echo htmlspecialchars($ev['event_venue']); ?></td>
                  <td><?php echo htmlspecialchars($ev['coordinator']); ?></td>
                  <td>
                    <span class="badge <?php echo $ev['status'] === 'open' ? 'badge-active' : 'badge-inactive'; ?>">
                      <?php echo strtoupper($ev['status']); ?>
                    </span>
                  </td>
                  <td>
                    <div class="action-btns">
                      <button class="action-btn" onclick='openEditEventModal(<?php echo json_encode($ev); ?>)'>✏️ Edit</button>
                      
                      <!-- Toggle Open / Closed -->
                      <?php if ($ev['status'] === 'open'): ?>
                        <a href="event_actions.php?action=toggle_status&id=<?php echo $ev['event_id']; ?>&status=closed" class="action-btn" style="color:#d97706;">Close</a>
                      <?php else: ?>
                        <a href="event_actions.php?action=toggle_status&id=<?php echo $ev['event_id']; ?>&status=open" class="action-btn" style="color:#16a34a;">Open</a>
                      <?php endif; ?>

                      <!-- Delete -->
                      <a href="event_actions.php?action=delete&id=<?php echo $ev['event_id']; ?>" 
                         class="action-btn" style="color:#dc2626;" 
                         onclick="return confirm('Delete event <?php echo htmlspecialchars($ev['event_id']); ?>?');">
                        🗑
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Add/Edit Event Modal -->
  <div class="modal-overlay" id="eventModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="modalEventTitle">Create Event</h3>
        <button class="modal-close" onclick="closeEventModal()">&times;</button>
      </div>

      <form action="event_actions.php" method="POST" enctype="multipart/form-data" id="eventForm">
        <input type="hidden" name="action" id="eventFormAction" value="add">

        <div class="form-grid">
          <div class="form-group">
            <label for="event_id">Event ID *</label>
            <input type="text" id="m_event_id" name="event_id" placeholder="e.g. EVT010" required>
          </div>
          <div class="form-group">
            <label for="event_name">Event Name *</label>
            <input type="text" id="m_event_name" name="event_name" required>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label for="event_date">Date *</label>
            <input type="date" id="m_event_date" name="event_date" required>
          </div>
          <div class="form-group">
            <label for="event_type">Category *</label>
            <select id="m_event_type" name="event_type" required>
              <option value="Technical">Technical</option>
              <option value="Cultural">Cultural</option>
              <option value="Workshop">Workshop</option>
              <option value="Sports">Sports</option>
              <option value="Seminar">Seminar</option>
            </select>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label for="event_venue">Venue</label>
            <input type="text" id="m_event_venue" name="event_venue" placeholder="e.g. Auditorium">
          </div>
          <div class="form-group">
            <label for="coordinator">Coordinator</label>
            <input type="text" id="m_coordinator" name="coordinator" placeholder="e.g. Dr. Jane Doe">
          </div>
        </div>

        <div class="form-group">
          <label for="description">Event Description</label>
          <textarea id="m_description" name="description" rows="2" style="padding:8px; border:1px solid var(--border); border-radius:4px; font-size:13px;"></textarea>
        </div>

        <div class="form-group">
          <label for="poster">Event Poster Image (Max 2MB - JPG, PNG, WebP)</label>
          <input type="file" id="poster" name="poster" accept="image/png, image/jpeg, image/webp">
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
          <button type="button" class="btn" style="background:#e2e8f0; color:#475569;" onclick="closeEventModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Event</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const modal = document.getElementById('eventModal');
    const form = document.getElementById('eventForm');

    function openAddEventModal() {
      document.getElementById('modalEventTitle').textContent = 'Create New Event';
      document.getElementById('eventFormAction').value = 'add';
      document.getElementById('m_event_id').readOnly = false;
      form.reset();
      modal.classList.add('active');
    }

    function openEditEventModal(ev) {
      document.getElementById('modalEventTitle').textContent = 'Edit Event: ' + ev.event_id;
      document.getElementById('eventFormAction').value = 'edit';
      document.getElementById('m_event_id').value = ev.event_id;
      document.getElementById('m_event_id').readOnly = true;
      document.getElementById('m_event_name').value = ev.event_name;
      document.getElementById('m_event_date').value = ev.event_date;
      document.getElementById('m_event_venue').value = ev.event_venue;
      document.getElementById('m_event_type').value = ev.event_type;
      document.getElementById('m_coordinator').value = ev.coordinator;
      document.getElementById('m_description').value = ev.description || '';
      modal.classList.add('active');
    }

    function closeEventModal() {
      modal.classList.remove('active');
    }
  </script>
</body>
</html>
