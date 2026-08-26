<?php
require_once '../includes/auth_check.php';
checkRole('student');
require_once '../config/db.php';

$student_id = $_SESSION['user_id'];

$assignment_grades = mysqli_query($conn, "
  SELECT s.*, a.title, a.max_marks, c.course_name FROM submissions s
  JOIN assignments a ON s.assignment_id = a.assignment_id
  JOIN courses c ON a.course_id = c.course_id
  WHERE s.student_id=$student_id AND s.marks_obtained IS NOT NULL
  ORDER BY s.graded_at DESC
");

$quiz_grades = mysqli_query($conn, "
  SELECT qa.*, q.title, q.total_marks, c.course_name FROM quiz_attempts qa
  JOIN quizzes q ON qa.quiz_id = q.quiz_id
  JOIN courses c ON q.course_id = c.course_id
  WHERE qa.student_id=$student_id
  ORDER BY qa.attempted_at DESC
");

$page_title = "My Grades";
include '../includes/header.php';
?>

<h3 class="section-title">My Grades</h3>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card p-3">
      <h5 class="mb-3">Assignment Grades</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-dark"><tr><th>Assignment</th><th>Course</th><th>Marks</th></tr></thead>
          <tbody>
          <?php if (mysqli_num_rows($assignment_grades) === 0): ?>
            <tr><td colspan="3" class="text-muted">No graded assignments yet.</td></tr>
          <?php endif; ?>
          <?php while ($g = mysqli_fetch_assoc($assignment_grades)): ?>
            <tr>
              <td><?php echo htmlspecialchars($g['title']); ?></td>
              <td><?php echo htmlspecialchars($g['course_name']); ?></td>
              <td><?php echo $g['marks_obtained']; ?>/<?php echo $g['max_marks']; ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card p-3">
      <h5 class="mb-3">Quiz Results</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-dark"><tr><th>Quiz</th><th>Course</th><th>Score</th></tr></thead>
          <tbody>
          <?php if (mysqli_num_rows($quiz_grades) === 0): ?>
            <tr><td colspan="3" class="text-muted">No quizzes attempted yet.</td></tr>
          <?php endif; ?>
          <?php while ($g = mysqli_fetch_assoc($quiz_grades)): ?>
            <tr>
              <td><?php echo htmlspecialchars($g['title']); ?></td>
              <td><?php echo htmlspecialchars($g['course_name']); ?></td>
              <td><?php echo $g['score']; ?>/<?php echo $g['total_marks']; ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

