<?php
/**
 * Conflict Resolution Microservice
 * 
 * This microservice handles scheduling conflict detection and resolution,
 * providing intelligent suggestions for alternative times and rooms.
 */

// Enable CORS for API access
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files
require_once 'config.php';
require_once 'ConflictResolver.php';
require_once 'Database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize the conflict resolver
$resolver = new ConflictResolver($db);

// Process the request
$requestMethod = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'check';

// Route the request to the appropriate handler
switch ($endpoint) {
    case 'check':
        handleCheckConflicts($resolver, $requestMethod);
        break;
    case 'alternatives':
        handleFindAlternatives($resolver, $requestMethod);
        break;
    case 'analyze':
        handleAnalyzeBooking($resolver, $requestMethod);
        break;
    default:
        sendResponse(404, ['error' => 'Endpoint not found']);
}

/**
 * Handle check conflicts endpoint
 */
function handleCheckConflicts($resolver, $method) {
    if ($method !== 'POST') {
        sendResponse(405, ['error' => 'Method not allowed']);
        return;
    }
    
    // Get request data
    $data = getRequestData();
    
    // Validate required fields
    $requiredFields = ['date', 'room_id', 'time_from', 'time_to'];
    if (!validateFields($data, $requiredFields)) {
        sendResponse(400, ['error' => 'Missing required fields']);
        return;
    }
    
    // Check for conflicts
    $conflicts = $resolver->checkConflicts(
        $data['date'],
        $data['room_id'],
        $data['time_from'],
        $data['time_to']
    );
    
    sendResponse(200, [
        'has_conflicts' => !empty($conflicts),
        'conflicts' => $conflicts
    ]);
}

/**
 * Handle find alternatives endpoint
 */
function handleFindAlternatives($resolver, $method) {
    if ($method !== 'POST') {
        sendResponse(405, ['error' => 'Method not allowed']);
        return;
    }
    
    // Get request data
    $data = getRequestData();
    
    // Validate required fields
    $requiredFields = ['date', 'room_id', 'department_id', 'duration', 'time_from', 'time_to'];
    if (!validateFields($data, $requiredFields)) {
        sendResponse(400, ['error' => 'Missing required fields']);
        return;
    }
    
    // Find alternative times
    $alternativeTimes = $resolver->findAlternatives(
        $data['date'],
        $data['room_id'],
        $data['department_id'],
        $data['duration'],
        $data['time_from'],
        $data['time_to']
    );
    
    // Find alternative rooms
    $alternativeRooms = $resolver->suggestAlternativeRooms(
        $data['date'],
        $data['time_from'],
        $data['time_to'],
        $data['room_id']
    );
    
    sendResponse(200, [
        'alternative_times' => $alternativeTimes,
        'alternative_rooms' => $alternativeRooms
    ]);
}

/**
 * Handle analyze booking endpoint
 */
function handleAnalyzeBooking($resolver, $method) {
    if ($method !== 'POST') {
        sendResponse(405, ['error' => 'Method not allowed']);
        return;
    }
    
    // Get request data
    $data = getRequestData();
    
    // Validate required fields
    $requiredFields = ['date', 'room_id', 'department_id', 'time_from', 'time_to', 'duration'];
    if (!validateFields($data, $requiredFields)) {
        sendResponse(400, ['error' => 'Missing required fields']);
        return;
    }
    
    // Analyze the booking
    $analysis = $resolver->analyzeBooking(
        $data['date'],
        $data['room_id'],
        $data['department_id'],
        $data['time_from'],
        $data['time_to'],
        $data['duration']
    );
    
    sendResponse(200, $analysis);
}

/**
 * Get request data from POST body
 */
function getRequestData() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?: [];
}

/**
 * Validate required fields in request data
 */
function validateFields($data, $requiredFields) {
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return false;
        }
    }
    return true;
}

/**
 * Send JSON response
 */
function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
} 