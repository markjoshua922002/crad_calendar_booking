<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch users
$users = $conn->query("SELECT id, username, is_active FROM users");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_activation'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $user_id);
    $stmt->execute();
    $stmt->close();
    header('Location: accounts.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Accounts</title>
    <link rel="stylesheet" href="mycss/style.css?v=1">
    <link rel="stylesheet" href="mycss/sidebar.css">
</head>
<body>
<button class="menu-button" id="menuButton">&#9776;</button> <!-- Menu button -->

<div class="sidebar" id="sidebar">
    <a href="index.php">CRAD</a>
    <a href="form.php">LOGBOOK</a>
    <a href="accounts.php">Users</a>
    <div style="flex-grow: 1;"></div> <!-- Spacer to push logout button to the bottom -->
    <a href="logout.php" class="logout-button">Logout</a>
</div>
<!-- End of sidebar code -->

<div class="container">
    <h1>User Accounts</h1>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= $user['is_active'] ? 'Active' : 'Inactive' ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $user['is_active'] ? 0 : 1 ?>">
                                <button type="submit" name="toggle_activation">
                                    <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="js/script.js"></script>
</body>
</html>