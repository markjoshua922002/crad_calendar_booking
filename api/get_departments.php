<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $query = "SELECT id, name, color FROM departments ORDER BY name";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $departments = array();
    while ($row = $result->fetch_assoc()) {
        $departments[] = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'color' => $row['color']
        );
    }
    
    echo json_encode($departments);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}

$conn->close();
?> 