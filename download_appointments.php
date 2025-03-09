<?php
session_start();
$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_appointments'])) {
    // Get current month and year
    $current_month = date('m');
    $current_year = date('Y');
    
    // Fetch appointments for current month with additional details
    $sql = "SELECT 
                b.id,
                b.name,
                b.id_number,
                b.booking_date,
                b.booking_time_from,
                b.booking_time_to,
                b.reason,
                b.representative_name,
                d.name as department_name,
                r.name as room_name,
                s.name as set_name
            FROM bookings b
            LEFT JOIN departments d ON b.department_id = d.id
            LEFT JOIN rooms r ON b.room_id = r.id
            LEFT JOIN sets s ON b.set_id = s.id
            WHERE MONTH(b.booking_date) = ? AND YEAR(b.booking_date) = ?
            ORDER BY b.booking_date, b.booking_time_from";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $current_month, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Set the filename with current month and year
        $filename = "appointments_" . date('F_Y') . ".csv";
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Create output handle
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for proper Excel display
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write CSV header
        fputcsv($output, array(
            'ID',
            'Research Adviser',
            'Representative',
            'Group ID',
            'Set',
            'Department',
            'Room',
            'Date',
            'Time From',
            'Time To',
            'Agenda',
        ));

        // Write data rows
        while ($row = $result->fetch_assoc()) {
            // Format the date and time
            $date = date('F d, Y', strtotime($row['booking_date']));
            $time_from = date('h:i A', strtotime($row['booking_time_from']));
            $time_to = date('h:i A', strtotime($row['booking_time_to']));
            
            fputcsv($output, array(
                $row['id'],
                $row['name'],
                $row['representative_name'],
                $row['id_number'],
                $row['set_name'],
                $row['department_name'],
                $row['room_name'],
                $date,
                $time_from,
                $time_to,
                $row['reason']
            ));
        }
        
        fclose($output);
        exit();
    } else {
        $_SESSION['error'] = "No appointments found for " . date('F Y') . "!";
        header('Location: index.php');
        exit();
    }
}

$conn->close();
?>
