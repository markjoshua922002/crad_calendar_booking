<?php
session_start();

// Check if 'destroy_token' action is requested
if (isset($_POST['action']) && $_POST['action'] === 'destroy_token') {
    // Remove only the token from the session
    unset($_SESSION['token']);  // Replace 'token' with the specific session variable name used for your token
} else {
    // Destroy the entire session if accessed normally (e.g., clicking the logout button)
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
