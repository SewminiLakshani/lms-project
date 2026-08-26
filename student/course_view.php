<?php
require_once '../includes/auth_check.php';
checkRole('student');
require_once '../config/db.php';

$course_id = intval($_GET['id']);
$student_id = $_SESSION['user_id'];

// Confirm enrollment
$enrolled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM enrollments WHERE course_id=$course_id AND student_id=$student_id"));
if (!$enrolled) { die("You are not enrolled in this course."); }

$course = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT c.*, u.full_name AS lecturer_name FROM courses c
  JOIN users u ON c.lecturer_id = u.user_id WHERE c.course_id=$course_id
"));

$materials = mysqli_query($conn, "SELECT * FROM materials WHERE course_id=$course_id ORDER BY uploaded_at DESC");

$assignments = mysqli_query($conn, "
  SELECT a.*, s.submission_id, s.marks_obtained, s.feedback, s.submitted_at
  FROM assignments a
  LEFT JOIN submissions s ON s.assignment_id = a.assignment_id AND s.student_id = $student_id
  WHERE a.course_id=$course_id ORDER BY a.deadline ASC
");

$quizzes = mysqli_query($conn, "
  SELECT q.*, qa.score, qa.attempted_at
  FROM quizzes q
  LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.quiz_id AND qa.student_id = $student_id
  WHERE q.course_id=$course_id ORDER BY q.created_at DESC
");

$announcements = mysqli_query($conn, "SELECT * FROM announcements WHERE course_id=$course_id ORDER BY created_at DESC");

$page_title = $course['course_name'];
include '../includes/header.php';
?>

<h3 class="section-title"><?php echo htmlspecialchars($course['course_name']); ?></h3>
<p class="text-muted"><?php echo htmlspecialchars($course['course_code']); ?> &middot; Lecturer: <?php echo htmlspecialchars($course['lecturer_name']); ?></p>
<p><?php echo htmlspecialchars($course['description']); ?></p>
<a href="certificate.php?course_id=<?php echo $course_id; ?>" class="btn btn-sm btn-outline-warning mb-3"><i class="bi bi-award"></i> View / Download Certificate</a>

<?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success auto-alert"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#materials">Materials</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#assignments">Assignments</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#quizzes">Quizzes</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#announce">Announcements</button></li>
</ul>

<div class="tab-content">

  <div class="tab-pane fade show active" id="materials">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-dark"><tr><th>Title</th><th>Type</th><th>Uploaded</th><th>Action</th></tr></thead>
        <tbody>
        <?php if (mysqli_num_rows($materials) === 0): ?>
          <tr><td colspan="4" class="text-muted">No materials uploaded yet.</td></tr>
        <?php endif; ?>
        <?php while ($m = mysqli_fetch_assoc($materials)): ?>
          <tr>
            <td><?php echo htmlspecialchars($m['title']); ?></td>
            <td><span class="badge bg-info text-dark"><?php echo str_replace('_',' ',$m['material_type']); ?></span></td>
            <td><?php echo date('M d, Y', strtotime($m['uploaded_at'])); ?></td>
            <td>
              <?php if ($m['material_type']=='video_link'): ?>
                <a href="<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Watch</a>
              <?php else: ?>
                <a href="/lms/assets/uploads/materials/<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Download</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="tab-pane fade" id="assignments">
    <?php if (mysqli_num_rows($assignments) === 0): ?>
      <p class="text-muted">No assignments posted yet.</p>
    <?php endif; ?>
    <?php while ($a = mysqli_fetch_assoc($assignments)):
      $is_late = strtotime($a['deadline']) < time();
    ?>
      <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between flex-wrap">
          <div>
            <h6 class="fw-bold"><?php echo htmlspecialchars($a['title']); ?></h6>
            <p class="small mb-1"><?php echo htmlspecialchars($a['description']); ?></p>
            <small class="text-muted">Deadline: <?php echo date('M d, Y h:i A', strtotime($a['deadline'])); ?> &middot; Max Marks: <?php echo $a['max_marks']; ?></small>
          </div>
          <div class="text-end">
            <?php if ($a['submission_id']): ?>
              <span class="badge bg-success mb-1">Submitted</span><br>
              <?php if ($a['marks_obtained'] !== null): ?>
                <span class="badge bg-primary">Marks: <?php echo $a['marks_obtained']; ?>/<?php echo $a['max_marks']; ?></span>
                <?php if ($a['feedback']): ?><p class="small mt-1 mb-0"><em>"<?php echo htmlspecialchars($a['feedback']); ?>"</em></p><?php endif; ?>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Awaiting Grade</span>
              <?php endif; ?>
            <?php elseif ($is_late): ?>
              <span class="badge bg-danger">Deadline Passed</span>
            <?php else: ?>
              <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#submit<?php echo $a['assignment_id']; ?>">Submit</button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (!$a['submission_id'] && !$is_late): ?>
      <div class="modal fade" id="submit<?php echo $a['assignment_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="POST" action="submit_assignment.php" enctype="multipart/form-data">
              <input type="hidden" name="assignment_id" value="<?php echo $a['assignment_id']; ?>">
              <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
              <div class="modal-header">
                <h5 class="modal-title">Submit: <?php echo htmlspecialchars($a['title']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <label class="form-label">Upload your file</label>
                <input type="file" name="submission_file" class="form-control" required>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Submit Assignment</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endif; ?>
    <?php endwhile; ?>
  </div>

  <div class="tab-pane fade" id="quizzes">
    <?php if (mysqli_num_rows($quizzes) === 0): ?>
      <p class="text-muted">No quizzes available yet.</p>
    <?php endif; ?>
    <?php while ($q = mysqli_fetch_assoc($quizzes)): ?>
      <div class="card p-3 mb-3 d-flex flex-row justify-content-between align-items-center flex-wrap">
        <div>
          <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($q['title']); ?></h6>
          <small class="text-muted"><?php echo $q['duration_minutes']; ?> minutes &middot; <?php echo $q['total_marks']; ?> marks</small>
        </div>
        <div>
          <?php if ($q['score'] !== null): ?>
            <span class="badge bg-success">Completed: <?php echo $q['score']; ?>/<?php echo $q['total_marks']; ?></span>
          <?php else: ?>
            <a href="take_quiz.php?quiz_id=<?php echo $q['quiz_id']; ?>" class="btn btn-sm btn-primary">Start Quiz</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

  <div class="tab-pane fade" id="announce">
    <?php if (mysqli_num_rows($announcements) === 0): ?>
      <p class="text-muted">No announcements for this course yet.</p>
    <?php endif; ?>
    <?php while ($an = mysqli_fetch_assoc($announcements)): ?>
      <div class="card p-3 mb-2">
        <h6 class="fw-bold"><?php echo htmlspecialchars($an['title']); ?></h6>
        <p class="mb-1"><?php echo htmlspecialchars($an['message']); ?></p>
        <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($an['created_at'])); ?></small>
      </div>
    <?php endwhile; ?>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
