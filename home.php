<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - BCP CRAD</title>
    <style>
        /* Body and Background Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #e6f7ff;
            background-image: url('../assets/bcpbg1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh; /* Full height */
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 150px;
            background-color: #0056b3;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            padding: 15px;
            text-align: center;
            text-decoration: none;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .sidebar a:hover {
            background-color: #003f7a;
        }

        /* Centered Content Styles */
        .content {
            text-align: center;
            color: #333;
        }

        /* Logo Styles */
        .logo {
            width: 150px; /* Adjust size as needed */
            margin-bottom: 20px;
        }

        /* Title Styles */
        .title {
            font-size: 48px; /* Font size for professionalism */
            font-weight: bold; /* Bold weight */
            color: #0056b3; /* Main color */
            margin: 0; /* Remove default margin */
            padding: 10px; /* Add padding */
            border-radius: 5px; /* Rounded corners */
            background-color: rgba(255, 255, 255, 0.9); /* Slightly transparent background */
            display: inline-block; /* Allow margin adjustments */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Subtle shadow for depth */
            text-transform: uppercase; /* Uppercase letters for emphasis */


        }

        /* Logout Button Styles */
        .logout-button {
            padding: 10px 15px;
            background-color: #FF4C4C;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        .logout-button:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <a href="home.php">HOME</a>
        <a href="index.php">BOOKING</a>
        <a href="hr.php">HR</a>
        <a href="its.php">ITS</a>
        <a href="osas.php">OSAS</a>
        <a href="osas.php">FACULTY</a>

    </div>

     <!-- Logout Button -->
    <header>
   
        <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
    </header>

    <!-- Centered Content -->
    <div class="content">
        <img src="../assets/bcplogo.png" alt="Logo" class="logo"> <!-- Update the logo path as necessary -->
        <h1 class="title">BCP CRAD 2024</h1>
    </div>

</body>
</html>
