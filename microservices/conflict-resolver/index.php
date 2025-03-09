<?php
/**
 * Conflict Resolution Microservice
 * 
 * This microservice handles scheduling conflict detection and resolution,
 * providing intelligent suggestions for alternative times and rooms.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
try {
    if (!file_exists('config.php')) {
        throw new Exception('Configuration file not found');
    }
    require_once 'config.php';
    
    if (!file_exists('ConflictResolver.php')) {
        throw new Exception('ConflictResolver class file not found');
    }
    require_once 'ConflictResolver.php';
    
    if (!file_exists('Database.php')) {
        throw new Exception('Database class file not found');
    }
    require_once 'Database.php';
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Failed to establish database connection');
    }

    // Initialize the conflict resolver
    $resolver = new ConflictResolver($db);

    // Process the request
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'check';
    
    // Log request details
    logMessage("Processing {$requestMethod} request to endpoint: {$endpoint}");
    logMessage("Request data: " . print_r(getRequestData(), true));

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
} catch (Exception $e) {
    logMessage("Critical error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString(), "ERROR");
    sendResponse(500, [
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
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