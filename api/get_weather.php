<?php
// Fetch weather data from OpenWeatherMap API

// Your OpenWeatherMap API key
$apiKey = '5b6cfc959767433117ee5cf212cab41f';

// City for which to fetch weather data
$city = 'Quezon City';

// API endpoint
$apiUrl = "http://api.openweathermap.org/data/2.5/weather?q={$QuezonCity}&appid={$5b6cfc959767433117ee5cf212cab41f}&units=metric";

// Fetch weather data
$weatherData = file_get_contents($apiUrl);

if ($weatherData === FALSE) {
    die('Error fetching weather data.');
}

// Decode JSON data
$weatherArray = json_decode($weatherData, true);

// Extract relevant information
$temperature = $weatherArray['main']['temp'];
$weatherDescription = $weatherArray['weather'][0]['description'];

// Output weather information
header('Content-Type: application/json');
echo json_encode([
    'temperature' => $temperature,
    'description' => $weatherDescription
]);
?> 