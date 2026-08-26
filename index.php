<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: /lms/" . $_SESSION['role'] . "/dashboard.php");
    exit();
} else {
    header("Location: /lms/auth/login.php");
    exit();
}
?>
