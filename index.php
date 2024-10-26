<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<?php
// Database connection
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
    $time = $_POST['time'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO bookings (name, id_number, department_id, room_id, booking_date, booking_time, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiisss", $name, $id_number, $department, $room, $date, $time, $reason);
    $stmt->execute();
    $stmt->close();

    header('Location: index.php');
    exit();
}

// Handle department addition
if (isset($_POST['add_department'])) {
    $department_name = $_POST['department_name'];
    $color = $_POST['color']; // Get the color input
    $stmt = $conn->prepare("INSERT INTO departments (name, color) VALUES (?, ?)");
    $stmt->bind_param("ss", $department_name, $color); // Bind color parameter
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
    <link rel="stylesheet" href="css/style.css"> <!-- External CSS File -->
    <style>
        /* Sidebar styles */
        #sidebar {
            height: 100vh; /* Full height */
            width: 250px; /* Fixed width */
            position: fixed; /* Fixed position */
            top: 0;
            left: 0;
            background-color: #f8f9fa; /* Light background */
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        #page-content {
            margin-left: 250px; /* Space for sidebar */
            transition: margin-left 0.3s ease; /* Smooth transition */
        }
    </style>
</head>
<body>

<div id="sidebar" class="bg-light text-center shadow">
    <div class="p-3">
        <img src="./css/bcp_logo.png" alt="Logo" class="logo">
        <h4 class="mb-4">Dashboard</h4>
        <ul class="nav flex-column">
            <li class="nav-item mb-1">
                <a class="nav-link active rounded" href="dashboard_admin.php">Home</a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link rounded" href="#">Bookings</a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link rounded" href="#">Departments</a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link rounded" href="#">Rooms</a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link rounded" href="logout.php">Logout</a>
            </li>
        </ul>
    </div>
</div>

<div class="container" id="page-content">
    <header>
        <img src="assets/bcplogo.png" alt="Logo" class="logo">
        <h1>Booking Calendar System</h1>
        <a href="logout.php" class="logout-button">Logout</a> <!-- Logout Button -->
    </header>

    <!-- Booking Form and Actions Section -->
    <div class="form-actions">
        <div class="form-container">
            <form method="POST" class="form">
                <div class="form-grid">
                    <input type="text" name="name" placeholder="Name" required>
                    <input type="text" name="id_number" placeholder="ID Number" required>
                    <input type="date" name="date" required>
                    <input type="time" name="time" required>
                    <textarea name="reason" placeholder="Reason" required></textarea>
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

        <!-- Right Action Section for Add Department/Room -->
        <div class="form-right">
            <button type="button" class="add-action" id="add_department_button">Add Department</button>
            <button type="button" class="add-action" id="add_room_button">Add Room</button>
        </div>
    </div>

    <!-- Calendar Navigation -->
    <div class="navigation">
        <a href="index.php?month=<?= ($month == 1) ? 12 : $month-1 ?>&year=<?= ($month == 1) ? $year-1 : $year ?>" class="nav-button">Previous</a>
        <span class="month-year"><?= date('F Y', strtotime("$year-$month-01")) ?></span>
        <a href="index.php?month=<?= ($month == 12) ? 1 : $month+1 ?>&year=<?= ($month == 12) ? $year+1 : $year ?>" class="nav-button">Next</a>
    </div>

    <!-- Calendar Grid -->
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
                            <?= $appointment['booking_time'] ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Edit Appointment Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Appointment</h2>
        <form method="POST">
            <input type="hidden" name="appointment_id" id="appointment_id">
            <input type="text" name="edit_name" id="edit_name" placeholder="Name" required>
            <input type="text" name="edit_reason" id="edit_reason" placeholder="Reason" required>
            <button type="submit" name="edit_booking" class="save-button">Save Changes</button>
        </form>
    </div>
</div>

<!-- Add Department Modal -->
<div id="addDepartmentModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add Department</h2>
        <form method="POST">
            <input type="text" name="department_name" placeholder="Department Name" required>
            <input type="color" name="color" required> <!-- Color Picker -->
            <button type="submit" name="add_department" class="save-button">Add Department</button>
        </form>
    </div>
</div>

<!-- Add Room Modal -->
<div id="addRoomModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add Room</h2>
        <form method="POST">
            <input type="text" name="room_name" placeholder="Room Name" required>
            <button type="submit" name="add_room" class="save-button">Add Room</button>
        </form>
    </div>
</div>

<script src="js/script.js"></script> <!-- External JS File -->
</body>
</html>
