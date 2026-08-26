<?php
require_once '../includes/auth_check.php';
checkRole('admin');
require_once '../config/db.php';

$message = "";

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM courses WHERE course_id = $id");
    $message = "Course deleted successfully.";
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $c = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM courses WHERE course_id = $id"));
    $new_status = ($c['status'] === 'active') ? 'inactive' : 'active';
    mysqli_query($conn, "UPDATE courses SET status = '$new_status' WHERE course_id = $id");
    $message = "Course status updated.";
}

$courses = mysqli_query($conn, "
  SELECT c.*, u.full_name AS lecturer_name,
    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id) AS enroll_count
  FROM courses c
  JOIN users u ON c.lecturer_id = u.user_id
  ORDER BY c.created_at DESC
");

$page_title = "Manage Courses";
include '../includes/header.php';
?>

<h3 class="section-title">Manage Courses</h3>

<?php if ($message): ?>
  <div class="alert alert-info auto-alert"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-dark">
        <tr><th>Course</th><th>Code</th><th>Lecturer</th><th>Enrolled</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php while ($c = mysqli_fetch_assoc($courses)): ?>
        <tr>
          <td><?php echo htmlspecialchars($c['course_name']); ?></td>
          <td><?php echo htmlspecialchars($c['course_code']); ?></td>
          <td><?php echo htmlspecialchars($c['lecturer_name']); ?></td>
          <td><?php echo $c['enroll_count']; ?> students</td>
          <td>
            <span class="badge <?php echo $c['status']=='active' ? 'bg-success' : 'bg-secondary'; ?>">
              <?php echo ucfirst($c['status']); ?>
            </span>
          </td>
          <td>
            <a href="?toggle=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-toggle2-on"></i></a>
            <a href="?delete=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-msg="Delete this course and all its data?"><i class="bi bi-trash"></i></a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
