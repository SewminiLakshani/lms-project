<?php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in? redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /lms/" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = "";
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = "You are not authorized to access that page.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'inactive') {
            $error = "Your account has been deactivated. Contact the admin.";
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            header("Location: /lms/" . $user['role'] . "/dashboard.php");
            exit();
        }
    } else {
        $error = "Invalid email or password.";
    }
}

$page_title = "Login";
include '../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="card auth-card">
    <div class="text-center mb-3">
      <i class="bi bi-mortarboard-fill"></i>
      <h3 class="mt-2">Welcome Back</h3>
      <p class="text-muted">Login to your EduLMS account</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger auto-alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>

    <p class="text-center mt-3 mb-0">Don't have an account? <a href="register.php">Register</a></p>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
