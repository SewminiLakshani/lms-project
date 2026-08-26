<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';
require_once '../includes/mailer.php';

$course_id = intval($_POST['course_id']);
$title = trim($_POST['title']);
$message = trim($_POST['message']);
$lecturer_id = $_SESSION['user_id'];

$course = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE course_id=$course_id AND lecturer_id=$lecturer_id"));
if (!$course) { die("Unauthorized."); }

if ($title !== "" && $message !== "") {
    $stmt = mysqli_prepare($conn, "INSERT INTO announcements (course_id, posted_by, title, message) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiss", $course_id, $lecturer_id, $title, $message);
    mysqli_stmt_execute($stmt);

    // Notify enrolled students by email
    $enrolled = mysqli_query($conn, "
      SELECT u.email, u.full_name FROM enrollments e
      JOIN users u ON e.student_id = u.user_id
      WHERE e.course_id = $course_id
    ");
    $subject = "New Announcement in " . $course['course_name'] . " - " . $title;
    while ($s = mysqli_fetch_assoc($enrolled)) {
        $body = "
          <p>Hi " . htmlspecialchars($s['full_name']) . ",</p>
          <p>New announcement in <strong>" . htmlspecialchars($course['course_name']) . "</strong>:</p>
          <p><strong>" . htmlspecialchars($title) . "</strong></p>
          <p>" . nl2br(htmlspecialchars($message)) . "</p>
        ";
        sendNotificationEmail($s['email'], $subject, $body);
    }
}

header("Location: course_detail.php?id=$course_id&msg=Announcement posted");
exit();
?>
