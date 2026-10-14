<!-- Security Wrapper -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($required_role) {
    if (!isLoggedIn()) {
        header("Location: /lms/auth/login.php");
        exit();
    }
    if ($_SESSION['role'] !== $required_role) {
        header("Location: /lms/auth/login.php?error=unauthorized");
        exit();
    }
}
?>
