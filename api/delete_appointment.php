<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Get the appointment ID from the URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Log the delete request
    error_log("Attempting to delete appointment ID: $id");
    
    // Delete the appointment
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        error_log("Successfully deleted appointment ID: $id");
        header('Location: ../index.php?delete=success');
    } else {
        error_log("Error deleting appointment: " . $stmt->error);
        header('Location: ../index.php?delete=error&message=' . urlencode($stmt->error));
    }
    $stmt->close();
} else {
    error_log("No appointment ID provided for deletion");
    header('Location: ../index.php?delete=error&message=No+appointment+ID+provided');
}

$conn->close();
?>
