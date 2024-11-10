<?php
// Start session
session_start();

// Destroy session and redirect to login page
session_unset();
session_destroy();
header("Location: user_login.php");
exit;
?>
