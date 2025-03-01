<?php
session_start();
require 'vendor/autoload.php'; // Ensure you have installed the necessary libraries using Composer

use Dompdf\Dompdf;
use Dompdf\Options;

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
    // Fetch all appointments for the current month
    $current_month = date('m');
    $current_year = date('Y');
    $sql = "SELECT bookings.id, bookings.name, bookings.id_number, bookings.booking_date, bookings.booking_time_from, bookings.booking_time_to, bookings.reason, bookings.group_members, bookings.representative_name, departments.name as department_name, rooms.name as room_name 
            FROM bookings 
            JOIN departments ON bookings.department_id = departments.id 
            JOIN rooms ON bookings.room_id = rooms.id 
            WHERE MONTH(booking_date) = ? AND YEAR(booking_date) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $current_month, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $html = '<html><body>';
        $html .= '<img src="assets/bcplogo.png" style="position: absolute; top: 10px; right: 10px;" />';
        $html .= '<h1 style="text-align: center;">Schedule List</h1>';

        // Prepare the data for the PDF
        while ($row = $result->fetch_assoc()) {
            $html .= '<p>';
            $html .= '<strong>Representative:</strong> ' . htmlspecialchars($row['representative_name']) . '<br>';
            $html .= '<strong>Research Adviser\'s Name:</strong> ' . htmlspecialchars($row['name']) . '<br>';
            $html .= '<strong>Group Number:</strong> ' . htmlspecialchars($row['id_number']) . '<br>';
            $html .= '<strong>Department:</strong> ' . htmlspecialchars($row['department_name']) . '<br>';
            $html .= '<strong>Room:</strong> ' . htmlspecialchars($row['room_name']) . '<br>';
            $html .= '<strong>Date:</strong> ' . htmlspecialchars($row['booking_date']) . '<br>';
            $html .= '<strong>Time:</strong> ' . htmlspecialchars($row['booking_time_from']) . ' - ' . htmlspecialchars($row['booking_time_to']) . '<br>';
            $html .= '<strong>Agenda:</strong> ' . htmlspecialchars($row['reason']) . '<br>';
            $html .= '<strong>Remarks:</strong> ' . htmlspecialchars($row['group_members']) . '<br>';
            $html .= '</p><hr>';
        }

        $html .= '</body></html>';

        // Initialize Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output the generated PDF
        $dompdf->stream("appointments.pdf", ["Attachment" => true]);
        exit();
    } else {
        echo "No appointments found!";
    }
}

$conn->close();
?>
