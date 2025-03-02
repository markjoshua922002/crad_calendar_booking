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
$sql = "SELECT department, COUNT(*) as bookings FROM appointments WHERE MONTH(booking_date) = ? AND YEAR(booking_date) = ? GROUP BY department";
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Department Booking Analytics for <?php echo date('F Y'); ?></h1>
    <canvas id="bookingChart" width="400" height="200"></canvas>
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
    </script>
</body>
</html>