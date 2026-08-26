<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$lecturer_id = $_SESSION['user_id'];
$courses = mysqli_query($conn, "
  SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.course_id) AS enroll_count
  FROM courses c WHERE lecturer_id=$lecturer_id ORDER BY created_at DESC
");

$page_title = "My Courses";
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="section-title mb-0">My Courses</h3>
  <a href="add_course.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Add New Course</a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
  <div class="alert alert-success auto-alert">Course created successfully!</div>
<?php endif; ?>

<div class="row g-3">
  <?php if (mysqli_num_rows($courses) === 0): ?>
    <p class="text-muted">No courses yet. Click "Add New Course" to get started.</p>
  <?php endif; ?>
  <?php while ($c = mysqli_fetch_assoc($courses)): ?>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <h6 class="fw-bold"><?php echo htmlspecialchars($c['course_name']); ?></h6>
            <span class="badge <?php echo $c['status']=='active' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo ucfirst($c['status']); ?></span>
          </div>
          <p class="text-muted small mb-2"><?php echo htmlspecialchars($c['course_code']); ?> &middot; <?php echo $c['enroll_count']; ?> students</p>
          <p class="small"><?php echo htmlspecialchars(mb_strimwidth($c['description'] ?? '', 0, 90, '...')); ?></p>
          <a href="course_detail.php?id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-outline-primary">Manage</a>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php include '../includes/footer.php'; ?>
