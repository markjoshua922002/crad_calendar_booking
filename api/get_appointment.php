<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// Get appointment ID
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Appointment ID is required']);
    exit();
}

$appointment_id = $_GET['id'];

// Get the appointment details
$stmt = $conn->prepare("SELECT bookings.*, departments.name as department_name, departments.color, 
                        rooms.name as room_name 
                        FROM bookings 
                        JOIN departments ON bookings.department_id = departments.id 
                        JOIN rooms ON bookings.room_id = rooms.id 
                        WHERE bookings.id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Appointment not found']);
    exit();
}

$appointment = $result->fetch_assoc();

// Format time values for better readability
$appointment['formatted_time_from'] = date('h:i A', strtotime($appointment['booking_time_from']));
$appointment['formatted_time_to'] = date('h:i A', strtotime($appointment['booking_time_to']));

echo json_encode($appointment);

$stmt->close();
$conn->close();
?>
