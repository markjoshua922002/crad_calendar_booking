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

// Handle POST request for adding a room
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $room_name = $_POST['room_name'];
    
    // Validate input
    if (empty($room_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Room name is required']);
        exit();
    }
    
    $room_capacity = isset($_POST['room_capacity']) ? (int)$_POST['room_capacity'] : 0;
    
    // Check if room already exists
    $check = $conn->prepare("SELECT * FROM rooms WHERE name = ?");
    if (!$check) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    
    $check->bind_param("s", $room_name);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Room already exists']);
        exit();
    }
    
    // Insert the room
    $stmt = $conn->prepare("INSERT INTO rooms (name, capacity) VALUES (?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("si", $room_name, $room_capacity);
    
    if ($stmt->execute()) {
        // Success - return JSON response
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Room added successfully']);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error adding room: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>
