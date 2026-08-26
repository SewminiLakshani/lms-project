<?php
require_once '../includes/auth_check.php';
checkRole('student');
require_once '../config/db.php';

$attempt_id = intval($_GET['attempt_id']);
$student_id = $_SESSION['user_id'];

$attempt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM quiz_attempts WHERE attempt_id=$attempt_id AND student_id=$student_id"));
if (!$attempt) { die("Result not found."); }

$quiz = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM quizzes WHERE quiz_id={$attempt['quiz_id']}"));

$answers = mysqli_query($conn, "
  SELECT qa.*, qq.question_text, qq.option_a, qq.option_b, qq.option_c, qq.option_d, qq.correct_option, qq.marks
  FROM quiz_answers qa
  JOIN quiz_questions qq ON qa.question_id = qq.question_id
  WHERE qa.attempt_id=$attempt_id
");

$pct = $quiz['total_marks'] > 0 ? round(($attempt['score'] / $quiz['total_marks']) * 100, 1) : 0;

$page_title = "Quiz Result";
include '../includes/header.php';
?>

<h3 class="section-title">Quiz Result: <?php echo htmlspecialchars($quiz['title']); ?></h3>

<div class="card p-4 mb-4 text-center">
  <h1 class="fw-bold"><?php echo $attempt['score']; ?> / <?php echo $quiz['total_marks']; ?></h1>
  <p class="text-muted mb-2">You scored <?php echo $pct; ?>%</p>
  <div class="progress">
    <div class="progress-bar bg-<?php echo $pct >= 50 ? 'success' : 'danger'; ?>" style="width: <?php echo $pct; ?>%"></div>
  </div>
</div>

<h5 class="mb-3">Answer Review</h5>
<?php $num = 1; while ($a = mysqli_fetch_assoc($answers)): ?>
  <div class="card p-3 mb-2">
    <p class="fw-bold">Q<?php echo $num++; ?>. <?php echo htmlspecialchars($a['question_text']); ?></p>
    <p class="mb-1">
      Your answer:
      <span class="<?php echo $a['is_correct'] ? 'text-success fw-bold' : 'text-danger fw-bold'; ?>">
        <?php echo $a['selected_option'] ? strtoupper($a['selected_option']) . '. ' . htmlspecialchars($a['option_' . $a['selected_option']]) : 'Not answered'; ?>
      </span>
    </p>
    <?php if (!$a['is_correct']): ?>
      <p class="mb-0 text-success">Correct answer: <?php echo strtoupper($a['correct_option']); ?>. <?php echo htmlspecialchars($a['option_' . $a['correct_option']]); ?></p>
    <?php endif; ?>
  </div>
<?php endwhile; ?>

<a href="course_view.php?id=<?php echo $quiz['course_id']; ?>" class="btn btn-outline-secondary">&larr; Back to Course</a>

<?php include '../includes/footer.php'; ?>
