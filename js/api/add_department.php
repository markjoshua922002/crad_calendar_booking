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

// Handle POST request for adding a department
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $department_name = $_POST['department_name'];
    $color = isset($_POST['color']) ? $_POST['color'] : '#3788d8';
    
    // Validate input
    if (empty($department_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Department name is required']);
        exit();
    }
    
    // Check if department already exists
    $check = $conn->prepare("SELECT * FROM departments WHERE name = ?");
    if (!$check) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    
    $check->bind_param("s", $department_name);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Department already exists']);
        exit();
    }
    
    // Insert the department
    $stmt = $conn->prepare("INSERT INTO departments (name, color) VALUES (?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("ss", $department_name, $color);
    
    if ($stmt->execute()) {
        // Success - return JSON response
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Department added successfully']);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error adding department: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>
