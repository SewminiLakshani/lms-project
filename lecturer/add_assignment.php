<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';
require_once '../includes/mailer.php';

$course_id = intval($_POST['course_id']);
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$deadline = $_POST['deadline'];
$max_marks = intval($_POST['max_marks'] ?: 100);

$course = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE course_id=$course_id AND lecturer_id={$_SESSION['user_id']}"));
if (!$course) { 
  die("Unauthorized."); 
}

if ($title !== "" && $deadline !== "") {
    $stmt = mysqli_prepare($conn, "INSERT INTO assignments (course_id, title, description, deadline, max_marks) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isssi", $course_id, $title, $description, $deadline, $max_marks);
    mysqli_stmt_execute($stmt);

    // Notify all enrolled students by email
    $enrolled = mysqli_query($conn, "
      SELECT u.email, u.full_name FROM enrollments e
      JOIN users u ON e.student_id = u.user_id
      WHERE e.course_id = $course_id
    ");
    $subject = "New assignment posted - " . $title;
    $deadline_display = date('M d, Y h:i A', strtotime($deadline));
    while ($s = mysqli_fetch_assoc($enrolled)) {
        $message = "
          <p>Hi " . htmlspecialchars($s['full_name']) . ",</p>
          <p>A new assignment has been posted in <strong>" . htmlspecialchars($course['course_name']) . "</strong>:</p>
          <p><strong>" . htmlspecialchars($title) . "</strong></p>
          <p>Deadline: $deadline_display &middot; Max Marks: $max_marks</p>
          <p>Log in to EduLMS to view details and submit.</p>
        ";
        sendNotificationEmail($s['email'], $subject, $message);
    }
}

header("Location: course_detail.php?id=$course_id&msg=Assignment created");
exit();
?>
