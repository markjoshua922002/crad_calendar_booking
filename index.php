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
        #sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #f8f9fa;
            transition: transform 0.3s ease;
        }
        #sidebar.collapsed {
            transform: translateX(-100%);
        }
        #page-content {
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }
        #page-content.collapsed {
            margin-left: 0;
        }
    </style>
    <script src="./js/jquery/jquery.min.js"></script>
</head>
<body>
    <!-- Sidebar Section -->
    <div id="sidebar" class="bg-light text-center shadow">
        <div class="p-3">
            <img src="./css/bcp_logo.png" alt="Logo" class="logo">
            <h4 class="mb-4">Dashboard</h4>
            <ul class="nav flex-column">
                <li class="nav-item mb-1">
                    <a class="nav-link active rounded" href="dashboard_admin.php">Home</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="index.php">Student List</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="picture.php">Pictures</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="logs.php">Logbook</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="users.php">Users</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="registrar.php">Registrar</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="hr.php">HR</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="its.php">ITS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded text-danger" href="#" id="logout">Logout</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div id="page-content">
        <nav>
            <button class="btn shadow" type="button" id="toggle-sidebar">☰ Menu</button>
        </nav>
        <div class="container-fluid">
            <header>
                <img src="assets/bcplogo.png" alt="Logo" class="logo">
                <h1>Booking Calendar System</h1>
                <a href="logout.php" class="logout-button">Logout</a>
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
    </div>

    <script src="js/script.js"></script> <!-- External JavaScript File -->

   
</body>
</html>
