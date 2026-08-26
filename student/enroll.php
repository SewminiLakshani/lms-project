<?php
require_once '../includes/auth_check.php';
checkRole('student');
require_once '../config/db.php';

$course_id = intval($_GET['course_id']);
$student_id = $_SESSION['user_id'];

$course = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE course_id=$course_id AND status='active'"));
if (!$course) { die("Course not found."); }

$check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM enrollments WHERE course_id=$course_id AND student_id=$student_id"));

if (!$check) {
    $stmt = mysqli_prepare($conn, "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $student_id, $course_id);
    mysqli_stmt_execute($stmt);
}

header("Location: browse_courses.php?msg=Enrolled in " . urlencode($course['course_name']) . " successfully");
exit();
?>
