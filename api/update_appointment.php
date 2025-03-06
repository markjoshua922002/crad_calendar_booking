<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Debug: Log the values being processed
    error_log("Updating Booking ID=$appointment_id: Name=$name, ID Number=$id_number, Group Members=$group_members, Representative Name=$representative_name, Set=$set, Department=$department, Room=$room, Date=$date, Time From=$time_from, Time To=$time_to, Reason=$reason");

    // Check if the booking date is in the past
    $current_date = date('Y-m-d');
    if ($date < $current_date) {
        $warning = "You cannot book a date that has already passed.";
    } else {
        // Check for double booking
        $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_date = ? AND room_id = ? AND id != ? AND ((booking_time_from < ? AND booking_time_to > ?) OR (booking_time_from < ? AND booking_time_to > ?))");
        if (!$stmt) {
            die('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("siissss", $date, $room, $appointment_id, $time_to, $time_from, $time_from, $time_to);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $warning = "Double booking detected for the specified time, date, and room.";
        } else {
            $stmt = $conn->prepare("UPDATE bookings SET name = ?, id_number = ?, group_members = ?, representative_name = ?, `set` = ?, department_id = ?, room_id = ?, booking_date = ?, booking_time_from = ?, booking_time_to = ?, reason = ? WHERE id = ?");
            if (!$stmt) {
                die('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param("ssssissssssi", $name, $id_number, $group_members, $representative_name, $set, $department, $room, $date, $time_from, $time_to, $reason, $appointment_id);
            if ($stmt->execute()) {
                // Debug: Log successful update
                error_log("Booking ID=$appointment_id successfully updated.");
                // Redirect to avoid form resubmission
                header('Location: ../index.php');
                exit();
            } else {
                // Debug: Log error
                error_log("Error updating booking ID=$appointment_id: " . $stmt->error);
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt->close();
    }
}

$conn->close();
?>
