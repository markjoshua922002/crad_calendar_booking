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

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT bookings.*, departments.name as department_name, rooms.name as room_name FROM bookings JOIN departments ON bookings.department_id = departments.id JOIN rooms ON bookings.room_id = rooms.id WHERE bookings.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $appointment = $result->fetch_assoc();
    echo json_encode($appointment);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Appointment not found.']);
}

$stmt->close();
$conn->close();
?>
