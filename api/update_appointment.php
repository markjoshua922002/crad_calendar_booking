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

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id']; // Get appointment ID from the form
    $name = $_POST['edit_name'];
    $id_number = $_POST['edit_id_number'];
    $date = $_POST['edit_date'];
    $time = $_POST['edit_time'];
    $reason = $_POST['edit_reason'];
    $department_id = $_POST['edit_department'];
    $room_id = $_POST['edit_room'];

    // Prepare the SQL statement
    $stmt = $conn->prepare("UPDATE bookings SET name=?, id_number=?, booking_date=?, booking_time=?, reason=?, department_id=?, room_id=? WHERE id=?");
    $stmt->bind_param("sssssiii", $name, $id_number, $date, $time, $reason, $department_id, $room_id, $appointment_id);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Appointment updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update appointment.']);
    }
    
    $stmt->close();
}

$conn->close();
?>
