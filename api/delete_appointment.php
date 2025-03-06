<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
if ($conn->connect_error) {
    http_response_code(500);
    echo 'Connection failed: ' . $conn->connect_error;
    exit();
}

// Check if ID is provided
if (!isset($_POST['id'])) {
    http_response_code(400);
    echo "Appointment ID is required";
    exit();
}

$id = $_POST['id'];

// Prepare and execute the delete query
$stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo "Prepare failed: " . $conn->error;
    exit();
}

$stmt->bind_param("i", $id);
$result = $stmt->execute();

if ($result) {
    echo "Appointment deleted successfully";
} else {
    http_response_code(500);
    echo "Error deleting appointment: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
