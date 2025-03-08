<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection and PHP logic remains the same
$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle form submissions
if (isset($_POST['add_booking'])) {
    $name = $_POST['name'];
    $id_number = $_POST['id_number'];
    $group_members = $_POST['group_members'];
    $representative_name = $_POST['representative_name'];
    $set = $_POST['set'];
    $department = $_POST['department'];
    $room = $_POST['room'];
    $date = date('Y-m-d', strtotime($_POST['date']));
    
    // Combine time fields
    $time_from = date('H:i:s', strtotime($_POST['time_from_hour'] . ':' . $_POST['time_from_minute'] . ' ' . $_POST['time_from_ampm']));
    $time_to = date('H:i:s', strtotime($_POST['time_to_hour'] . ':' . $_POST['time_to_minute'] . ' ' . $_POST['time_to_ampm']));
    
    $reason = $_POST['reason'];

    // Debug: Log the values being processed
    error_log("Booking Details: Name=$name, ID Number=$id_number, Group Members=$group_members, Representative Name=$representative_name, Set=$set, Department=$department, Room=$room, Date=$date, Time From=$time_from, Time To=$time_to, Reason=$reason");

    // Check if the booking date is in the past
    $current_date = date('Y-m-d');
    if ($date < $current_date) {
        $warning = "You cannot book a date that has already passed.";
    } else {
        // Check for double booking
        $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_date = ? AND room_id = ? AND ((booking_time_from < ? AND booking_time_to > ?) OR (booking_time_from < ? AND booking_time_to > ?))");
        if (!$stmt) {
            die('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("sissss", $date, $room, $time_to, $time_from, $time_from, $time_to);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $warning = "Double booking detected for the specified time, date, and room.";
        } else {
            $stmt = $conn->prepare("INSERT INTO bookings (name, id_number, group_members, representative_name, `set`, department_id, room_id, booking_date, booking_time_from, booking_time_to, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                die('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param("ssssissssss", $name, $id_number, $group_members, $representative_name, $set, $department, $room, $date, $time_from, $time_to, $reason);
            if ($stmt->execute()) {
                // Debug: Log successful insertion
                error_log("Booking successfully inserted: ID=" . $stmt->insert_id);
                // Redirect to avoid form resubmission
                header('Location: index.php');
                exit();
            } else {
                // Debug: Log error
                error_log("Error inserting booking: " . $stmt->error);
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt->close();
    }
}

// Handle department addition
if (isset($_POST['add_department'])) {
    $department_name = $_POST['department_name'];
    $color = $_POST['color']; 
    $stmt = $conn->prepare("INSERT INTO departments (name, color) VALUES (?, ?)");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("ss", $department_name, $color);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php');
    exit();
}

// Handle room addition
if (isset($_POST['add_room'])) {
    $room_name = $_POST['room_name'];
    $stmt = $conn->prepare("INSERT INTO rooms (name) VALUES (?)");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("s", $room_name);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php');
    exit();
}

// Search for appointments
$searched_appointment = null;
if (isset($_POST['search_booking'])) {
    $search_name = $_POST['search_name'];
    $stmt = $conn->prepare("SELECT bookings.*, departments.name as department_name, departments.color, rooms.name as room_name 
                          FROM bookings 
                          JOIN departments ON bookings.department_id = departments.id 
                          JOIN rooms ON bookings.room_id = rooms.id 
                          WHERE bookings.representative_name LIKE ? OR bookings.name LIKE ?");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $search_param = "%$search_name%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $searched_appointment = $result->fetch_assoc();
    }
    $stmt->close();
}

// Fetch departments and rooms
$departments = $conn->query("SELECT * FROM departments");
$rooms = $conn->query("SELECT * FROM rooms");

// Fetch current month and year
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$firstDayOfMonth = date('w', strtotime("$year-$month-01"));
$totalDaysInMonth = date('t', strtotime("$year-$month-01"));

// Fetch bookings for the current month
$bookings = $conn->query("SELECT bookings.*, departments.name as department_name, departments.color, rooms.name as room_name 
    FROM bookings 
    JOIN departments ON bookings.department_id = departments.id 
    JOIN rooms ON bookings.room_id = rooms.id 
    WHERE MONTH(booking_date) = '$month' AND YEAR(booking_date) = '$year'");

$appointments = [];
while ($row = $bookings->fetch_assoc()) {
    $date = date('j', strtotime($row['booking_date']));
    $appointments[$date][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Scheduling System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/bcplogo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.js"></script>
    <link rel="stylesheet" href="mycss/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/calendar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/form.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/day.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/reminder.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/general.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/conflict-resolver.css?v=<?= time() ?>">
    <style>
        /* Remove scrollbar */
        body {
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            font-size: 12px;
            transform: scale(0.9);
            transform-origin: top left;
            width: 111.11%;
            height: 111.11%;
        }
        
        /* App container for proper layout */
        .app-container {
            display: flex;
            min-height: 100vh;
            position: relative;
            max-width: 2133px; /* 1920px * 1.11 */
            margin: 0 auto;
        }
        
        /* Fix for main content positioning */
        .main-content {
            flex: 1;
            padding: 15px 20px;
            margin-left: 250px; /* Match sidebar width */
            transition: margin-left 0.3s ease;
            position: relative;
            width: calc(100% - 250px); /* Match sidebar width */
            min-height: 100vh;
        }
        
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            transition: width 0.3s ease;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            overflow-y: auto;
        }
        
        /* When sidebar is collapsed */
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar-collapsed .main-content {
            margin-left: 70px;
            width: calc(100% - 70px);
        }
        
        /* Sidebar sections */
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #eee;
            flex-shrink: 0;
        }
        
        .sidebar-menu {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            flex-shrink: 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.collapsed {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .sidebar-collapsed .main-content {
                margin-left: 70px;
            }
        }
        
        /* Fix for top bar positioning */
        .top-bar {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
            position: relative;
            gap: 20px;
        }
        
        /* New top content container */
        .top-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: #555;
            font-size: 22px;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: color 0.2s, background-color 0.2s;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        .menu-toggle:hover {
            background-color: #f0f0f0;
            color: #4285f4;
        }
        
        .page-title {
            flex-shrink: 0;
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
        
        .user-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        /* Action button styling */
        .action-button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }
        
        .action-button.primary {
            background-color: #4285f4;
            color: white;
        }
        
        .action-button.primary:hover {
            background-color: #3367d6;
        }
        
        /* Search form styling */
        .search-form {
            position: relative;
        }
        
        .search-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .search-input-container input {
            padding: 8px 15px 8px 35px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 200px;
            font-size: 14px;
            background-color: #f8f8f8;
            transition: all 0.3s;
        }
        
        .search-input-container input:focus {
            width: 250px;
            border-color: #4285f4;
            outline: none;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.1);
        }
        
        .search-input-container button {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #777;
            font-size: 14px;
            cursor: pointer;
        }
        
        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 50px auto;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 600px;
            animation: slideIn 0.3s;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 10;
        }
        
        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
            max-height: calc(90vh - 120px);
        }
        
        .close-button {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #777;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            transition: background-color 0.2s;
        }
        
        .close-button:hover {
            color: #333;
            background-color: #f0f0f0;
        }
        
        .appointment-item {
            border-left: 4px solid;
            background-color: #fff;
            color: #333;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .appointment-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .appointment-header h3 {
            font-size: 16px;
            margin: 0;
            font-weight: 600;
        }
        
        .appointment-time {
            font-size: 14px;
            color: #666;
        }
        
        .appointment-details {
            margin-bottom: 10px;
        }
        
        .appointment-details p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .appointment-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .appointment-actions button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .view-appointment {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .view-appointment:hover {
            background-color: #e0e0e0;
        }
        
        .edit-appointment {
            background-color: #4285f4;
            color: white;
        }
        
        .edit-appointment:hover {
            background-color: #3367d6;
        }
        
        /* Fix for day view modal */
        #dayViewModal .modal-content {
            max-width: 700px;
        }
        
        /* Fix for appointment list */
        .appointment-list {
            max-height: 60vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        /* Fix for mobile view */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .modal-content {
                width: 95%;
                margin: 10px auto;
            }
        }
        
        /* Fix for modal animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
            <button class="menu-toggle" id="menuButton">
                <i class="fas fa-bars"></i>
            </button>
            <div class="top-content">
                <div class="page-title">
                    <h1>Calendar</h1>
                    <p><?= date('l, F j, Y') ?></p>
                </div>
                <div class="user-controls">
                    <button id="openBookingModal" class="action-button primary">
                        <i class="fas fa-plus"></i> New Booking
                    </button>
                    <div class="search-form">
                        <div class="search-input-container">
                            <input type="text" id="search_name" placeholder="Search by name...">
                            <button type="button" id="search_button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-layout">
            <!-- Calendar Section -->
            <div class="calendar-section">
                <div class="card">
                    <div class="card-header">
                        <div class="calendar-navigation">
                            <a href="index.php?month=<?= ($month == 1) ? 12 : $month-1 ?>&year=<?= ($month == 1) ? $year-1 : $year ?>" class="nav-arrow">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <h2 class="month-year"><?= date('F Y', strtotime("$year-$month-01")) ?></h2>
                            <a href="index.php?month=<?= ($month == 12) ? 1 : $month+1 ?>&year=<?= ($month == 12) ? $year+1 : $year ?>" class="nav-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <div class="view-options">
                            <button class="view-btn active" data-view="month">Month</button>
                            <button class="view-btn" data-view="week">Week</button>
                            <button class="view-btn" data-view="day">Day</button>
                        </div>
                    </div>

                    <div class="calendar-body">
                        <div class="weekday-header">
                            <div>Sun</div>
                            <div>Mon</div>
                            <div>Tue</div>
                            <div>Wed</div>
                            <div>Thu</div>
                            <div>Fri</div>
                            <div>Sat</div>
                        </div>

                        <div class="calendar">
                            <?php for ($i = 0; $i < $firstDayOfMonth; $i++): ?>
                                <div class="day empty"></div>
                            <?php endfor; ?>

                            <?php for ($day = 1; $day <= $totalDaysInMonth; $day++): ?>
                                <?php
                                $currentDate = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                                $isCurrentDay = ($currentDate === date('Y-m-d'));
                                ?>
                                <div class="day <?= $isCurrentDay ? 'current-day' : '' ?>">
                                    <div class="day-header">
                                        <span class="day-number"><?= $day ?></span>
                                        <?php if (isset($appointments[$day]) && count($appointments[$day]) > 0): ?>
                                            <span class="appointment-badge" data-count="<?= count($appointments[$day]) ?>"><?= count($appointments[$day]) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (isset($appointments[$day]) && count($appointments[$day]) > 0): ?>
                                        <div class="day-content">
                                            <?php
                                            $count = 0;
                                            foreach ($appointments[$day] as $appointment):
                                                if ($count < 2):
                                                    $timeFrom = date('g:i A', strtotime($appointment['booking_time_from']));
                                                    ?>
                                                    <div class="day-event" style="background-color: <?= $appointment['color'] ?>" data-id="<?= $appointment['id'] ?>">
                                                        <span class="event-time"><?= $timeFrom ?></span>
                                                        <span class="event-title"><?= htmlspecialchars($appointment['representative_name']) ?></span>
                                                    </div>
                                                <?php endif;
                                                $count++;
                                            endforeach;
                                            
                                            if ($count > 2): ?>
                                                <div class="more-events">+<?= $count - 2 ?> more</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Content -->
            <div class="dashboard-sidebar">
                <!-- Upcoming Appointments -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-calendar-check"></i> Upcoming Appointments</h2>
                    </div>
                    <div class="card-body">
                        <ul class="upcoming-events">
                            <?php
                            $currentDateTime = new DateTime();
                            $sevenDaysLater = (clone $currentDateTime)->modify('+7 days');
                            $upcomingAppointments = [];

                            // Collect upcoming appointments within the next 7 days
                            foreach ($appointments as $day => $dayAppointments) {
                                foreach ($dayAppointments as $appointment) {
                                    $appointmentDateTime = new DateTime($appointment['booking_date'] . ' ' . $appointment['booking_time_from']);
                                    if ($appointmentDateTime >= $currentDateTime && $appointmentDateTime <= $sevenDaysLater) {
                                        $upcomingAppointments[] = $appointment;
                                    }
                                }
                            }

                            // Sort the upcoming appointments by date and time
                            usort($upcomingAppointments, function($a, $b) {
                                $dateTimeA = new DateTime($a['booking_date'] . ' ' . $a['booking_time_from']);
                                $dateTimeB = new DateTime($b['booking_date'] . ' ' . $b['booking_time_from']);
                                return $dateTimeA <=> $dateTimeB;
                            });

                            // Display the sorted upcoming appointments
                            if (count($upcomingAppointments) > 0):
                                foreach ($upcomingAppointments as $appointment):
                                    $appointmentDate = new DateTime($appointment['booking_date']);
                                    $timeFrom = date('g:i A', strtotime($appointment['booking_time_from']));
                                    $timeTo = date('g:i A', strtotime($appointment['booking_time_to']));
                                    ?>
                                    <li class="event-item upcoming-appointment" data-id="<?= $appointment['id'] ?>">
                                        <div class="event-color" style="background-color: <?= $appointment['color'] ?>"></div>
                                        <div class="event-details">
                                            <div class="event-date"><?= $appointmentDate->format('D, M j') ?> · <?= $timeFrom ?> - <?= $timeTo ?></div>
                                            <div class="event-title"><?= htmlspecialchars($appointment['representative_name']) ?></div>
                                            <div class="event-location"><i class="fas fa-map-marker-alt"></i> <?= $appointment['room_name'] ?></div>
                                        </div>
                                    </li>
                                <?php endforeach;
                            else: ?>
                                <li class="no-events">No upcoming appointments</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <button id="openAddDepartmentModal" class="action-tile">
                                <i class="fas fa-building"></i>
                                <span>Add Department</span>
                            </button>
                            <button id="openAddRoomModal" class="action-tile">
                                <i class="fas fa-door-open"></i>
                                <span>Add Room</span>
                            </button>
                            <button id="viewAllAppointments" class="action-tile">
                                <i class="fas fa-list"></i>
                                <span>All Appointments</span>
                            </button>
                            <button id="exportCalendar" class="action-tile">
                                <i class="fas fa-file-export"></i>
                                <span>Export Calendar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="appointmentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>All Appointments</h2>
            <button class="close-button" id="closeAppointmentModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="appointments-list">
                <?php
                // Fetch all appointments
                $allAppointments = [];
                foreach ($appointments as $day => $dayAppointments) {
                    foreach ($dayAppointments as $appointment) {
                        $allAppointments[] = $appointment;
                    }
                }

                // Sort the appointments by date and time
                usort($allAppointments, function($a, $b) {
                    $dateTimeA = new DateTime($a['booking_date'] . ' ' . $a['booking_time_from']);
                    $dateTimeB = new DateTime($b['booking_date'] . ' ' . $b['booking_time_from']);
                    return $dateTimeA <=> $dateTimeB;
                });

                // Display the sorted appointments
                foreach ($allAppointments as $appointment):
                    $appointmentDate = new DateTime($appointment['booking_date']);
                    $timeFrom = date('g:i A', strtotime($appointment['booking_time_from']));
                    $timeTo = date('g:i A', strtotime($appointment['booking_time_to']));
                    ?>
                    <div class="appointment-card" data-appointment='<?= json_encode($appointment) ?>'>
                        <div class="appointment-color" style="background-color: <?= $appointment['color'] ?>"></div>
                        <div class="appointment-content">
                            <div class="appointment-date">
                                <?= $appointmentDate->format('l, F j, Y') ?>
                            </div>
                            <div class="appointment-title">
                                <?= htmlspecialchars($appointment['representative_name']) ?>
                            </div>
                            <div class="appointment-details">
                                <span><i class="fas fa-clock"></i> <?= $timeFrom ?> - <?= $timeTo ?></span>
                                <span><i class="fas fa-map-marker-alt"></i> <?= $appointment['room_name'] ?></span>
                                <span><i class="fas fa-building"></i> <?= $appointment['department_name'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Appointment Details</h2>
            <button class="close-button" id="closeViewModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div id="viewContainer"></div>
        </div>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Appointment</h2>
            <button class="close-button" id="closeEditModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST" action="api/update_appointment.php" class="booking-form">
                <input type="hidden" name="appointment_id" id="appointment_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_department">Department</label>
                        <select name="edit_department" id="edit_department" required>
                            <option value="">Select Department</option>
                            <?php
                            $departments->data_seek(0);
                            while ($department = $departments->fetch_assoc()): ?>
                                <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_room">Room</label>
                        <select name="edit_room" id="edit_room" required>
                            <option value="">Select Room</option>
                            <?php
                            $rooms->data_seek(0);
                            while ($room = $rooms->fetch_assoc()): ?>
                                <option value="<?= $room['id'] ?>"><?= $room['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_name">Research Adviser's Name</label>
                        <input type="text" name="edit_name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_representative_name">Representative Name</label>
                        <input type="text" name="edit_representative_name" id="edit_representative_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_id_number">Group Number</label>
                        <select name="edit_id_number" id="edit_id_number" required>
                            <option value="">Select Group Number</option>
                            <?php for ($i = 1; $i <= 200; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_set">Set</label>
                        <select name="edit_set" id="edit_set">
                            <option value="">Select Set</option>
                            <?php foreach (range('A', 'F') as $set): ?>
                                <option value="<?= $set ?>"><?= $set ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_date">Date</label>
                        <input type="date" name="edit_date" id="edit_date" required>
                    </div>
                </div>

                <div class="form-row time-inputs">
                    <div class="form-group">
                        <label>Time From</label>
                        <div class="time-picker">
                            <div class="time-input-container">
                                <input type="number" id="edit_time_from_hour" name="edit_time_from_hour" min="1" max="12" placeholder="Hour" required>
                                <button type="button" class="toggle-time-input" data-target="edit_time_from_hour_dropdown"><i class="fas fa-caret-down"></i></button>
                                <div class="time-dropdown" id="edit_time_from_hour_dropdown">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <div class="dropdown-item" data-value="<?= $i ?>"><?= $i ?></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span>:</span>
                            <div class="time-input-container">
                                <input type="number" id="edit_time_from_minute" name="edit_time_from_minute" min="0" max="59" step="1" placeholder="Min" required>
                                <button type="button" class="toggle-time-input" data-target="edit_time_from_minute_dropdown"><i class="fas fa-caret-down"></i></button>
                                <div class="time-dropdown" id="edit_time_from_minute_dropdown">
                                    <?php for ($i = 0; $i < 60; $i += 5): ?>
                                        <div class="dropdown-item" data-value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <select id="edit_time_from_ampm" name="edit_time_from_ampm" required>
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Time To</label>
                        <div class="time-picker">
                            <div class="time-input-container">
                                <input type="number" id="edit_time_to_hour" name="edit_time_to_hour" min="1" max="12" placeholder="Hour" required>
                                <button type="button" class="toggle-time-input" data-target="edit_time_to_hour_dropdown"><i class="fas fa-caret-down"></i></button>
                                <div class="time-dropdown" id="edit_time_to_hour_dropdown">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <div class="dropdown-item" data-value="<?= $i ?>"><?= $i ?></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span>:</span>
                            <div class="time-input-container">
                                <input type="number" id="edit_time_to_minute" name="edit_time_to_minute" min="0" max="59" step="1" placeholder="Min" required>
                                <button type="button" class="toggle-time-input" data-target="edit_time_to_minute_dropdown"><i class="fas fa-caret-down"></i></button>
                                <div class="time-dropdown" id="edit_time_to_minute_dropdown">
                                    <?php for ($i = 0; $i < 60; $i += 5): ?>
                                        <div class="dropdown-item" data-value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <select id="edit_time_to_ampm" name="edit_time_to_ampm" required>
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_reason">Agenda</label>
                    <textarea name="edit_reason" id="edit_reason" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_group_members">Remarks</label>
                    <textarea name="edit_group_members" id="edit_group_members" rows="3"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" id="delete_button" class="danger-button">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                    <button type="submit" id="save_button" class="primary-button">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Book Schedule</h2>
            <button class="close-button" id="closeBookingModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" class="booking-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select name="department" id="department" required>
                            <option value="">Select Department</option>
                            <?php
                            $departments->data_seek(0);
                            while ($department = $departments->fetch_assoc()): ?>
                                <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="room">Room</label>
                        <select name="room" id="room" required>
                            <option value="">Select Room</option>
                            <?php
                            $rooms->data_seek(0);
                            while ($room = $rooms->fetch_assoc()): ?>
                                <option value="<?= $room['id'] ?>"><?= $room['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Research Adviser's Name</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="representative_name">Representative Name</label>
                        <input type="text" name="representative_name" id="representative_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="id_number">Group Number</label>
                        <select name="id_number" id="id_number" required>
                            <option value="">Select Group Number</option>
                            <?php for ($i = 1; $i <= 200; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="set">Set</label>
                        <select name="set" id="set" required>
                            <option value="">Select Set</option>
                            <?php foreach (range('A', 'F') as $set): ?>
                                <option value="<?= $set ?>"><?= $set ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" name="date" id="date" required>
                    </div>
                </div>

                <div class="form-row time-inputs">
                    <div class="form-group">
                        <label>Time From</label>
                        <div class="time-picker">
                            <div class="time-input-container">
                                <input type="number" id="time_from_hour" name="time_from_hour" min="1" max="12" placeholder="Hour" required>
                                <button type="button" class="toggle-time-input" data-target="time_from_hour_dropdown"><i class="fas fa-caret-down"></i></button>
                                <div class="time-dropdown" id="time_from_hour_dropdown">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <div class="dropdown-item" data-value="<?= $i ?>"><?= $i ?></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span>:</span>
                            <div class="time-input-container">
                                <input type="number" id="time_from_minute" name="time_from_minute" min="0" max="59" step="1" placeholder="Min" required>
                                <button type="button" class="toggle-time-input" data-target="time_from_minute_dropdown"><i class="fas fa-caret-down"></i></button>
                                <div class="time-dropdown" id="time_from_minute_dropdown">
                                    <?php for ($i = 0; $i < 60; $i += 5): ?>
                                        <div class="dropdown-item" data-value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <select id="time_from_ampm" name="time_from_ampm" required>
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Time To</label>
                        <div class="time-picker">
                            <div class="time-input-container">
                                <input type="number" id="time_to_hour" name="time_to_hour" min="1" max="12" placeholder="Hour" required>
                                <button type="button" class="toggle-time-input" data-target="time_to_hour_dropdown"><i class="fas fa-caret-down"></i></button>
                                <div class="time-dropdown" id="time_to_hour_dropdown">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <div class="dropdown-item" data-value="<?= $i ?>"><?= $i ?></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span>:</span>
                            <div class="time-input-container">
                                <input type="number" id="time_to_minute" name="time_to_minute" min="0" max="59" step="1" placeholder="Min" required>
                                <button type="button" class="toggle-time-input" data-target="time_to_minute_dropdown"><i class="fas fa-caret-down"></i></button>
                                <div class="time-dropdown" id="time_to_minute_dropdown">
                                    <?php for ($i = 0; $i < 60; $i += 5): ?>
                                        <div class="dropdown-item" data-value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <select id="time_to_ampm" name="time_to_ampm" required>
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">Agenda</label>
                    <textarea name="reason" id="reason" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="group_members">Remarks</label>
                    <textarea name="group_members" id="group_members" rows="3"></textarea>
                </div>

                <!-- Conflict Resolution AI Component -->
                <div id="conflict-resolution-container" style="display: none;">
                    <div class="conflict-alert">
                        <h4>
                            <i class="fas fa-exclamation-triangle"></i>
                            Scheduling Conflict Detected
                            <span class="ai-badge"><i class="fas fa-robot"></i> AI Assistant</span>
                        </h4>
                        <p id="conflict-message">There are scheduling conflicts with your requested time. Please review the suggestions below.</p>
                        
                        <div class="conflict-details">
                            <h5>Alternative Times</h5>
                            <div id="alternative-times" class="alternatives-container">
                                <!-- Alternative time slots will be inserted here -->
                            </div>
                            
                            <h5>Alternative Rooms</h5>
                            <div id="alternative-rooms" class="alternatives-container">
                                <!-- Alternative rooms will be inserted here -->
                            </div>
                        </div>
                        
                        <div class="conflict-actions">
                            <button type="button" class="ignore-conflicts">Keep Original Time</button>
                            <button type="button" class="apply-alternative" disabled>Apply Selected Alternative</button>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="add_booking" class="primary-button">
                        <i class="fas fa-calendar-plus"></i> Book Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div id="addDepartmentModal" class="modal">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h2>Add Department</h2>
            <button class="close-button" id="closeAddDepartmentModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="api/add_department.php">
                <div class="form-group">
                    <label for="department_name">Department Name</label>
                    <input type="text" name="department_name" id="department_name" required>
                </div>
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="color" name="color" id="color" value="#ff0000" required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_department" class="primary-button">
                        <i class="fas fa-plus"></i> Add Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Room Modal -->
<div id="addRoomModal" class="modal">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h2>Add Room</h2>
            <button class="close-button" id="closeAddRoomModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="api/add_room.php">
                <div class="form-group">
                    <label for="room_name">Room Name</label>
                    <input type="text" name="room_name" id="room_name" required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_room" class="primary-button">
                        <i class="fas fa-plus"></i> Add Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Day View Modal -->
<div id="dayViewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="dayTitle">Appointments</h2>
            <button class="close-button" id="closeDayViewModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div id="appointmentList" class="appointment-list">
                <!-- Appointments will be loaded here dynamically -->
            </div>
            <div class="form-actions">
                <button type="button" id="openBookingFromDayView" class="primary-button">
                    <i class="fas fa-plus"></i> Add Appointment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Search Results Modal -->
<div id="searchModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Search Results</h2>
            <button class="close-button" id="closeSearchModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div id="searchResults"></div>
        </div>
    </div>
</div>

<!-- Add this element to hold the appointments data -->
<script id="appointmentsData" type="application/json">
    <?= json_encode($appointments) ?>
</script>
<script id="roomsData" type="application/json">
    <?= json_encode($rooms->fetch_all(MYSQLI_ASSOC)) ?>
</script>
<script id="departmentsData" type="application/json">
    <?= json_encode($departments->fetch_all(MYSQLI_ASSOC)) ?>
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/conflict-resolver.js?v=<?= time() ?>"></script>
<script defer src="js/script.js?<?= time() ?>"></script>

<!-- Modal initialization script -->
<script>
    // This script ensures all modals are properly initialized when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Modal initialization script running');
        
        // Direct initialization of all modals
        const modals = {
            'bookingModal': 'openBookingModal',
            'editModal': null,
            'viewModal': null,
            'addDepartmentModal': 'openAddDepartmentModal',
            'addRoomModal': 'openAddRoomModal',
            'dayViewModal': null,
            'appointmentModal': null
        };
        
        // Initialize each modal
        for (const [modalId, openButtonId] of Object.entries(modals)) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                console.error(`Modal with ID ${modalId} not found`);
                continue;
            }
            
            // Setup open button if provided
            if (openButtonId) {
                const openButton = document.getElementById(openButtonId);
                if (openButton) {
                    openButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        modal.style.display = 'block';
                        console.log(`Modal ${modalId} opened via direct initialization`);
                    });
                }
            }
            
            // Setup close button
            const closeButtonId = `close${modalId.charAt(0).toUpperCase() + modalId.slice(1)}`;
            const closeButton = document.getElementById(closeButtonId);
            if (closeButton) {
                closeButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    modal.style.display = 'none';
                    console.log(`Modal ${modalId} closed via direct initialization`);
                });
            }
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    console.log(`Modal ${modalId} closed by clicking outside via direct initialization`);
                }
            });
        }
        
        // Add click handlers to all buttons with data-modal attribute
        document.querySelectorAll('[data-modal]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const modalId = this.getAttribute('data-modal');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'block';
                    console.log(`Modal ${modalId} opened via data-modal attribute`);
                }
            });
        });
        
        // Add click handlers to all close buttons
        document.querySelectorAll('.close-button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                    console.log(`Modal closed via close button`);
                }
            });
        });
    });
</script>

<!-- Add this right before the closing body tag -->
<?php if ($searched_appointment): ?>
<script>
    // Data to pass to the view modal
    const searchedAppointmentData = <?= json_encode($searched_appointment) ?>;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Show the appointment details in the view modal
        const viewContainer = document.getElementById('viewContainer');
        if (viewContainer) {
            const timeFrom = new Date(`2000-01-01T${searchedAppointmentData.booking_time_from}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const timeTo = new Date(`2000-01-01T${searchedAppointmentData.booking_time_to}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            viewContainer.innerHTML = `
                <div class="appointment-details">
                    <p><strong>Research Adviser's Name:</strong> ${searchedAppointmentData.name}</p>
                    <p><strong>Group Number:</strong> ${searchedAppointmentData.id_number}</p>
                    <p><strong>Set:</strong> ${searchedAppointmentData.set}</p>
                    <p><strong>Department:</strong> ${searchedAppointmentData.department_name}</p>
                    <p><strong>Room:</strong> ${searchedAppointmentData.room_name}</p>
                    <p><strong>Date:</strong> ${searchedAppointmentData.booking_date}</p>
                    <p><strong>Time:</strong> ${timeFrom} - ${timeTo}</p>
                    <p><strong>Agenda:</strong> ${searchedAppointmentData.reason}</p>
                    <p><strong>Representative:</strong> ${searchedAppointmentData.representative_name}</p>
                    <p><strong>Remarks:</strong> ${searchedAppointmentData.group_members || "None"}</p>
                </div>
                <div class="form-actions-right" style="margin-top: 20px;">
                    <button type="button" class="edit-search-result" data-id="${searchedAppointmentData.id}">Edit Appointment</button>
                </div>
            `;
            
            // Function to show a modal
            function showModal(modal) {
                if (!modal) return;
                
                // Use flex display for centering
                modal.style.display = 'flex';
                modal.style.justifyContent = 'center';
                modal.style.alignItems = 'center';
                
                // Center the modal
                centerModal(modal);
                
                // Prevent body scrolling
                document.body.style.overflow = 'hidden';
            }
            
            // Function to hide a modal
            function hideModal(modal) {
                if (!modal) return;
                
                // Hide the modal
                modal.style.display = 'none';
                
                // Restore body scrolling
                document.body.style.overflow = '';
            }
            
            // Function to center a modal
            function centerModal(modal) {
                const modalContent = modal.querySelector('.modal-content');
                if (!modalContent) return;
                
                // Ensure the modal is using flex display
                modal.style.display = 'flex';
                modal.style.justifyContent = 'center';
                modal.style.alignItems = 'center';
                
                // Reset any previous styles
                modalContent.style.margin = 'auto';
                
                // Ensure the modal content doesn't exceed the viewport height
                const viewportHeight = window.innerHeight;
                const contentHeight = modalContent.scrollHeight;
                
                if (contentHeight > viewportHeight * 0.9) {
                    modalContent.style.maxHeight = `${viewportHeight * 0.9}px`;
                    modalContent.style.overflowY = 'auto';
                } else {
                    modalContent.style.maxHeight = 'none';
                }
            }
            
            // Show the view modal automatically
            const viewModal = document.getElementById('viewModal');
            
            // Close any other open modals first
            document.querySelectorAll('.modal').forEach(m => {
                if (m.id !== 'viewModal' && m.style.display === 'flex') {
                    hideModal(m);
                }
            });
            
            // Show the modal
            showModal(viewModal);
            
            // Add event listener to the edit button
            document.querySelector('.edit-search-result').addEventListener('click', function() {
                // Fill the edit form with the appointment data
                document.getElementById('appointment_id').value = searchedAppointmentData.id;
                document.getElementById('edit_department').value = searchedAppointmentData.department_id;
                document.getElementById('edit_name').value = searchedAppointmentData.name;
                document.getElementById('edit_id_number').value = searchedAppointmentData.id_number;
                document.getElementById('edit_set').value = searchedAppointmentData.set;
                document.getElementById('edit_date').value = searchedAppointmentData.booking_date;
                document.getElementById('edit_reason').value = searchedAppointmentData.reason;
                document.getElementById('edit_room').value = searchedAppointmentData.room_id;
                document.getElementById('edit_representative_name').value = searchedAppointmentData.representative_name;
                document.getElementById('edit_group_members').value = searchedAppointmentData.group_members;
                
                // Time handling - parse the time into components
                const timeFrom = new Date(`2000-01-01T${searchedAppointmentData.booking_time_from}`);
                const timeTo = new Date(`2000-01-01T${searchedAppointmentData.booking_time_to}`);
                
                const fromHour = timeFrom.getHours() % 12 || 12;
                const fromMinute = timeFrom.getMinutes();
                const fromAMPM = timeFrom.getHours() < 12 ? 'AM' : 'PM';
                
                const toHour = timeTo.getHours() % 12 || 12;
                const toMinute = timeTo.getMinutes();
                const toAMPM = timeTo.getHours() < 12 ? 'AM' : 'PM';
                
                document.getElementById('edit_time_from_hour').value = fromHour;
                document.getElementById('edit_time_from_minute').value = fromMinute.toString().padStart(2, '0');
                document.getElementById('edit_time_from_ampm').value = fromAMPM;
                
                document.getElementById('edit_time_to_hour').value = toHour;
                document.getElementById('edit_time_to_minute').value = toMinute.toString().padStart(2, '0');
                document.getElementById('edit_time_to_ampm').value = toAMPM;
                
                // Close the view modal and open the edit modal
                hideModal(viewModal);
                
                const editModal = document.getElementById('editModal');
                showModal(editModal);
            });
        }
    });
</script>
<?php endif; ?>

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
    });
</script>
</body>
</html>