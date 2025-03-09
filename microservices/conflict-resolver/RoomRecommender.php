<?php
/**
 * Room Recommender Class
 * 
 * Uses AI to recommend optimal rooms based on various factors
 */
class RoomRecommender {
    private $db;
    private $roomMetrics = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->initializeRoomMetrics();
    }
    
    /**
     * Initialize room usage metrics
     */
    private function initializeRoomMetrics() {
        $sql = "SELECT 
                    r.id,
                    r.name,
                    COUNT(b.id) as usage_count,
                    AVG(TIMESTAMPDIFF(MINUTE, b.booking_time_from, b.booking_time_to)) as avg_duration,
                    COUNT(DISTINCT b.department_id) as department_diversity,
                    COUNT(DISTINCT DATE(b.booking_date)) as unique_days
                FROM rooms r
                LEFT JOIN bookings b ON r.id = b.room_id
                WHERE b.booking_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                GROUP BY r.id";
        
        $result = $this->db->executeQuery($sql);
        $this->roomMetrics = $this->db->fetchAll($result);
    }
    
    /**
     * Recommend rooms based on department and duration
     */
    public function recommendRooms($departmentId, $duration, $date, $timeFrom, $timeTo) {
        $recommendations = [];
        
        foreach ($this->roomMetrics as $room) {
            $score = $this->calculateRoomScore($room, $departmentId, $duration);
            
            // Check availability
            $conflicts = $this->checkAvailability($room['id'], $date, $timeFrom, $timeTo);
            if (!$conflicts) {
                $recommendations[] = [
                    'room_id' => $room['id'],
                    'name' => $room['name'],
                    'score' => $score,
                    'reasons' => $this->generateRecommendationReasons($room, $score, $departmentId)
                ];
            }
        }
        
        // Sort by score
        usort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice($recommendations, 0, 3); // Return top 3 recommendations
    }
    
    /**
     * Calculate room score based on various factors
     */
    private function calculateRoomScore($room, $departmentId, $duration) {
        $weights = [
            'usage' => 0.3,
            'duration_match' => 0.3,
            'department_affinity' => 0.2,
            'diversity' => 0.2
        ];
        
        // Usage score (prefer rooms with moderate usage)
        $usageScore = $this->calculateUsageScore($room['usage_count']);
        
        // Duration match score
        $durationScore = $this->calculateDurationMatchScore($duration, $room['avg_duration']);
        
        // Department affinity score
        $affinityScore = $this->calculateDepartmentAffinityScore($room['id'], $departmentId);
        
        // Diversity score (prefer rooms that serve multiple departments)
        $diversityScore = $room['department_diversity'] / 10; // Normalize by assuming max 10 departments
        
        return ($usageScore * $weights['usage']) +
               ($durationScore * $weights['duration_match']) +
               ($affinityScore * $weights['department_affinity']) +
               ($diversityScore * $weights['diversity']);
    }
    
    /**
     * Calculate usage score with a preference for moderate usage
     */
    private function calculateUsageScore($usageCount) {
        $optimal = 50; // Optimal number of bookings per month
        $diff = abs($usageCount - $optimal);
        return 1 / (1 + ($diff / $optimal));
    }
    
    /**
     * Calculate how well the duration matches room's typical usage
     */
    private function calculateDurationMatchScore($requestedDuration, $avgDuration) {
        if (!$avgDuration) return 0.5; // Neutral score for new rooms
        $diff = abs($requestedDuration - $avgDuration);
        return 1 / (1 + ($diff / 60)); // Normalize by hour
    }
    
    /**
     * Calculate department's historical affinity for the room
     */
    private function calculateDepartmentAffinityScore($roomId, $departmentId) {
        $sql = "SELECT COUNT(*) as booking_count
                FROM bookings
                WHERE room_id = ? AND department_id = ?
                AND booking_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
        
        $result = $this->db->executeQuery($sql, [$roomId, $departmentId], "ii");
        $data = $this->db->fetchOne($result);
        
        return min(1, $data['booking_count'] / 20); // Cap at 20 bookings per month
    }
    
    /**
     * Check if room is available for the requested time
     */
    private function checkAvailability($roomId, $date, $timeFrom, $timeTo) {
        $sql = "SELECT COUNT(*) as conflict_count
                FROM bookings
                WHERE room_id = ?
                AND booking_date = ?
                AND (
                    (booking_time_from < ? AND booking_time_to > ?) OR
                    (booking_time_from < ? AND booking_time_to > ?) OR
                    (booking_time_from >= ? AND booking_time_to <= ?)
                )";
        
        $result = $this->db->executeQuery($sql, [
            $roomId, $date, $timeTo, $timeFrom, $timeTo, $timeFrom, $timeFrom, $timeTo
        ], "ssssssss");
        
        $data = $this->db->fetchOne($result);
        return $data['conflict_count'] > 0;
    }
    
    /**
     * Generate human-readable reasons for room recommendations
     */
    private function generateRecommendationReasons($room, $score, $departmentId) {
        $reasons = [];
        
        if ($score > 0.8) {
            $reasons[] = "This room is an excellent match for your needs";
        } elseif ($score > 0.6) {
            $reasons[] = "This room is a good match based on historical data";
        }
        
        // Add specific reasons based on metrics
        if ($room['usage_count'] > 0) {
            $reasons[] = "This room has been used successfully for similar meetings";
        }
        
        if ($this->calculateDepartmentAffinityScore($room['id'], $departmentId) > 0.5) {
            $reasons[] = "Your department has had successful meetings in this room";
        }
        
        if ($room['department_diversity'] > 5) {
            $reasons[] = "This room is versatile and works well for various departments";
        }
        
        return $reasons;
    }
} 