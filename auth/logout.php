<?php
session_start();

// Check if the current session belongs to an admin
$isAdmin = isset($_SESSION['admin_id']);

// Destroy all session data
$_SESSION = [];
session_unset();
session_destroy();

// Redirect to the correct login page
if ($isAdmin) {
    header("Location: ../auth/admin_login.php");
} else {
    header("Location: login.php");
}
exit();
