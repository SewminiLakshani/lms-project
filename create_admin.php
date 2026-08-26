<?php
// =====================================================
// RUN THIS FILE ONCE IN YOUR BROWSER TO CREATE THE ADMIN
// e.g. http://localhost/lms/create_admin.php
// DELETE THIS FILE AFTER YOU USE IT (for security)
// =====================================================
require_once "config/db.php";

$full_name = "System Admin";
$email = "admin@lms.com";
$plain_password = "admin123";
$role = "admin";

// Check if admin already exists
$check = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
if (mysqli_num_rows($check) > 0) {
    die("Admin account already exists. You can delete this file now. Login with: $email");
}

$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $hashed_password, $role);

if (mysqli_stmt_execute($stmt)) {
    echo "<h2>Admin account created successfully!</h2>";
    echo "<p>Email: <b>$email</b></p>";
    echo "<p>Password: <b>$plain_password</b></p>";
    echo "<p style='color:red;'>IMPORTANT: Delete create_admin.php now for security reasons.</p>";
    echo "<a href='auth/login.php'>Go to Login</a>";
} else {
    echo "Error creating admin: " . mysqli_error($conn);
}
?>
