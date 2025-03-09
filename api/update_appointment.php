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
        echo '<script>alert("Invalid set selected."); window.location.href = "../index.php";</script>';
        exit();
    }
    $set_row = $set_result->fetch_assoc();
    $set = $set_row['id'];
    $check_set->close();

    // Debug log the set information
    error_log("Set information:");
    error_log("Set name: " . $set_name);
    error_log("Set ID: " . $set);

    // Also validate department_id and room_id
    $check_dept = $conn->prepare("SELECT id FROM departments WHERE id = ?");
    if (!$check_dept) {
        die('Prepare failed: ' . $conn->error);
    }
    $check_dept->bind_param("i", $department);
    $check_dept->execute();
    $dept_result = $check_dept->get_result();
    if ($dept_result->num_rows === 0) {
        $check_dept->close();
        error_log("Invalid department_id provided: " . $department);
        echo '<script>alert("Invalid department selected."); window.location.href = "../index.php";</script>';
        exit();
    }
    $check_dept->close();

    $check_room = $conn->prepare("SELECT id FROM rooms WHERE id = ?");
    if (!$check_room) {
        die('Prepare failed: ' . $conn->error);
    }
    $check_room->bind_param("i", $room);
    $check_room->execute();
    $room_result = $check_room->get_result();
    if ($room_result->num_rows === 0) {
        $check_room->close();
        error_log("Invalid room_id provided: " . $room);
        echo '<script>alert("Invalid room selected."); window.location.href = "../index.php";</script>';
        exit();
    }
    $check_room->close();
    
    // Debug log the foreign key values
    error_log("Foreign key values:");
    error_log("set_id: " . $set);
    error_log("department_id: " . $department);
    error_log("room_id: " . $room);

    // Validate and format the date
    $input_date = $_POST['edit_date'];
    error_log("Raw input date: " . $input_date);
    
    // Convert date to DateTime object for validation
    try {
        $dateObj = new DateTime($input_date);
        $date = $dateObj->format('Y-m-d');
        error_log("Formatted date using DateTime: " . $date);
        
        // Additional validation to ensure date is properly formatted
        $dateParts = explode('-', $date);
        if (count($dateParts) !== 3 || 
            !checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
            throw new Exception("Invalid date components");
        }
    } catch (Exception $e) {
        error_log("Date parsing error: " . $e->getMessage());
        error_log("Input date was: " . $input_date);
        echo '<script>alert("Invalid date format provided. Please use YYYY-MM-DD format."); window.location.href = "../index.php";</script>';
        exit();
    }
    
    // Additional date validation
    if (!$date || $date === '1970-01-01' || $date === '0000-00-00') {
        error_log("Invalid date after formatting: " . $date);
        echo '<script>alert("Invalid date format. Please use YYYY-MM-DD format."); window.location.href = "../index.php";</script>';
        exit();
    }
    
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
    
    // Debug log all variables before update
    error_log("Debug values before update:");
    error_log("appointment_id: " . $appointment_id);
    error_log("name: " . $name);
    error_log("date: " . $date);
    error_log("time_from: " . $time_from);
    error_log("time_to: " . $time_to);
    error_log("room: " . $room);
    error_log("set: " . $set);
    error_log("department: " . $department);
    
    // Update the booking
    $stmt = $conn->prepare("UPDATE bookings SET 
        `name` = ?, 
        `id_number` = ?, 
        `group_members` = ?, 
        `representative_name` = ?, 
        `set_id` = ?, 
        `department_id` = ?, 
        `room_id` = ?, 
        `booking_date` = STR_TO_DATE(?, '%Y-%m-%d'), 
        `booking_time_from` = ?, 
        `booking_time_to` = ?, 
        `reason` = ? 
        WHERE `id` = ?");
        
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die('Prepare failed: ' . $conn->error);
    }
    
    // Debug the final values before binding
    error_log("Final values before binding:");
    error_log("Date value: " . $date);
    error_log("Date type: " . gettype($date));
    error_log("Set ID: " . $set);
    error_log("Department ID: " . $department);
    error_log("Room ID: " . $room);
    
    try {
        // Ensure date is in correct format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new Exception("Invalid date format. Expected YYYY-MM-DD, got: " . $date);
        }

        // Log all values before binding
        error_log("Attempting to bind parameters with values:");
        error_log("name: " . $name);
        error_log("id_number: " . $id_number);
        error_log("group_members: " . $group_members);
        error_log("representative_name: " . $representative_name);
        error_log("set_id: " . $set);
        error_log("department_id: " . $department);
        error_log("room_id: " . $room);
        error_log("date: " . $date);
        error_log("time_from: " . $time_from);
        error_log("time_to: " . $time_to);
        error_log("reason: " . $reason);
        error_log("appointment_id: " . $appointment_id);

        $stmt->bind_param("ssssiiiisssi", 
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
            error_log("MySQL Error: " . $stmt->error);
            error_log("MySQL Error Code: " . $stmt->errno);
            throw new Exception("Database error: " . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            error_log("No rows were updated. Appointment ID might not exist: " . $appointment_id);
            throw new Exception("No changes were made to the booking.");
        }
        
        $stmt->close();
        header('Location: ../index.php');
        exit();
    } catch (Exception $e) {
        error_log("Error in update process: " . $e->getMessage());
        echo '<script>
            alert("Error updating booking: ' . addslashes($e->getMessage()) . '");
            window.location.href = "../index.php";
        </script>';
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}

$conn->close();
?>
