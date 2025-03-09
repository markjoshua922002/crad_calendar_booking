<?php
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if name is provided
if (!isset($_POST['name']) || empty($_POST['name'])) {
    echo json_encode(['success' => false, 'message' => 'Room name is required']);
    exit();
}

$name = trim($_POST['name']);

// Check if room already exists
$check = $conn->prepare("SELECT * FROM rooms WHERE name = ?");
$check->bind_param("s", $name);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Room already exists']);
    exit();
}

// Insert new room
$stmt = $conn->prepare("INSERT INTO rooms (name) VALUES (?)");
$stmt->bind_param("s", $name);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Room added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error adding room: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
