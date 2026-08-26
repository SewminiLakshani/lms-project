<?php
require_once '../includes/auth_check.php';
checkRole('student');
require_once '../config/db.php';

$assignment_id = intval($_POST['assignment_id']);
$course_id = intval($_POST['course_id']);
$student_id = $_SESSION['user_id'];

// Confirm enrollment
$enrolled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM enrollments WHERE course_id=$course_id AND student_id=$student_id"));
if (!$enrolled) { die("You are not enrolled in this course."); }

// Confirm deadline not passed
$assignment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM assignments WHERE assignment_id=$assignment_id AND course_id=$course_id"));
if (!$assignment) { die("Assignment not found."); }
if (strtotime($assignment['deadline']) < time()) {
    header("Location: course_view.php?id=$course_id&msg=Deadline has passed, submission not allowed");
    exit();
}

if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === 0) {
    $upload_dir = "../assets/uploads/assignments/";
    $original_name = basename($_FILES['submission_file']['name']);
    $safe_name = "sub_" . $student_id . "_" . time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', $original_name);
    $target = $upload_dir . $safe_name;

    if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $target)) {
        $stmt = mysqli_prepare($conn, "
          INSERT INTO submissions (assignment_id, student_id, file_path) VALUES (?, ?, ?)
          ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), submitted_at = NOW(), marks_obtained = NULL, feedback = NULL
        ");
        mysqli_stmt_bind_param($stmt, "iis", $assignment_id, $student_id, $safe_name);
        mysqli_stmt_execute($stmt);
    }
}

header("Location: course_view.php?id=$course_id&msg=Assignment submitted successfully");
exit();
?>
