<?php
require_once '../includes/auth_check.php';
checkRole('student');
require_once '../config/db.php';

$course_id = intval($_GET['course_id'] ?? 0);
$student_id = $_SESSION['user_id'];

$enrollment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM enrollments WHERE course_id=$course_id AND student_id=$student_id"));
if (!$enrollment) { die("You are not enrolled in this course."); }

$course = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT c.*, u.full_name AS lecturer_name FROM courses c
  JOIN users u ON c.lecturer_id = u.user_id WHERE c.course_id=$course_id
"));
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id=$student_id"));

// ---------------- Completion check ----------------
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM assignments WHERE course_id=$course_id"))['c'];
$completed_assignments = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT COUNT(*) c FROM submissions s
  JOIN assignments a ON s.assignment_id = a.assignment_id
  WHERE a.course_id=$course_id AND s.student_id=$student_id AND s.marks_obtained IS NOT NULL
"))['c'];

$total_quizzes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM quizzes WHERE course_id=$course_id"))['c'];
$completed_quizzes = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT COUNT(*) c FROM quiz_attempts qa
  JOIN quizzes q ON qa.quiz_id = q.quiz_id
  WHERE q.course_id=$course_id AND qa.student_id=$student_id
"))['c'];

// Overall average % across graded assignments + quiz attempts
$avg_row = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT
    (SELECT AVG(s.marks_obtained / a.max_marks) FROM submissions s
       JOIN assignments a ON s.assignment_id=a.assignment_id
       WHERE a.course_id=$course_id AND s.student_id=$student_id AND s.marks_obtained IS NOT NULL) AS assign_avg,
    (SELECT AVG(qa.score / q.total_marks) FROM quiz_attempts qa
       JOIN quizzes q ON qa.quiz_id=q.quiz_id
       WHERE q.course_id=$course_id AND qa.student_id=$student_id AND q.total_marks > 0) AS quiz_avg
"));
$parts = array_filter([$avg_row['assign_avg'], $avg_row['quiz_avg']], fn($v) => $v !== null);
$overall_pct = count($parts) > 0 ? round((array_sum($parts) / count($parts)) * 100, 1) : 0;

$has_gradable_items = ($total_assignments + $total_quizzes) > 0;
$all_assignments_done = ($total_assignments === 0) || ($completed_assignments >= $total_assignments);
$all_quizzes_done = ($total_quizzes === 0) || ($completed_quizzes >= $total_quizzes);
$is_eligible = $has_gradable_items && $all_assignments_done && $all_quizzes_done && $overall_pct >= 50;

if (!$is_eligible) {
    $page_title = "Certificate";
    include '../includes/header.php';
    ?>
    <div class="card p-4 text-center" style="max-width:600px;margin:0 auto;">
      <i class="bi bi-award" style="font-size:2.5rem;color:#d97706;"></i>
      <h4 class="mt-2">Certificate Not Yet Available</h4>
      <p class="text-muted">You need to complete all assignments and quizzes in this course with an overall average of 50% or higher to unlock your certificate.</p>
      <table class="table mt-3">
        <tr><td>Assignments completed</td><td><?php echo $completed_assignments; ?> / <?php echo $total_assignments; ?></td></tr>
        <tr><td>Quizzes completed</td><td><?php echo $completed_quizzes; ?> / <?php echo $total_quizzes; ?></td></tr>
        <tr><td>Current average</td><td><?php echo $overall_pct; ?>%</td></tr>
      </table>
      <a href="course_view.php?id=<?php echo $course_id; ?>" class="btn btn-outline-secondary">&larr; Back to Course</a>
    </div>
    <?php
    include '../includes/footer.php';
    exit();
}

$cert_id = "EDU-" . str_pad($course_id, 3, '0', STR_PAD_LEFT) . "-" . str_pad($student_id, 4, '0', STR_PAD_LEFT) . "-" . date('Y');
$issue_date = date('F d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Certificate - <?php echo htmlspecialchars($course['course_name']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Poppins', sans-serif;
    background: #e5e7eb;
    margin: 0;
    padding: 30px 0;
  }
  .no-print { text-align: center; margin-bottom: 20px; }
  .no-print button {
    background: #4338ca; color: #fff; border: none; padding: 10px 24px;
    border-radius: 8px; font-size: 15px; cursor: pointer;
  }
  .certificate {
    width: 1000px;
    max-width: 95vw;
    margin: 0 auto;
    background: #fff;
    padding: 60px 70px;
    border: 14px solid #4338ca;
    outline: 2px solid #06b6d4;
    outline-offset: -22px;
    position: relative;
    text-align: center;
  }
  .cert-brand { font-size: 15px; letter-spacing: 4px; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; }
  .cert-title { font-family: 'Playfair Display', serif; font-size: 42px; color: #1f2937; margin: 10px 0 4px; }
  .cert-sub { font-size: 16px; color: #6b7280; margin-bottom: 30px; }
  .cert-name { font-family: 'Playfair Display', serif; font-size: 36px; color: #4338ca; margin: 20px 0; border-bottom: 2px solid #e5e7eb; display: inline-block; padding-bottom: 10px; }
  .cert-body { font-size: 16px; color: #374151; max-width: 700px; margin: 0 auto 30px; line-height: 1.8; }
  .cert-course { font-weight: 600; color: #1f2937; font-size: 20px; }
  .cert-footer { display: flex; justify-content: space-between; margin-top: 50px; padding: 0 40px; }
  .cert-footer div { text-align: center; }
  .cert-footer .line { border-top: 1px solid #9ca3af; width: 200px; margin: 0 auto 6px; padding-top: 8px; }
  .cert-id { position: absolute; bottom: 18px; right: 30px; font-size: 11px; color: #9ca3af; }
  .cert-score { display:inline-block; margin-top:10px; background:#ecfdf5; color:#16a34a; padding:6px 16px; border-radius:20px; font-weight:600; }

  @media print {
    body { background: #fff; padding: 0; }
    .no-print { display: none; }
    .certificate { border: 14px solid #4338ca; box-shadow: none; margin: 0; width: 100%; }
  }
</style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()">🖨️ Print / Save as PDF</button>
</div>

<div class="certificate">
  <div class="cert-brand">EduLMS &middot; Certificate of Completion</div>
  <div class="cert-title">Certificate of Achievement</div>
  <div class="cert-sub">This certificate is proudly presented to</div>

  <div class="cert-name"><?php echo htmlspecialchars($student['full_name']); ?></div>

  <div class="cert-body">
    for successfully completing the course
    <div class="cert-course"><?php echo htmlspecialchars($course['course_name']); ?> (<?php echo htmlspecialchars($course['course_code']); ?>)</div>
    <div class="cert-score">Overall Score: <?php echo $overall_pct; ?>%</div>
  </div>

  <div class="cert-footer">
    <div>
      <div class="line"><?php echo htmlspecialchars($course['lecturer_name']); ?></div>
      Course Lecturer
    </div>
    <div>
      <div class="line"><?php echo $issue_date; ?></div>
      Date Issued
    </div>
  </div>

  <div class="cert-id">Certificate ID: <?php echo $cert_id; ?></div>
</div>

</body>
</html>
