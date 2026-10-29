<?php
/**
 * Admin-Dashboard.php - Practical 14: Admin Dashboard with Role-Based Access Control,
 * Dynamic Menu and Audit Log.
 * All numbers, tables and charts below are loaded live from the "studenthub" database.
 */
session_start();
require_once __DIR__ . '/db.php';

/* ---------- 1. Access control (admin only) + session timeout ---------- */
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: Login.html');
    exit;
}
$timeoutSeconds = 1800; // 30 minutes of inactivity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeoutSeconds) {
    $_SESSION = [];
    session_destroy();
    header('Location: Login.html');
    exit;
}
$_SESSION['last_activity'] = time();

$role     = $_SESSION['role'];
$userName = $_SESSION['full_name'] ?? 'Admin';
$initial  = strtoupper(substr($userName, 0, 1));

/* ---------- 2. Helpers ---------- */
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function fetchAll($conn, $sql) {
    $rows = [];
    if ($conn && ($r = mysqli_query($conn, $sql))) {
        while ($row = mysqli_fetch_assoc($r)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function fetchCount($conn, $sql) {
    $rows = fetchAll($conn, $sql);
    return $rows ? (int)$rows[0]['c'] : 0;
}

/* ---------- 3. Dashboard data (SQL aggregation) ---------- */
$conn = getDBConnection();

$totalStudents  = fetchCount($conn, "SELECT COUNT(*) AS c FROM students");
$activeStudents = fetchCount($conn, "SELECT COUNT(*) AS c FROM students WHERE status = 'active'");
$totalEvents    = fetchCount($conn, "SELECT COUNT(*) AS c FROM events");
$openEvents     = fetchCount($conn, "SELECT COUNT(*) AS c FROM events WHERE status = 'open'");
$totalUsers     = fetchCount($conn, "SELECT COUNT(*) AS c FROM users");
$totalRegs      = fetchCount($conn, "SELECT COUNT(*) AS c FROM registrations");

$latestRegs = fetchAll($conn,
    "SELECT r.student_name, r.student_email, e.event_name, r.registered_at
       FROM registrations r
       JOIN events e ON e.event_id = r.event_id
      ORDER BY r.id DESC LIMIT 5");

$regsPerEvent = fetchAll($conn,
    "SELECT e.event_name, COUNT(r.id) AS c
       FROM events e
       LEFT JOIN registrations r ON r.event_id = e.event_id
      GROUP BY e.event_id, e.event_name, e.event_date
      ORDER BY e.event_date LIMIT 8");

$byDepartment = fetchAll($conn,
    "SELECT department AS label, COUNT(*) AS c
       FROM students
      GROUP BY department
      ORDER BY c DESC LIMIT 6");

$recentActivities = fetchAll($conn,
    "SELECT action, details, created_at FROM audit_logs ORDER BY id DESC LIMIT 6");

$maxEventRegs = 1;
foreach ($regsPerEvent as $row) { $maxEventRegs = max($maxEventRegs, (int)$row['c']); }
$maxDept = 1;
foreach ($byDepartment as $row) { $maxDept = max($maxDept, (int)$row['c']); }

/* ---------- 4. Dynamic menu (depends on the logged-in role) ---------- */
$menu = [
    ['Dashboard',        'Admin-Dashboard.php', ['admin'],                     true],
    ['Manage Students',  'manage-students.php', ['admin'],                     false],
    ['Manage Events',    'manage-events.php',   ['admin'],                     false],
    ['Events Page',      'events.html',         ['admin', 'faculty', 'student'], false],
    ['Contact Messages', 'contact-records.php', ['admin'],                     false],
    ['Home',             '../index.html',       ['admin', 'faculty', 'student'], false],
];

function actionClass($a) {
    if (strpos($a, 'DELETE') !== false) return 'tag-red';
    if (strpos($a, 'LOGOUT') !== false) return 'tag-grey';
    if (strpos($a, 'LOGIN')  !== false) return 'tag-green';
    return 'tag-blue';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudentHub | Admin Dashboard</title>
<style>
  :root {
    --primary: #4f46e5;
    --primary-dark: #3730a3;
    --bg: #f1f5f9;
    --card: #ffffff;
    --text: #0f172a;
    --muted: #64748b;
    --border: #e2e8f0;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: "Segoe UI", Arial, Helvetica, sans-serif; background: var(--bg); color: var(--text); }

  .layout { display: flex; min-height: 100vh; }

  /* ---------- Sidebar ---------- */
  .sidebar {
    width: 240px; background: linear-gradient(180deg, #1e1b4b, #312e81);
    color: #fff; padding: 24px 16px; position: sticky; top: 0; height: 100vh;
    display: flex; flex-direction: column; flex-shrink: 0;
  }
  .brand { font-size: 22px; font-weight: 700; padding: 0 10px 22px; letter-spacing: .5px; }
  .brand small { display: block; font-size: 12px; font-weight: 400; color: #c7d2fe; margin-top: 4px; }
  .sidebar nav a {
    display: block; color: #c7d2fe; text-decoration: none; padding: 11px 14px;
    border-radius: 10px; margin-bottom: 4px; font-size: 14.5px;
  }
  .sidebar nav a:hover { background: rgba(255,255,255,.1); color: #fff; }
  .sidebar nav a.active { background: #fff; color: var(--primary-dark); font-weight: 600; }
  .sidebar .logout {
    margin-top: auto; display: block; text-align: center; text-decoration: none;
    background: #ef4444; color: #fff; padding: 11px; border-radius: 10px; font-weight: 600; font-size: 14px;
  }
  .sidebar .logout:hover { background: #dc2626; }

  /* ---------- Main area ---------- */
  .main { flex: 1; min-width: 0; }
  .topbar {
    background: var(--card); border-bottom: 1px solid var(--border);
    padding: 16px 28px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  }
  .topbar h1 { font-size: 22px; }
  .topbar p { color: var(--muted); font-size: 13px; margin-top: 2px; }
  .user { display: flex; align-items: center; gap: 10px; }
  .avatar {
    width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: #fff;
    display: flex; align-items: center; justify-content: center; font-weight: 700;
  }
  .user strong { display: block; font-size: 14px; }
  .user span { font-size: 12px; color: var(--muted); text-transform: capitalize; }

  .content { padding: 26px 28px; }

  /* ---------- Stat cards ---------- */
  .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 18px; margin-bottom: 24px; }
  .stat {
    background: var(--card); border-radius: 14px; padding: 18px 20px;
    border: 1px solid var(--border); border-left: 5px solid var(--primary);
    box-shadow: 0 2px 8px rgba(15,23,42,.05);
  }
  .stat .label { font-size: 13px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
  .stat .value { font-size: 34px; font-weight: 700; margin: 6px 0 2px; }
  .stat .sub { font-size: 12.5px; color: var(--muted); }
  .stat.c2 { border-left-color: #0891b2; }
  .stat.c3 { border-left-color: #16a34a; }
  .stat.c4 { border-left-color: #f59e0b; }

  /* ---------- Panels ---------- */
  .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 20px; }
  .panel {
    background: var(--card); border-radius: 14px; border: 1px solid var(--border);
    padding: 20px 22px; box-shadow: 0 2px 8px rgba(15,23,42,.05);
  }
  .panel h2 { font-size: 17px; margin-bottom: 14px; }
  .empty { color: var(--muted); font-size: 14px; padding: 10px 0; }

  /* bar chart (pure CSS) */
  .bar-row { margin-bottom: 12px; }
  .bar-label { display: flex; justify-content: space-between; font-size: 13.5px; margin-bottom: 5px; }
  .bar-label span:last-child { font-weight: 700; }
  .bar-track { background: #e0e7ff; border-radius: 999px; height: 10px; overflow: hidden; }
  .bar-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #6366f1, #4f46e5); }
  .bar-fill.teal { background: linear-gradient(90deg, #22d3ee, #0891b2); }

  /* table */
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th { text-align: left; color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .4px; padding: 8px 10px; border-bottom: 2px solid var(--border); }
  td { padding: 11px 10px; border-bottom: 1px solid var(--border); }
  tr:last-child td { border-bottom: none; }
  td small { display: block; color: var(--muted); font-size: 12px; }
  .table-wrap { overflow-x: auto; }

  /* activity list */
  .activity { list-style: none; }
  .activity li { display: flex; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--border); align-items: flex-start; }
  .activity li:last-child { border-bottom: none; }
  .tag { font-size: 11px; font-weight: 700; padding: 4px 9px; border-radius: 999px; white-space: nowrap; }
  .tag-blue  { background: #e0e7ff; color: #3730a3; }
  .tag-green { background: #dcfce7; color: #166534; }
  .tag-red   { background: #fee2e2; color: #991b1b; }
  .tag-grey  { background: #e2e8f0; color: #334155; }
  .activity .text { font-size: 14px; }
  .activity .time { font-size: 12px; color: var(--muted); margin-top: 2px; }

  /* quick actions */
  .actions { display: flex; gap: 12px; flex-wrap: wrap; }
  .actions a {
    text-decoration: none; padding: 11px 20px; border-radius: 10px; color: #fff;
    font-weight: 600; font-size: 14px; background: var(--primary);
  }
  .actions a.teal { background: #0891b2; }
  .actions a.green { background: #16a34a; }
  .actions a:hover { opacity: .9; }

  footer { text-align: center; color: var(--muted); font-size: 13px; padding: 18px; }

  /* ---------- Responsive ---------- */
  @media (max-width: 860px) {
    .layout { flex-direction: column; }
    .sidebar { width: 100%; height: auto; position: static; padding: 16px; }
    .sidebar nav { display: flex; flex-wrap: wrap; gap: 6px; }
    .sidebar nav a { margin: 0; }
    .sidebar .logout { margin-top: 14px; }
    .content { padding: 18px 14px; }
    .topbar { padding: 14px; }
  }
</style>
</head>

<body>
<div class="layout">

  <!-- ================= Sidebar (dynamic menu by role) ================= -->
  <aside class="sidebar">
    <div class="brand">StudentHub<small>Admin Panel</small></div>
    <nav aria-label="Dashboard menu">
      <?php foreach ($menu as $item): ?>
        <?php if (in_array($role, $item[2], true)): ?>
          <a href="<?php echo h($item[1]); ?>"<?php echo $item[3] ? ' class="active"' : ''; ?>><?php echo h($item[0]); ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <a class="logout" href="logout.php">Logout</a>
  </aside>

  <!-- ================= Main content ================= -->
  <div class="main">

    <div class="topbar">
      <div>
        <h1>Admin Dashboard</h1>
        <p><?php echo date('l, d F Y'); ?> &middot; Overview of students, events and activity</p>
      </div>
      <div class="user">
        <div class="avatar"><?php echo h($initial); ?></div>
        <div>
          <strong><?php echo h($userName); ?></strong>
          <span><?php echo h($role); ?></span>
        </div>
      </div>
    </div>

    <main class="content">

      <!-- Statistic cards -->
      <section class="stats" aria-label="Statistics">
        <div class="stat">
          <div class="label">Students</div>
          <div class="value"><?php echo $totalStudents; ?></div>
          <div class="sub"><?php echo $activeStudents; ?> active</div>
        </div>
        <div class="stat c2">
          <div class="label">Events</div>
          <div class="value"><?php echo $totalEvents; ?></div>
          <div class="sub"><?php echo $openEvents; ?> open for registration</div>
        </div>
        <div class="stat c3">
          <div class="label">Registrations</div>
          <div class="value"><?php echo $totalRegs; ?></div>
          <div class="sub">across all events</div>
        </div>
        <div class="stat c4">
          <div class="label">Users</div>
          <div class="value"><?php echo $totalUsers; ?></div>
          <div class="sub">admin, faculty and students</div>
        </div>
      </section>

      <!-- Charts -->
      <div class="grid-2">
        <section class="panel">
          <h2>Registrations per Event</h2>
          <?php if (empty($regsPerEvent)): ?>
            <p class="empty">No events found yet.</p>
          <?php else: ?>
            <?php foreach ($regsPerEvent as $row): ?>
              <div class="bar-row">
                <div class="bar-label">
                  <span><?php echo h($row['event_name']); ?></span>
                  <span><?php echo (int)$row['c']; ?></span>
                </div>
                <div class="bar-track">
                  <div class="bar-fill" style="width: <?php echo round(((int)$row['c'] / $maxEventRegs) * 100); ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </section>

        <section class="panel">
          <h2>Students by Department</h2>
          <?php if (empty($byDepartment)): ?>
            <p class="empty">No students found yet.</p>
          <?php else: ?>
            <?php foreach ($byDepartment as $row): ?>
              <div class="bar-row">
                <div class="bar-label">
                  <span><?php echo h($row['label']); ?></span>
                  <span><?php echo (int)$row['c']; ?></span>
                </div>
                <div class="bar-track">
                  <div class="bar-fill teal" style="width: <?php echo round(((int)$row['c'] / $maxDept) * 100); ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </section>
      </div>

      <!-- Latest registrations + audit trail -->
      <div class="grid-2">
        <section class="panel">
          <h2>Latest Registrations</h2>
          <?php if (empty($latestRegs)): ?>
            <p class="empty">No event registrations yet.</p>
          <?php else: ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>Student</th><th>Event</th><th>Date</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($latestRegs as $r): ?>
                    <tr>
                      <td><?php echo h($r['student_name']); ?><small><?php echo h($r['student_email']); ?></small></td>
                      <td><?php echo h($r['event_name']); ?></td>
                      <td><?php echo h(date('d M, H:i', strtotime($r['registered_at']))); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>

        <section class="panel">
          <h2>Recent Activity (Audit Log)</h2>
          <?php if (empty($recentActivities)): ?>
            <p class="empty">No activity recorded yet. Actions such as login, logout and student changes will appear here.</p>
          <?php else: ?>
            <ul class="activity">
              <?php foreach ($recentActivities as $act): ?>
                <li>
                  <span class="tag <?php echo actionClass($act['action']); ?>"><?php echo h($act['action']); ?></span>
                  <div>
                    <div class="text"><?php echo h($act['details']); ?></div>
                    <div class="time"><?php echo h(date('d M Y, H:i', strtotime($act['created_at']))); ?></div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>
      </div>

      <!-- Quick actions -->
      <section class="panel">
        <h2>Quick Actions</h2>
        <div class="actions">
          <a href="manage-students.php">Manage Students</a>
          <a class="teal" href="manage-events.php">Manage Events &amp; Posters</a>
          <a class="green" href="contact-records.php">View Contact Messages</a>
        </div>
      </section>

    </main>

    <footer>&copy; 2026 StudentHub</footer>
  </div>
</div>
</body>
</html>