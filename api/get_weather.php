<?php
// Fetch weather data from OpenWeatherMap API
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Your OpenWeatherMap API key
$apiKey = '5b6cfc959767433117ee5cf212cab41f';

// City for which to fetch weather data (default)
$city = isset($_GET['city']) ? $_GET['city'] : 'Quezon City,PH';

// Log the request
error_log("Weather API Request - City: $city, API Key: $apiKey");

// API endpoint
$apiUrl = "http://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";
error_log("Weather API URL: $apiUrl");

// Fetch weather data
try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5 // 5 second timeout
        ]
    ]);
    $weatherData = file_get_contents($apiUrl, false, $context);

    if ($weatherData === FALSE) {
        error_log("Error fetching weather data: " . error_get_last()['message']);
        throw new Exception("Failed to fetch weather data");
    }

    // Decode JSON data
    $weatherArray = json_decode($weatherData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        throw new Exception("Failed to decode weather data");
    }
    
    error_log("Weather data received: " . print_r($weatherArray, true));

    // Extract relevant information
    $temperature = $weatherArray['main']['temp'];
    $weatherDescription = $weatherArray['weather'][0]['description'];
    $weatherIcon = $weatherArray['weather'][0]['icon'];
    $humidity = $weatherArray['main']['humidity'];
    $windSpeed = $weatherArray['wind']['speed'];
    $cityName = $weatherArray['name'];

    // Output weather information
    header('Content-Type: application/json');
    $response = [
        'temperature' => $temperature,
        'description' => $weatherDescription,
        'icon' => $weatherIcon,
        'humidity' => $humidity,
        'windSpeed' => $windSpeed,
        'city' => $cityName
    ];
    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Weather API error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?> 