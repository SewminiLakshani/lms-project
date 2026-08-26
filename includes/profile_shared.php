<?php
// Included by admin/profile.php, lecturer/profile.php, student/profile.php
// Expects: $conn, session already started, role already checked

$message = "";
$user_id = $_SESSION['user_id'];

// Update profile info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $contact_no = trim($_POST['contact_no']);

    $stmt = mysqli_prepare($conn, "UPDATE users SET full_name = ?, contact_no = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "ssi", $full_name, $contact_no, $user_id);
    mysqli_stmt_execute($stmt);

    $_SESSION['full_name'] = $full_name;
    $message = "Profile updated successfully.";
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM users WHERE user_id = $user_id"));

    if (!password_verify($current, $user['password'])) {
        $message = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $message = "New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $message = "New password must be at least 6 characters.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
        mysqli_stmt_execute($stmt);
        $message = "Password changed successfully.";
    }
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id"));
?>

<h3 class="section-title">My Profile</h3>

<?php if ($message): ?>
  <div class="alert alert-info auto-alert"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card p-3">
      <h5 class="mb-3">Edit Profile</h5>
      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email (cannot be changed)</label>
          <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label">Contact Number</label>
          <input type="text" name="contact_no" class="form-control" value="<?php echo htmlspecialchars($user['contact_no'] ?? ''); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
        </div>
        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card p-3">
      <h5 class="mb-3">Change Password</h5>
      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label">Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" required minlength="6">
        </div>
        <div class="mb-3">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required minlength="6">
        </div>
        <button type="submit" name="change_password" class="btn btn-outline-primary">Change Password</button>
      </form>
    </div>
  </div>
</div>
