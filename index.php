<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection and PHP logic remains the same
$conn = new mysqli('localhost', 'crad_crad', 'crad', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle form submissions
if (isset($_POST['add_booking'])) {
    $name = $_POST['name'];
    $id_number = $_POST['id_number'];
    $department = $_POST['department'];
    $room = $_POST['room'];
    $date = $_POST['date'];
    $time_range = $_POST['time_range'];
    list($time_from, $time_to) = explode(' - ', $time_range);
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO bookings (name, id_number, department_id, room_id, booking_date, booking_time_from, booking_time_to, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiissss", $name, $id_number, $department, $room, $date, $time_from, $time_to, $reason);
    $stmt->execute();
    $stmt->close();

    header('Location: index.php');
    exit();
}

// Handle department addition
if (isset($_POST['add_department'])) {
    $department_name = $_POST['department_name'];
    $color = $_POST['color']; 
    $stmt = $conn->prepare("INSERT INTO departments (name, color) VALUES (?, ?)");
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
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE name LIKE ?");
    $search_param = "%$search_name%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $searched_appointment = $stmt->get_result()->fetch_assoc();
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
    <title>Booking Calendar System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.js"></script>
    <script defer src="js/script.js"></script>
</head>
<body>
<button class="menu-button" id="menuButton">&#9776;</button> <!-- Menu button -->

<div class="sidebar" id="sidebar">
    <a href="home.php">HOME</a>
    <a href="index.php">BOOKING</a>
    <a href="hr.php">HR</a>
    <a href="its.php">ITS</a>
    <a href="osas.php">OSAS</a>
    <a href="faculty.php">FACULTY</a>
</div>

    <div class="container">
        <header>
            <img src="assets/bcplogo.png" alt="Logo" class="logo">
            <h1>Booking Calendar System</h1>
            <a href="logout.php" class="logout-button">Logout</a>
        </header>

        <div class="form-actions">
            <div class="search-container">
                <div class="left">
                    <form method="POST">
                        <input type="text" name="search_name" placeholder="Search by Name" required>
                        <button type="submit" name="search_booking">Search</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <div class="form-container">
                <form method="POST" class="form">
                    <div class="form-grid">
                        <input type="text" name="name" placeholder="Group Name" required>
                        <input type="text" name="id_number" placeholder="Code Number" required>
                        <input type="date" name="date" required>
                        <input type="text" name="time_range" id="time_range" placeholder="From - To" required>
                        <textarea name="reason" placeholder="Purpose" required></textarea>
                        <select name="department" required>
                            <option value="">Department</option>
                            <?php while ($department = $departments->fetch_assoc()): ?>
                                <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                        <select name="room" required>
                            <option value="">Room Number</option>
                            <?php while ($room = $rooms->fetch_assoc()): ?>
                                <option value="<?= $room['id'] ?>"><?= $room['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_booking" class="book-button">Book</button>
                </form>
            </div>

            <div class="form-right">
            <button type="button" data-modal="room">Add Room</button>
            <button type="button" data-modal="department">Add Department</button>
            </div>
            <div class="right">
                    <form method="POST" action="download_appointments.php">
                        <button type="submit" name="download_appointments">Download All Appointments</button>
                    </form>
                </div>

        </div>

        <div class="navigation">
            <a href="index.php?month=<?= ($month == 1) ? 12 : $month-1 ?>&year=<?= ($month == 1) ? $year-1 : $year ?>" class="nav-button">Previous</a>
            <span class="month-year"><?= date('F Y', strtotime("$year-$month-01")) ?></span>
            <a href="index.php?month=<?= ($month == 12) ? 1 : $month+1 ?>&year=<?= ($month == 12) ? $year+1 : $year ?>" class="nav-button">Next</a>
        </div>

        <div class="calendar">
            <div>Sunday</div>
            <div>Monday</div>
            <div>Tuesday</div>
            <div>Wednesday</div>
            <div>Thursday</div>
            <div>Friday</div>
            <div>Saturday</div>

            <?php for ($i = 0; $i < $firstDayOfMonth; $i++): ?>
                <div class="day"></div>
            <?php endfor; ?>

            <?php for ($day = 1; $day <= $totalDaysInMonth; $day++): ?>
                <div class="day">
                    <div class="day-number"><?= $day ?></div>
                    <?php if (isset($appointments[$day])): ?>
                        <?php foreach ($appointments[$day] as $appointment): ?>
                            <div class="appointment" data-id="<?= $appointment['id'] ?>" style="background-color: <?= $appointment['color'] ?>">
                                <?= $appointment['name'] ?><br>
                                <?= $appointment['department_name'] ?><br>
                                <?= date('g:i A', strtotime($appointment['booking_time_from'])) ?> - <?= date('g:i A', strtotime($appointment['booking_time_to'])) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Modals -->
    <div id="editModal" class="modal" data-show-modal="<?= isset($searched_appointment) ? 'true' : 'false' ?>">
        <div class="modal-content">
            <span class="close" id="closeEditModal">&times;</span>
            <h2>Edit Appointment</h2>
            <form id="editForm">
                <input type="hidden" name="appointment_id" id="appointment_id" value="<?= $searched_appointment['id'] ?? '' ?>">
                <input type="text" name="edit_name" id="edit_name" value="<?= $searched_appointment['name'] ?? '' ?>" required>
                <input type="text" name="edit_id_number" id="edit_id_number" value="<?= $searched_appointment['id_number'] ?? '' ?>" required>
                <input type="date" name="edit_date" id="edit_date" value="<?= $searched_appointment['booking_date'] ?? '' ?>" required>
                <input type="text" name="edit_time_range" id="edit_time_range" value="<?= isset($searched_appointment) ? date('g:i A', strtotime($searched_appointment['booking_time_from'])) . ' - ' . date('g:i A', strtotime($searched_appointment['booking_time_to'])) : '' ?>" required>
                <textarea name="edit_reason" id="edit_reason" required><?= $searched_appointment['reason'] ?? '' ?></textarea>
                <select name="edit_department" id="edit_department" required>
                    <option value="">Department</option>
                    <?php
                    $departments->data_seek(0);
                    while ($department = $departments->fetch_assoc()): ?>
                        <option value="<?= $department['id'] ?>" <?= (isset($searched_appointment) && $searched_appointment['department_id'] == $department['id']) ? 'selected' : '' ?>><?= $department['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <select name="edit_room" id="edit_room" required>
                    <option value="">Room Number</option>
                    <?php
                    $rooms->data_seek(0);
                    while ($room = $rooms->fetch_assoc()): ?>
                        <option value="<?= $room['id'] ?>" <?= (isset($searched_appointment) && $searched_appointment['room_id'] == $room['id']) ? 'selected' : '' ?>><?= $room['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" id="save_button">Save Changes</button>
                <button type="button" id="delete_button">Delete Appointment</button>
            </form>
        </div>
    </div>

    <div id="addDepartmentModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAddDepartmentModal">&times;</span>
            <h2>Add Department</h2>
            <form method="POST">
                <input type="text" name="department_name" placeholder="Department Name" required>
                <input type="color" name="color" value="#ff0000" required>
                <button type="submit" name="add_department">Add Department</button>
            </form>
        </div>
    </div>

    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAddRoomModal">&times;</span>
            <h2>Add Room</h2>
            <form method="POST">
                <input type="text" name="room_name" placeholder="Room Name" required>
                <button type="submit" name="add_room">Add Room</button>
            </form>
        </div>
    </div>
</body>
</html>