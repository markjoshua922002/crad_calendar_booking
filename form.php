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
        /* Additional styles for the logbook page */
        body {
            overflow-y: auto;
            background-color: #f5f7fa;
        }
        
        .app-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
            transition: margin-left 0.3s ease;
            position: relative;
            width: calc(100% - 250px);
            overflow-y: auto;
        }
        
        .sidebar-collapsed .main-content {
            margin-left: 70px;
            width: calc(100% - 70px);
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .page-title h1 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .page-title p {
            color: #666;
            font-size: 14px;
        }
        
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .search-container form {
            display: flex;
            width: 100%;
            gap: 10px;
        }
        
        .search-container input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-container input:focus {
            outline: none;
            border-color: #4285f4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }
        
        .search-container button {
            padding: 12px 20px;
            background-color: #4285f4;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-container button:hover {
            background-color: #3367d6;
        }
        
        .reset-search {
            padding: 12px 20px;
            background-color: #f1f3f4;
            color: #5f6368;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .reset-search:hover {
            background-color: #e8eaed;
            text-decoration: none;
        }
        
        .result-count {
            margin-bottom: 15px;
            font-size: 14px;
            color: #5f6368;
            background-color: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .page-layout {
            display: flex;
            gap: 30px;
            height: calc(100vh - 200px);
            min-height: 600px;
        }
        
        .form-container {
            flex: 1;
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            overflow-y: auto;
            max-height: 100%;
        }
        
        .form-container h2 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 10;
        }
        
        .form-container h2 i {
            color: #4285f4;
        }
        
        .data-container {
            flex: 2;
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            max-height: 100%;
            overflow: hidden;
        }
        
        .data-container h2 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 10;
        }
        
        .data-container h2 i {
            color: #4285f4;
        }
        
        .data-table-wrapper {
            flex: 1;
            overflow-x: auto;
            overflow-y: auto;
            border: 1px solid #f0f0f0;
            border-radius: 6px;
            height: calc(100% - 60px);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            color: #5f6368;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e0e0e0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .data-table td {
            padding: 15px;
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
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
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
            gap: 10px;
        }
        
        .time-picker select {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .time-picker select:focus {
            outline: none;
            border-color: #4285f4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
        }
        
        .submit-button {
            padding: 12px 24px;
            background-color: #4285f4;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .submit-button:hover {
            background-color: #3367d6;
        }
        
        .success-message {
            color: #0f9d58;
            padding: 15px;
            background-color: #e6f4ea;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-message {
            color: #d93025;
            padding: 15px;
            background-color: #fce8e6;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 1200px) {
            .page-layout {
                height: auto;
                flex-direction: column;
            }
            
            .form-container, .data-container {
                max-height: none;
            }
            
            .form-container {
                margin-bottom: 30px;
            }
            
            .data-container {
                height: 600px;
            }
        }
        
        @media (max-width: 992px) {
            .main-content {
                padding: 20px;
            }
            
            .data-container {
                height: 500px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
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
            }
            
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .data-container {
                height: 400px;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 10px;
            }
            
            .form-container, .data-container {
                padding: 15px;
            }
            
            .search-container {
                padding: 10px;
            }
            
            .data-table th, .data-table td {
                padding: 10px;
                font-size: 13px;
            }
        }
        
        /* Fix for sidebar height */
        .sidebar {
            height: 100vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
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
                <a href="accounts.php" class="<?= basename($_SERVER['PHP_SELF']) == 'accounts.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
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
            <!-- Top Bar -->
            <div class="top-bar">
                <button id="menuToggle" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="page-title">
                    <h1>Logbook</h1>
                    <p>Track and manage visitor information</p>
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
                                <select id="time_hour" name="time_hour" required>
                                    <option value="" disabled selected>Hour</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                                
                                <select id="time_minute" name="time_minute" required>
                                    <option value="" disabled selected>Minute</option>
                                    <?php for ($i = 0; $i < 60; $i += 5): ?>
                                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                                
                                <select id="time_ampm" name="time_ampm" required>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const appContainer = document.querySelector('.app-container');
            const mainContent = document.querySelector('.main-content');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    appContainer.classList.toggle('sidebar-collapsed');
                });
            }
            
            // Handle responsive behavior
            function handleResponsive() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.add('collapsed');
                    appContainer.classList.add('sidebar-collapsed');
                    
                    // On mobile, clicking outside sidebar should close it
                    mainContent.addEventListener('click', function() {
                        if (window.innerWidth <= 768 && !sidebar.classList.contains('collapsed')) {
                            sidebar.classList.add('collapsed');
                            appContainer.classList.add('sidebar-collapsed');
                        }
                    });
                }
            }
            
            // Initial check
            handleResponsive();
            
            // Listen for window resize
            window.addEventListener('resize', handleResponsive);
            
            // Adjust heights on window resize
            function adjustHeights() {
                if (window.innerWidth > 1200) {
                    const topBarHeight = document.querySelector('.top-bar').offsetHeight;
                    const searchContainerHeight = document.querySelector('.search-container').offsetHeight;
                    const resultCountHeight = document.querySelector('.result-count') ? 
                                             document.querySelector('.result-count').offsetHeight + 15 : 0;
                    
                    const totalHeight = topBarHeight + searchContainerHeight + resultCountHeight + 60; // 60px for padding
                    document.querySelector('.page-layout').style.height = `calc(100vh - ${totalHeight}px)`;
                } else {
                    document.querySelector('.page-layout').style.height = 'auto';
                }
            }
            
            // Call once on load
            setTimeout(adjustHeights, 100);
            
            // Call on resize
            window.addEventListener('resize', adjustHeights);
        });
    </script>
</body>
</html>