<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$quiz_id = intval($_GET['quiz_id']);
$lecturer_id = $_SESSION['user_id'];

$quiz = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT q.*, c.course_name, c.course_id FROM quizzes q
  JOIN courses c ON q.course_id = c.course_id
  WHERE q.quiz_id=$quiz_id AND c.lecturer_id=$lecturer_id
"));
if (!$quiz) { die("Unauthorized or quiz not found."); }

$message = "";

// Add question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_option = $_POST['correct_option'];
    $marks = intval($_POST['marks'] ?: 1);

    $stmt = mysqli_prepare($conn, "INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issssssi", $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option, $marks);
    mysqli_stmt_execute($stmt);

    // Update quiz total marks
    mysqli_query($conn, "UPDATE quizzes SET total_marks = (SELECT SUM(marks) FROM quiz_questions WHERE quiz_id=$quiz_id) WHERE quiz_id=$quiz_id");

    $message = "Question added.";
}

// Delete question
if (isset($_GET['delete'])) {
    $qid = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM quiz_questions WHERE question_id=$qid AND quiz_id=$quiz_id");
    mysqli_query($conn, "UPDATE quizzes SET total_marks = COALESCE((SELECT SUM(marks) FROM quiz_questions WHERE quiz_id=$quiz_id),0) WHERE quiz_id=$quiz_id");
    $message = "Question deleted.";
}

$questions = mysqli_query($conn, "SELECT * FROM quiz_questions WHERE quiz_id=$quiz_id ORDER BY question_id ASC");

$page_title = "Manage Quiz Questions";
include '../includes/header.php';
?>

<h3 class="section-title"><?php echo htmlspecialchars($quiz['title']); ?></h3>
<p class="text-muted"><?php echo htmlspecialchars($quiz['course_name']); ?> &middot; <?php echo $quiz['duration_minutes']; ?> minutes</p>

<?php if ($message): ?><div class="alert alert-info auto-alert"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="card p-3 mb-4">
  <h6 class="mb-3">Add MCQ Question</h6>
  <form method="POST" action="">
    <div class="mb-3">
      <label class="form-label">Question</label>
      <textarea name="question_text" class="form-control" rows="2" required></textarea>
    </div>
    <div class="row g-2 mb-2">
      <div class="col-md-6"><input type="text" name="option_a" class="form-control" placeholder="Option A" required></div>
      <div class="col-md-6"><input type="text" name="option_b" class="form-control" placeholder="Option B" required></div>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-md-6"><input type="text" name="option_c" class="form-control" placeholder="Option C" required></div>
      <div class="col-md-6"><input type="text" name="option_d" class="form-control" placeholder="Option D" required></div>
    </div>
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Correct Option</label>
        <select name="correct_option" class="form-select" required>
          <option value="a">Option A</option>
          <option value="b">Option B</option>
          <option value="c">Option C</option>
          <option value="d">Option D</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Marks</label>
        <input type="number" name="marks" class="form-control" value="1" min="1">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button type="submit" name="add_question" class="btn btn-primary w-100">Add Question</button>
      </div>
    </div>
  </form>
</div>

<h6 class="mb-3">Questions (<?php echo mysqli_num_rows($questions); ?>)</h6>
<?php while ($q = mysqli_fetch_assoc($questions)): ?>
  <div class="card p-3 mb-2">
    <div class="d-flex justify-content-between">
      <p class="fw-bold mb-2"><?php echo htmlspecialchars($q['question_text']); ?> <span class="badge bg-secondary"><?php echo $q['marks']; ?> mark(s)</span></p>
      <a href="?quiz_id=<?php echo $quiz_id; ?>&delete=<?php echo $q['question_id']; ?>" class="text-danger confirm-delete" data-msg="Delete this question?"><i class="bi bi-trash"></i></a>
    </div>
    <ul class="mb-0">
      <li class="<?php echo $q['correct_option']=='a'?'text-success fw-bold':''; ?>">A. <?php echo htmlspecialchars($q['option_a']); ?></li>
      <li class="<?php echo $q['correct_option']=='b'?'text-success fw-bold':''; ?>">B. <?php echo htmlspecialchars($q['option_b']); ?></li>
      <li class="<?php echo $q['correct_option']=='c'?'text-success fw-bold':''; ?>">C. <?php echo htmlspecialchars($q['option_c']); ?></li>
      <li class="<?php echo $q['correct_option']=='d'?'text-success fw-bold':''; ?>">D. <?php echo htmlspecialchars($q['option_d']); ?></li>
    </ul>
  </div>
<?php endwhile; ?>

<a href="course_detail.php?id=<?php echo $quiz['course_id']; ?>" class="btn btn-outline-secondary mt-2">&larr; Back to Course</a>

<?php include '../includes/footer.php'; ?>
