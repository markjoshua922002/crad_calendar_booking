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

// API endpoint - using HTTPS instead of HTTP
$apiUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=metric";
error_log("Weather API URL: $apiUrl");

// Fetch weather data using cURL
try {
    // Check if cURL is available
    if (!function_exists('curl_init')) {
        error_log("cURL is not available. Falling back to file_get_contents");
        
        // Fallback to file_get_contents if cURL is not available
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);
        
        $weatherData = file_get_contents($apiUrl, false, $context);
        
        if ($weatherData === FALSE) {
            error_log("Error fetching weather data with file_get_contents: " . print_r(error_get_last(), true));
            throw new Exception("Failed to fetch weather data");
        }
    } else {
        // Use cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for compatibility
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $weatherData = curl_exec($ch);
        
        if ($weatherData === FALSE) {
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            error_log("cURL Error: " . $error);
            error_log("cURL Info: " . print_r($info, true));
            curl_close($ch);
            throw new Exception("Failed to fetch weather data: " . $error);
        }
        
        curl_close($ch);
    }

    // Decode JSON data
    $weatherArray = json_decode($weatherData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        error_log("Raw response: " . $weatherData);
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