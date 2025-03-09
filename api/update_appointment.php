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
    $appointment_id = $_POST['appointment_id'];
    $name = $_POST['edit_name'];
    $id_number = $_POST['edit_id_number'];
    $group_members = $_POST['edit_group_members'];
    $representative_name = $_POST['edit_representative_name'];
    $set = $_POST['edit_set'];
    $department = $_POST['edit_department'];
    $room = $_POST['edit_room'];
    $date = date('Y-m-d', strtotime($_POST['edit_date']));
    
    // Combine time fields
    $time_from = date('H:i:s', strtotime($_POST['edit_time_from_hour'] . ':' . $_POST['edit_time_from_minute'] . ' ' . $_POST['edit_time_from_ampm']));
    $time_to = date('H:i:s', strtotime($_POST['edit_time_to_hour'] . ':' . $_POST['edit_time_to_minute'] . ' ' . $_POST['edit_time_to_ampm']));
    
    $reason = $_POST['edit_reason'];

    // Check if the booking date is in the past
    $current_date = date('Y-m-d');
    if ($date < $current_date) {
        echo '<script>alert("You cannot book a date that has already passed."); window.location.href = "../index.php";</script>';
        exit();
    }
    
    // Check for double booking, excluding this appointment
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_date = ? AND room_id = ? 
                          AND id != ? 
                          AND ((booking_time_from < ? AND booking_time_to > ?) 
                           OR (booking_time_from < ? AND booking_time_to > ?))");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("sisssss", $date, $room, $appointment_id, $time_to, $time_from, $time_from, $time_to);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Double booking detected
        $stmt->close();
        echo '<script>alert("Double booking detected for the specified time, date, and room."); window.location.href = "../index.php";</script>';
        exit();
    }
    $stmt->close();
    
    // Update the booking
    $stmt = $conn->prepare("UPDATE bookings SET name = ?, id_number = ?, group_members = ?, 
                          representative_name = ?, `set` = ?, department_id = ?, 
                          room_id = ?, booking_date = ?, booking_time_from = ?, 
                          booking_time_to = ?, reason = ? WHERE id = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("ssssissssssi", $name, $id_number, $group_members, $representative_name, $set, 
                    $department, $room, $date, $time_from, $time_to, $reason, $appointment_id);
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: ../index.php');
        exit();
    } else {
        echo "Error updating record: " . $stmt->error;
        $stmt->close();
    }
} else {
    header('Location: ../index.php');
    exit();
}

$conn->close();
?>
