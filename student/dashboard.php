<?php
require_once '../includes/auth_check.php';
checkRole('student');
require_once '../config/db.php';

$student_id = $_SESSION['user_id'];

$total_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM enrollments WHERE student_id=$student_id"))['c'];

$pending_assignments = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT COUNT(*) c FROM assignments a
  JOIN enrollments e ON a.course_id = e.course_id
  LEFT JOIN submissions s ON s.assignment_id = a.assignment_id AND s.student_id = $student_id
  WHERE e.student_id = $student_id AND s.submission_id IS NULL AND a.deadline > NOW()
"))['c'];

$quizzes_taken = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM quiz_attempts WHERE student_id=$student_id"))['c'];

$avg_marks = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT AVG(marks_obtained) avg_m FROM submissions WHERE student_id=$student_id AND marks_obtained IS NOT NULL
"))['avg_m'];

// Upcoming deadlines
$upcoming = mysqli_query($conn, "
  SELECT a.*, c.course_name FROM assignments a
  JOIN enrollments e ON a.course_id = e.course_id
  JOIN courses c ON a.course_id = c.course_id
  LEFT JOIN submissions s ON s.assignment_id = a.assignment_id AND s.student_id = $student_id
  WHERE e.student_id = $student_id AND s.submission_id IS NULL AND a.deadline > NOW()
  ORDER BY a.deadline ASC LIMIT 5
");

// Recent announcements (global + enrolled courses)
$announcements = mysqli_query($conn, "
  SELECT an.*, c.course_name FROM announcements an
  LEFT JOIN courses c ON an.course_id = c.course_id
  WHERE an.course_id IS NULL OR an.course_id IN (SELECT course_id FROM enrollments WHERE student_id=$student_id)
  ORDER BY an.created_at DESC LIMIT 5
");

// -------- Chart data --------

// Assignment marks (as % of max) for graded submissions
$assignment_marks_result = mysqli_query($conn, "
  SELECT a.title, s.marks_obtained, a.max_marks FROM submissions s
  JOIN assignments a ON s.assignment_id = a.assignment_id
  WHERE s.student_id=$student_id AND s.marks_obtained IS NOT NULL
  ORDER BY s.graded_at ASC
");
$assignment_labels = [];
$assignment_pct = [];
while ($row = mysqli_fetch_assoc($assignment_marks_result)) {
    $assignment_labels[] = $row['title'];
    $assignment_pct[] = $row['max_marks'] > 0 ? round(($row['marks_obtained'] / $row['max_marks']) * 100, 1) : 0;
}

// Quiz scores ( %)
$quiz_scores_result = mysqli_query($conn, "
  SELECT q.title, qa.score, q.total_marks FROM quiz_attempts qa
  JOIN quizzes q ON qa.quiz_id = q.quiz_id
  WHERE qa.student_id=$student_id
  ORDER BY qa.attempted_at ASC
");
$quiz_labels = [];
$quiz_pct = [];
while ($row = mysqli_fetch_assoc($quiz_scores_result)) {
    $quiz_labels[] = $row['title'];
    $quiz_pct[] = $row['total_marks'] > 0 ? round(($row['score'] / $row['total_marks']) * 100, 1) : 0;
}

// Attendance summary
$present_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM attendance WHERE student_id=$student_id AND status='present'"))['c'];
$absent_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM attendance WHERE student_id=$student_id AND status='absent'"))['c'];

$page_title = "Student Dashboard";
include '../includes/header.php';
?>

<h3 class="section-title">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="stat-card bg-purple">
      <p><i class="bi bi-journal-bookmark-fill"></i> Enrolled Courses</p>
      <h2><?php echo $total_courses; ?></h2>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-orange">
      <p><i class="bi bi-hourglass-split"></i> Pending Assignments</p>
      <h2><?php echo $pending_assignments; ?></h2>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-cyan">
      <p><i class="bi bi-patch-question-fill"></i> Quizzes Taken</p>
      <h2><?php echo $quizzes_taken; ?></h2>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-green">
      <p><i class="bi bi-graph-up"></i> Avg. Assignment Marks</p>
      <h2><?php echo $avg_marks ? round($avg_marks,1) : '-'; ?></h2>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3">Assignment Marks (%)</h5>
      <canvas id="assignmentMarksChart" height="220"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3">Quiz Scores (%)</h5>
      <canvas id="quizScoresChart" height="220"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3">Attendance</h5>
      <canvas id="attendanceChart" height="220"></canvas>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card p-3">
      <h5 class="mb-3">Upcoming Deadlines</h5>
      <?php if (mysqli_num_rows($upcoming) === 0): ?>
        <p class="text-muted">No pending assignments. You're all caught up!</p>
      <?php endif; ?>
      <?php while ($u = mysqli_fetch_assoc($upcoming)): ?>
        <div class="d-flex justify-content-between border-bottom py-2">
          <div>
            <strong><?php echo htmlspecialchars($u['title']); ?></strong><br>
            <small class="text-muted"><?php echo htmlspecialchars($u['course_name']); ?></small>
          </div>
          <span class="badge bg-warning text-dark align-self-center"><?php echo date('M d, h:i A', strtotime($u['deadline'])); ?></span>
        </div>
      <?php endwhile; ?>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card p-3">
      <h5 class="mb-3">Recent Announcements</h5>
      <?php if (mysqli_num_rows($announcements) === 0): ?>
        <p class="text-muted">No announcements yet.</p>
      <?php endif; ?>
      <?php while ($a = mysqli_fetch_assoc($announcements)): ?>
        <div class="border-bottom py-2">
          <strong><?php echo htmlspecialchars($a['title']); ?></strong>
          <span class="badge bg-secondary"><?php echo $a['course_name'] ? htmlspecialchars($a['course_name']) : 'Global'; ?></span>
          <p class="mb-0 small"><?php echo htmlspecialchars($a['message']); ?></p>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>

<script>
// Assignment Marks - Bar
new Chart(document.getElementById('assignmentMarksChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($assignment_labels); ?>,
    datasets: [{
      label: 'Marks %',
      data: <?php echo json_encode($assignment_pct); ?>,
      backgroundColor: '#4338ca'
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, max: 100 } }
  }
});

// Quiz Scores - Bar
new Chart(document.getElementById('quizScoresChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($quiz_labels); ?>,
    datasets: [{
      label: 'Score %',
      data: <?php echo json_encode($quiz_pct); ?>,
      backgroundColor: '#06b6d4'
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, max: 100 } }
  }
});

// Attendance - Doughnut
new Chart(document.getElementById('attendanceChart'), {
  type: 'doughnut',
  data: {
    labels: ['Present', 'Absent'],
    datasets: [{
      data: [<?php echo $present_count; ?>, <?php echo $absent_count; ?>],
      backgroundColor: ['#16a34a', '#dc2626']
    }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include '../includes/footer.php'; ?>
