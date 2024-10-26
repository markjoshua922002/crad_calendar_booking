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
    <style>
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
        .search-container {
            margin-bottom: 20px;
        }
        .search-container input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: calc(100% - 22px);
        }
        .search-container button {
            padding: 10px;
            background-color: #00509e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 5px;
        }
        .search-container button:hover {
            background-color: #0073e6;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="home.php">HOME</a>
    <a href="index.php">BOOKING</a>
    <a href="hr.php">HR</a>
    <a href="its.php">ITS</a>
    <a href="osas.php">OSAS</a>
</div>

<div class="container" style="margin-left: 170px;">
    <header>
        <img src="assets/bcplogo.png" alt="Logo" class="logo">
        <h1>Booking Calendar System</h1>
        <a href="logout.php" class="logout-button">Logout</a>
    </header>

    <div class="form-actions">
        <div class="search-container">
            <form method="POST">
                <input type="text" name="search_name" placeholder="Search by Name" required>
                <button type="submit" name="search_booking">Search</button>
            </form>
        </div>

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

        <div class="form-right">
            <button type="button" class="add-action" id="add_department_button">Add Department</button>
            <button type="button" class="add-action" id="add_room_button">Add Room</button>
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
                            <?= $appointment['name'] ?> - <?= $appointment['room_name'] ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Department Modal -->
<div id="add_department_modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add Department</h2>
        <form method="POST">
            <input type="text" name="department_name" placeholder="Department Name" required>
            <input type="color" name="color" required>
            <button type="submit" name="add_department">Add</button>
        </form>
    </div>
</div>

<!-- Room Modal -->
<div id="add_room_modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add Room</h2>
        <form method="POST">
            <input type="text" name="room_name" placeholder="Room Name" required>
            <button type="submit" name="add_room">Add</button>
        </form>
    </div>
</div>

<!-- Edit Appointment Modal -->
<div id="edit_appointment_modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Appointment</h2>
        <form id="edit_appointment_form">
            <input type="text" name="edit_name" placeholder="Name" required>
            <input type="text" name="edit_id_number" placeholder="ID Number" required>
            <input type="date" name="edit_date" required>
            <input type="time" name="edit_time" required>
            <textarea name="edit_reason" placeholder="Reason" required></textarea>
            <select name="edit_department" required>
                <option value="">Department</option>
                <?php while ($department = $departments->fetch_assoc()): ?>
                    <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                <?php endwhile; ?>
            </select>
            <select name="edit_room" required>
                <option value="">Room Number</option>
                <?php while ($room = $rooms->fetch_assoc()): ?>
                    <option value="<?= $room['id'] ?>"><?= $room['name'] ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="edit_booking" class="edit-button">Save Changes</button>
            <button type="button" id="delete_button">Delete Appointment</button>
        </form>
    </div>
</div>

<script src="js/script.js"></script>
<script>
    document.getElementById('add_department_button').onclick = function() {
        document.getElementById('add_department_modal').style.display = 'block';
    }
    document.getElementById('add_room_button').onclick = function() {
        document.getElementById('add_room_modal').style.display = 'block';
    }

    document.querySelectorAll('.close').forEach(item => {
        item.onclick = function() {
            this.closest('.modal').style.display = 'none';
        }
    });

    // Open edit appointment modal
    document.querySelectorAll('.appointment').forEach(item => {
        item.onclick = function() {
            const appointmentId = this.getAttribute('data-id');
            // Fetch appointment data with AJAX and populate the edit modal fields
            fetch('get_appointment.php?id=' + appointmentId)
                .then(response => response.json())
                .then(data => {
                    document.querySelector('input[name="edit_name"]').value = data.name;
                    document.querySelector('input[name="edit_id_number"]').value = data.id_number;
                    document.querySelector('input[name="edit_date"]').value = data.booking_date;
                    document.querySelector('input[name="edit_time"]').value = data.booking_time;
                    document.querySelector('textarea[name="edit_reason"]').value = data.reason;
                    document.querySelector('select[name="edit_department"]').value = data.department_id;
                    document.querySelector('select[name="edit_room"]').value = data.room_id;
                    document.getElementById('edit_appointment_modal').style.display = 'block';
                });
        }
    });

    document.getElementById('delete_button').onclick = function() {
        const appointmentId = document.querySelector('.appointment.active').getAttribute('data-id');
        fetch('delete_appointment.php?id=' + appointmentId, { method: 'DELETE' })
            .then(() => {
                document.getElementById('edit_appointment_modal').style.display = 'none';
                location.reload(); // Refresh the page after deletion
            });
    }
</script>
</body>
</html>
