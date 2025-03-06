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
if (isset($_GET['id'])) {
    $appointment_id = $_GET['id'];
} elseif (isset($_POST['id'])) {
    $appointment_id = $_POST['id'];
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Appointment ID is required']);
    exit();
}

// Delete the appointment
$stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $appointment_id);

if ($stmt->execute()) {
    // Success
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        echo json_encode(['success' => true, 'message' => 'Appointment deleted successfully']);
    } else {
        // For GET/POST requests, redirect back to the calendar
        header('Location: ../index.php');
        exit();
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error deleting appointment: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
