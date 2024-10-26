<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management</title>
    <style>
        /* Body and Background Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #e6f7ff;
            background-image: url('.../assets/bcpbg1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
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

        /* Main Container Styles */
        .container {
            margin-left: 170px; /* Adjust based on sidebar width */
            max-width: 1200px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
        }

        /* Header Styles */
        header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 20px;
        }
        .logo {
            width: 100px;
            margin-right: 20px;
        }

        /* Form Container Styles */
        .form-container {
            background: #f8f8f8;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
        }
        .form-container h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-container input[type="text"],
        .form-container select,
        .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-container button {
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .form-container button:hover {
            background-color: #0056b3;
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
    </div>

    <!-- Header with Logo and Logout Button -->
    <header>
        <img src="../assets/logo.png" alt="Logo" class="logo">
        <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
    </header>

    <!-- Main Container with Form -->
    <div class="container">
        <div class="form-container">
            <h2>HR Request Form</h2>
            <form method="POST" action="submit_request.php">
                <!-- Name Input -->
                <input type="text" name="name" placeholder="Enter Your Name" required>

                <!-- Position Dropdown -->
                <label for="position">Position:</label>
                <select name="position" id="position" required>
                    <option value="">Choose your position...</option>
                    <option value="Manager">Manager</option>
                    <option value="Staff">Staff</option>
                    <option value="Intern">Intern</option>
                </select>

                <!-- Request Textarea -->
                <textarea name="comments" rows="4" placeholder="Enter your request details here..." required></textarea>

                <!-- Submit Button -->
                <button type="submit">Submit Request</button>
            </form>
        </div>
    </div>

</body>
</html>
