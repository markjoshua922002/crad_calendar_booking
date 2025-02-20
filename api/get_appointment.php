<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli('localhost', 'crad_crad', 'crad', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT bookings.*, departments.name as department_name, rooms.name as room_name FROM bookings JOIN departments ON bookings.department_id = departments.id JOIN rooms ON bookings.room_id = rooms.id WHERE bookings.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

// Fetch departments and rooms for the modal
$departments = $conn->query("SELECT * FROM departments");
$rooms = $conn->query("SELECT * FROM rooms");

$response = [
    'id' => $appointment['id'],
    'name' => $appointment['name'],
    'id_number' => $appointment['id_number'],
    'department_id' => $appointment['department_id'],
    'room_id' => $appointment['room_id'],
    'booking_date' => $appointment['booking_date'],
    'booking_time_from' => date('h:i A', strtotime($appointment['booking_time_from'])),
    'booking_time_to' => date('h:i A', strtotime($appointment['booking_time_to'])),
    'reason' => $appointment['reason'],
    'departments' => $departments->fetch_all(MYSQLI_ASSOC),
    'rooms' => $rooms->fetch_all(MYSQLI_ASSOC)
];

echo json_encode($response);
?>
