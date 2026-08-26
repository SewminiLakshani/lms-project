<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$lecturer_id = $_SESSION['user_id'];

$total_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM courses WHERE lecturer_id=$lecturer_id"))['c'];
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT COUNT(DISTINCT e.student_id) c FROM enrollments e
  JOIN courses c ON e.course_id = c.course_id WHERE c.lecturer_id=$lecturer_id"))['c'];
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT COUNT(*) c FROM assignments a JOIN courses c ON a.course_id=c.course_id WHERE c.lecturer_id=$lecturer_id"))['c'];
$pending_grading = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT COUNT(*) c FROM submissions s
  JOIN assignments a ON s.assignment_id = a.assignment_id
  JOIN courses c ON a.course_id = c.course_id
  WHERE c.lecturer_id=$lecturer_id AND s.marks_obtained IS NULL"))['c'];

$my_courses = mysqli_query($conn, "SELECT * FROM courses WHERE lecturer_id=$lecturer_id ORDER BY created_at DESC LIMIT 6");

// -------- Chart data --------

// Students enrolled per course
$course_enroll_result = mysqli_query($conn, "
  SELECT c.course_name, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.course_id) AS c
  FROM courses c WHERE c.lecturer_id=$lecturer_id ORDER BY c.created_at DESC
");
$course_labels = [];
$course_student_counts = [];
while ($row = mysqli_fetch_assoc($course_enroll_result)) {
    $course_labels[] = $row['course_name'];
    $course_student_counts[] = (int)$row['c'];
}

// Submission status: graded vs pending
$graded_count = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT COUNT(*) c FROM submissions s
  JOIN assignments a ON s.assignment_id = a.assignment_id
  JOIN courses c ON a.course_id = c.course_id
  WHERE c.lecturer_id=$lecturer_id AND s.marks_obtained IS NOT NULL"))['c'];

// Average quiz score (%) per quiz
$quiz_avg_result = mysqli_query($conn, "
  SELECT q.title, q.total_marks, AVG(qa.score) AS avg_score
  FROM quizzes q
  JOIN courses c ON q.course_id = c.course_id
  LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.quiz_id
  WHERE c.lecturer_id=$lecturer_id
  GROUP BY q.quiz_id
");
$quiz_labels = [];
$quiz_avg_pct = [];
while ($row = mysqli_fetch_assoc($quiz_avg_result)) {
    $quiz_labels[] = $row['title'];
    $pct = ($row['total_marks'] > 0 && $row['avg_score'] !== null) ? round(($row['avg_score'] / $row['total_marks']) * 100, 1) : 0;
    $quiz_avg_pct[] = $pct;
}

$page_title = "Lecturer Dashboard";
include '../includes/header.php';
?>

<h3 class="section-title">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="stat-card bg-purple">
      <p><i class="bi bi-journal-bookmark-fill"></i> My Courses</p>
      <h2><?php echo $total_courses; ?></h2>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-cyan">
      <p><i class="bi bi-people-fill"></i> Students</p>
      <h2><?php echo $total_students; ?></h2>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-green">
      <p><i class="bi bi-file-earmark-text-fill"></i> Assignments</p>
      <h2><?php echo $total_assignments; ?></h2>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-orange">
      <p><i class="bi bi-hourglass-split"></i> Pending Grading</p>
      <h2><?php echo $pending_grading; ?></h2>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3">Students per Course</h5>
      <canvas id="courseStudentsChart" height="220"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3">Submission Status</h5>
      <canvas id="submissionStatusChart" height="220"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3">Average Quiz Score (%)</h5>
      <canvas id="quizAvgChart" height="220"></canvas>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">My Courses</h5>
  <a href="add_course.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Add New Course</a>
</div>

<div class="row g-3">
  <?php if (mysqli_num_rows($my_courses) === 0): ?>
    <p class="text-muted">You haven't created any courses yet.</p>
  <?php endif; ?>
  <?php while ($c = mysqli_fetch_assoc($my_courses)): ?>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="fw-bold"><?php echo htmlspecialchars($c['course_name']); ?></h6>
          <p class="text-muted small mb-2"><?php echo htmlspecialchars($c['course_code']); ?></p>
          <p class="small"><?php echo htmlspecialchars(mb_strimwidth($c['description'] ?? '', 0, 80, '...')); ?></p>
          <a href="course_detail.php?id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-outline-primary">Manage Course</a>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<script>
// Students per Course - Bar
new Chart(document.getElementById('courseStudentsChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($course_labels); ?>,
    datasets: [{
      label: 'Students',
      data: <?php echo json_encode($course_student_counts); ?>,
      backgroundColor: '#4338ca'
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

// Submission Status - Doughnut
new Chart(document.getElementById('submissionStatusChart'), {
  type: 'doughnut',
  data: {
    labels: ['Graded', 'Pending'],
    datasets: [{
      data: [<?php echo $graded_count; ?>, <?php echo $pending_grading; ?>],
      backgroundColor: ['#16a34a', '#d97706']
    }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

// Average Quiz Score - Bar
new Chart(document.getElementById('quizAvgChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($quiz_labels); ?>,
    datasets: [{
      label: 'Avg Score %',
      data: <?php echo json_encode($quiz_avg_pct); ?>,
      backgroundColor: '#06b6d4'
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, max: 100 } }
  }
});
</script>

<?php include '../includes/footer.php'; ?>
