<?php
require_once '../includes/auth_check.php';
checkRole('student');
require_once '../config/db.php';

$student_id = $_SESSION['user_id'];

$courses = mysqli_query($conn, "
  SELECT c.*, u.full_name AS lecturer_name,
    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.course_id) AS enroll_count,
    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.course_id AND e.student_id=$student_id) AS is_enrolled
  FROM courses c
  JOIN users u ON c.lecturer_id = u.user_id
  WHERE c.status = 'active'
  ORDER BY c.created_at DESC
");

$page_title = "Browse Courses";
include '../includes/header.php';
?>

<h3 class="section-title">Browse Courses</h3>

<?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success auto-alert"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<div class="mb-3">
  <input type="text" id="courseSearch" class="form-control" placeholder="Search courses by name, code, or category...">
</div>

<div class="row g-3">
  <?php while ($c = mysqli_fetch_assoc($courses)): ?>
    <div class="col-md-4 course-card">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <h6 class="fw-bold"><?php echo htmlspecialchars($c['course_name']); ?></h6>
          </div>
          <p class="text-muted small mb-1"><?php echo htmlspecialchars($c['course_code']); ?> &middot; <?php echo htmlspecialchars($c['category']); ?></p>
          <p class="text-muted small mb-2">By <?php echo htmlspecialchars($c['lecturer_name']); ?> &middot; <?php echo $c['enroll_count']; ?> students</p>
          <p class="small"><?php echo htmlspecialchars(mb_strimwidth($c['description'] ?? '', 0, 90, '...')); ?></p>

          <?php if ($c['is_enrolled'] > 0): ?>
            <button class="btn btn-sm btn-success w-100" disabled><i class="bi bi-check-circle"></i> Enrolled</button>
          <?php else: ?>
            <a href="enroll.php?course_id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-primary w-100">Enroll Now</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<script>
filterCourses('courseSearch', 'course-card');
</script>

<?php include '../includes/footer.php'; ?>
