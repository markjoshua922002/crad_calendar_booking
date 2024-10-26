<!-- hr.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management</title>
    <style>
        /* Copying and modifying CSS from index.php */

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #e6f7ff;
            background-image: url('../assets/bcpbg1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex; /* To align sidebar and content side-by-side */
        }

        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background-color: #007BFF;
            color: white;
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
            position: fixed;
            height: 100%;
        }

        .sidebar a {
            color: white;
            padding: 15px;
            margin-bottom: 10px;
            background-color: #007BFF;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }

        .sidebar a:hover {
            background-color: #0056b3;
        }

        /* Adjust main content to sit next to sidebar */
        .main-content {
            margin-left: 270px; /* Space for the sidebar */
            padding: 20px;
            width: calc(100% - 270px);
        }

        /* Logout button styling within sidebar */
        .logout-button {
            padding: 10px 15px;
            background-color: #FF4C4C;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            margin-top: auto;
        }

        .logout-button:hover {
            background-color: #cc0000;
        }

        /* Form container styling */
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
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <img src="../assets/logo.png" alt="Logo" class="logo" style="width: 100%; margin-bottom: 20px;">
        
        <a href="home.php">Home</a>
        <a href="booking.php">Booking</a>
        <a href="hr.php">HR</a>
        <a href="its.php">ITS</a>
        <a href="osas.php">OSAS</a>
        
        <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container form-container">
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
