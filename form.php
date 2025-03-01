<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form - BCP CRAD</title>
    <link rel="stylesheet" href="mycss/style.css?v=4">
    <link rel="stylesheet" href="mycss/sidebar.css?v=2">
    <link rel="stylesheet" href="mycss/form.css?v=2"> <!-- Incremented version -->
    <link rel="icon" href="assets/bcplogo.png" type="image/png">
</head>
<body>
<button class="menu-button" id="menuButton">&#9776;</button> <!-- Menu button -->

<div class="sidebar" id="sidebar">
    <a href="index.php">CRAD</a>
    <a href="form.php">LOGBOOK</a>
    <div style="flex-grow: 1;"></div> <!-- Spacer to push logout button to the bottom -->
    <a href="logout.php" class="logout-button">Logout</a>
</div>

<div class="container">
    <div class="form-container">
        <h2>Logbook Form</h2>
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = $conn->real_escape_string($_POST['name']);
            $position = $conn->real_escape_string($_POST['position']);
            $purpose = $conn->real_escape_string($_POST['purpose']);
            $inquiry = $conn->real_escape_string($_POST['inquiry']);
            $submission_date = $conn->real_escape_string($_POST['submission_date']); // Changed from submission to submission_date
            
            // Get time components and build the time string
            $hour = intval($_POST['time_hour']);
            $minute = intval($_POST['time_minute']);
            $ampm = $_POST['time_ampm'];
            
            // Convert to 24-hour format for database
            if ($ampm === 'PM' && $hour < 12) {
                $hour += 12;
            } else if ($ampm === 'AM' && $hour === 12) {
                $hour = 0;
            }
            
            // Format time as HH:MM:SS
            $time = sprintf("%02d:%02d:00", $hour, $minute);

            // Insert into database
            $sql = "INSERT INTO logbook (name, position, purpose, inquiry, submission_date, time) 
                    VALUES ('$name', '$position', '$purpose', '$inquiry', '$submission_date', '$time')";

            if ($conn->query($sql) === TRUE) {
                echo "<p class='success-message'>New record created successfully</p>";
            } else {
                echo "<p class='error-message'>Error: " . $sql . "<br>" . $conn->error . "</p>";
            }
        }
        ?>
        <form action="form.php" method="POST" class="logbook-form">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="position">Position:</label>
                <select id="position" name="position" required>
                    <option value="Student">Student</option>
                    <option value="Teacher">Teacher</option>
                    <option value="Staff">Staff</option>
                    <option value="Visitor">Visitor</option>
                </select>
            </div>
            <div class="form-group">
                <label for="purpose">Purpose:</label>
                <input type="text" id="purpose" name="purpose" required>
            </div>
            <div class="form-group">
                <label for="inquiry">Inquiry:</label>
                <input type="text" id="inquiry" name="inquiry">
            </div>
            <div class="form-group">
                <label for="submission_date">Submission Date:</label>
                <input type="date" id="submission_date" name="submission_date" required>
            </div>
            <div class="form-group">
                <label for="time">Time:</label>
                <div class="time-picker">
                    <select id="time_hour" name="time_hour" required>
                        <option value="">Hour</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="time_minute" name="time_minute" required>
                        <option value="">Minute</option>
                        <?php for ($i = 0; $i < 60; $i++): ?>
                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="time_ampm" name="time_ampm" required>
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="submit-button">Submit</button>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script defer src="js/script.js?v=12"></script>
</body>
</html>