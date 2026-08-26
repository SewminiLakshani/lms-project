<?php
require_once '../config/db.php';
require_once '../includes/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // student or lecturer only (admin created separately)

    if ($full_name === "" || $email === "" || $password === "") {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!in_array($role, ['student', 'lecturer'])) {
        $error = "Invalid role selected.";
    } else {
        // Check duplicate email
        $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "This email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $hashed_password, $role);

            if (mysqli_stmt_execute($stmt)) {
                $subject = "Welcome to EduLMS!";
                $body = "
                  <p>Hi " . htmlspecialchars($full_name) . ",</p>
                  <p>Your EduLMS account has been created successfully as a <strong>" . ucfirst($role) . "</strong>.</p>
                  <p>You can now log in and start " . ($role === 'lecturer' ? "creating courses." : "browsing and enrolling in courses.") . "</p>
                ";
                sendNotificationEmail($email, $subject, $body);
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

$page_title = "Register";
include '../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="card auth-card">
    <div class="text-center mb-3">
      <i class="bi bi-person-plus-fill"></i>
      <h3 class="mt-2">Create Account</h3>
      <p class="text-muted">Join EduLMS as a student or lecturer</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger auto-alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="login.php">Login here</a></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Register As</label>
        <select name="role" class="form-select" required>
          <option value="student">Student</option>
          <option value="lecturer">Lecturer</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required minlength="6">
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required minlength="6">
      </div>
      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>

    <p class="text-center mt-3 mb-0">Already have an account? <a href="login.php">Login</a></p>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
