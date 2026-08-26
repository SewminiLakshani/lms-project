<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';
require_once '../includes/mailer.php';

$submission_id = intval($_POST['submission_id']);
$assignment_id = intval($_POST['assignment_id']);
$marks = intval($_POST['marks_obtained']);
$feedback = trim($_POST['feedback']);

// Ownership check
$check = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT s.submission_id, s.student_id, a.title AS assignment_title, a.max_marks, c.course_name,
         u.full_name, u.email
  FROM submissions s
  JOIN assignments a ON s.assignment_id = a.assignment_id
  JOIN courses c ON a.course_id = c.course_id
  JOIN users u ON s.student_id = u.user_id
  WHERE s.submission_id = $submission_id AND c.lecturer_id = {$_SESSION['user_id']}
"));
if (!$check) { die("Unauthorized."); }

$stmt = mysqli_prepare($conn, "UPDATE submissions SET marks_obtained=?, feedback=?, graded_at=NOW() WHERE submission_id=?");
mysqli_stmt_bind_param($stmt, "isi", $marks, $feedback, $submission_id);
mysqli_stmt_execute($stmt);

// Notify student by email
$subject = "Your assignment has been graded - " . $check['assignment_title'];
$message = "
  <p>Hi " . htmlspecialchars($check['full_name']) . ",</p>
  <p>Your submission for <strong>" . htmlspecialchars($check['assignment_title']) . "</strong>
  (" . htmlspecialchars($check['course_name']) . ") has been graded.</p>
  <p><strong>Marks: $marks / {$check['max_marks']}</strong></p>
  " . ($feedback ? "<p>Feedback: " . htmlspecialchars($feedback) . "</p>" : "") . "
  <p>Log in to EduLMS to view full details.</p>
";
sendNotificationEmail($check['email'], $subject, $message);

header("Location: view_submissions.php?assignment_id=$assignment_id&msg=Grade saved");
exit();
?>
