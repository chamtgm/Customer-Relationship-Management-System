<?php
session_start();
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session

header("Location: LoginPage.php"); // Redirect to login
exit();
?>
