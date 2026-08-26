<?php
require_once '../includes/auth_check.php';
checkRole('admin');
require_once '../config/db.php';
require_once '../includes/mailer.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_announcement'])) {
    $title = trim($_POST['title']);
    $msg_text = trim($_POST['message']);
    $stmt = mysqli_prepare($conn, "INSERT INTO announcements (course_id, posted_by, title, message) VALUES (NULL, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $title, $msg_text);
    mysqli_stmt_execute($stmt);

    // Notify all active users by email
    $all_users = mysqli_query($conn, "SELECT email, full_name FROM users WHERE status='active'");
    $subject = "New Announcement - " . $title;
    while ($u = mysqli_fetch_assoc($all_users)) {
        $body = "
          <p>Hi " . htmlspecialchars($u['full_name']) . ",</p>
          <p><strong>" . htmlspecialchars($title) . "</strong></p>
          <p>" . nl2br(htmlspecialchars($msg_text)) . "</p>
        ";
        sendNotificationEmail($u['email'], $subject, $body);
    }

    $message = "Announcement posted to all users.";
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM announcements WHERE announcement_id = $id AND course_id IS NULL");
    $message = "Announcement deleted.";
}

$announcements = mysqli_query($conn, "SELECT * FROM announcements WHERE course_id IS NULL ORDER BY created_at DESC");

$page_title = "Announcements";
include '../includes/header.php';
?>

<h3 class="section-title">Global Announcements</h3>

<?php if ($message): ?>
  <div class="alert alert-info auto-alert"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card p-3">
      <h5 class="mb-3">Post New Announcement</h5>
      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Message</label>
          <textarea name="message" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" name="post_announcement" class="btn btn-primary w-100">Post Announcement</button>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <?php while ($a = mysqli_fetch_assoc($announcements)): ?>
      <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between">
          <h6 class="fw-bold"><?php echo htmlspecialchars($a['title']); ?></h6>
          <a href="?delete=<?php echo $a['announcement_id']; ?>" class="text-danger confirm-delete" data-msg="Delete this announcement?"><i class="bi bi-trash"></i></a>
        </div>
        <p class="mb-1"><?php echo nl2br(htmlspecialchars($a['message'])); ?></p>
        <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($a['created_at'])); ?></small>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
