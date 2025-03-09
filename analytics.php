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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Booking Analytics - BCP CRAD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="mycss/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/sidebar.css?v=<?= time() ?>">
    <link rel="icon" href="assets/bcplogo.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            overflow: hidden;
            background-color: #f5f7fa;
            height: 100vh;
            margin: 0;
            padding: 0;
            font-size: 12px;
            transform: scale(0.9);
            transform-origin: top left;
            width: 111.11%;
            height: 111.11%;
        }
        
        .app-container {
            display: flex;
            height: 100vh;
            position: relative;
            overflow: hidden;
            max-width: 2133px; /* 1920px * 1.11 */
            margin: 0 auto;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px; /* Match sidebar width */
            transition: margin-left 0.3s ease;
            position: relative;
            width: calc(100% - 250px);
            overflow: auto;
        }
        
        /* When sidebar is collapsed */
        .sidebar.collapsed + .main-content,
        .sidebar-collapsed .main-content {
            margin-left: 70px; /* Match collapsed sidebar width */
            width: calc(100% - 70px);
        }
        
        .analytics-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .analytics-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .chart-container {
            height: 400px;
            position: relative;
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: #333;
            font-size: 20px;
            cursor: pointer;
            display: none;
        }
        
        @media screen and (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar - Using sidebar.css styles -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="assets/bcplogo.png" alt="BCP Logo" class="sidebar-logo">
                <h2>BCP CRAD</h2>
            </div>
            
            <div class="sidebar-menu">
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendar</span>
                </a>
                <a href="form.php" class="<?= basename($_SERVER['PHP_SELF']) == 'form.php' ? 'active' : '' ?>">
                    <i class="fas fa-book"></i>
                    <span>Logbook</span>
                </a>
                <a href="analytics.php" class="<?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
                <a href="admin.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>">
                    <i class="fas fa-cogs"></i>
                    <span>Admin</span>
                </a>
            </div>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-button">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1>Analytics</h1>
                        <p><?= date('l, F j, Y') ?></p>
                    </div>
                </div>
            </div>
            
            <div class="analytics-container">
                <div class="analytics-header">
                    <h1>Bookings by Department - <?php echo date('F Y'); ?></h1>
                </div>
                <div class="chart-container">
                    <canvas id="bookingChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Add scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/sidebar.js?v=<?= time() ?>"></script>

    <script>
        var ctx = document.getElementById('bookingChart').getContext('2d');
        var bookingChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($departments); ?>,
                datasets: [{
                    label: 'Number of Bookings',
                    data: <?php echo json_encode($bookings); ?>,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)',
                        'rgba(199, 199, 199, 0.6)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + ' bookings';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>