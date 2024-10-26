<!-- hr.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management</title>
    <style>
        /* Reusing styles from index.php */

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #e6f7ff;
            background-image: url('../assets/bcpbg1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
        }

        .sidebar {
            width: 200px;
            background-color: #0056b3;
            color: #fff;
            padding: 20px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
        }

        .sidebar h2 {
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar a,
        .sidebar button {
            padding: 10px 15px;
            margin: 10px 0;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            width: 100%;
            text-align: center;
            border: none;
            cursor: pointer;
        }

        .sidebar a:hover,
        .sidebar button:hover {
            background-color: #0056b3;
        }

        .main-content {
            margin-left: 220px;
            padding: 20px;
        }

        .form-container {
            background: #f8f8f8;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
            max-width: 600px;
            margin: 0 auto;
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
            padding: 10px;
            width: 100%;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .form-container button:hover {
            background-color: #0056b3;
        }

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

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Navigation</h2>
        <a href="home.php">Home</a>
        <a href="booking.php">Booking</a>
        <a href="hr.php">HR</a>
        <a href="its.php">ITS</a>
        <a href="osas.php">OSAS</a>
        <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="form-container">
                <h2>Request Form</h2>
                <form method="POST" action="submit_request.php">
                    <!-- Name Input -->
                    <input type="text" name="name" placeholder="Name" required>

                    <!-- Position Dropdown -->
                    <label for="position">Position:</label>
                    <select name="position" id="position" required>
                        <option value="">Choose...</option>
                        <option value="Manager">Manager</option>
                        <option value="Staff">Staff</option>
                        <option value="Intern">Intern</option>
                        <!-- Add more options as needed -->
                    </select>

                    <!-- Text Area for Comments -->
                    <textarea name="comments" rows="4" placeholder="Enter your request or comments here..." required></textarea>

                    <!-- Submit Button -->
                    <button type="submit">Submit</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
