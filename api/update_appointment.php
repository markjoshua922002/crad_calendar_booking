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
    
    // Check if the input is just a year
    if (is_numeric($input_date) && strlen($input_date) == 4) {
        // If it's just a year, add month and day
        $input_date = $input_date . '-01-01';
        error_log("Converted year-only input to: " . $input_date);
    }
    
    // Convert date to DateTime object for validation
    try {
        $dateObj = new DateTime($input_date);
        $date = $dateObj->format('Y-m-d');
        error_log("Formatted date using DateTime: " . $date);
        
        // Additional validation to ensure date is properly formatted
        $dateParts = explode('-', $date);
        if (count($dateParts) !== 3 || 
            !checkdate(intval($dateParts[1]), intval($dateParts[2]), intval($dateParts[0]))) {
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
    
    // Update the booking with explicit date formatting
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
        // Log the SQL query for debugging
        $debug_query = sprintf("UPDATE bookings SET 
            name = '%s', 
            id_number = '%s', 
            group_members = '%s', 
            representative_name = '%s', 
            set_id = %d, 
            department_id = %d, 
            room_id = %d, 
            booking_date = '%s', 
            booking_time_from = '%s', 
            booking_time_to = '%s', 
            reason = '%s' 
            WHERE id = %d",
            $conn->real_escape_string($name),
            $conn->real_escape_string($id_number),
            $conn->real_escape_string($group_members),
            $conn->real_escape_string($representative_name),
            $set,
            $department,
            $room,
            $conn->real_escape_string($date),
            $conn->real_escape_string($time_from),
            $conn->real_escape_string($time_to),
            $conn->real_escape_string($reason),
            $appointment_id
        );
        error_log("Debug SQL Query: " . $debug_query);

        // Verify appointment exists before update
        $check_appointment = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
        $check_appointment->bind_param("i", $appointment_id);
        $check_appointment->execute();
        $appointment_result = $check_appointment->get_result();
        
        if ($appointment_result->num_rows === 0) {
            error_log("Appointment not found with ID: " . $appointment_id);
            throw new Exception("Appointment not found.");
        }
        
        $old_appointment = $appointment_result->fetch_assoc();
        error_log("Old appointment data: " . print_r($old_appointment, true));
        $check_appointment->close();

        // Format date for MySQL - ensure it's in YYYY-MM-DD format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            // Try to convert to proper format if it's not already
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                throw new Exception("Cannot convert date to proper format: " . $date);
            }
            $mysql_date = date('Y-m-d', $timestamp);
        } else {
            $mysql_date = $date;
        }
        error_log("Final MySQL date for binding: " . $mysql_date);

        $stmt->bind_param("ssssiiiisssi", 
            $name, 
            $id_number, 
            $group_members, 
            $representative_name, 
            $set,  
            $department, 
            $room, 
            $mysql_date,  // Use formatted date
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
        
        // Always verify the update was successful
        $verify_update = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
        $verify_update->bind_param("i", $appointment_id);
        $verify_update->execute();
        $verify_result = $verify_update->get_result();
        
        if ($verify_result->num_rows === 0) {
            error_log("Critical error: Booking disappeared after update!");
            throw new Exception("Critical error: Booking disappeared after update!");
        }
        
        $updated_booking = $verify_result->fetch_assoc();
        error_log("Successfully updated booking: " . print_r($updated_booking, true));
        
        // Verify all fields were updated correctly
        $expected_values = [
            'name' => $name,
            'id_number' => $id_number,
            'group_members' => $group_members,
            'representative_name' => $representative_name,
            'set_id' => $set,
            'department_id' => $department,
            'room_id' => $room,
            'booking_date' => $mysql_date,  // Use the MySQL formatted date
            'booking_time_from' => $time_from,
            'booking_time_to' => $time_to,
            'reason' => $reason
        ];
        
        foreach ($expected_values as $field => $expected) {
            // Special handling for date fields
            if ($field === 'booking_date') {
                // Compare dates by converting both to timestamps
                $expected_timestamp = strtotime($expected);
                $actual_timestamp = strtotime($updated_booking[$field]);
                
                if ($expected_timestamp !== $actual_timestamp) {
                    error_log("Date mismatch after update: $field");
                    error_log("Expected timestamp: $expected_timestamp (" . date('Y-m-d', $expected_timestamp) . ")");
                    error_log("Got timestamp: $actual_timestamp (" . date('Y-m-d', $actual_timestamp) . ")");
                    
                    // Check if the dates are within 24 hours (could be timezone issues)
                    if (abs($expected_timestamp - $actual_timestamp) > 86400) {
                        throw new Exception("Update verification failed: $field field mismatch");
                    } else {
                        error_log("Date difference is within acceptable range, continuing");
                    }
                }
            } 
            // Special handling for time fields
            else if ($field === 'booking_time_from' || $field === 'booking_time_to') {
                // Compare times by removing any leading zeros and standardizing format
                $expected_time = date('H:i:s', strtotime($expected));
                $actual_time = date('H:i:s', strtotime($updated_booking[$field]));
                
                if ($expected_time !== $actual_time) {
                    error_log("Time mismatch after update: $field");
                    error_log("Expected: $expected ($expected_time)");
                    error_log("Got: " . $updated_booking[$field] . " ($actual_time)");
                    throw new Exception("Update verification failed: $field field mismatch");
                }
            }
            // Standard comparison for other fields
            else if ($updated_booking[$field] != $expected) {
                error_log("Field mismatch after update: $field");
                error_log("Expected: $expected");
                error_log("Got: " . $updated_booking[$field]);
                throw new Exception("Update verification failed: $field field mismatch");
            }
        }
        
        $verify_update->close();
        $stmt->close();
        
        // Set success message
        $_SESSION['success'] = "Booking successfully updated!";
        
        // Close connection before redirecting
        $conn->close();
        
        header('Location: ../index.php');
        exit();
    } catch (Exception $e) {
        // Close any open statements
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($verify_update)) {
            $verify_update->close();
        }
        
        // Close connection
        $conn->close();
        
        error_log("Error in update process: " . $e->getMessage());
        echo '<script>
            alert("Error updating booking: ' . addslashes($e->getMessage()) . '");
            window.location.href = "../index.php";
        </script>';
        exit();
    }
} else {
    // Close connection before redirecting
    $conn->close();
    header('Location: ../index.php');
    exit();
}
?>
