<?php
/**
 * Conflict Resolver Class
 * 
 * Handles scheduling conflict detection and resolution,
 * providing intelligent suggestions for alternative times and rooms.
 */
class ConflictResolver {
    private $db;
    private $conn;
    private $timeSlots = [];
    private $roomAvailability = [];
    private $departmentAvailability = [];
    
    /**
     * Constructor
     */
    public function __construct($conn) {
        $this->conn = $conn;
        $this->timeSlots = $this->generateTimeSlots();
        
        logMessage("ConflictResolver initialized");
    }
    
    /**
     * Generate standard time slots for a day based on configured interval
     */
    private function generateTimeSlots() {
        $slots = [];
        $start = strtotime(BUSINESS_HOURS_START);
        $end = strtotime(BUSINESS_HOURS_END);
        $interval = TIME_SLOT_INTERVAL * 60; // convert to seconds
        
        for ($time = $start; $time < $end; $time += $interval) {
            $slots[] = date('H:i', $time);
        }
        
        logMessage("Generated " . count($slots) . " time slots");
        return $slots;
    }
    
    /**
     * Check if a proposed booking conflicts with existing bookings
     */
    public function checkConflicts($date, $roomId, $timeFrom, $timeTo) {
        logMessage("Checking conflicts for date: $date, room: $roomId, time: $timeFrom - $timeTo");
        
        // Convert times to 24-hour format if they aren't already
        $startTime = $this->convertTo24Hour($timeFrom);
        $endTime = $this->convertTo24Hour($timeTo);
        
        // Query to find conflicting bookings
        $sql = "SELECT b.*, 
                d.name as department_name, 
                d.color as department_color,
                r.name as room_name
                FROM bookings b
                JOIN departments d ON b.department_id = d.id
                JOIN rooms r ON b.room_id = r.id
                WHERE b.booking_date = ? 
                AND b.room_id = ?
                AND (
                    (b.booking_time_from < ? AND b.booking_time_to > ?) OR
                    (b.booking_time_from < ? AND b.booking_time_to > ?) OR
                    (b.booking_time_from >= ? AND b.booking_time_to <= ?)
                )";
        
        $params = [$date, $roomId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime];
        $types = "sssssssss";
        
        $result = $this->conn->query($sql, $params, $types);
        $conflicts = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $conflicts[] = [
                    'id' => $row['id'],
                    'room_id' => $row['room_id'],
                    'room_name' => $row['room_name'],
                    'department_id' => $row['department_id'],
                    'department_name' => $row['department_name'],
                    'time_from' => $this->convertTo12Hour($row['booking_time_from']),
                    'time_to' => $this->convertTo12Hour($row['booking_time_to']),
                    'date' => $row['booking_date']
                ];
            }
        }
        
        logMessage("Found " . count($conflicts) . " conflicts");
        return $conflicts;
    }
    
    /**
     * Find alternative time slots for a booking
     */
    public function findAlternatives($date, $roomId, $departmentId, $duration, $originalTimeFrom, $originalTimeTo) {
        logMessage("Finding alternatives for date: $date, room: $roomId, department: $departmentId, duration: $duration");
        
        // Convert times to 24-hour format
        $originalStartTime = $this->convertTo24Hour($originalTimeFrom);
        $originalEndTime = $this->convertTo24Hour($originalTimeTo);
        
        // Get all bookings for the room on the given date
        $sql = "SELECT booking_time_from, booking_time_to 
                FROM bookings 
                WHERE booking_date = ? AND room_id = ?";
        
        $result = $this->conn->executeQuery($sql, [$date, $roomId], "ss");
        $bookedSlots = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $bookedSlots[] = [
                    'timeFrom' => $row['booking_time_from'],
                    'timeTo' => $row['booking_time_to']
                ];
            }
        }
        
        // Convert duration to minutes if it's a string like "1:30"
        if (is_string($duration) && strpos($duration, ':') !== false) {
            list($hours, $minutes) = explode(':', $duration);
            $durationMinutes = ($hours * 60) + $minutes;
        } else {
            $durationMinutes = intval($duration);
        }
        
        // Ensure minimum duration
        $durationMinutes = max($durationMinutes, MIN_BOOKING_DURATION);
        
        // Find available slots
        $alternatives = [];
        $businessStart = strtotime(BUSINESS_HOURS_START);
        $businessEnd = strtotime(BUSINESS_HOURS_END);
        $interval = TIME_SLOT_INTERVAL * 60; // convert to seconds
        
        for ($time = $businessStart; $time < $businessEnd; $time += $interval) {
            $slotStart = date('H:i:s', $time);
            $slotEnd = date('H:i:s', $time + ($durationMinutes * 60));
            
            // Skip if slot end is after business hours
            if (strtotime($slotEnd) > $businessEnd) {
                continue;
            }
            
            // Skip the original time slot
            if ($slotStart === $originalStartTime && $slotEnd === $originalEndTime) {
                continue;
            }
            
            // Check if slot is available
            $isAvailable = true;
            foreach ($bookedSlots as $bookedSlot) {
                if ($this->isTimeOverlap($slotStart, $slotEnd, $bookedSlot['timeFrom'], $bookedSlot['timeTo'])) {
                    $isAvailable = false;
                    break;
                }
            }
            
            if ($isAvailable) {
                // Calculate score based on proximity to original time
                $score = $this->calculateTimeScore($slotStart, $date);
                
                $alternatives[] = [
                    'time_from' => $this->convertTo12Hour($slotStart),
                    'time_to' => $this->convertTo12Hour($slotEnd),
                    'score' => $score
                ];
            }
        }
        
        // Sort by score (higher is better)
        usort($alternatives, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // Limit the number of alternatives
        $alternatives = array_slice($alternatives, 0, MAX_ALTERNATIVES);
        
        logMessage("Found " . count($alternatives) . " alternative time slots");
        return $alternatives;
    }
    
    /**
     * Suggest alternative rooms for a booking
     */
    public function suggestAlternativeRooms($date, $timeFrom, $timeTo, $originalRoomId) {
        logMessage("Suggesting alternative rooms for date: $date, time: $timeFrom - $timeTo");
        
        // Convert times to 24-hour format
        $startTime = $this->convertTo24Hour($timeFrom);
        $endTime = $this->convertTo24Hour($timeTo);
        
        // Get all rooms
        $roomsSql = "SELECT * FROM rooms ORDER BY name";
        $roomsResult = $this->conn->executeQuery($roomsSql);
        $rooms = [];
        
        if ($roomsResult) {
            while ($row = $roomsResult->fetch_assoc()) {
                // Skip the original room
                if ($row['id'] == $originalRoomId) {
                    continue;
                }
                
                $rooms[] = $row;
            }
        }
        
        // Check availability for each room
        $alternativeRooms = [];
        foreach ($rooms as $room) {
            // Check if room is available during the requested time
            $conflicts = $this->checkConflicts($date, $room['id'], $timeFrom, $timeTo);
            
            if (empty($conflicts)) {
                // Calculate a score for this room
                $score = $this->calculateRoomScore($room);
                
                $alternativeRooms[] = [
                    'id' => $room['id'],
                    'name' => $room['name'],
                    'score' => $score
                ];
            }
        }
        
        // Sort by score (higher is better)
        usort($alternativeRooms, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // Limit the number of alternatives
        $alternativeRooms = array_slice($alternativeRooms, 0, MAX_ALTERNATIVES);
        
        logMessage("Found " . count($alternativeRooms) . " alternative rooms");
        return $alternativeRooms;
    }
    
    /**
     * Analyze a booking for conflicts and suggest alternatives
     */
    public function analyzeBooking($date, $roomId, $departmentId, $timeFrom, $timeTo, $duration) {
        logMessage("Analyzing booking for date: $date, room: $roomId, department: $departmentId, time: $timeFrom - $timeTo");
        
        // Check for conflicts
        $conflicts = $this->checkConflicts($date, $roomId, $timeFrom, $timeTo);
        $hasConflicts = !empty($conflicts);
        
        $result = [
            'has_conflicts' => $hasConflicts,
            'conflicts' => $conflicts,
            'message' => $hasConflicts 
                ? 'There are scheduling conflicts with your requested time. Please review the suggestions below.' 
                : 'No conflicts detected. Your booking can be scheduled as requested.'
        ];
        
        // If there are conflicts, find alternatives
        if ($hasConflicts) {
            $result['alternative_times'] = $this->findAlternatives($date, $roomId, $departmentId, $duration, $timeFrom, $timeTo);
            $result['alternative_rooms'] = $this->suggestAlternativeRooms($date, $timeFrom, $timeTo, $roomId);
        }
        
        return $result;
    }
    
    /**
     * Convert time string to 24-hour format
     */
    private function convertTo24Hour($timeStr) {
        // Handle SQL time format (HH:MM:SS)
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $timeStr)) {
            return $timeStr;
        }
        
        // Handle SQL time format without seconds (HH:MM)
        if (preg_match('/^\d{1,2}:\d{2}$/', $timeStr)) {
            return $timeStr . ':00';
        }
        
        // Handle 12-hour format with AM/PM
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $timeStr, $matches)) {
            $hours = intval($matches[1]);
            $minutes = $matches[2];
            $meridiem = strtoupper($matches[3]);
            
            // Convert to 24-hour format
            if ($hours == 12) {
                $hours = ($meridiem == 'AM') ? 0 : 12;
            } else if ($meridiem == 'PM') {
                $hours += 12;
            }
            
            return sprintf('%02d:%02d:00', $hours, $minutes);
        }
        
        // If we can't parse it, log an error and return the original
        logMessage("Failed to convert time: $timeStr", "ERROR");
        return $timeStr;
    }
    
    /**
     * Convert 24-hour format to 12-hour format
     */
    private function convertTo12Hour($timeStr) {
        // Extract hours and minutes from HH:MM:SS format
        if (preg_match('/^(\d{2}):(\d{2})/', $timeStr, $matches)) {
            $hours = intval($matches[1]);
            $minutes = $matches[2];
            
            $meridiem = ($hours >= 12) ? 'PM' : 'AM';
            $hours = ($hours % 12) ?: 12; // Convert 0 to 12 for 12 AM
            
            return sprintf('%d:%02d %s', $hours, intval($minutes), $meridiem);
        }
        
        // If we can't parse it, log an error and return the original
        logMessage("Failed to convert time to 12-hour format: $timeStr", "ERROR");
        return $timeStr;
    }
    
    /**
     * Check if two time ranges overlap
     */
    private function isTimeOverlap($start1, $end1, $start2, $end2) {
        return ($start1 < $end2 && $end1 > $start2);
    }
    
    /**
     * Calculate a score for a time slot based on various factors
     */
    private function calculateTimeScore($timeSlot, $date) {
        $score = 50; // Base score
        
        // Prefer times during the middle of the day (10 AM - 2 PM)
        $hour = intval(substr($timeSlot, 0, 2));
        if ($hour >= 10 && $hour <= 14) {
            $score += 20;
        }
        
        // Prefer times on the hour or half-hour
        $minute = intval(substr($timeSlot, 3, 2));
        if ($minute == 0) {
            $score += 15; // On the hour
        } else if ($minute == 30) {
            $score += 10; // On the half-hour
        }
        
        // Adjust score based on day of week (prefer weekdays)
        $dayOfWeek = date('N', strtotime($date));
        if ($dayOfWeek <= 5) { // Monday to Friday
            $score += 10;
        }
        
        return $score;
    }
    
    /**
     * Calculate a score for a room based on various factors
     */
    private function calculateRoomScore($room) {
        // For now, just return a base score
        // This could be enhanced with room capacity, equipment, etc.
        return 50;
    }
} 