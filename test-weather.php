<?php
// Simple test file to verify the weather API is working
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather API Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .weather-display {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }
        .weather-icon {
            margin-right: 15px;
        }
        .weather-info {
            display: flex;
            flex-direction: column;
        }
        .temperature {
            font-size: 24px;
            font-weight: bold;
        }
        .description {
            text-transform: capitalize;
            margin: 5px 0;
        }
        .city {
            color: #666;
        }
        button {
            background-color: #4285f4;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #3367d6;
        }
    </style>
</head>
<body>
    <h1>Weather API Test</h1>
    
    <div class="card">
        <h2>Original API Test</h2>
        <?php
        // Test the original API
        $apiUrl = 'api/get_weather.php';
        
        echo "<p>Testing: $apiUrl</p>";
        
        try {
            // Use cURL to test
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                echo "<p>HTTP Code: $httpCode</p>";
                
                if ($response === FALSE) {
                    echo '<p>Error: ' . curl_error($ch) . '</p>';
                } else {
                    $data = json_decode($response, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        echo '<p>Error: Failed to decode JSON response</p>';
                        echo '<pre>' . json_last_error_msg() . '</pre>';
                        echo '<p>Raw response:</p>';
                        echo '<pre>' . htmlspecialchars($response) . '</pre>';
                    } else {
                        echo '<p>Weather data successfully retrieved:</p>';
                        echo '<pre>' . print_r($data, true) . '</pre>';
                        
                        if (!isset($data['error'])) {
                            // Display the weather
                            echo '<div class="weather-display">';
                            echo '<div class="weather-icon">';
                            echo '<img src="https://openweathermap.org/img/wn/' . $data['icon'] . '.png" alt="Weather icon" width="50" height="50">';
                            echo '</div>';
                            echo '<div class="weather-info">';
                            echo '<span class="temperature">' . round($data['temperature']) . '°C</span>';
                            echo '<span class="description">' . $data['description'] . '</span>';
                            echo '<span class="city">' . $data['city'] . '</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                }
                
                curl_close($ch);
            } else {
                echo '<p>cURL is not available</p>';
            }
        } catch (Exception $e) {
            echo '<p>Exception: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>
    
    <div class="card">
        <h2>Fixed API Test</h2>
        <?php
        // Test the fixed API
        $apiUrl = 'api/get_weather_fixed.php';
        
        echo "<p>Testing: $apiUrl</p>";
        
        try {
            // Use cURL to test
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                echo "<p>HTTP Code: $httpCode</p>";
                
                if ($response === FALSE) {
                    echo '<p>Error: ' . curl_error($ch) . '</p>';
                } else {
                    $data = json_decode($response, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        echo '<p>Error: Failed to decode JSON response</p>';
                        echo '<pre>' . json_last_error_msg() . '</pre>';
                        echo '<p>Raw response:</p>';
                        echo '<pre>' . htmlspecialchars($response) . '</pre>';
                    } else {
                        echo '<p>Weather data successfully retrieved:</p>';
                        echo '<pre>' . print_r($data, true) . '</pre>';
                        
                        if (!isset($data['error'])) {
                            // Display the weather
                            echo '<div class="weather-display">';
                            echo '<div class="weather-icon">';
                            echo '<img src="https://openweathermap.org/img/wn/' . $data['icon'] . '.png" alt="Weather icon" width="50" height="50">';
                            echo '</div>';
                            echo '<div class="weather-info">';
                            echo '<span class="temperature">' . round($data['temperature']) . '°C</span>';
                            echo '<span class="description">' . $data['description'] . '</span>';
                            echo '<span class="city">' . $data['city'] . '</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                }
                
                curl_close($ch);
            } else {
                echo '<p>cURL is not available</p>';
            }
        } catch (Exception $e) {
            echo '<p>Exception: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>
    
    <div class="card">
        <h2>JavaScript Fetch Test</h2>
        <div id="js-result">
            <p>Testing API with JavaScript fetch...</p>
        </div>
        <button id="test-original">Test Original API</button>
        <button id="test-fixed">Test Fixed API</button>
    </div>
    
    <script>
        // Test the API with JavaScript fetch
        function testWeatherAPI(apiUrl) {
            const resultDiv = document.getElementById('js-result');
            resultDiv.innerHTML = '<p>Fetching weather data from ' + apiUrl + '...</p>';
            
            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();
            const url = apiUrl + '?_=' + timestamp;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Weather data:', data);
                    
                    let html = '<p>Weather data successfully retrieved from ' + apiUrl + ':</p>';
                    html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    
                    if (!data.error) {
                        // Display the weather
                        html += '<div class="weather-display">';
                        html += '<div class="weather-icon">';
                        html += '<img src="https://openweathermap.org/img/wn/' + data.icon + '.png" alt="Weather icon" width="50" height="50">';
                        html += '</div>';
                        html += '<div class="weather-info">';
                        html += '<span class="temperature">' + Math.round(data.temperature) + '°C</span>';
                        html += '<span class="description">' + data.description + '</span>';
                        html += '<span class="city">' + data.city + '</span>';
                        html += '</div>';
                        html += '</div>';
                    }
                    
                    resultDiv.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultDiv.innerHTML = '<p>Error fetching from ' + apiUrl + ': ' + error.message + '</p>';
                });
        }
        
        // Run the test when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            testWeatherAPI('api/get_weather_fixed.php');
        });
        
        // Run the tests when the buttons are clicked
        document.getElementById('test-original').addEventListener('click', function() {
            testWeatherAPI('api/get_weather.php');
        });
        
        document.getElementById('test-fixed').addEventListener('click', function() {
            testWeatherAPI('api/get_weather_fixed.php');
        });
    </script>
</body>
</html> 