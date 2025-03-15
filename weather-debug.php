<?php
// Simple direct test of the OpenWeatherMap API
error_reporting(E_ALL);
ini_set('display_errors', 1);

// API key and city
$apiKey = '5b6cfc959767433117ee5cf212cab41f';
$city = 'Quezon City,PH';

// API endpoint
$apiUrl = "http://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";

echo "<h1>Weather API Debug</h1>";
echo "<p>Testing API URL: " . htmlspecialchars($apiUrl) . "</p>";

// Check if allow_url_fopen is enabled
echo "<h2>PHP Configuration</h2>";
echo "<p>allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled') . "</p>";

// Try with file_get_contents
echo "<h2>Testing with file_get_contents</h2>";
try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]
    ]);
    
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response === FALSE) {
        echo "<p style='color: red;'>Error: Failed to fetch data using file_get_contents</p>";
        echo "<pre>" . print_r(error_get_last(), true) . "</pre>";
    } else {
        echo "<p style='color: green;'>Success! Response received.</p>";
        $data = json_decode($response, true);
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}

// Try with cURL as an alternative
echo "<h2>Testing with cURL</h2>";
if (function_exists('curl_version')) {
    echo "<p>cURL is available</p>";
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        if ($response === FALSE) {
            echo "<p style='color: red;'>Error: Failed to fetch data using cURL</p>";
            echo "<p>cURL Error: " . $error . "</p>";
            echo "<pre>cURL Info: " . print_r($info, true) . "</pre>";
        } else {
            echo "<p style='color: green;'>Success! Response received.</p>";
            $data = json_decode($response, true);
            echo "<pre>" . print_r($data, true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>cURL is not available</p>";
}

// Test with HTTPS URL instead of HTTP
echo "<h2>Testing with HTTPS URL</h2>";
$httpsApiUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";
echo "<p>Testing HTTPS API URL: " . htmlspecialchars($httpsApiUrl) . "</p>";

try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]
    ]);
    
    $response = file_get_contents($httpsApiUrl, false, $context);
    
    if ($response === FALSE) {
        echo "<p style='color: red;'>Error: Failed to fetch data using HTTPS</p>";
        echo "<pre>" . print_r(error_get_last(), true) . "</pre>";
    } else {
        echo "<p style='color: green;'>Success! HTTPS Response received.</p>";
        $data = json_decode($response, true);
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception with HTTPS: " . $e->getMessage() . "</p>";
}
?> 