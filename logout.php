<?php
session_start();
session_destroy();

// Clear the JWT cookie
setcookie('jwt', '', time() - 3600, '/', 'crad.schoolmanagementsystem2.com', true, true);

// Redirect to login page
header('Location: login.php');
exit();
?>
