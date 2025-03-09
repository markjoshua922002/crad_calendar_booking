<?php

require_once 'config.php';
require_once 'Database.php';
require_once 'AIDoubleBookingDetector.php';
require_once 'AIAvailabilityChecker.php';

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to establish database connection');
    }

    // Initialize AI services
    $doubleBookingDetector = new AIDoubleBookingDetector($db);
    $availabilityChecker = new AIAvailabilityChecker($db);

    // Get the endpoint from the request
    $endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
    
    switch ($endpoint) {
        case 'check_double_booking':
            handleDoubleBookingCheck($doubleBookingDetector);
            break;
            
        case 'get_available_slots':
            handleAvailableSlots($availabilityChecker);
            break;
            
        default:
            sendResponse(404, ['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    sendResponse(500, [
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Handle double booking check endpoint
 */
function handleDoubleBookingCheck($detector) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, ['error' => 'Method not allowed']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $requiredFields = ['user_id', 'department_id', 'start_time', 'end_time', 'date'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            sendResponse(400, ['error' => "Missing required field: {$field}"]);
            return;
        }
    }
    
    // Check for double bookings
    $result = $detector->checkDoubleBooking(
        $data['user_id'],
        $data['department_id'],
        $data['start_time'],
        $data['end_time'],
        $data['date']
    );
    
    sendResponse(200, $result);
}

/**
 * Handle available slots endpoint
 */
function handleAvailableSlots($checker) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse(405, ['error' => 'Method not allowed']);
        return;
    }
    
    // Validate required parameters
    if (!isset($_GET['date']) || !isset($_GET['room_id'])) {
        sendResponse(400, ['error' => 'Missing required parameters: date and room_id']);
        return;
    }
    
    // Get duration parameter (optional)
    $duration = isset($_GET['duration']) ? (int)$_GET['duration'] : 60;
    
    // Get available slots
    $result = $checker->getAvailableSlots(
        $_GET['date'],
        $_GET['room_id'],
        $duration
    );
    
    sendResponse(200, $result);
}

/**
 * Send JSON response
 */
function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
} 