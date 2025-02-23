<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection and PHP logic remains the same
$conn = new mysqli('localhost', 'crad_crad', 'crad', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle form submissions
if (isset($_POST['add_booking'])) {
    $name = $_POST['name'];
    $id_number = $_POST['id_number'];
    $group_members = $_POST['group_members'];
    $representative_name = $_POST['representative_name'];
    $set = $_POST['set'];
    $department = $_POST['department'];
    $room = $_POST['room'];
    $date = date('Y-m-d', strtotime($_POST['date']));
    $time_from = date('H:i:s', strtotime($_POST['time_from']));
    $time_to = date('H:i:s', strtotime($_POST['time_to']));
    $reason = $_POST['reason'];

    // Check for double booking
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_date = ? AND room_id = ? AND ((booking_time_from < ? AND booking_time_to > ?) OR (booking_time_from < ? AND booking_time_to > ?))");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("sissss", $date, $room, $time_to, $time_from, $time_from, $time_to);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $warning = "Double booking detected for the specified time, date, and room.";
    } else {
        $stmt = $conn->prepare("INSERT INTO bookings (name, id_number, group_members, representative_name, `set`, department_id, room_id, booking_date, booking_time_from, booking_time_to, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("ssssissssss", $name, $id_number, $group_members, $representative_name, $set, $department, $room, $date, $time_from, $time_to, $reason);
        if ($stmt->execute()) {
            echo "Booking successfully added.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $stmt->close();
}

// Handle department addition
if (isset($_POST['add_department'])) {
    $department_name = $_POST['department_name'];
    $color = $_POST['color']; 
    $stmt = $conn->prepare("INSERT INTO departments (name, color) VALUES (?, ?)");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("ss", $department_name, $color);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php');
    exit();
}

// Handle room addition
if (isset($_POST['add_room'])) {
    $room_name = $_POST['room_name'];
    $stmt = $conn->prepare("INSERT INTO rooms (name) VALUES (?)");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("s", $room_name);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php');
    exit();
}

// Search for appointments
$searched_appointment = null;
if (isset($_POST['search_booking'])) {
    $search_name = $_POST['search_name'];
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE representative_name LIKE ?");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $search_param = "%$search_name%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $searched_appointment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch departments and rooms
$departments = $conn->query("SELECT * FROM departments");
$rooms = $conn->query("SELECT * FROM rooms");

// Fetch current month and year
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$firstDayOfMonth = date('w', strtotime("$year-$month-01"));
$totalDaysInMonth = date('t', strtotime("$year-$month-01"));

// Fetch bookings for the current month
$bookings = $conn->query("SELECT bookings.*, departments.name as department_name, departments.color, rooms.name as room_name 
    FROM bookings 
    JOIN departments ON bookings.department_id = departments.id 
    JOIN rooms ON bookings.room_id = rooms.id 
    WHERE MONTH(booking_date) = '$month' AND YEAR(booking_date) = '$year'");

$appointments = [];
while ($row = $bookings->fetch_assoc()) {
    $date = date('j', strtotime($row['booking_date']));
    $appointments[$date][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Scheduling System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/calendar.css">
    <link rel="stylesheet" href="css/day.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.css">
    <link rel="icon" href="assets/bcplogo.png" type="image/png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.js"></script>
    <script defer src="js/script.js"></script>
    <script>
        $(document).ready(function(){
            $('#time_from, #time_to').timepicker({
                timeFormat: 'h:i A',
                interval: 30,
                minTime: '6:00am',
                maxTime: '11:00pm',
                dynamic: false,
                dropdown: true,
                scrollbar: true
            });

            // Show appointments for a specific day
            $('.day').on('click', function() {
                var day = $(this).find('.day-number').text();
                var appointments = <?= json_encode($appointments) ?>;
                var dayAppointments = appointments[day] || [];
                var appointmentList = $('#appointmentList');
                appointmentList.empty();
                dayAppointments.forEach(function(appointment) {
                    var appointmentItem = $('<div class="appointment-item"></div>');
                    appointmentItem.css('background-color', appointment.color);
                    appointmentItem.html('<div class="appointment-container"><strong>' + appointment.name + '</strong><br>' + appointment.department_name + '<br>' + appointment.room_name + '<br>' + appointment.booking_time_from + ' to ' + appointment.booking_time_to + '</div>');
                    appointmentItem.data('appointment', appointment);
                    appointmentItem.append('<div class="appointment-buttons"><button class="view-button">View</button><button class="edit-button">Edit</button></div>');
                    appointmentList.append(appointmentItem);
                });
                $('#appointmentModal').show();
            });

            // Show appointment details in view modal
            $(document).on('click', '.view-button', function() {
                var appointment = $(this).closest('.appointment-item').data('appointment');
                var viewModalContent = $('#viewModal .modal-content');
                viewModalContent.html('<span class="close" id="closeViewModal">&times;</span>' +
                                      '<h2>Appointment Details</h2>' +
                                      '<strong>Name:</strong> ' + appointment.name + '<br>' +
                                      '<strong>Department:</strong> ' + appointment.department_name + '<br>' +
                                      '<strong>Room:</strong> ' + appointment.room_name + '<br>' +
                                      '<strong>Time:</strong> ' + appointment.booking_time_from + ' to ' + appointment.booking_time_to + '<br>' +
                                      '<strong>Date:</strong> ' + appointment.booking_date + '<br>' +
                                      '<strong>Reason:</strong> ' + appointment.reason + '<br>' +
                                      '<strong>Group Members:</strong> ' + appointment.group_members + '<br>' +
                                      '<strong>Representative Name:</strong> ' + appointment.representative_name);
                $('#viewModal').show();
            });

            // Show appointment details in edit modal
            $(document).on('click', '.edit-button', function() {
                var appointment = $(this).closest('.appointment-item').data('appointment');
                $('#appointment_id').val(appointment.id);
                $('#edit_name').val(appointment.name);
                $('#edit_id_number').val(appointment.id_number);
                $('#edit_set').val(appointment.set);
                $('#edit_date').val(appointment.booking_date);
                $('#edit_time_from').val(appointment.booking_time_from);
                $('#edit_time_to').val(appointment.booking_time_to);
                $('#edit_reason').val(appointment.reason);
                $('#edit_department').val(appointment.department_id);
                $('#edit_room').val(appointment.room_id);
                $('#edit_group_members').val(appointment.group_members);
                $('#edit_representative_name').val(appointment.representative_name);
                $('#appointmentModal').hide();
                $('#editModal').show();
            });

            // Handle delete button click event
            $('#delete_button').on('click', function() {
                var appointmentId = $('#appointment_id').val();
                if (confirm('Are you sure you want to delete this appointment?')) {
                    $.ajax({
                        url: 'api/delete_appointment.php',
                        type: 'POST',
                        data: { id: appointmentId },
                        success: function(response) {
                            alert(response);
                            location.reload();
                        },
                        error: function(xhr, status, error) {
                            alert('Error deleting appointment: ' + xhr.responseText);
                        }
                    });
                }
            });

            // Open and close modals
            $('#openBookingModal').on('click', function() {
                $('#bookingModal').show();
            });

            $('#closeBookingModal').on('click', function() {
                $('#bookingModal').hide();
            });

            $('#closeAddDepartmentModal').on('click', function() {
                $('#addDepartmentModal').hide();
            });

            $('#closeAddRoomModal').on('click', function() {
                $('#addRoomModal').hide();
            });

            $(document).on('click', '.close', function() {
                $(this).closest('.modal').hide();
            });

            // Show/hide buttons on hover
            $(document).on('mouseenter', '.appointment-item', function() {
                $(this).find('.appointment-buttons').show();
            });

            $(document).on('mouseleave', '.appointment-item', function() {
                $(this).find('.appointment-buttons').hide();
            });
        });
    </script>
</head>
<body>
<button class="menu-button" id="menuButton">&#9776;</button> <!-- Menu button -->

<div class="sidebar" id="sidebar">
    <a href="home.php">HOME</a>
    <a href="index.php">BOOKING</a>
    <a href="hr.php">HR</a>
    <a href="its.php">ITS</a>
    <a href="osas.php">OSAS</a>
    <a href="faculty.php">FACULTY</a>
    <div style="flex-grow: 1;"></div> <!-- Spacer to push logout button to the bottom -->
    <a href="logout.php" class="logout-button">Logout</a>
</div>

<div class="container">
    <div class="form-actions" style="text-align: right; margin-bottom: 10px;">
        <div class="search-container" style="display: inline-block;">
            <form method="POST" style="display: flex; gap: 5px;">
                <input type="text" name="search_name" placeholder="Search by Name" required style="width: 150px; padding: 5px;">
                <button type="submit" name="search_booking" style="padding: 5px 10px;">Search</button>
                <button type="button" id="openBookingModal" style="padding: 5px 10px;">Book</button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <div class="calendar-container">
            <div class="navigation" style="margin-bottom: 10px;">
                <a href="index.php?month=<?= ($month == 1) ? 12 : $month-1 ?>&year=<?= ($month == 1) ? $year-1 : $year ?>" class="nav-button">Previous</a>
                <span class="month-year"><?= date('F Y', strtotime("$year-$month-01")) ?></span>
                <a href="index.php?month=<?= ($month == 12) ? 1 : $month+1 ?>&year=<?= ($month == 12) ? $year+1 : $year ?>" class="nav-button">Next</a>
            </div>

            <div class="weekday-header">
                <div>SUNDAY</div>
                <div>MONDAY</div>
                <div>TUESDAY</div>
                <div>WEDNESDAY</div>
                <div>THURSDAY</div>
                <div>FRIDAY</div>
                <div>SATURDAY</div>
            </div>

            <div class="calendar">
                <?php for ($i = 0; $i < $firstDayOfMonth; $i++): ?>
                    <div class="day"></div>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $totalDaysInMonth; $day++): ?>
                    <div class="day">
                        <div class="day-number"><?= $day ?></div>
                        <div class="appointment-count"><?= isset($appointments[$day]) ? count($appointments[$day]) : '' ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="reminder-container">
            <h2>Upcoming Appointments</h2>
            <ul id="reminderList">
                <?php
                $currentDateTime = new DateTime();
                foreach ($appointments as $day => $dayAppointments) {
                    foreach ($dayAppointments as $appointment) {
                        $appointmentDateTime = new DateTime($appointment['booking_date'] . ' ' . $appointment['booking_time_from']);
                        $interval = $currentDateTime->diff($appointmentDateTime);
                        if ($interval->h <= 3 && $interval->invert == 0) {
                            echo '<li>' . $appointment['name'] . ' - ' . $appointment['booking_time_from'] . '</li>';
                        }
                    }
                }
                ?>
            </ul>
        </div>
    </div>
</div>

    <!-- Modals -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAppointmentModal">&times;</span>
            <h2>Appointments</h2>
            <div id="appointmentList"></div>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeViewModal">&times;</span>
            <h2>Appointment Details</h2>
            <div id="viewContainer"></div>
        </div>
    </div>

    <div id="editModal" class="modal" data-show-modal="<?= isset($searched_appointment) ? 'true' : 'false' ?>">
        <div class="modal-content">
            <span class="close" id="closeEditModal">&times;</span>
            <h2>Edit Appointment</h2>
            <form id="editForm">
                <input type="hidden" name="appointment_id" id="appointment_id" value="<?= $searched_appointment['id'] ?? '' ?>">
                <input type="text" name="edit_name" id="edit_name" value="<?= $searched_appointment['name'] ?? '' ?>" required>
                <select name="edit_id_number" id="edit_id_number" required>
                    <option value="">Group Number</option>
                    <?php for ($i = 1; $i <= 200; $i++): ?>
                        <option value="<?= $i ?>" <?= (isset($searched_appointment) && $searched_appointment['id_number'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <select name="edit_set" id="edit_set" required>
                    <option value="">Set</option>
                    <?php foreach (range('A', 'F') as $set): ?>
                        <option value="<?= $set ?>" <?= (isset($searched_appointment) && $searched_appointment['set'] == $set) ? 'selected' : '' ?>><?= $set ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="edit_date" id="edit_date" value="<?= $searched_appointment['booking_date'] ?? '' ?>" required>
                <input type="text" name="edit_time_from" id="edit_time_from" value="<?= isset($searched_appointment) ? date('g:i A', strtotime($searched_appointment['booking_time_from'])) : '' ?>" required>
                <input type="text" name="edit_time_to" id="edit_time_to" value="<?= isset($searched_appointment) ? date('g:i A', strtotime($searched_appointment['booking_time_to'])) : '' ?>" required>
                <textarea name="edit_reason" id="edit_reason" required><?= $searched_appointment['reason'] ?? '' ?></textarea>
                <select name="edit_department" id="edit_department" required>
                    <option value="">Department</option>
                    <?php
                    $departments->data_seek(0);
                    while ($department = $departments->fetch_assoc()): ?>
                        <option value="<?= $department['id'] ?>" <?= (isset($searched_appointment) && $searched_appointment['department_id'] == $department['id']) ? 'selected' : '' ?>><?= $department['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <select name="edit_room" id="edit_room" required>
                    <option value="">Room Number</option>
                    <?php
                    $rooms->data_seek(0);
                    while ($room = $rooms->fetch_assoc()): ?>
                        <option value="<?= $room['id'] ?>" <?= (isset($searched_appointment) && $searched_appointment['room_id'] == $room['id']) ? 'selected' : '' ?>><?= $room['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="edit_representative_name" id="edit_representative_name" value="<?= $searched_appointment['representative_name'] ?? '' ?>" required>
                <textarea name="edit_group_members" id="edit_group_members" rows="4" required><?= $searched_appointment['group_members'] ?? '' ?></textarea>
                <button type="submit" id="save_button">Save Changes</button>
                <button type="button" id="delete_button">Delete Appointment</button>
            </form>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeBookingModal">&times;</span>
            <h2>Book Schedule</h2>
            <form method="POST" class="form">
                <div class="form-grid">
                    <select name="department" required>
                        <option value="">Department</option>
                        <?php
                        $departments->data_seek(0);
                        while ($department = $departments->fetch_assoc()): ?>
                            <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <input type="text" name="name" placeholder="Research Adviser's Name" required>
                    <select name="id_number" required>
                        <option value="">Group Number</option>
                        <?php for ($i = 1; $i <= 200; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="set" required>
                        <option value="">Set</option>
                        <?php foreach (range('A', 'F') as $set): ?>
                            <option value="<?= $set ?>"><?= $set ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="time_from" id="time_from" placeholder="Start Time" required>
                    <input type="text" name="time_to" id="time_to" placeholder="End Time" required>
                    <input type="date" name="date" required>
                    <textarea name="reason" placeholder="Agenda" required></textarea>
                    <select name="room" required>
                        <option value="">Room Number</option>
                        <?php
                        $rooms->data_seek(0);
                        while ($room = $rooms->fetch_assoc()): ?>
                            <option value="<?= $room['id'] ?>"><?= $room['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <input type="text" name="representative_name" placeholder="Representative Name" required>
                </div>
                <textarea name="group_members" placeholder="Group Members" rows="4" required></textarea>
                <div class="form-actions-right">
                    <button type="submit" name="add_booking" class="book-button">Book Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Department Modal -->
    <div id="addDepartmentModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAddDepartmentModal">&times;</span>
            <h2>Add Department</h2>
            <form method="POST" action="api/add_department.php">
                <input type="text" name="department_name" placeholder="Department Name" required>
                <input type="color" name="color" value="#ff0000" required>
                <button type="submit" name="add_department">Add Department</button>
            </form>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAddRoomModal">&times;</span>
            <h2>Add Room</h2>
            <form method="POST" action="api/add_room.php">
                <input type="text" name="room_name" placeholder="Room Name" required>
                <button type="submit" name="add_room">Add Room</button>
            </form>
        </div>
    </div>
</body>
</html>