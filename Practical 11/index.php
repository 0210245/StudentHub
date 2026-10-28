<?php
session_start();
require_once __DIR__ . '/../Practical 8/config.php';

$conn = getMySQLiConnection();
$search = trim($_GET['search'] ?? '');
$dept   = trim($_GET['department'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM students WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}

if (!empty($dept)) {
    $sql .= " AND department = ?";
    $params[] = $dept;
    $types .= "s";
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY student_id DESC";

$students = [];
if ($conn) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $students[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Practical 11 | Student Management CRUD</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <div class="header-box">
      <div>
        <h1>StudentHub | Student Management CRUD</h1>
        <p>Practical 11: MySQLi CRUD with Search, Filter, Soft Delete &amp; CSV Export</p>
      </div>
      <div>
        <button class="btn btn-success" onclick="openAddModal()">+ Add New Student</button>
      </div>
    </div>

    <div style="margin-bottom: 15px; font-size:13px;">
      <a href="../Practical 10/admin_dashboard.php" style="color:var(--primary); font-weight:600; text-decoration:none;">&larr; Practical 10 (Admin)</a>
      <span style="margin: 0 8px; color:#cbd5e1;">|</span>
      <a href="../Practical 12/index.php" style="color:var(--primary); font-weight:600; text-decoration:none;">Practical 12 (Events CRUD) &rarr;</a>
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

    <!-- Filter & Search Toolbar -->
    <div class="card">
      <form method="GET" action="index.php" class="toolbar">
        <input type="search" name="search" placeholder="Search by name, email or enrollment no..." value="<?php echo htmlspecialchars($search); ?>">
        
        <select name="department">
          <option value="">All Departments</option>
          <option value="Computer Engineering" <?php if ($dept === 'Computer Engineering') echo 'selected'; ?>>Computer Engineering</option>
          <option value="Information Technology" <?php if ($dept === 'Information Technology') echo 'selected'; ?>>Information Technology</option>
          <option value="Electronics & Communication" <?php if ($dept === 'Electronics & Communication') echo 'selected'; ?>>Electronics &amp; Communication</option>
          <option value="Mechanical Engineering" <?php if ($dept === 'Mechanical Engineering') echo 'selected'; ?>>Mechanical Engineering</option>
          <option value="Civil Engineering" <?php if ($dept === 'Civil Engineering') echo 'selected'; ?>>Civil Engineering</option>
          <option value="Computer Applications" <?php if ($dept === 'Computer Applications') echo 'selected'; ?>>Computer Applications</option>
        </select>

        <select name="status">
          <option value="">All Statuses</option>
          <option value="active" <?php if ($status === 'active') echo 'selected'; ?>>Active Only</option>
          <option value="inactive" <?php if ($status === 'inactive') echo 'selected'; ?>>Inactive Only (Soft Deleted)</option>
        </select>

        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="export_csv.php?<?php echo http_build_query($_GET); ?>" class="btn btn-warning" title="Intermediate Extension">
          📥 Export CSV
        </a>
      </form>
    </div>

    <!-- Student Records Table -->
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3>Enrolled Students (<?php echo count($students); ?> Found)</h3>
        <small style="color:var(--text-muted);">Database: <code>studenthub.students</code></small>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Enrollment</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Course</th>
              <th>Department</th>
              <th>Year</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr><td colspan="9" style="text-align:center; padding:20px; color:#64748b;">No student records found matching the filter criteria.</td></tr>
            <?php else: ?>
              <?php foreach ($students as $s): ?>
                <tr>
                  <td><code><?php echo htmlspecialchars($s['enrollment_no']); ?></code></td>
                  <td><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($s['email']); ?></td>
                  <td><?php echo htmlspecialchars($s['phone'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($s['course']); ?></td>
                  <td><?php echo htmlspecialchars($s['department']); ?></td>
                  <td>Yr <?php echo htmlspecialchars($s['year']); ?></td>
                  <td>
                    <span class="badge <?php echo $s['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                      <?php echo strtoupper($s['status']); ?>
                    </span>
                  </td>
                  <td>
                    <div class="action-btns">
                      <button class="action-btn" onclick='openEditModal(<?php echo json_encode($s); ?>)'>✏️ Edit</button>
                      
                      <!-- Soft Delete Toggle (Advanced Extension) -->
                      <?php if ($s['status'] === 'active'): ?>
                        <a href="student_actions.php?action=toggle_status&id=<?php echo $s['student_id']; ?>&status=inactive" class="action-btn" style="color:#d97706;" title="Soft Delete">
                          ⏸ Deactivate
                        </a>
                      <?php else: ?>
                        <a href="student_actions.php?action=toggle_status&id=<?php echo $s['student_id']; ?>&status=active" class="action-btn" style="color:#16a34a;" title="Reactivate">
                          ▶ Activate
                        </a>
                      <?php endif; ?>

                      <!-- Permanent Delete with Confirmation -->
                      <a href="student_actions.php?action=delete&id=<?php echo $s['student_id']; ?>" 
                         class="action-btn" style="color:#dc2626;" 
                         onclick="return confirm('Are you sure you want to permanently delete student <?php echo htmlspecialchars($s['enrollment_no']); ?>?');">
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

  <!-- Add/Edit Modal -->
  <div class="modal-overlay" id="studentModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="modalTitle">Add New Student</h3>
        <button class="modal-close" onclick="closeModal()">&times;</button>
      </div>

      <form action="student_actions.php" method="POST" id="studentForm">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="student_id" id="studentId" value="">

        <div class="form-grid">
          <div class="form-group">
            <label for="m_enrollment">Enrollment No *</label>
            <input type="text" id="m_enrollment" name="enrollment_no" required>
          </div>
          <div class="form-group">
            <label for="m_name">Full Name *</label>
            <input type="text" id="m_name" name="full_name" required>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label for="m_email">Email *</label>
            <input type="email" id="m_email" name="email" required>
          </div>
          <div class="form-group">
            <label for="m_phone">Phone</label>
            <input type="text" id="m_phone" name="phone">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label for="m_course">Course *</label>
            <select id="m_course" name="course" required>
              <option value="B.Tech">B.Tech</option>
              <option value="M.Tech">M.Tech</option>
              <option value="BCA">BCA</option>
              <option value="MCA">MCA</option>
            </select>
          </div>
          <div class="form-group">
            <label for="m_year">Year *</label>
            <select id="m_year" name="year" required>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="m_dept">Department *</label>
          <select id="m_dept" name="department" required>
            <option value="Computer Engineering">Computer Engineering</option>
            <option value="Information Technology">Information Technology</option>
            <option value="Electronics & Communication">Electronics & Communication</option>
            <option value="Mechanical Engineering">Mechanical Engineering</option>
            <option value="Civil Engineering">Civil Engineering</option>
            <option value="Computer Applications">Computer Applications</option>
          </select>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
          <button type="button" class="btn" style="background:#e2e8f0; color:#475569;" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveBtn">Save Student</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const modal = document.getElementById('studentModal');
    const form = document.getElementById('studentForm');

    function openAddModal() {
      document.getElementById('modalTitle').textContent = 'Add New Student';
      document.getElementById('formAction').value = 'add';
      document.getElementById('studentId').value = '';
      form.reset();
      modal.classList.add('active');
    }

    function openEditModal(stu) {
      document.getElementById('modalTitle').textContent = 'Edit Student: ' + stu.enrollment_no;
      document.getElementById('formAction').value = 'edit';
      document.getElementById('studentId').value = stu.student_id;
      document.getElementById('m_enrollment').value = stu.enrollment_no;
      document.getElementById('m_name').value = stu.full_name;
      document.getElementById('m_email').value = stu.email;
      document.getElementById('m_phone').value = stu.phone || '';
      document.getElementById('m_course').value = stu.course;
      document.getElementById('m_year').value = stu.year;
      document.getElementById('m_dept').value = stu.department;
      modal.classList.add('active');
    }

    function closeModal() {
      modal.classList.remove('active');
    }
  </script>
</body>
</html>
