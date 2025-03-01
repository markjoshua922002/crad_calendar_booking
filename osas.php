<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - BCP CRAD</title>
    <link rel="stylesheet" href="mycss/sidebar.css?v=1">
    <link rel="stylesheet" href="mycss/style.css?v=3">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <a href="index.php">CRAD</a>
        <a href="osas.php">OSAS</a>
        <div style="flex-grow: 1;"></div> <!-- Spacer to push logout button to the bottom -->
        <a href="logout.php" class="logout-button">Logout</a>
    </div>

    <!-- Centered Content -->
    <div class="content">
        <img src="../assets/bcplogo.png" alt="Logo" class="logo"> <!-- Update the logo path as necessary -->
        <h1 class="title">OSAS INTEG</h1>
    </div>

    <?php
    $conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
    ?>
</body>
</html>
