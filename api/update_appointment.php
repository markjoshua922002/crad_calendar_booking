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

// Handle POST request for updating an appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $appointment_id = $_POST['appointment_id'];
    $name = $_POST['edit_name'];
    $id_number = $_POST['edit_id_number'];
    $group_members = $_POST['edit_group_members'];
    $representative_name = $_POST['edit_representative_name'];
    $set = $_POST['edit_set'];
    $department = $_POST['edit_department'];
    $room = $_POST['edit_room'];
    $date = date('Y-m-d', strtotime($_POST['edit_date']));
    
    // Combine time fields
    $time_from = date('H:i:s', strtotime($_POST['edit_time_from_hour'] . ':' . $_POST['edit_time_from_minute'] . ' ' . $_POST['edit_time_from_ampm']));
    $time_to = date('H:i:s', strtotime($_POST['edit_time_to_hour'] . ':' . $_POST['edit_time_to_minute'] . ' ' . $_POST['edit_time_to_ampm']));
    
    $reason = $_POST['edit_reason'];

    // Check if the booking date is in the past
    $current_date = date('Y-m-d');
    if ($date < $current_date) {
        http_response_code(400);
        echo json_encode(['error' => 'You cannot book a date that has already passed.']);
        exit();
    }

    // Check for double booking (excluding the current appointment)
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_date = ? AND room_id = ? AND id != ? AND ((booking_time_from < ? AND booking_time_to > ?) OR (booking_time_from < ? AND booking_time_to > ?))");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("ssissss", $date, $room, $appointment_id, $time_to, $time_from, $time_from, $time_to);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Double booking detected for the specified time, date, and room.']);
        exit();
    }
    
    // Update the appointment
    $stmt = $conn->prepare("UPDATE bookings SET name = ?, id_number = ?, group_members = ?, representative_name = ?, `set` = ?, department_id = ?, room_id = ?, booking_date = ?, booking_time_from = ?, booking_time_to = ?, reason = ? WHERE id = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("ssssssssssssi", $name, $id_number, $group_members, $representative_name, $set, $department, $room, $date, $time_from, $time_to, $reason, $appointment_id);
    
    if ($stmt->execute()) {
        // Success
        echo json_encode(['success' => true, 'message' => 'Appointment updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error updating appointment: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>
