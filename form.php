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
    <title>Logbook - BCP CRAD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="mycss/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/form.css?v=<?= time() ?>">
    <link rel="icon" href="assets/bcplogo.png" type="image/png">
    <style>
        /* Additional styles for the logbook page - maximum zoom out */
        body {
            overflow: hidden;
            background-color: #f5f7fa;
            height: 100vh;
            margin: 0;
            padding: 0;
            font-size: 12px;
            transform: scale(0.9);
            transform-origin: top left;
            width: 111.11%;
            height: 111.11%;
        }
        
        .app-container {
            display: flex;
            height: 100vh;
            position: relative;
            overflow: hidden;
            max-width: 2133px; /* 1920px * 1.11 */
            margin: 0 auto;
        }
        
        /* Main content styles - adjusted for sidebar from sidebar.css */
        .main-content {
            flex: 1;
            padding: 15px 20px;
            margin-left: 250px; /* Match sidebar width from sidebar.css */
            transition: margin-left 0.3s ease;
            position: relative;
            width: calc(100% - 250px); /* Match sidebar width from sidebar.css */
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 978px;
            max-height: 100vh;
        }
        
        /* When sidebar is collapsed */
        .sidebar.collapsed + .main-content,
        .sidebar-collapsed .main-content {
            margin-left: 70px; /* Match collapsed sidebar width from sidebar.css */
            width: calc(100% - 70px); /* Match collapsed sidebar width from sidebar.css */
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
            flex-shrink: 0;
            min-height: 40px;
        }
        
        .page-title h1 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
            margin-top: 0;
        }
        
        .page-title p {
            color: #666;
            font-size: 12px;
            margin: 0;
        }
        
        .search-container {
            margin-bottom: 15px;
            background-color: #fff;
            padding: 12px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            flex-shrink: 0;
        }
        
        .search-container form {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }
        
        .search-container input {
            flex: 1;
            min-width: 200px;
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 12px;
            height: 32px;
        }
        
        .search-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-shrink: 0;
        }
        
        .search-container button,
        .reset-search {
            height: 32px;
            padding: 0 16px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .search-container button:hover {
            background-color: #3367d6;
        }
        
        .reset-search:hover {
            background-color: #e8eaed;
            text-decoration: none;
        }
        
        .result-count {
            margin-bottom: 10px;
            font-size: 12px;
            color: #5f6368;
            background-color: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            flex-shrink: 0;
            height: 16px;
            display: flex;
            align-items: center;
        }
        
        .page-layout {
            display: flex;
            gap: 15px;
            flex: 1;
            overflow: visible;
            height: auto;
            min-height: 0;
        }
        
        .form-container {
            flex: 1;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            width: 28%;
            height: auto;
            overflow: visible;
        }
        
        .form-container h2 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        
        .form-container h2 i {
            color: #4285f4;
        }
        
        .data-container {
            flex: 2;
            background-color: #fff;
            padding: 12px 15px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            width: 72%;
            height: 700px; /* Fixed height for data container */
        }
        
        .data-container h2 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        
        .data-container h2 i {
            color: #4285f4;
        }
        
        .data-table-wrapper {
            flex: 1;
            overflow: hidden;
            border: 1px solid #f0f0f0;
            border-radius: 4px;
            height: calc(100% - 50px); /* Subtract header height */
        }
        
        .data-table-container {
            overflow-y: auto;
            height: 100%;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            color: #5f6368;
            font-weight: 600;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .data-table td {
            padding: 6px 10px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }
        
        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .no-data {
            text-align: center;
            font-style: italic;
            color: #777;
            padding: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group:last-child {
            margin-bottom: 10px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: #333;
            font-size: 12px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 12px;
            transition: all 0.3s;
            height: 30px;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4285f4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }
        
        .time-picker {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        .time-picker input[type="time"] {
            flex: 2;
            padding: 6px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 12px;
            transition: all 0.3s;
            height: 30px;
        }

        .time-picker select {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 12px;
            transition: all 0.3s;
            height: 30px;
        }
        
        .time-picker input[type="time"]:focus,
        .time-picker select:focus {
            outline: none;
            border-color: #4285f4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }
        
        .submit-button {
            margin-top: 10px;
        }
        
        .success-message {
            color: #0f9d58;
            padding: 8px;
            background-color: #e6f4ea;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }
        
        .error-message {
            color: #d93025;
            padding: 8px;
            background-color: #fce8e6;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }
        
        /* Scrollable areas when needed */
        .logbook-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        /* Specific for 1920x978 resolution with zoom out */
        @media screen and (width: 1920px) and (height: 978px) {
            body {
                transform: scale(0.9);
                transform-origin: top left;
                width: 111.11%;
                height: 111.11%;
            }
            
            .main-content {
                height: auto;
                overflow: visible;
            }
            
            .page-layout {
                height: auto;
            }
            
            .data-container {
                height: 700px;
            }
            
            .logbook-form {
                height: auto;
            }
        }
        
        /* Mobile responsiveness */
        @media (max-width: 1200px) {
            body {
                transform: none;
                width: 100%;
                height: 100%;
            }
            
            .page-layout {
                flex-direction: column;
            }
            
            .form-container, .data-container {
                max-height: none;
                width: 100%;
            }
            
            .form-container {
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 992px) {
            .main-content {
                padding: 12px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 8px;
            }
            
            .sidebar-collapsed .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .time-picker {
                flex-direction: column;
            }
            
            .search-container form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-actions {
                display: flex;
                gap: 8px;
                justify-content: flex-end;
            }
            
            .search-container input {
                width: 100%;
            }
            
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .page-title {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 6px;
            }
            
            .form-container, .data-container {
                padding: 8px;
            }
            
            .search-container {
                padding: 6px;
            }
            
            .data-table th, .data-table td {
                padding: 4px 6px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar - Using sidebar.css styles -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="assets/bcplogo.png" alt="BCP Logo" class="sidebar-logo">
                <h2>BCP CRAD</h2>
            </div>
            
            <div class="sidebar-menu">
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendar</span>
                </a>
                <a href="form.php" class="<?= basename($_SERVER['PHP_SELF']) == 'form.php' ? 'active' : '' ?>">
                    <i class="fas fa-book"></i>
                    <span>Logbook</span>
                </a>
                <a href="analytics.php" class="<?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
            </div>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-button">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1>Logbook</h1>
                        <p><?= date('l, F j, Y') ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Search Container -->
            <div class="search-container">
                <form action="form.php" method="GET">
                    <input type="text" name="search" placeholder="Search by name..." value="<?= htmlspecialchars($search_term) ?>">
                    <div class="search-actions">
                        <button type="submit">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($search_term)): ?>
                            <a href="form.php" class="reset-search">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Results Count -->
            <?php if (!empty($search_term) || $logbook_result->num_rows > 0): ?>
                <div class="result-count">
                    <i class="fas fa-info-circle"></i> 
                    Showing <?= $logbook_result->num_rows ?> <?= !empty($search_term) ? 'matched' : 'total' ?> entries
                    <?= !empty($search_term) ? "for \"" . htmlspecialchars($search_term) . "\"" : "" ?>
                </div>
            <?php endif; ?>
            
            <!-- Main Layout -->
            <div class="page-layout">
                <!-- Left side - Form container -->
                <div class="form-container">
                    <h2><i class="fas fa-edit"></i> Logbook Form</h2>
                    
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
                            echo "<div class='error-message'><i class='fas fa-exclamation-circle'></i> You cannot submit a log entry for a past date.</div>";
                        } else {
                            // Insert into database
                            $sql = "INSERT INTO logbook (name, position, purpose, inquiry, submission_date, time) 
                                    VALUES ('$name', '$position', '$purpose', '$inquiry', '$submission_date', '$time')";

                            if ($conn->query($sql) === TRUE) {
                                echo "<div class='success-message'><i class='fas fa-check-circle'></i> New record created successfully</div>";
                                // Refresh the page to show the new entry
                                echo "<script>window.location.href = 'form.php';</script>";
                            } else {
                                echo "<div class='error-message'><i class='fas fa-exclamation-circle'></i> Error: " . $sql . "<br>" . $conn->error . "</div>";
                            }
                        }
                    }
                    ?>
                    
                    <form action="form.php" method="POST" class="logbook-form">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" placeholder="Enter full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="position">Position:</label>
                            <select id="position" name="position" required>
                                <option value="" disabled selected>Select position</option>
                                <option value="Student">Student</option>
                                <option value="Teacher">Teacher</option>
                                <option value="Staff">Staff</option>
                                <option value="Visitor">Visitor</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="purpose">Purpose:</label>
                            <input type="text" id="purpose" name="purpose" placeholder="Purpose of visit" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="inquiry">Inquiry (Optional):</label>
                            <input type="text" id="inquiry" name="inquiry" placeholder="Additional information">
                        </div>
                        
                        <div class="form-group">
                            <label for="submission_date">Date:</label>
                            <input type="date" id="submission_date" name="submission_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="time">Time:</label>
                            <div class="time-picker">
                                <input type="time" id="time_manual" name="time_manual" required>
                                <span style="color: #666;">or select:</span>
                                <select id="time_hour" name="time_hour">
                                    <option value="" disabled selected>Hour</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>"><?= sprintf("%02d", $i) ?></option>
                                    <?php endfor; ?>
                                </select>
                                
                                <select id="time_minute" name="time_minute">
                                    <option value="" disabled selected>Min</option>
                                    <?php for ($i = 0; $i < 60; $i += 5): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                                
                                <select id="time_ampm" name="time_ampm">
                                    <option value="AM">AM</option>
                                    <option value="PM">PM</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="submit-button">
                            <i class="fas fa-save"></i> Submit
                        </button>
                    </form>
                </div>
                
                <!-- Right side - Data display container -->
                <div class="data-container">
                    <h2><i class="fas fa-list"></i> Logbook Entries</h2>
                    
                    <!-- Fixed-height scrollable container -->
                    <div class="data-table-wrapper">
                        <div class="data-table-container">
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
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/sidebar.js?v=<?= time() ?>"></script>
    <script>
        // Time picker synchronization
        document.addEventListener('DOMContentLoaded', function() {
            const timeManual = document.getElementById('time_manual');
            const timeHour = document.getElementById('time_hour');
            const timeMinute = document.getElementById('time_minute');
            const timeAmPm = document.getElementById('time_ampm');

            // Update dropdowns when manual time changes
            timeManual.addEventListener('change', function() {
                if (this.value) {
                    const time = new Date(`2000-01-01T${this.value}`);
                    let hours = time.getHours();
                    const minutes = time.getMinutes();
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    
                    // Convert to 12-hour format
                    hours = hours % 12;
                    hours = hours ? hours : 12;

                    timeHour.value = hours;
                    timeMinute.value = minutes - (minutes % 5); // Round to nearest 5
                    timeAmPm.value = ampm;
                }
            });

            // Update manual time when dropdowns change
            [timeHour, timeMinute, timeAmPm].forEach(el => {
                el.addEventListener('change', function() {
                    if (timeHour.value && timeMinute.value && timeAmPm.value) {
                        let hours = parseInt(timeHour.value);
                        if (timeAmPm.value === 'PM' && hours !== 12) hours += 12;
                        if (timeAmPm.value === 'AM' && hours === 12) hours = 0;
                        
                        const timeString = `${String(hours).padStart(2, '0')}:${timeMinute.value}`;
                        timeManual.value = timeString;
                    }
                });
            });
        });
    </script>
</body>
</html>