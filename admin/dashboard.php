<?php
require_once '../includes/auth_check.php';
checkRole('admin');
require_once '../config/db.php';

// Stats
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='student'"))['c'];
$total_lecturers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='lecturer'"))['c'];
$total_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM courses"))['c'];
$total_enrollments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM enrollments"))['c'];

// Recent users
$recent_users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
// Recent courses
$recent_courses = mysqli_query($conn, "SELECT c.*, u.full_name AS lecturer_name FROM courses c JOIN users u ON c.lecturer_id = u.user_id ORDER BY c.created_at DESC LIMIT 5");

// -------- Chart data --------
$total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='admin'"))['c'];

// Courses by category
$cat_result = mysqli_query($conn, "SELECT COALESCE(NULLIF(category,''),'Uncategorized') AS cat, COUNT(*) AS c FROM courses GROUP BY cat ORDER BY c DESC");
$category_labels = [];
$category_counts = [];
while ($row = mysqli_fetch_assoc($cat_result)) {
    $category_labels[] = $row['cat'];
    $category_counts[] = (int)$row['c'];
}

// Enrollment trend - last 6 months
$months_labels = [];
$months_counts = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM enrollments WHERE DATE_FORMAT(enrolled_at,'%Y-%m') = '$ym'"))['c'];
    $months_labels[] = $label;
    $months_counts[] = (int)$count;
}

$page_title = "Admin Dashboard";
include '../includes/header.php';
?>

<h3 class="section-title">Admin Dashboard</h3>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="stat-card bg-purple">
      <p><i class="bi bi-people-fill"></i> Students</p>
      <h2><?php echo $total_students; ?></h2>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-cyan">
      <p><i class="bi bi-person-badge-fill"></i> Lecturers</p>
      <h2><?php echo $total_lecturers; ?></h2>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-green">
      <p><i class="bi bi-journal-bookmark-fill"></i> Courses</p>
      <h2><?php echo $total_courses; ?></h2>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-orange">
      <p><i class="bi bi-clipboard-check-fill"></i> Enrollments</p>
      <h2><?php echo $total_enrollments; ?></h2>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3">Users by Role</h5>
      <canvas id="usersRoleChart" height="220"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3">Courses by Category</h5>
      <canvas id="courseCategoryChart" height="220"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3">Enrollments (Last 6 Months)</h5>
      <canvas id="enrollmentTrendChart" height="220"></canvas>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card p-3">
      <h5 class="mb-3">Recently Registered Users</h5>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>Name</th>
              <th>Role</th>
              <th>Joined</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($u = mysqli_fetch_assoc($recent_users)): ?>
            <tr>
              <td><?php echo htmlspecialchars($u['full_name']); ?></td>
              <td><span class="badge badge-role-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
              <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <a href="manage_users.php" class="btn btn-sm btn-outline-primary">Manage All Users</a>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card p-3">
      <h5 class="mb-3">Recently Added Courses</h5>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-dark"><tr><th>Course</th><th>Lecturer</th><th>Added</th></tr></thead>
          <tbody>
            <?php while ($c = mysqli_fetch_assoc($recent_courses)): ?>
            <tr>
              <td><?php echo htmlspecialchars($c['course_name']); ?></td>
              <td><?php echo htmlspecialchars($c['lecturer_name']); ?></td>
              <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <a href="manage_courses.php" class="btn btn-sm btn-outline-primary">Manage All Courses</a>
    </div>
  </div>
</div>

<script>
// Users by Role - Doughnut
new Chart(document.getElementById('usersRoleChart'), {
  type: 'doughnut',
  data: {
    labels: ['Students', 'Lecturers', 'Admins'],
    datasets: [{
      data: [<?php echo $total_students; ?>, <?php echo $total_lecturers; ?>, <?php echo $total_admins; ?>],
      backgroundColor: ['#16a34a', '#0891b2', '#7c3aed']
    }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

// Courses by Category - Bar
new Chart(document.getElementById('courseCategoryChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($category_labels); ?>,
    datasets: [{
      label: 'Courses',
      data: <?php echo json_encode($category_counts); ?>,
      backgroundColor: '#4338ca'
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

// Enrollment Trend - Line
new Chart(document.getElementById('enrollmentTrendChart'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode($months_labels); ?>,
    datasets: [{
      label: 'Enrollments',
      data: <?php echo json_encode($months_counts); ?>,
      borderColor: '#06b6d4',
      backgroundColor: 'rgba(6,182,212,0.15)',
      tension: 0.35,
      fill: true
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});
</script>

<?php include '../includes/footer.php'; ?>
