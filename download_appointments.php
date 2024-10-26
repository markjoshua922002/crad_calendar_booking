<?php
session_start();
$conn = new mysqli('localhost', 'crad_crad', 'crad', 'crad_calendar_booking');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_appointments'])) {
    // Fetch all appointments
    $sql = "SELECT id, name, id_number, booking_date, booking_time, reason FROM bookings";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $file_content = "ID\tName\tID Number\tDate\tTime\tReason\n";

        // Prepare the data for the file
        while ($row = $result->fetch_assoc()) {
            $file_content .= "{$row['id']}\t{$row['name']}\t{$row['id_number']}\t{$row['booking_date']}\t{$row['booking_time']}\t{$row['reason']}\n";
        }

        // Set headers to trigger download
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="appointments.txt"');
        echo $file_content;
        exit();
    } else {
        echo "No appointments found!";
    }
}

$conn->close();
?>
