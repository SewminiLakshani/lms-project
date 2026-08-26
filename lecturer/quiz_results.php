<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$quiz_id = intval($_GET['quiz_id']);
$quiz = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT q.*, c.course_name, c.course_id FROM quizzes q
  JOIN courses c ON q.course_id = c.course_id
  WHERE q.quiz_id=$quiz_id AND c.lecturer_id={$_SESSION['user_id']}
"));
if (!$quiz) { die("Unauthorized."); }

$attempts = mysqli_query($conn, "
  SELECT qa.*, u.full_name, u.email FROM quiz_attempts qa
  JOIN users u ON qa.student_id = u.user_id
  WHERE qa.quiz_id=$quiz_id ORDER BY qa.score DESC
");

$page_title = "Quiz Results";
include '../includes/header.php';
?>

<h3 class="section-title">Results: <?php echo htmlspecialchars($quiz['title']); ?></h3>
<p class="text-muted"><?php echo htmlspecialchars($quiz['course_name']); ?> &middot; Total Marks: <?php echo $quiz['total_marks']; ?></p>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-dark"><tr><th>Student</th><th>Score</th><th>Percentage</th><th>Attempted On</th></tr></thead>
    <tbody>
      <?php if (mysqli_num_rows($attempts) === 0): ?>
        <tr><td colspan="4" class="text-muted">No attempts yet.</td></tr>
      <?php endif; ?>
      <?php while ($a = mysqli_fetch_assoc($attempts)):
        $pct = $quiz['total_marks'] > 0 ? round(($a['score'] / $quiz['total_marks']) * 100, 1) : 0;
      ?>
      <tr>
        <td><?php echo htmlspecialchars($a['full_name']); ?></td>
        <td><?php echo $a['score']; ?> / <?php echo $quiz['total_marks']; ?></td>
        <td><?php echo $pct; ?>%</td>
        <td><?php echo date('M d, Y h:i A', strtotime($a['attempted_at'])); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<a href="course_detail.php?id=<?php echo $quiz['course_id']; ?>" class="btn btn-outline-secondary">&larr; Back to Course</a>

<?php include '../includes/footer.php'; ?>
