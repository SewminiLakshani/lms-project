<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$id = intval($_GET['id']);
$course_id = intval($_GET['course_id']);

// Ownership check via join
mysqli_query($conn, "
  DELETE m FROM materials m
  JOIN courses c ON m.course_id = c.course_id
  WHERE m.material_id = $id AND c.lecturer_id = {$_SESSION['user_id']}
");

header("Location: course_detail.php?id=$course_id&msg=Material deleted");
exit();
?>
