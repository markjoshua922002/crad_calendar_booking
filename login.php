<?php
session_start();
require 'vendor/autoload.php'; // Make sure this path is correct
use \Firebase\JWT\JWT;

$conn = new mysqli('localhost', 'crad_crad', 'crad', 'crad_calendar_booking');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// JWT secret key
$key = 'your_secret_key'; // Replace with your actual secret key

// Handle login request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Create the JWT payload
            $payload = [
                'iss' => 'crad.schoolmanagementsystem2.com', // Issuer
                'iat' => time(), // Issued at
                'exp' => time() + 3600, // Expiration time (1 hour)
                'userId' => $user['id'] // User ID or other payload data
            ];
            // Generate the JWT
            $token = JWT::encode($payload, $key);

            // Set the JWT in a secure cookie
            setcookie('jwt', $token, [
                'expires' => time() + 3600, // 1 hour expiration
                'path' => '/',
                'domain' => 'crad.schoolmanagementsystem2.com', // Your domain
                'secure' => true, // Only send over HTTPS
                'httponly' => true, // Prevent JavaScript access
                'samesite' => 'Strict' // Prevent CSRF
            ]);

            // Store user ID in session
            $_SESSION['user_id'] = $user['id'];

            // Redirect to the protected page
            header('Location: index.php');
            exit();
        } else {
            $login_error = "Invalid username or password!";
        }
    } else {
        $login_error = "Invalid username or password!";
    }
}

// Handle registration request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $reg_username = $_POST['reg_username'];
    $reg_password = $_POST['reg_password'];
    $reg_code = $_POST['reg_code'];

    // Check the registration code
    if ($reg_code === 'BCPCRAD2024') {
        // Check if username already exists
        $check_user = $conn->query("SELECT * FROM users WHERE username='$reg_username'");
        if ($check_user->num_rows > 0) {
            $register_error = "Username already exists!";
        } else {
            // Hash the password before saving
            $hashed_password = password_hash($reg_password, PASSWORD_BCRYPT);
            $conn->query("INSERT INTO users (username, password) VALUES ('$reg_username', '$hashed_password')");
            $register_success = "Registration successful! You can now log in.";
        }
    } else {
        $register_error = "Invalid registration code!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register</title>
    <link rel="stylesheet" href="css/style_login.css">
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

            <form method="POST" class="register-form">
                <h2>Register</h2>
                <input type="text" name="reg_username" placeholder="Username" required>
                <input type="password" name="reg_password" placeholder="Password" required>
                <input type="text" name="reg_code" placeholder="Registration Code" required>
                <button type="submit" name="register">Register</button>
                <?php if (isset($register_error)) echo "<p class='error'>$register_error</p>"; ?>
                <?php if (isset($register_success)) echo "<p class='success'>$register_success</p>"; ?>
            </form>
        </div>
    </div>
</body>
</html>
