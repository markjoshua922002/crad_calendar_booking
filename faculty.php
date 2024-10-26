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
            background-image: url('../assets/bcpbg1.jpg');
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

        /* Booked Appointments List Styles */
        .appointments {
            margin-top: 20px;
        }
        .appointments h3 {
            text-align: center;
            color: #333;
        }
        .appointment-item {
            background: #f0f8ff;
            border: 1px solid #b0e0e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <a href="home.php">HOME</a>
        <a href="index.php">BOOKING</a>
        <a href="faculty.php">FACULTY</a>
        <a href="hr.php">HR</a>
        <a href="its.php">ITS</a>
        <a href="osas.php">OSAS</a>
    </div>

    <!-- Header with Logo and Logout Button -->
    <header>
        <img src="../assets/logo.png" alt="Logo" class="logo">
        <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
    </header>

    <!-- Main Container -->
    <div class="container">
        <div class="form-container">
            <h2>HR Request Form</h2>
            <form method="POST" action="submit_request.php">
                <!-- Name Input -->
                <input type="text" name="name" placeholder="Enter Your Name" required>

                <!-- ID Number Input -->
                <input type="text" name="id_number" placeholder="Enter Your ID Number" required>

                <!-- Department Dropdown -->
                <label for="department">Department:</label>
                <select name="department" id="department" required>
                    <option value="">Choose your department...</option>
                    <?php while ($department = $departments->fetch_assoc()): ?>
                        <option value="<?php echo $department['id']; ?>"><?php echo $department['name']; ?></option>
                    <?php endwhile; ?>
                </select>

                <!-- Room Dropdown -->
                <label for="room">Room:</label>
                <select name="room" id="room" required>
                    <option value="">Choose your room...</option>
                    <?php while ($room = $rooms->fetch_assoc()): ?>
                        <option value="<?php echo $room['id']; ?>"><?php echo $room['name']; ?></option>
                    <?php endwhile; ?>
                </select>

                <!-- Date Input -->
                <input type="date" name="date" required>

                <!-- Time Input -->
                <input type="time" name="time" required>

                <!-- Reason Textarea -->
                <textarea name="reason" rows="4" placeholder="Enter your reason here..." required></textarea>

                <!-- Submit Button -->
                <button type="submit" name="add_booking">Submit Request</button>
            </form>
        </div>

        <!-- List of Booked Appointments -->
        <div class="appointments">
            <h3>Booked Appointments</h3>
            <?php if (!empty($appointments)): ?>
                <?php foreach ($appointments as $date => $dayAppointments): ?>
                    <?php foreach ($dayAppointments as $appointment): ?>
                        <div class="appointment-item">
                            <strong>Name:</strong> <?php echo htmlspecialchars($appointment['name']); ?><br>
                            <strong>ID Number:</strong> <?php echo htmlspecialchars($appointment['id_number']); ?><br>
                            <strong>Department:</strong> <?php echo htmlspecialchars($appointment['department_name']); ?><br>
                            <strong>Room:</strong> <?php echo htmlspecialchars($appointment['room_name']); ?><br>
                            <strong>Date:</strong> <?php echo htmlspecialchars($appointment['booking_date']); ?><br>
                            <strong>Time:</strong> <?php echo htmlspecialchars($appointment['booking_time']); ?><br>
                            <strong>Reason:</strong> <?php echo htmlspecialchars($appointment['reason']); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No booked appointments for this month.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
