<?php
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset(); //Session eke data clear karanawa
session_destroy(); // session fil eka distroy karanawa
header("Location: /lms/auth/login.php");
exit();
?>
