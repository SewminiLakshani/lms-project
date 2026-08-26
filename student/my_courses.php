<?php
require_once '../includes/auth_check.php';
checkRole('student');
require_once '../config/db.php';

$student_id = $_SESSION['user_id'];
$courses = mysqli_query($conn, "
  SELECT c.*, u.full_name AS lecturer_name FROM enrollments e
  JOIN courses c ON e.course_id = c.course_id
  JOIN users u ON c.lecturer_id = u.user_id
  WHERE e.student_id=$student_id
  ORDER BY e.enrolled_at DESC
");

$page_title = "My Courses";
include '../includes/header.php';
?>

<h3 class="section-title">My Enrolled Courses</h3>

<div class="row g-3">
  <?php if (mysqli_num_rows($courses) === 0): ?>
    <p class="text-muted">You haven't enrolled in any courses yet. <a href="browse_courses.php">Browse courses</a> to get started.</p>
  <?php endif; ?>
  <?php while ($c = mysqli_fetch_assoc($courses)): ?>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="fw-bold"><?php echo htmlspecialchars($c['course_name']); ?></h6>
          <p class="text-muted small mb-2"><?php echo htmlspecialchars($c['course_code']); ?> &middot; <?php echo htmlspecialchars($c['lecturer_name']); ?></p>
          <a href="course_view.php?id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-outline-primary">Open Course</a>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php include '../includes/footer.php'; ?>
