<?php
require_once '../includes/auth_check.php';
checkRole('student');
require_once '../config/db.php';

$student_id = $_SESSION['user_id'];
$quiz_id = intval($_GET['quiz_id'] ?? $_POST['quiz_id'] ?? 0);

$quiz = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM quizzes WHERE quiz_id=$quiz_id"));
if (!$quiz) { die("Quiz not found."); }

// Confirm enrollment
$enrolled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM enrollments WHERE course_id={$quiz['course_id']} AND student_id=$student_id"));
if (!$enrolled) { die("You are not enrolled in this course."); }

// Already attempted?
$already = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM quiz_attempts WHERE quiz_id=$quiz_id AND student_id=$student_id"));
if ($already) {
    header("Location: course_view.php?id={$quiz['course_id']}&msg=You have already completed this quiz");
    exit();
}

// ---------------- HANDLE SUBMISSION ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $answers = $_POST['answer'] ?? []; // [question_id => option]
    $questions = mysqli_query($conn, "SELECT * FROM quiz_questions WHERE quiz_id=$quiz_id");

    $total_score = 0;
    $answer_rows = [];

    while ($q = mysqli_fetch_assoc($questions)) {
        $selected = $answers[$q['question_id']] ?? null;
        $is_correct = ($selected === $q['correct_option']) ? 1 : 0;
        if ($is_correct) $total_score += $q['marks'];
        $answer_rows[] = [$q['question_id'], $selected, $is_correct];
    }

    // Create attempt
    $stmt = mysqli_prepare($conn, "INSERT INTO quiz_attempts (quiz_id, student_id, score) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iii", $quiz_id, $student_id, $total_score);
    mysqli_stmt_execute($stmt);
    $attempt_id = mysqli_insert_id($conn);

    foreach ($answer_rows as $row) {
        [$qid, $selected, $is_correct] = $row;
        $stmt2 = mysqli_prepare($conn, "INSERT INTO quiz_answers (attempt_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt2, "iisi", $attempt_id, $qid, $selected, $is_correct);
        mysqli_stmt_execute($stmt2);
    }

    header("Location: quiz_result.php?attempt_id=$attempt_id");
    exit();
}

// ---------------- SHOW QUIZ ----------------
$questions = mysqli_query($conn, "SELECT * FROM quiz_questions WHERE quiz_id=$quiz_id ORDER BY question_id ASC");

if (mysqli_num_rows($questions) === 0) {
    die("This quiz has no questions yet. Please check back later.");
}

$page_title = $quiz['title'];
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="section-title mb-0"><?php echo htmlspecialchars($quiz['title']); ?></h3>
  <div class="badge bg-danger fs-6" id="timerDisplay">--:--</div>
</div>
<p class="text-muted">You have <?php echo $quiz['duration_minutes']; ?> minutes. The quiz will auto-submit when time runs out.</p>

<form method="POST" action="" id="quizForm">
  <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
  <?php $num = 1; while ($q = mysqli_fetch_assoc($questions)): ?>
    <div class="card p-3 mb-3">
      <p class="fw-bold">Q<?php echo $num++; ?>. <?php echo htmlspecialchars($q['question_text']); ?> <span class="badge bg-secondary"><?php echo $q['marks']; ?> mark(s)</span></p>
      <label class="quiz-option"><input type="radio" name="answer[<?php echo $q['question_id']; ?>]" value="a" required> A. <?php echo htmlspecialchars($q['option_a']); ?></label>
      <label class="quiz-option"><input type="radio" name="answer[<?php echo $q['question_id']; ?>]" value="b"> B. <?php echo htmlspecialchars($q['option_b']); ?></label>
      <label class="quiz-option"><input type="radio" name="answer[<?php echo $q['question_id']; ?>]" value="c"> C. <?php echo htmlspecialchars($q['option_c']); ?></label>
      <label class="quiz-option"><input type="radio" name="answer[<?php echo $q['question_id']; ?>]" value="d"> D. <?php echo htmlspecialchars($q['option_d']); ?></label>
    </div>
  <?php endwhile; ?>

  <button type="submit" name="submit_quiz" class="btn btn-primary w-100">Submit Quiz</button>
</form>

<script>
startQuizTimer(<?php echo $quiz['duration_minutes']; ?>, 'timerDisplay', 'quizForm');
</script>

<?php include '../includes/footer.php'; ?>
