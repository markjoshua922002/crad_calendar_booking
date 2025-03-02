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

// Search functionality
$search_term = '';
$search_condition = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $search_condition = " WHERE name LIKE '%$search_term%'";
}

// Fetch existing logbook entries
$logbook_query = "SELECT name, position, purpose, inquiry, submission_date, time FROM logbook$search_condition ORDER BY submission_date DESC, time DESC";
$logbook_result = $conn->query($logbook_query);

// Count total entries
$count_query = "SELECT COUNT(*) as total FROM logbook$search_condition";
$count_result = $conn->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_entries = $count_row['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form - BCP CRAD</title>
    <link rel="stylesheet" href="mycss/style.css?v=4">
    <link rel="stylesheet" href="mycss/sidebar.css?v=2">
    <link rel="stylesheet" href="mycss/form.css?v=5"> <!-- Incremented version -->
    <link rel="icon" href="assets/bcplogo.png" type="image/png">
    <style>
        /* Additional inline styles to ensure the new features work correctly */
        .search-container {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }
        
        .search-container input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-container button {
            padding: 8px 16px;
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-container button:hover {
            background-color: #003d7a;
        }
        
        .result-count {
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
<button class="menu-button" id="menuButton">&#9776;</button> <!-- Menu button -->

<div class="sidebar" id="sidebar">
    <a href="index.php">CRAD</a>
    <a href="form.php">LOGBOOK</a>
    <div style="flex-grow: 1;"></div> <!-- Spacer to push logout button to the bottom -->
    <a href="logout.php" class="logout-button">Logout</a>
</div>

<div class="container page-layout">
    <!-- Left side - Form container -->
    <div class="form-container">
        <h2>Logbook Form</h2>
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = $conn->real_escape_string($_POST['name']);
            $position = $conn->real_escape_string($_POST['position']);
            $purpose = $conn->real_escape_string($_POST['purpose']);
            $inquiry = $conn->real_escape_string($_POST['inquiry']);
            $submission_date = $conn->real_escape_string($_POST['submission_date']);
            
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

            // Check if the submission date is in the past
            $current_date = date('Y-m-d');
            if ($submission_date < $current_date) {
                echo "<p class='error-message'>You cannot submit a log entry for a past date.</p>";
            } else {
                // Insert into database
                $sql = "INSERT INTO logbook (name, position, purpose, inquiry, submission_date, time) 
                        VALUES ('$name', '$position', '$purpose', '$inquiry', '$submission_date', '$time')";

                if ($conn->query($sql) === TRUE) {
                    echo "<p class='success-message'>New record created successfully</p>";
                    // Refresh the page to show the new entry
                    echo "<script>window.location.href = 'form.php';</script>";
                } else {
                    echo "<p class='error-message'>Error: " . $sql . "<br>" . $conn->error . "</p>";
                }
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
                <input type="date" id="submission_date" name="submission_date" required min="<?= date('Y-m-d') ?>">
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
    
    <!-- Right side - Data display container -->
    <div class="data-container">
        <h2>Logbook Entries</h2>
        
        <!-- Search bar -->
        <div class="search-container">
            <form action="form.php" method="GET">
                <input type="text" name="search" placeholder="Search by name..." value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit">Search</button>
                <?php if (!empty($search_term)): ?>
                    <a href="form.php" class="reset-search">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Results count -->
        <div class="result-count">
            Showing <?= $logbook_result->num_rows ?> <?= !empty($search_term) ? 'matched' : 'total' ?> entries
            <?= !empty($search_term) ? "for \"" . htmlspecialchars($search_term) . "\"" : "" ?>
        </div>
        
        <!-- Fixed-height scrollable container -->
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Purpose</th>
                        <th>Inquiry</th>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($logbook_result->num_rows > 0) {
                        while ($row = $logbook_result->fetch_assoc()) {
                            // Convert time from 24-hour format to 12-hour format
                            $time_obj = new DateTime($row['time']);
                            $formatted_time = $time_obj->format('h:i A');
                            
                            // Format date
                            $date_obj = new DateTime($row['submission_date']);
                            $formatted_date = $date_obj->format('m/d/Y');
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['position']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['purpose']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['inquiry'] ?: 'N/A') . "</td>";
                            echo "<td>" . $formatted_date . "</td>";
                            echo "<td>" . $formatted_time . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='no-data'>No entries found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script defer src="js/script.js?v=12"></script>
</body>
</html>