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

// Handle searching for an appointment
$search_results = [];
if (isset($_POST['search_booking'])) {
    $search_name = $_POST['search_name'];
    $stmt = $conn->prepare("SELECT bookings.*, departments.name as department_name, rooms.name as room_name 
        FROM bookings 
        JOIN departments ON bookings.department_id = departments.id 
        JOIN rooms ON bookings.room_id = rooms.id 
        WHERE bookings.name LIKE CONCAT('%', ?, '%')");
    $stmt->bind_param("s", $search_name);
    $stmt->execute();
    $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
        #searchResults div {
            padding: 10px;
            border: 1px solid #ccc;
            margin: 5px 0;
            cursor: pointer;
        }
        #searchResults div:hover {
            background-color: #f0f0f0;
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
        <div class="form-container">
            <form method="POST" class="form">
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
                            <span class="name"><?= $appointment['name'] ?></span><br>
                            <span class="department_id" style="display: none;"><?= $appointment['department_id'] ?></span>
                            <span class="room_id" style="display: none;"><?= $appointment['room_id'] ?></span>
                            <span class="id_number"><?= $appointment['id_number'] ?></span><br>
                            <span class="booking_date"><?= $appointment['booking_date'] ?></span><br>
                            <span class="booking_time"><?= $appointment['booking_time'] ?></span><br>
                            <span class="reason"><?= $appointment['reason'] ?></span><br>
                            <button class="edit-button" onclick="openEditModal(<?= json_encode($appointment) ?>)">Edit</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>

    <div id="searchResults" style="margin: 20px 0;"></div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeEditModal">&times;</span>
            <h2>Edit Appointment</h2>
            <form method="POST" action="edit_booking.php">
                <input type="hidden" name="appointment_id" id="appointment_id">
                <input type="text" name="edit_name" id="edit_name" required>
                <input type="text" name="edit_id_number" id="edit_id_number" required>
                <input type="date" name="edit_date" id="edit_date" required>
                <input type="time" name="edit_time" id="edit_time" required>
                <textarea name="edit_reason" id="edit_reason" required></textarea>
                <select name="edit_department" id="edit_department" required>
                    <?php while ($department = $departments->fetch_assoc()): ?>
                        <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <select name="edit_room" id="edit_room" required>
                    <?php while ($room = $rooms->fetch_assoc()): ?>
                        <option value="<?= $room['id'] ?>"><?= $room['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="update-button">Update</button>
            </form>
        </div>
    </div>
</div>

<script src="js/script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editModal');
        const closeEditModal = document.getElementById('closeEditModal');
        
        closeEditModal.onclick = function() {
            editModal.style.display = "none";
        }

        // Function to open edit modal with search results
        window.openEditModal = function(appointment) {
            document.getElementById('appointment_id').value = appointment.id;
            document.getElementById('edit_name').value = appointment.name;
            document.getElementById('edit_id_number').value = appointment.id_number;
            document.getElementById('edit_date').value = appointment.booking_date;
            document.getElementById('edit_time').value = appointment.booking_time;
            document.getElementById('edit_reason').value = appointment.reason;
            document.getElementById('edit_department').value = appointment.department_id;
            document.getElementById('edit_room').value = appointment.room_id;

            editModal.style.display = "block";
        }

        // Event listener for displaying search results
        const searchForm = document.querySelector('form[method="POST"]');
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchName = e.target.search_name.value;

            // Clear previous search results
            const resultsContainer = document.getElementById('searchResults');
            resultsContainer.innerHTML = '';

            // Fetch search results
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `search_name=${encodeURIComponent(searchName)}&search_booking=1`
            })
            .then(response => response.text())
            .then(data => {
                // Parse the response data
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');

                // Update the modal content
                const appointments = doc.querySelectorAll('.appointment');
                appointments.forEach(appointment => {
                    const appointmentData = {
                        id: appointment.dataset.id,
                        name: appointment.querySelector('.name').textContent,
                        id_number: appointment.querySelector('.id_number').textContent,
                        booking_date: appointment.querySelector('.booking_date').textContent,
                        booking_time: appointment.querySelector('.booking_time').textContent,
                        reason: appointment.querySelector('.reason').textContent,
                        department_id: appointment.querySelector('.department_id').value,
                        room_id: appointment.querySelector('.room_id').value
                    };

                    const resultItem = document.createElement('div');
                    resultItem.textContent = appointmentData.name; // Display appointment name
                    resultItem.onclick = () => openEditModal(appointmentData);
                    resultsContainer.appendChild(resultItem);
                });
            });
        });
    });
</script>
</body>
</html>
