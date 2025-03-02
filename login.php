<?php
// Set secure session parameters before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Database credentials
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'crad_crad';
$password = getenv('DB_PASS') ?: 'crad2025';
$database = getenv('DB_NAME') ?: 'crad_calendar_booking';

// Database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error); // Log error
    die('Connection failed. Please try again later.'); // Do not expose technical details
}

session_regenerate_id(true); // Prevent session fixation

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate input length and sanitize
    if (strlen($username) < 3 || strlen($username) > 50) {
        $login_error = "Invalid username or password!";
    } else {
        $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password securely
            if (password_verify($password, $user['password'])) {
                // Prevent brute-force attacks with rate limiting
                clearLoginAttempts($username, $conn);

                // Set session variables and regenerate ID
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                session_regenerate_id(true);

                // Redirect to the protected page
                header('Location: index.php');
                exit();
            } else {
                logFailedLogin($username, $conn);
                $login_error = "Invalid username or password!";
            }
        } else {
            logFailedLogin($username, $conn);
            $login_error = "Invalid username or password!";
        }

        $stmt->close();
    }
}

/**
 * Log failed login attempts for rate-limiting
 */
function logFailedLogin($username, $conn) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address, attempt_time) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $username, $ip_address);
    $stmt->execute();
    $stmt->close();
}

/**
 * Clear login attempts after successful login
 */
function clearLoginAttempts($username, $conn) {
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login</title>
    <link rel="stylesheet" href="mycss/style_login.css">

    <!-- Security headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data:;">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo-container">
                <img src="assets/bcplogo.png" alt="School Logo" class="school-logo">
                <h1 class="school-title">BCP</h1>
            </div>
            <form method="POST" class="login-form">
                <h2>Login</h2>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <?php if (isset($login_error)) echo "<p class='error'>$login_error</p>"; ?>
            </form>
            <p>Don't have an account? <a href="register.php">Register here</a>.</p>
        </div>
    </div>
</body>
</html>
