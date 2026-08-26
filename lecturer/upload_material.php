<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$course_id = intval($_POST['course_id']);
$title = trim($_POST['title']);
$type = $_POST['material_type'];
$video_link = trim($_POST['video_link'] ?? '');

// Verify course belongs to this lecturer
$course = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE course_id=$course_id AND lecturer_id={$_SESSION['user_id']}"));
if (!$course) { die("Unauthorized."); }

$file_path = "";

if ($type === 'video_link' && $video_link !== '') {
    $file_path = $video_link;
} elseif (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === 0) {
    $upload_dir = "../assets/uploads/materials/";
    $original_name = basename($_FILES['material_file']['name']);
    $safe_name = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', $original_name);
    $target = $upload_dir . $safe_name;

    if (move_uploaded_file($_FILES['material_file']['tmp_name'], $target)) {
        $file_path = $safe_name;
    }
}

if ($title !== "" && $file_path !== "") {
    $stmt = mysqli_prepare($conn, "INSERT INTO materials (course_id, title, file_path, material_type) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $course_id, $title, $file_path, $type);
    mysqli_stmt_execute($stmt);
}

header("Location: course_detail.php?id=$course_id&msg=Material uploaded successfully");
exit();
?>
