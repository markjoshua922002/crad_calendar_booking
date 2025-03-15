<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

if (isset($_POST['appointment_id'])) {
    // Debug log all POST data
    error_log("POST data received:");
    error_log(print_r($_POST, true));

    $appointment_id = $_POST['appointment_id'];
    $name = $_POST['edit_name'];
    $id_number = $_POST['edit_id_number'];
    $group_members = $_POST['edit_group_members'];
    $representative_name = $_POST['edit_representative_name'];
    $set_name = $_POST['edit_set'];
    $department = $_POST['edit_department'];
    $room = $_POST['edit_room'];
    
    // Get set_id from name
    $check_set = $conn->prepare("SELECT id FROM sets WHERE name = ?");
    if (!$check_set) {
        die('Prepare failed: ' . $conn->error);
    }
    $check_set->bind_param("s", $set_name);
    $check_set->execute();
    $set_result = $check_set->get_result();
    if ($set_result->num_rows === 0) {
        $check_set->close();
        error_log("Invalid set name provided: " . $set_name);
        $_SESSION['error'] = "Invalid set selected.";
        header('Location: ../index.php');
        exit();
    }
    $set_row = $set_result->fetch_assoc();
    $set = $set_row['id'];
    $check_set->close();

    // Validate date
    $date = $_POST['edit_date'];
    if (!$date || !strtotime($date)) {
        error_log("Invalid date provided: " . $date);
        $_SESSION['error'] = "Invalid date format provided.";
        header('Location: ../index.php');
        exit();
    }
    
    // Format date for MySQL
    $date = date('Y-m-d', strtotime($date));
    
    // Combine time fields with validation
    $time_from = $_POST['edit_time_from_hour'] . ':' . $_POST['edit_time_from_minute'] . ' ' . $_POST['edit_time_from_ampm'];
    $time_to = $_POST['edit_time_to_hour'] . ':' . $_POST['edit_time_to_minute'] . ' ' . $_POST['edit_time_to_ampm'];
    
    if (!strtotime($time_from) || !strtotime($time_to)) {
        error_log("Invalid time format provided - From: $time_from, To: $time_to");
        $_SESSION['error'] = "Invalid time format provided.";
        header('Location: ../index.php');
        exit();
    }
    
    $time_from = date('H:i:s', strtotime($time_from));
    $time_to = date('H:i:s', strtotime($time_to));
    
    $reason = $_POST['edit_reason'];

    // Check if the booking date is in the past
    $current_date = date('Y-m-d');
    if ($date < $current_date) {
        $_SESSION['error'] = "You cannot book a date that has already passed.";
        header('Location: ../index.php');
        exit();
    }

    // Update the booking
    $stmt = $conn->prepare("UPDATE bookings SET 
        name = ?, 
        id_number = ?, 
        group_members = ?, 
        representative_name = ?, 
        set_id = ?, 
        department_id = ?, 
        room_id = ?, 
        booking_date = ?, 
        booking_time_from = ?, 
        booking_time_to = ?, 
        reason = ? 
        WHERE id = ?");

    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $_SESSION['error'] = "Database error occurred.";
        header('Location: ../index.php');
        exit();
    }

    $stmt->bind_param("ssssiisssssi", 
        $name, 
        $id_number, 
        $group_members, 
        $representative_name, 
        $set, 
        $department, 
        $room, 
        $date, 
        $time_from, 
        $time_to, 
        $reason, 
        $appointment_id
    );

    if (!$stmt->execute()) {
        error_log("Update failed: " . $stmt->error);
        $_SESSION['error'] = "Failed to update appointment: " . $stmt->error;
        $stmt->close();
        header('Location: ../index.php');
        exit();
    }

    $stmt->close();
    $_SESSION['success'] = "Appointment updated successfully!";
    header('Location: ../index.php');
    exit();
} else {
    // Close connection before redirecting
    $conn->close();
    header('Location: ../index.php');
    exit();
}
?>
