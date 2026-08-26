<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name = trim($_POST['course_name']);
    $course_code = trim($_POST['course_code']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $lecturer_id = $_SESSION['user_id'];

    if ($course_name === "" || $course_code === "") {
        $error = "Course name and code are required.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO courses (course_name, course_code, description, category, lecturer_id) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssi", $course_name, $course_code, $description, $category, $lecturer_id);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: my_courses.php?msg=added");
            exit();
        } else {
            $error = "Course code must be unique. Please try a different code.";
        }
    }
}

$page_title = "Add Course";
include '../includes/header.php';
?>

<h3 class="section-title">Add New Course</h3>

<?php if ($error): ?>
  <div class="alert alert-danger auto-alert"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card p-4" style="max-width:600px;">
  <form method="POST" action="">
    <div class="mb-3">
      <label class="form-label">Course Name</label>
      <input type="text" name="course_name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Course Code</label>
      <input type="text" name="course_code" class="form-control" placeholder="e.g. HNDIT-3201" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Category</label>
      <input type="text" name="category" class="form-control" placeholder="e.g. Web Development">
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="4"></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Create Course</button>
    <a href="my_courses.php" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
