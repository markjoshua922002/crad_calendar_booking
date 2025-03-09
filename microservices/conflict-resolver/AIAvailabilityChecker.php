<?php

use Phpml\Clustering\KMeans;
use Phpml\Preprocessing\Normalizer;

class AIAvailabilityChecker {
    private $db;
    private $normalizer;
    
    public function __construct($db) {
        $this->db = $db;
        $this->normalizer = new Normalizer();
    }
    
    /**
     * Get available time slots with AI-powered optimization
     */
    public function getAvailableSlots($date, $roomId, $duration = 60) {
        // Get all bookings for the room on the given date
        $existingBookings = $this->getRoomBookings($date, $roomId);
        
        // Get historical booking patterns
        $patterns = $this->analyzeBookingPatterns($roomId);
        
        // Generate all possible time slots
        $slots = $this->generateTimeSlots($duration);
        
        // Filter out booked slots
        $availableSlots = $this->filterAvailableSlots($slots, $existingBookings);
        
        // Score and rank available slots
        $rankedSlots = $this->rankTimeSlots($availableSlots, $patterns);
        
        // Group slots into optimal time windows
        return $this->groupOptimalTimeWindows($rankedSlots);
    }
    
    /**
     * Analyze historical booking patterns using K-means clustering
     */
    private function analyzeBookingPatterns($roomId) {
        $query = "SELECT 
            HOUR(time_from) as hour,
            MINUTE(time_from) as minute,
            COUNT(*) as booking_count,
            AVG(TIMESTAMPDIFF(MINUTE, time_from, time_to)) as avg_duration,
            AVG(participant_count) as avg_participants
        FROM bookings
        WHERE room_id = ?
        AND time_from >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY HOUR(time_from), MINUTE(time_from)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$roomId]);
        $historicalData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($historicalData)) {
            return [];
        }
        
        // Prepare data for clustering
        $samples = [];
        foreach ($historicalData as $data) {
            $samples[] = [
                $data['hour'] + ($data['minute'] / 60),
                $data['booking_count'],
                $data['avg_duration'],
                $data['avg_participants']
            ];
        }
        
        // Normalize the data
        $samples = $this->normalizer->transform($samples);
        
        // Perform clustering
        $kmeans = new KMeans(3); // 3 clusters for peak, normal, and off-peak times
        $clusters = $kmeans->cluster($samples);
        
        return [
            'clusters' => $clusters,
            'raw_data' => $historicalData
        ];
    }
    
    /**
     * Generate all possible time slots for the day
     */
    private function generateTimeSlots($duration) {
        $slots = [];
        $startHour = 7; // Start at 7 AM
        $endHour = 22; // End at 10 PM
        
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $time = sprintf('%02d:%02d:00', $hour, $minute);
                $endTime = date('H:i:s', strtotime($time) + ($duration * 60));
                
                $slots[] = [
                    'start_time' => $time,
                    'end_time' => $endTime,
                    'duration' => $duration
                ];
            }
        }
        
        return $slots;
    }
    
    /**
     * Filter out slots that conflict with existing bookings
     */
    private function filterAvailableSlots($slots, $existingBookings) {
        $availableSlots = [];
        
        foreach ($slots as $slot) {
            $isAvailable = true;
            $slotStart = strtotime($slot['start_time']);
            $slotEnd = strtotime($slot['end_time']);
            
            foreach ($existingBookings as $booking) {
                $bookingStart = strtotime($booking['time_from']);
                $bookingEnd = strtotime($booking['time_to']);
                
                if ($slotStart < $bookingEnd && $slotEnd > $bookingStart) {
                    $isAvailable = false;
                    break;
                }
            }
            
            if ($isAvailable) {
                $availableSlots[] = $slot;
            }
        }
        
        return $availableSlots;
    }
    
    /**
     * Rank time slots based on historical patterns and AI analysis
     */
    private function rankTimeSlots($slots, $patterns) {
        $rankedSlots = [];
        
        foreach ($slots as $slot) {
            $score = $this->calculateSlotScore($slot, $patterns);
            $rankedSlots[] = array_merge($slot, ['score' => $score]);
        }
        
        // Sort by score
        usort($rankedSlots, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $rankedSlots;
    }
    
    /**
     * Calculate score for a time slot based on various factors
     */
    private function calculateSlotScore($slot, $patterns) {
        $hour = (int)substr($slot['start_time'], 0, 2);
        $score = 1.0;
        
        // Prefer business hours
        if ($hour >= 9 && $hour <= 17) {
            $score *= 1.5;
        }
        
        // Consider historical patterns
        foreach ($patterns['raw_data'] as $pattern) {
            if ($pattern['hour'] == $hour) {
                // Higher score for historically popular times
                $score *= (1 + ($pattern['booking_count'] / 100));
                break;
            }
        }
        
        // Penalize early morning and late evening
        if ($hour < 8 || $hour > 18) {
            $score *= 0.7;
        }
        
        return $score;
    }
    
    /**
     * Group time slots into optimal windows
     */
    private function groupOptimalTimeWindows($rankedSlots) {
        $windows = [];
        $currentWindow = null;
        
        foreach ($rankedSlots as $slot) {
            if (!$currentWindow) {
                $currentWindow = [
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'score' => $slot['score'],
                    'slots' => [$slot]
                ];
                continue;
            }
            
            // If slot is continuous with current window
            if ($currentWindow['end_time'] === $slot['start_time']) {
                $currentWindow['end_time'] = $slot['end_time'];
                $currentWindow['score'] = ($currentWindow['score'] + $slot['score']) / 2;
                $currentWindow['slots'][] = $slot;
            } else {
                $windows[] = $currentWindow;
                $currentWindow = [
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'score' => $slot['score'],
                    'slots' => [$slot]
                ];
            }
        }
        
        if ($currentWindow) {
            $windows[] = $currentWindow;
        }
        
        // Sort windows by score
        usort($windows, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return [
            'optimal_windows' => array_slice($windows, 0, 5),
            'all_available_windows' => $windows
        ];
    }
    
    /**
     * Get all bookings for a room on a specific date
     */
    private function getRoomBookings($date, $roomId) {
        $query = "SELECT * FROM bookings 
                 WHERE room_id = ? 
                 AND DATE(time_from) = ?
                 ORDER BY time_from";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$roomId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 