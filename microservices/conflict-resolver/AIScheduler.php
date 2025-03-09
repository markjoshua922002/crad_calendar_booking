<?php
/**
 * AI Scheduler Class
 * 
 * Enhances scheduling decisions with machine learning capabilities
 */
class AIScheduler {
    private $db;
    private $historicalData = [];
    private $learningRate = 0.1;
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadHistoricalData();
    }
    
    /**
     * Load historical booking patterns
     */
    private function loadHistoricalData() {
        $sql = "SELECT 
                    b.booking_date,
                    b.booking_time_from,
                    b.booking_time_to,
                    b.room_id,
                    b.department_id,
                    COUNT(*) as booking_frequency,
                    AVG(TIMESTAMPDIFF(MINUTE, booking_time_from, booking_time_to)) as avg_duration
                FROM bookings b
                GROUP BY 
                    DAYOFWEEK(booking_date),
                    HOUR(booking_time_from),
                    room_id,
                    department_id";
                    
        $result = $this->db->executeQuery($sql);
        $this->historicalData = $this->db->fetchAll($result);
    }
    
    /**
     * Calculate optimal time slots based on historical patterns
     */
    public function suggestOptimalTimeSlots($date, $roomId, $departmentId, $duration) {
        $dayOfWeek = date('w', strtotime($date));
        $suggestions = [];
        
        // Filter relevant historical data
        $relevantData = array_filter($this->historicalData, function($booking) use ($dayOfWeek, $roomId, $departmentId) {
            return date('w', strtotime($booking['booking_date'])) == $dayOfWeek
                && ($booking['room_id'] == $roomId || $booking['department_id'] == $departmentId);
        });
        
        // Calculate time slot scores
        foreach ($this->generateTimeSlots() as $timeSlot) {
            $score = $this->calculateTimeSlotScore($timeSlot, $relevantData, $duration);
            if ($score > 0.5) { // Threshold for good suggestions
                $suggestions[] = [
                    'time_slot' => $timeSlot,
                    'confidence' => $score,
                    'reason' => $this->generateSuggestionReason($timeSlot, $score)
                ];
            }
        }
        
        // Sort by confidence score
        usort($suggestions, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return $suggestions;
    }
    
    /**
     * Generate available time slots
     */
    private function generateTimeSlots() {
        $slots = [];
        $start = strtotime(BUSINESS_HOURS_START);
        $end = strtotime(BUSINESS_HOURS_END);
        $interval = TIME_SLOT_INTERVAL * 60;
        
        for ($time = $start; $time < $end; $time += $interval) {
            $slots[] = date('H:i', $time);
        }
        
        return $slots;
    }
    
    /**
     * Calculate score for a time slot based on historical data
     */
    private function calculateTimeSlotScore($timeSlot, $historicalData, $duration) {
        $score = 0;
        $weights = [
            'frequency' => 0.4,
            'duration_match' => 0.3,
            'spacing' => 0.3
        ];
        
        foreach ($historicalData as $booking) {
            // Frequency score
            $score += $this->calculateFrequencyScore($timeSlot, $booking) * $weights['frequency'];
            
            // Duration match score
            $score += $this->calculateDurationMatchScore($duration, $booking['avg_duration']) * $weights['duration_match'];
            
            // Spacing score (avoid back-to-back meetings)
            $score += $this->calculateSpacingScore($timeSlot, $booking) * $weights['spacing'];
        }
        
        return min(1, $score / count($historicalData));
    }
    
    /**
     * Calculate how well this time slot matches historical booking frequencies
     */
    private function calculateFrequencyScore($timeSlot, $booking) {
        $slotHour = (int)substr($timeSlot, 0, 2);
        $bookingHour = (int)substr($booking['booking_time_from'], 0, 2);
        
        $hourDiff = abs($slotHour - $bookingHour);
        return $hourDiff === 0 ? 1 : (1 / (1 + $hourDiff));
    }
    
    /**
     * Calculate how well the requested duration matches historical patterns
     */
    private function calculateDurationMatchScore($requestedDuration, $historicalAvgDuration) {
        $durationDiff = abs($requestedDuration - $historicalAvgDuration);
        return 1 / (1 + ($durationDiff / 60)); // Normalize by hour
    }
    
    /**
     * Calculate score based on spacing between meetings
     */
    private function calculateSpacingScore($timeSlot, $booking) {
        $minBuffer = 15; // 15 minutes minimum buffer
        $optimalBuffer = 30; // 30 minutes optimal buffer
        
        $slotTime = strtotime($timeSlot);
        $bookingTime = strtotime($booking['booking_time_to']);
        $buffer = abs($slotTime - $bookingTime) / 60; // in minutes
        
        if ($buffer < $minBuffer) {
            return 0;
        } elseif ($buffer >= $optimalBuffer) {
            return 1;
        } else {
            return ($buffer - $minBuffer) / ($optimalBuffer - $minBuffer);
        }
    }
    
    /**
     * Generate human-readable reason for the suggestion
     */
    private function generateSuggestionReason($timeSlot, $score) {
        $reasons = [];
        
        if ($score > 0.8) {
            $reasons[] = "This time slot has historically been very successful for similar meetings";
        } elseif ($score > 0.6) {
            $reasons[] = "This time slot has shown good booking patterns in the past";
        }
        
        $hour = (int)substr($timeSlot, 0, 2);
        if ($hour >= 9 && $hour <= 11) {
            $reasons[] = "Morning slots typically have higher productivity";
        } elseif ($hour >= 14 && $hour <= 16) {
            $reasons[] = "Afternoon slots are often preferred for this type of booking";
        }
        
        return implode(". ", $reasons);
    }
    
    /**
     * Predict meeting duration based on historical data
     */
    public function predictDuration($departmentId, $roomId) {
        $relevantData = array_filter($this->historicalData, function($booking) use ($departmentId, $roomId) {
            return $booking['department_id'] == $departmentId && $booking['room_id'] == $roomId;
        });
        
        if (empty($relevantData)) {
            return 60; // Default duration if no historical data
        }
        
        $totalDuration = array_reduce($relevantData, function($carry, $booking) {
            return $carry + $booking['avg_duration'];
        }, 0);
        
        return round($totalDuration / count($relevantData));
    }
} 