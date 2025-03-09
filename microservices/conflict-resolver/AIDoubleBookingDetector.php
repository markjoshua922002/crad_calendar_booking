<?php

use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\Preprocessing\Normalizer;

class AIDoubleBookingDetector {
    private $db;
    private $model;
    private $normalizer;
    
    public function __construct($db) {
        $this->db = $db;
        $this->model = new SVC(Kernel::RBF);
        $this->normalizer = new Normalizer();
        $this->initializeModel();
    }
    
    /**
     * Initialize and train the ML model with historical booking patterns
     */
    private function initializeModel() {
        $query = "SELECT 
            u.user_id,
            u.department_id,
            TIME_TO_SEC(b1.time_from) as booking1_start,
            TIME_TO_SEC(b1.time_to) as booking1_end,
            TIME_TO_SEC(b2.time_from) as booking2_start,
            TIME_TO_SEC(b2.time_to) as booking2_end,
            CASE WHEN 
                (b1.time_from < b2.time_to AND b1.time_to > b2.time_from)
                THEN 1 ELSE 0 
            END as was_double_booked
        FROM bookings b1
        JOIN bookings b2 ON b1.user_id = b2.user_id 
            AND b1.booking_id < b2.booking_id
            AND DATE(b1.time_from) = DATE(b2.time_from)
        JOIN users u ON b1.user_id = u.user_id
        LIMIT 1000";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $historicalData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($historicalData)) {
            return;
        }
        
        // Prepare training data
        $samples = [];
        $labels = [];
        foreach ($historicalData as $booking) {
            $samples[] = [
                $booking['user_id'],
                $booking['department_id'],
                $booking['booking1_start'],
                $booking['booking1_end'],
                $booking['booking2_start'],
                $booking['booking2_end']
            ];
            $labels[] = $booking['was_double_booked'];
        }
        
        // Normalize the data
        $samples = $this->normalizer->transform($samples);
        
        // Train the model
        $this->model->train($samples, $labels);
    }
    
    /**
     * Check for potential double bookings
     */
    public function checkDoubleBooking($userId, $departmentId, $proposedStart, $proposedEnd, $date) {
        // First check actual double bookings
        $actualConflicts = $this->checkActualDoubleBookings($userId, $date, $proposedStart, $proposedEnd);
        
        if (!empty($actualConflicts)) {
            return [
                'has_double_booking' => true,
                'conflicts' => $actualConflicts,
                'risk_level' => 'high',
                'recommendations' => $this->generateRecommendations($actualConflicts)
            ];
        }
        
        // Prepare data for ML prediction
        $userBookings = $this->getUserDayBookings($userId, $date);
        $predictions = [];
        
        foreach ($userBookings as $booking) {
            $sample = [
                [
                    $userId,
                    $departmentId,
                    strtotime($proposedStart),
                    strtotime($proposedEnd),
                    strtotime($booking['time_from']),
                    strtotime($booking['time_to'])
                ]
            ];
            
            $sample = $this->normalizer->transform($sample);
            $risk = $this->model->predict($sample[0]);
            
            if ($risk > 0) {
                $predictions[] = [
                    'existing_booking' => $booking,
                    'risk_score' => $risk
                ];
            }
        }
        
        return [
            'has_double_booking' => false,
            'potential_conflicts' => $predictions,
            'risk_level' => $this->calculateRiskLevel($predictions),
            'recommendations' => $this->generateRecommendations($predictions)
        ];
    }
    
    /**
     * Check for actual double bookings in the database
     */
    private function checkActualDoubleBookings($userId, $date, $startTime, $endTime) {
        $query = "SELECT * FROM bookings 
                 WHERE user_id = ? 
                 AND DATE(time_from) = ?
                 AND (
                     (time_from < ? AND time_to > ?) OR
                     (time_from < ? AND time_to > ?) OR
                     (time_from >= ? AND time_to <= ?)
                 )";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $userId, 
            $date, 
            $endTime, 
            $startTime, 
            $endTime, 
            $startTime, 
            $startTime, 
            $endTime
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all user bookings for a specific day
     */
    private function getUserDayBookings($userId, $date) {
        $query = "SELECT * FROM bookings 
                 WHERE user_id = ? 
                 AND DATE(time_from) = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calculate risk level based on predictions
     */
    private function calculateRiskLevel($predictions) {
        if (empty($predictions)) {
            return 'low';
        }
        
        $maxRisk = max(array_column($predictions, 'risk_score'));
        
        if ($maxRisk > 0.7) {
            return 'high';
        } elseif ($maxRisk > 0.3) {
            return 'medium';
        }
        
        return 'low';
    }
    
    /**
     * Generate smart recommendations based on conflicts
     */
    private function generateRecommendations($conflicts) {
        $recommendations = [];
        
        foreach ($conflicts as $conflict) {
            $booking = is_array($conflict) ? $conflict : $conflict['existing_booking'];
            
            // Calculate buffer time (30 minutes)
            $bufferTime = 30 * 60;
            $alternativeStart = date('Y-m-d H:i:s', strtotime($booking['time_to']) + $bufferTime);
            $alternativeBefore = date('Y-m-d H:i:s', strtotime($booking['time_from']) - $bufferTime);
            
            $recommendations[] = [
                'conflict_booking_id' => $booking['booking_id'],
                'suggestions' => [
                    [
                        'type' => 'reschedule_after',
                        'time' => $alternativeStart,
                        'confidence' => $this->calculateConfidence($alternativeStart)
                    ],
                    [
                        'type' => 'reschedule_before',
                        'time' => $alternativeBefore,
                        'confidence' => $this->calculateConfidence($alternativeBefore)
                    ]
                ]
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate confidence score for suggested time
     */
    private function calculateConfidence($time) {
        $hour = (int)date('H', strtotime($time));
        
        // Base confidence
        $confidence = 1.0;
        
        // Prefer business hours (9 AM - 5 PM)
        if ($hour >= 9 && $hour <= 17) {
            $confidence *= 1.5;
        }
        
        // Avoid early morning and late evening
        if ($hour < 7 || $hour > 19) {
            $confidence *= 0.5;
        }
        
        return min(1.0, $confidence);
    }
} 