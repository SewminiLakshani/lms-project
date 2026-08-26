<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$id = intval($_GET['id']);
$course_id = intval($_GET['course_id']);

mysqli_query($conn, "
  DELETE q FROM quizzes q
  JOIN courses c ON q.course_id = c.course_id
  WHERE q.quiz_id = $id AND c.lecturer_id = {$_SESSION['user_id']}
");

header("Location: course_detail.php?id=$course_id&msg=Quiz deleted");
exit();
?>
