<?php
// Set secure cookie parameters
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'crad.schoolmanagementsystem2.com', // Set to your domain
    'secure' => true, // Only send over HTTPS
    'httponly' => true, // Prevent JavaScript access
    'samesite' => 'Strict' // Prevent CSRF
]);

session_start();
use \Firebase\JWT\JWT; // Ensure to include the JWT namespace

// Include Composer autoload
require 'vendor/autoload.php'; // Adjust the path if needed

// Function to verify the JWT
function verifyJWT($token) {
    $key = 'your_secret_key'; // Same secret key used to sign the JWT

    try {
        $decoded = JWT::decode($token, $key, ['HS256']);
        return $decoded; // You can return the decoded data (user info, etc.)
    } catch (Exception $e) {
        return null; // Token is invalid
    }
}

// Check if the JWT cookie is set
if (isset($_COOKIE['jwt'])) {
    $decoded = verifyJWT($_COOKIE['jwt']);
    if ($decoded) {
        // User is authenticated, proceed with application logic
        $_SESSION['user_id'] = $decoded->userId; // Store user ID in session if needed
    } else {
        // Token is invalid, redirect to login
        header('Location: login.php');
        exit();
    }
} else {
    // No token found, redirect to login
    header('Location: login.php');
    exit();
}

// Regenerate session ID upon login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Regenerate session ID
if (!isset($_SESSION['SESSION_CREATED'])) {
    session_regenerate_id(true);
    $_SESSION['SESSION_CREATED'] = time();
}

// CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

$csrf_token = generateCsrfToken();
?>

<?php
// Database connection
$conn = new mysqli('localhost', 'crad_crad', 'crad', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle form submissions
if (isset($_POST['add_booking'])) {
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

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
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

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
    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

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
</head>
<body>

<div class="container">
    <header>
        <img src="assets/bcplogo.png" alt="Logo" class="logo">
        <h1>Booking Calendar System</h1>
        <a href="logout.php" class="logout-button">Logout</a> <!-- Logout Button -->
    </header>

    <!-- Booking Form and Actions Section -->
    <div class="form-actions">
        <div class="form-container">
            <form method="POST" class="form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
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
                <strong><?= $day ?></strong>
                <?php if (isset($appointments[$day])): ?>
                    <?php foreach ($appointments[$day] as $appointment): ?>
                        <div class="appointment" style="background-color: <?= $appointment['color'] ?>;">
                            <?= htmlspecialchars($appointment['name']) ?> <br>
                            <?= htmlspecialchars($appointment['reason']) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Modals -->
    <div id="addDepartmentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add Department</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="text" name="department_name" placeholder="Department Name" required>
                <input type="color" name="color" required> <!-- Color input -->
                <button type="submit" name="add_department">Add Department</button>
            </form>
        </div>
    </div>

    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add Room</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="text" name="room_name" placeholder="Room Name" required>
                <button type="submit" name="add_room">Add Room</button>
            </form>
        </div>
    </div>
</div>

<script src="js/script.js"></script> <!-- External JS File -->
</body>
</html>
