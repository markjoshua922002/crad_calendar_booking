<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$conn = new mysqli('localhost', 'crad_crad', 'crad', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    if (!$stmt) {
        echo 'Prepare failed: ' . $conn->error;
        exit();
    }
    $stmt->bind_param("i", $appointmentId);
    if ($stmt->execute()) {
        echo "Appointment deleted successfully.";
    } else {
        echo "Error deleting appointment: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>
