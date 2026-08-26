<?php
// =====================================================
// Database Connection
// Change these settings to match your XAMPP/WAMP setup
// =====================================================
$db_host = "localhost:3308";
$db_user = "root";
$db_pass = "";
$db_name = "lms_db";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
