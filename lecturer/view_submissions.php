<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$lecturer_id = $_SESSION['user_id'];

// If assignment_id given, show that assignment's submissions; otherwise show all pending across all courses
$assignment_id = intval($_GET['assignment_id'] ?? 0);

if ($assignment_id > 0) {
    $assignment = mysqli_fetch_assoc(mysqli_query($conn, "
      SELECT a.*, c.course_name, c.course_id FROM assignments a
      JOIN courses c ON a.course_id = c.course_id
      WHERE a.assignment_id=$assignment_id AND c.lecturer_id=$lecturer_id
    "));
    if (!$assignment) { die("Unauthorized or not found."); }

    $submissions = mysqli_query($conn, "
      SELECT s.*, u.full_name, u.email FROM submissions s
      JOIN users u ON s.student_id = u.user_id
      WHERE s.assignment_id=$assignment_id ORDER BY s.submitted_at DESC
    ");
} else {
    $submissions_all = mysqli_query($conn, "
      SELECT s.*, u.full_name, a.title AS assignment_title, a.max_marks, c.course_name, c.course_id
      FROM submissions s
      JOIN users u ON s.student_id = u.user_id
      JOIN assignments a ON s.assignment_id = a.assignment_id
      JOIN courses c ON a.course_id = c.course_id
      WHERE c.lecturer_id = $lecturer_id
      ORDER BY s.submitted_at DESC
    ");
}

$page_title = "Submissions";
include '../includes/header.php';
?>

<?php if ($assignment_id > 0): ?>
  <h3 class="section-title">Submissions: <?php echo htmlspecialchars($assignment['title']); ?></h3>
  <p class="text-muted"><?php echo htmlspecialchars($assignment['course_name']); ?> &middot; Max marks: <?php echo $assignment['max_marks']; ?></p>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-dark"><tr><th>Student</th><th>Submitted</th><th>File</th><th>Marks</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (mysqli_num_rows($submissions) === 0): ?>
        <tr><td colspan="5" class="text-muted">No submissions yet.</td></tr>
      <?php endif; ?>
      <?php while ($s = mysqli_fetch_assoc($submissions)): ?>
        <tr>
          <td><?php echo htmlspecialchars($s['full_name']); ?></td>
          <td><?php echo date('M d, Y h:i A', strtotime($s['submitted_at'])); ?></td>
          <td><a href="/lms/assets/uploads/assignments/<?php echo htmlspecialchars($s['file_path']); ?>" target="_blank">Download</a></td>
          <td><?php echo $s['marks_obtained'] !== null ? $s['marks_obtained'].'/'.$assignment['max_marks'] : '<span class="text-warning">Pending</span>'; ?></td>
          <td>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#grade<?php echo $s['submission_id']; ?>">Grade</button>
          </td>
        </tr>

        <!-- Grade Modal -->
        <div class="modal fade" id="grade<?php echo $s['submission_id']; ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="POST" action="grade_submission.php">
                <input type="hidden" name="submission_id" value="<?php echo $s['submission_id']; ?>">
                <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
                <div class="modal-header">
                  <h5 class="modal-title">Grade Submission - <?php echo htmlspecialchars($s['full_name']); ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label">Marks (out of <?php echo $assignment['max_marks']; ?>)</label>
                    <input type="number" name="marks_obtained" class="form-control" max="<?php echo $assignment['max_marks']; ?>" min="0" value="<?php echo $s['marks_obtained']; ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Feedback</label>
                    <textarea name="feedback" class="form-control" rows="3"><?php echo htmlspecialchars($s['feedback'] ?? ''); ?></textarea>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-primary">Save Grade</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>

<?php else: ?>
  <h3 class="section-title">All Pending & Recent Submissions</h3>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-dark"><tr><th>Student</th><th>Assignment</th><th>Course</th><th>Submitted</th><th>Marks</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (mysqli_num_rows($submissions_all) === 0): ?>
        <tr><td colspan="6" class="text-muted">No submissions yet.</td></tr>
      <?php endif; ?>
      <?php while ($s = mysqli_fetch_assoc($submissions_all)): ?>
        <tr>
          <td><?php echo htmlspecialchars($s['full_name']); ?></td>
          <td><?php echo htmlspecialchars($s['assignment_title']); ?></td>
          <td><?php echo htmlspecialchars($s['course_name']); ?></td>
          <td><?php echo date('M d, Y', strtotime($s['submitted_at'])); ?></td>
          <td><?php echo $s['marks_obtained'] !== null ? $s['marks_obtained'].'/'.$s['max_marks'] : '<span class="text-warning">Pending</span>'; ?></td>
          <td><a href="view_submissions.php?assignment_id=<?php echo $s['assignment_id']; ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
