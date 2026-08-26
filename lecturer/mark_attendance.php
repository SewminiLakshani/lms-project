<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$course_id = intval($_POST['course_id']);
$date = $_POST['attendance_date'];
$present_ids = $_POST['present'] ?? [];
$lecturer_id = $_SESSION['user_id'];

$course = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE course_id=$course_id AND lecturer_id=$lecturer_id"));
if (!$course) { die("Unauthorized."); }

$students = mysqli_query($conn, "SELECT student_id FROM enrollments WHERE course_id=$course_id");

while ($s = mysqli_fetch_assoc($students)) {
    $sid = $s['student_id'];
    $status = in_array($sid, $present_ids) ? 'present' : 'absent';

    $stmt = mysqli_prepare($conn, "
      INSERT INTO attendance (course_id, student_id, attendance_date, status, marked_by)
      VALUES (?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)
    ");
    mysqli_stmt_bind_param($stmt, "iissi", $course_id, $sid, $date, $status, $lecturer_id);
    mysqli_stmt_execute($stmt);
}

header("Location: course_detail.php?id=$course_id&msg=Attendance saved for $date");
exit();
?>
