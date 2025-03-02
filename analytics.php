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

// Fetch department booking data for the current month
$currentMonth = date('m');
$currentYear = date('Y');
$sql = "SELECT d.name as department, COUNT(*) as bookings 
        FROM bookings b 
        JOIN departments d ON b.department_id = d.id 
        WHERE MONTH(b.booking_date) = ? AND YEAR(b.booking_date) = ? 
        GROUP BY d.name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $currentMonth, $currentYear);
$stmt->execute();
$result = $stmt->get_result();

$departments = [];
$bookings = [];

while ($row = $result->fetch_assoc()) {
    $departments[] = $row['department'];
    $bookings[] = $row['bookings'];
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Department Booking Analytics</title>
    <link rel="stylesheet" href="mycss/style.css?v=4">
    <link rel="stylesheet" href="mycss/sidebar.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
    </style>
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

<div class="container">
    <h1>Department Booking Analytics for <?php echo date('F Y'); ?></h1>
    <canvas id="bookingChart" width="400" height="200"></canvas>
</div>

<script>
    var ctx = document.getElementById('bookingChart').getContext('2d');
    var bookingChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($departments); ?>,
            datasets: [{
                label: 'Number of Bookings',
                data: <?php echo json_encode($bookings); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Sidebar script
    document.getElementById('menuButton').addEventListener('click', function() {
        var sidebar = document.getElementById('sidebar');
        if (sidebar.style.display === 'block') {
            sidebar.style.display = 'none';
        } else {
            sidebar.style.display = 'block';
        }
    });
</script>
<script src="js/script.js?v=11"></script>
</body>
</html>