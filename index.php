<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'crad_crad', 'crad', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle form submissions for booking
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

// Fetch departments, rooms, and bookings
$departments = $conn->query("SELECT * FROM departments");
$rooms = $conn->query("SELECT * FROM rooms");

$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$firstDayOfMonth = date('w', strtotime("$year-$month-01"));
$totalDaysInMonth = date('t', strtotime("$year-$month-01"));

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
</head>
<body>

<!-- Sidebar Navigation -->
<div id="sidebar" class="bg-light text-center shadow">
    <div class="p-3">
        <img src="./css/bcp_logo.png" alt="Logo" class="logo">
        <h4 class="mb-4">Dashboard</h4>
        <ul class="nav flex-column">
            <li class="nav-item mb-1"><a class="nav-link active rounded" href="dashboard_admin.php">Home</a></li>
            <li class="nav-item mb-1"><a class="nav-link rounded" href="index.php">Student List</a></li>
            <li class="nav-item mb-1"><a class="nav-link rounded" href="picture.php">Pictures</a></li>
            <li class="nav-item mb-1"><a class="nav-link rounded" href="logs.php">Logbook</a></li>
            <li class="nav-item mb-1"><a class="nav-link rounded" href="users.php">Users</a></li>
            <li class="nav-item mb-1"><a class="nav-link rounded" href="registrar.php">Registrar</a></li>
            <li class="nav-item mb-1"><a class="nav-link rounded" href="hr.php">HR</a></li>
            <li class="nav-item mb-1"><a class="nav-link rounded" href="its.php">ITS</a></li>
            <li class="nav-item"><a class="nav-link rounded text-danger" href="logout.php">Logout</a></li>
        </ul>
    </div>
</div>

<!-- Toggle Sidebar Button -->
<button id="sidebar-toggle" style="position:fixed; top:20px; left:20px; z-index:1000;">Toggle Sidebar</button>

<div class="container">
    <header>
        <img src="assets/bcplogo.png" alt="Logo" class="logo">
        <h1>Booking Calendar System</h1>
        <a href="logout.php" class="logout-button">Logout</a>
    </header>

    <!-- Booking Form -->
    <div class="form-container">
        <form method="POST">
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
                <option value="">Room</option>
                <?php while ($room = $rooms->fetch_assoc()): ?>
                    <option value="<?= $room['id'] ?>"><?= $room['name'] ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="add_booking">Book</button>
        </form>
    </div>

    <!-- Calendar Navigation -->
    <div class="navigation">
        <a href="index.php?month=<?= ($month == 1) ? 12 : $month-1 ?>&year=<?= ($month == 1) ? $year-1 : $year ?>" class="nav-button">Previous</a>
        <span class="month-year"><?= date('F Y', strtotime("$year-$month-01")) ?></span>
        <a href="index.php?month=<?= ($month == 12) ? 1 : $month+1 ?>&year=<?= ($month == 12) ? $year+1 : $year ?>" class="nav-button">Next</a>
    </div>

    <!-- Calendar Grid -->
    <div class="calendar">
        <div>Sunday</div><div>Monday</div><div>Tuesday</div><div>Wednesday</div><div>Thursday</div><div>Friday</div><div>Saturday</div>

        <?php for ($i = 0; $i < $firstDayOfMonth; $i++): ?>
            <div class="day"></div>
        <?php endfor; ?>

        <?php for ($day = 1; $day <= $totalDaysInMonth; $day++): ?>
            <div class="day">
                <div class="day-number"><?= $day ?></div>
                <?php if (isset($appointments[$day])): ?>
                    <?php foreach ($appointments[$day] as $appointment): ?>
                        <div class="appointment" style="background-color: <?= $appointment['color'] ?>">
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

<!-- Modal Templates for Adding Departments, Rooms, and Editing Appointments -->
<!-- Department Modal -->
<div id="addDepartmentModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeAddDepartmentModal">&times;</span>
        <form method="POST">
            <input type="text" name="department_name" placeholder="Department Name" required>
            <input type="color" name="color" value="#ff0000" required>
            <button type="submit" name="add_department">Add Department</button>
        </form>
    </div>
</div>

<!-- Room Modal -->
<div id="addRoomModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeAddRoomModal">&times;</span>
        <form method="POST">
            <input type="text" name="room_name" placeholder="Room Name" required>
            <button type="submit" name="add_room">Add Room</button>
        </form>
    </div>
</div>

<!-- Edit Appointment Modal -->
<div id="editAppointmentModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeEditAppointmentModal">&times;</span>
        <form method="POST" id="editAppointmentForm">
            <input type="hidden" name="booking_id" id="editBookingId">
            <input type="text" name="name" id="editName" placeholder="Name" required>
            <input type="date" name="date" id="editDate" required>
            <input type="time" name="time" id="editTime" required>
            <textarea name="reason" id="editReason" placeholder="Reason" required></textarea>
            <select name="department" id="editDepartment" required>
                <option value="">Department</option>
                <?php mysqli_data_seek($departments, 0); while ($department = $departments->fetch_assoc()): ?>
                    <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                <?php endwhile; ?>
            </select>
            <select name="room" id="editRoom" required>
                <option value="">Room</option>
                <?php mysqli_data_seek($rooms, 0); while ($room = $rooms->fetch_assoc()): ?>
                    <option value="<?= $room['id'] ?>"><?= $room['name'] ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="update_booking">Update Appointment</button>
            <button type="submit" name="delete_booking" class="delete-button">Delete Appointment</button>
        </form>
    </div>
</div>

<script src="js/script.js"></script>
<script>
    document.getElementById('sidebar-toggle').onclick = function() {
        var sidebar = document.getElementById('sidebar');
        if (sidebar.style.display === 'none' || sidebar.style.display === '') {
            sidebar.style.display = 'block';
        } else {
            sidebar.style.display = 'none';
        }
    };
</script>
</body>
</html>
