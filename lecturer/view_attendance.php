<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$course_id = intval($_GET['course_id']);
$course = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE course_id=$course_id AND lecturer_id={$_SESSION['user_id']}"));
if (!$course) { die("Unauthorized."); }

$records = mysqli_query($conn, "
  SELECT a.*, u.full_name FROM attendance a
  JOIN users u ON a.student_id = u.user_id
  WHERE a.course_id=$course_id
  ORDER BY a.attendance_date DESC, u.full_name ASC
");

$page_title = "Attendance Records";
include '../includes/header.php';
?>

<h3 class="section-title">Attendance Records: <?php echo htmlspecialchars($course['course_name']); ?></h3>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-dark"><tr><th>Date</th><th>Student</th><th>Status</th></tr></thead>
    <tbody>
      <?php if (mysqli_num_rows($records) === 0): ?>
        <tr><td colspan="3" class="text-muted">No attendance records yet.</td></tr>
      <?php endif; ?>
      <?php while ($r = mysqli_fetch_assoc($records)): ?>
      <tr>
        <td><?php echo date('M d, Y', strtotime($r['attendance_date'])); ?></td>
        <td><?php echo htmlspecialchars($r['full_name']); ?></td>
        <td><span class="badge <?php echo $r['status']=='present'?'bg-success':'bg-danger'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<a href="course_detail.php?id=<?php echo $course_id; ?>" class="btn btn-outline-secondary">&larr; Back to Course</a>

<?php include '../includes/footer.php'; ?>
