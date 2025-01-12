<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - BCP CRAD</title>
    <style>
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
        .search-container {
            margin-bottom: 20px;
        }
        .search-container input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: calc(100% - 22px);
        }
        .search-container button {
            padding: 10px;
            background-color: #00509e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 5px;
        }
        .search-container button:hover {
            background-color: #0073e6;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="home.php">HOME</a>
    <a href="index.php">BOOKING</a>
    <a href="hr.php">HR</a>
    <a href="faculty.php">ITS</a>
    <a href="osas.php">OSAS</a>
    <a href="faculty.php">FACULTY</a>
</div>

     <!-- Logout Button -->
    <header>
   
        <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
    </header>

    <!-- Centered Content -->
    <div class="content">
        <img src="../assets/bcplogo.png" alt="Logo" class="logo"> <!-- Update the logo path as necessary -->
        <h1 class="title">ITS INTEG4</h1>
    </div>

</body>
</html>
