<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($page_title) ? $page_title . ' - EduLMS' : 'EduLMS'; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/lms/assets/css/style.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/lms/index.php"><i class="bi bi-mortarboard-fill"></i> EduLMS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <?php if (isset($_SESSION['user_id'])): ?>
          <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="/lms/admin/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/lms/admin/manage_users.php">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="/lms/admin/manage_courses.php">Courses</a></li>
            <li class="nav-item"><a class="nav-link" href="/lms/admin/announcements.php">Announcements</a></li>
          <?php elseif ($_SESSION['role'] === 'lecturer'): ?>
            <li class="nav-item"><a class="nav-link" href="/lms/lecturer/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/lms/lecturer/my_courses.php">My Courses</a></li>
            <li class="nav-item"><a class="nav-link" href="/lms/lecturer/view_submissions.php">Submissions</a></li>
          <?php elseif ($_SESSION['role'] === 'student'): ?>
            <li class="nav-item"><a class="nav-link" href="/lms/student/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/lms/student/browse_courses.php">Browse Courses</a></li>
            <li class="nav-item"><a class="nav-link" href="/lms/student/my_courses.php">My Courses</a></li>
            <li class="nav-item"><a class="nav-link" href="/lms/student/view_grades.php">My Grades</a></li>
          <?php endif; ?>
          <li class="nav-item dropdown ms-lg-3">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle fs-5"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="/lms/<?php echo $_SESSION['role']; ?>/profile.php">My Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="/lms/auth/logout.php">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/lms/auth/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="/lms/auth/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid px-3 px-md-4 py-4">
