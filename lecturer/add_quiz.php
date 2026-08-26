<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$course_id = intval($_POST['course_id']);
$title = trim($_POST['title']);
$duration = intval($_POST['duration_minutes'] ?: 30);

$course = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE course_id=$course_id AND lecturer_id={$_SESSION['user_id']}"));
if (!$course) { die("Unauthorized."); }

if ($title !== "") {
    $stmt = mysqli_prepare($conn, "INSERT INTO quizzes (course_id, title, duration_minutes) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isi", $course_id, $title, $duration);
    mysqli_stmt_execute($stmt);
}

header("Location: course_detail.php?id=$course_id&msg=Quiz created. Now add questions.");
exit();
?>
