<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['date']) || !isset($data['room_id']) || !isset($data['time_from']) || !isset($data['time_to'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$date = date('Y-m-d', strtotime($data['date']));
$room_id = $data['room_id'];

// Convert input times to 24-hour format for database operations
$time_from = date('H:i:s', strtotime($data['time_from']));
$time_to = date('H:i:s', strtotime($data['time_to']));

// Function to convert time to 12-hour format with AM/PM
function format12Hour($time) {
    if (is_numeric($time)) {
        // If timestamp is provided
        $formatted = date('g:i A', $time);
    } else {
        // If time string is provided
        $formatted = date('g:i A', strtotime($time));
    }
    // Ensure single-digit hours don't have leading zeros
    return preg_replace('/^0/', '', $formatted);
}

// Function to generate time slots before and after a given time
function generateTimeSlots($baseTime, $duration, $count = 2) {
    $slots = [];
    $interval = 30 * 60; // 30 minutes in seconds
    
    // Generate slots before
    for ($i = $count; $i > 0; $i--) {
        $startTime = strtotime("-" . ($i * 30) . " minutes", $baseTime);
        $endTime = $startTime + $duration;
        
        // Only add if it's not before 8 AM or after 5 PM
        if (date('H', $startTime) >= 8 && date('H', $endTime) <= 17) {
            $slots[] = [
                'time_from' => format12Hour($startTime),
                'time_to' => format12Hour($endTime)
            ];
        }
    }
    
    // Generate slots after
    for ($i = 1; $i <= $count; $i++) {
        $startTime = strtotime("+" . ($i * 30) . " minutes", $baseTime);
        $endTime = $startTime + $duration;
        
        // Only add if it's not before 8 AM or after 5 PM
        if (date('H', $startTime) >= 8 && date('H', $endTime) <= 17) {
            $slots[] = [
                'time_from' => format12Hour($startTime),
                'time_to' => format12Hour($endTime)
            ];
        }
    }
    
    return $slots;
}

// Check for conflicts
$stmt = $conn->prepare("SELECT b.*, r.name as room_name, d.name as department_name, d.color 
                       FROM bookings b 
                       JOIN rooms r ON b.room_id = r.id 
                       JOIN departments d ON b.department_id = d.id
                       WHERE b.booking_date = ? AND b.room_id = ? 
                       AND ((b.booking_time_from <= ? AND b.booking_time_to >= ?) 
                       OR (b.booking_time_from <= ? AND b.booking_time_to >= ?) 
                       OR (? <= b.booking_time_from AND ? >= b.booking_time_to))");

$stmt->bind_param("sissssss", $date, $room_id, $time_to, $time_from, $time_from, $time_to, $time_from, $time_to);
$stmt->execute();
$result = $stmt->get_result();

$conflicts = [];
while ($row = $result->fetch_assoc()) {
    $conflicts[] = [
        'room_name' => $row['room_name'],
        'department' => $row['department_name'],
        'time_from' => format12Hour($row['booking_time_from']),
        'time_to' => format12Hour($row['booking_time_to']),
        'color' => $row['color']
    ];
}

// Generate alternative times
$base_time = strtotime($time_from);
$duration = strtotime($time_to) - $base_time;
$alternative_times = generateTimeSlots($base_time, $duration);

// Filter out times that have conflicts
$alternative_times = array_filter($alternative_times, function($time) use ($conn, $date, $room_id) {
    // Convert 12-hour format back to 24-hour for database comparison
    $time_from = date('H:i:s', strtotime($time['time_from']));
    $time_to = date('H:i:s', strtotime($time['time_to']));
    
    $stmt = $conn->prepare("SELECT 1 FROM bookings 
                           WHERE booking_date = ? AND room_id = ?
                           AND ((booking_time_from <= ? AND booking_time_to >= ?) 
                           OR (booking_time_from <= ? AND booking_time_to >= ?) 
                           OR (? <= booking_time_from AND ? >= booking_time_to))");
    
    $stmt->bind_param("sissssss", $date, $room_id, 
                      $time_to, $time_from, 
                      $time_from, $time_to,
                      $time_from, $time_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 0;
});

// Get alternative rooms
$stmt = $conn->prepare("SELECT r.id, r.name 
                       FROM rooms r 
                       WHERE r.id != ? 
                       AND NOT EXISTS (
                           SELECT 1 FROM bookings b 
                           WHERE b.room_id = r.id 
                           AND b.booking_date = ?
                           AND ((b.booking_time_from <= ? AND b.booking_time_to >= ?) 
                           OR (b.booking_time_from <= ? AND b.booking_time_to >= ?) 
                           OR (? <= b.booking_time_from AND ? >= b.booking_time_to))
                       )");

$stmt->bind_param("isssssss", $room_id, $date, $time_to, $time_from, $time_from, $time_to, $time_from, $time_to);
$stmt->execute();
$result = $stmt->get_result();

$alternative_rooms = [];
while ($row = $result->fetch_assoc()) {
    $alternative_rooms[] = [
        'id' => $row['id'],
        'name' => $row['name']
    ];
}

// Reindex array after filtering
$alternative_times = array_values($alternative_times);

// Sort alternative times chronologically
usort($alternative_times, function($a, $b) {
    return strtotime($a['time_from']) - strtotime($b['time_from']);
});

echo json_encode([
    'has_conflicts' => count($conflicts) > 0,
    'conflicts' => $conflicts,
    'alternative_rooms' => $alternative_rooms,
    'alternative_times' => $alternative_times
]); 