<?php
/**
 * Configuration for Conflict Resolution Microservice
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'crad_calendar_booking');
define('DB_USER', 'crad_crad');
define('DB_PASS', 'crad2025');

// Time settings
define('BUSINESS_HOURS_START', '08:00');
define('BUSINESS_HOURS_END', '17:00');
define('TIME_SLOT_INTERVAL', 30); // minutes

// Conflict resolution settings
define('CONFLICT_THRESHOLD', 15); // minutes
define('MAX_ALTERNATIVES', 5); // maximum number of alternatives to suggest
define('MIN_BOOKING_DURATION', 30); // minimum booking duration in minutes

// Logging
define('LOG_ENABLED', true);
define('LOG_FILE', __DIR__ . '/logs/conflict_resolver.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('Asia/Manila');

// Helper function for logging
function logMessage($message, $level = 'INFO') {
    if (!LOG_ENABLED) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
} 