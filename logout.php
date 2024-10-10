<?php
session_start(); // Start the session
session_destroy(); // Destroy all data registered to a session
header('Location: login.php'); // Redirect to the login page
exit(); // Ensure no further code is executed
?>
