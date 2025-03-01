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
    <link rel="stylesheet" href="mycss/form.css?v=1"> <!-- New CSS file for forms -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.css">
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
            $submission = $conn->real_escape_string($_POST['submission']);
            $time = $conn->real_escape_string($_POST['time']);

            // Convert time to HH:MM:SS format
            $time = date("H:i:s", strtotime($time));

            $sql = "INSERT INTO logbook (name, position, purpose, inquiry, submission, time) VALUES ('$name', '$position', '$purpose', '$inquiry', '$submission', '$time')";

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
                <input type="text" id="inquiry" name="inquiry" required>
            </div>
            <div class="form-group">
                <label for="submission">Submission:</label>
                <input type="text" id="submission" name="submission" required>
            </div>
            <div class="form-group">
                <label for="time">Time:</label>
                <input type="text" id="time" name="time" class="timepicker" required>
            </div>
            <button type="submit" class="submit-button">Submit</button>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.js"></script>
<script>
    $(document).ready(function(){
        $('.timepicker').timepicker({
            timeFormat: 'h:i A',
            interval: 30,
            minTime: '6:00am',
            maxTime: '11:00pm',
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });
    });
</script>
<script defer src="js/script.js?v=10"></script>
</body>
</html>