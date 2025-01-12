<?php
// Database connection
$conn = new mysqli('localhost', 'crad_crad', 'crad', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch current month and year
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$firstDayOfMonth = date('w', strtotime("$year-$month-01"));
$totalDaysInMonth = date('t', strtotime("$year-$month-01"));

// Fetch bookings for the current month
$bookings = $conn->query("SELECT bookings.*, departments.name as department_name, departments.color, rooms.name as room_name 
    FROM bookings 
    JOIN departments ON bookings.department_id = departments.id 
    JOIN rooms ON bookings.room_id = rooms.id 
    WHERE MONTH(booking_date) = '$month' AND YEAR(booking_date) = '$year'");

$appointments = [];
while ($row = $bookings->fetch_assoc()) {
    $date = date('j', strtotime($row['booking_date']));
    $appointments[$date][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar View</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 150px;
            background-color: #0056b3;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            padding: 15px;
            text-align: center;
            text-decoration: none;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .sidebar a:hover {
            background-color: #003f7a;
        }
        .appointment {
            padding: 5px;
            margin: 2px;
            border-radius: 3px;
            font-size: 12px;
            color: white;
            cursor: default;
        }
        .day {
            min-height: 100px;
            border: 1px solid #ddd;
            padding: 5px;
            background-color: white;
        }
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-top: 20px;
        }
        .navigation {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
        }
        .nav-button {
            padding: 10px 20px;
            background-color: #0056b3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
        }
        .month-year {
            font-size: 1.5em;
            font-weight: bold;
            margin: 0 20px;
        }
        .login-link {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #0056b3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .login-link:hover {
            background-color: #003f7a;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="home.php">HOME</a>
    <a href="login.php">BOOK NOW</a>
</div>

<div class="container" style="margin-left: 170px;">
    <header>
        <img src="assets/bcplogo.png" alt="Logo" class="logo">
        <h1>Calendar Schedule</h1>
        <a href="login.php" class="login-link">Login</a>
    </header>

    <div class="navigation">
        <a href="home.php?month=<?= ($month == 1) ? 12 : $month-1 ?>&year=<?= ($month == 1) ? $year-1 : $year ?>" class="nav-button">Previous</a>
        <span class="month-year"><?= date('F Y', strtotime("$year-$month-01")) ?></span>
        <a href="home.php?month=<?= ($month == 12) ? 1 : $month+1 ?>&year=<?= ($month == 12) ? $year+1 : $year ?>" class="nav-button">Next</a>
    </div>

    <div class="calendar">
        <div>Sunday</div>
        <div>Monday</div>
        <div>Tuesday</div>
        <div>Wednesday</div>
        <div>Thursday</div>
        <div>Friday</div>
        <div>Saturday</div>

        <?php for ($i = 0; $i < $firstDayOfMonth; $i++): ?>
            <div class="day"></div>
        <?php endfor; ?>

        <?php for ($day = 1; $day <= $totalDaysInMonth; $day++): ?>
            <div class="day">
                <div class="day-number"><?= $day ?></div>
                <?php if (isset($appointments[$day])): ?>
                    <?php foreach ($appointments[$day] as $appointment): ?>
                        <div class="appointment" style="background-color: <?= $appointment['color'] ?>">
                            <?= $appointment['name'] ?><br>
                            <?= $appointment['department_name'] ?><br>
                            <?= $appointment['booking_time'] ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

</body>
</html>