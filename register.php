<?php
$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle registration request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $reg_username = $_POST['reg_username'];
    $reg_password = $_POST['reg_password'];
    $reg_code = $_POST['reg_code'];

    // Check if username already exists
    $check_user = $conn->query("SELECT * FROM users WHERE username='$reg_username'");
    if ($check_user->num_rows > 0) {
        $register_error = "Username already exists!";
    } else {
        // Hash the password before saving
        $hashed_password = password_hash($reg_password, PASSWORD_BCRYPT);
        
        // Set activation status based on registration code
        $is_active = (!empty($reg_code) && $reg_code === 'BCPCRAD2024') ? 1 : 0;

        // Insert the new user into the database
        $stmt = $conn->prepare("INSERT INTO users (username, password, is_active) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $reg_username, $hashed_password, $is_active);
        $stmt->execute();
        $stmt->close();

        if ($is_active) {
            $register_success = "Registration successful! Your account is active. You can now log in!";
        } else {
            $register_success = "Registration successful! Your account is inactive. Please contact the administrator to activate your account.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="mycss/style_login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo-container">
                <img src="assets/bcplogo.png" alt="School Logo" class="school-logo">
                <h1 class="school-title">BCP</h1>
            </div>
            <form method="POST" class="register-form">
                <h2>Register</h2>
                <input type="text" name="reg_username" placeholder="Username" required>
                <input type="password" name="reg_password" placeholder="Password" required>
                <input type="text" name="reg_code" placeholder="Registration Code">
                <button type="submit" name="register">Register</button>
                <?php if (isset($register_error)) echo "<p class='error'>$register_error</p>"; ?>
                <?php if (isset($register_success)) echo "<p class='success'>$register_success</p>"; ?>
            </form>
            <p>Already have an account? <a href="login.php">Log in here</a>.</p>
        </div>
    </div>
</body>
</html>
