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
$time_from = date('H:i:s', strtotime($data['time_from']));
$time_to = date('H:i:s', strtotime($data['time_to']));

// Function to convert 24-hour time to 12-hour format with AM/PM
function format12Hour($time) {
    return date('g:i A', strtotime($time));
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

// Get alternative times (30 min before and after, in 30 min increments)
$base_time_from = strtotime($time_from);
$base_time_to = strtotime($time_to);
$duration = $base_time_to - $base_time_from;
$alternative_times = [];

for ($i = -2; $i <= 2; $i++) {
    if ($i == 0) continue; // Skip the current time
    
    $alt_time_from = strtotime(($i * 30) . ' minutes', $base_time_from);
    $alt_time_to = $alt_time_from + $duration;
    
    // Check if this alternative time has conflicts
    $stmt = $conn->prepare("SELECT 1 FROM bookings 
                           WHERE booking_date = ? AND room_id = ?
                           AND ((booking_time_from <= ? AND booking_time_to >= ?) 
                           OR (booking_time_from <= ? AND booking_time_to >= ?) 
                           OR (? <= booking_time_from AND ? >= booking_time_to))");
                           
    $alt_time_from_sql = date('H:i:s', $alt_time_from);
    $alt_time_to_sql = date('H:i:s', $alt_time_to);
    
    $stmt->bind_param("sissssss", $date, $room_id, 
                      $alt_time_to_sql, $alt_time_from_sql, 
                      $alt_time_from_sql, $alt_time_to_sql,
                      $alt_time_from_sql, $alt_time_to_sql);
    $stmt->execute();
    $conflict_check = $stmt->get_result();
    
    if ($conflict_check->num_rows === 0) {
        $alternative_times[] = [
            'time_from' => format12Hour($alt_time_from_sql),
            'time_to' => format12Hour($alt_time_to_sql)
        ];
    }
}

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