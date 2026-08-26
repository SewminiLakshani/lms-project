<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$page_title = "My Profile";
include '../includes/header.php';
include '../includes/profile_shared.php';
include '../includes/footer.php';
?>
