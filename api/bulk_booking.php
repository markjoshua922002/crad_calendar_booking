<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get JSON input
$json = file_get_contents('php://input');
$bookings = json_decode($json, true);

if (!is_array($bookings)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input format']);
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    $successful_bookings = 0;
    $errors = [];

    foreach ($bookings as $booking) {
        // Validate required fields
        $required_fields = ['department', 'room', 'adviser', 'representative', 'group', 'set', 'date', 'timeFrom', 'timeTo', 'agenda'];
        foreach ($required_fields as $field) {
            if (empty($booking[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Get department ID
        $stmt = $conn->prepare("SELECT id FROM departments WHERE name = ?");
        $stmt->bind_param("s", $booking['department']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Department not found: {$booking['department']}");
        }
        $department_id = $result->fetch_assoc()['id'];
        $stmt->close();

        // Get room ID
        $stmt = $conn->prepare("SELECT id FROM rooms WHERE name = ?");
        $stmt->bind_param("s", $booking['room']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Room not found: {$booking['room']}");
        }
        $room_id = $result->fetch_assoc()['id'];
        $stmt->close();

        // Get set ID
        $stmt = $conn->prepare("SELECT id FROM sets WHERE name = ?");
        $stmt->bind_param("s", $booking['set']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Set not found: {$booking['set']}");
        }
        $set_id = $result->fetch_assoc()['id'];
        $stmt->close();

        // Format times
        $date = date('Y-m-d', strtotime($booking['date']));
        $time_from = date('H:i:s', strtotime($booking['timeFrom']));
        $time_to = date('H:i:s', strtotime($booking['timeTo']));

        // Check for double booking
        $stmt = $conn->prepare("SELECT b.*, r.name as room_name FROM bookings b 
                              JOIN rooms r ON b.room_id = r.id 
                              WHERE b.booking_date = ? AND b.room_id = ? 
                              AND ((b.booking_time_from <= ? AND b.booking_time_to >= ?) 
                              OR (b.booking_time_from <= ? AND b.booking_time_to >= ?) 
                              OR (? <= b.booking_time_from AND ? >= b.booking_time_to))");
        $stmt->bind_param("sissssss", $date, $room_id, $time_to, $time_from, $time_from, $time_to, $time_from, $time_to);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $conflicting_booking = $result->fetch_assoc();
            throw new Exception("Double booking detected for room {$booking['room']} from " . 
                              date('g:i A', strtotime($conflicting_booking['booking_time_from'])) . " to " . 
                              date('g:i A', strtotime($conflicting_booking['booking_time_to'])) . " on $date");
        }
        $stmt->close();

        // Insert booking
        $stmt = $conn->prepare("INSERT INTO bookings (name, id_number, group_members, representative_name, set_id, 
                              department_id, room_id, booking_date, booking_time_from, booking_time_to, reason) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssssissssss", 
            $booking['adviser'],
            $booking['group'],
            $booking['remarks'],
            $booking['representative'],
            $set_id,
            $department_id,
            $room_id,
            $date,
            $time_from,
            $time_to,
            $booking['agenda']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting booking: " . $stmt->error);
        }

        $successful_bookings++;
        $stmt->close();
    }

    // If we got here, all bookings were successful
    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => "Successfully created $successful_bookings booking(s)",
        'bookings_created' => $successful_bookings
    ]);

} catch (Exception $e) {
    // Roll back the transaction on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'bookings_created' => $successful_bookings
    ]);
}

$conn->close();
?> 