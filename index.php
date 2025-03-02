<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection and PHP logic remains the same
$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
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
    
    // Combine time fields
    $time_from = date('H:i:s', strtotime($_POST['time_from_hour'] . ':' . $_POST['time_from_minute'] . ' ' . $_POST['time_from_ampm']));
    $time_to = date('H:i:s', strtotime($_POST['time_to_hour'] . ':' . $_POST['time_to_minute'] . ' ' . $_POST['time_to_ampm']));
    
    $reason = $_POST['reason'];

    // Debug: Log the values being processed
    error_log("Booking Details: Name=$name, ID Number=$id_number, Group Members=$group_members, Representative Name=$representative_name, Set=$set, Department=$department, Room=$room, Date=$date, Time From=$time_from, Time To=$time_to, Reason=$reason");

    // Check if the booking date is in the past
    $current_date = date('Y-m-d');
    if ($date < $current_date) {
        $warning = "You cannot book a date that has already passed.";
    } else {
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
                // Debug: Log successful insertion
                error_log("Booking successfully inserted: ID=" . $stmt->insert_id);
                // Redirect to avoid form resubmission
                header('Location: index.php');
                exit();
            } else {
                // Debug: Log error
                error_log("Error inserting booking: " . $stmt->error);
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt->close();
    }
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
    $stmt = $conn->prepare("SELECT bookings.*, departments.name as department_name, departments.color, rooms.name as room_name 
                          FROM bookings 
                          JOIN departments ON bookings.department_id = departments.id 
                          JOIN rooms ON bookings.room_id = rooms.id 
                          WHERE bookings.representative_name LIKE ? OR bookings.name LIKE ?");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $search_param = "%$search_name%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $searched_appointment = $result->fetch_assoc();
    }
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
    <link rel="stylesheet" href="mycss/style.css?v=13">
    <link rel="stylesheet" href="mycss/sidebar.css?v=3">
    <link rel="stylesheet" href="mycss/calendar.css?v=26">
    <link rel="stylesheet" href="mycss/day.css">
    <link rel="stylesheet" href="mycss/reminder.css?v=11">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.css">
    <link rel="icon" href="assets/bcplogo.png" type="image/png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.13.18/jquery.timepicker.min.js"></script>
</head>
<body>
<button class="menu-button" id="menuButton">&#9776;</button> <!-- Menu button -->

<div class="sidebar" id="sidebar">
    <a href="index.php">CRAD</a>
    <a href="form.php">LOGBOOK</a>
    <a href="accounts.php">USERS</a>
    <a href="analytics.php">ANALYTICS</a>
    <div style="flex-grow: 1;"></div> <!-- Spacer to push logout button to the bottom -->
    
    <a href="logout.php" class="logout-button">Logout</a>
</div>
<div class="container">
    <?php if (isset($warning)): ?>
        <div class="warning" style="color: red; text-align: center; margin-bottom: 10px;">
            <?= $warning ?>
        </div>
    <?php endif; ?>
    <div class="search-container-wrapper">
        <div class="form-actions" style="text-align: right; margin-bottom: 10px;">
            <div class="search-container" style="display: inline-block;">
                <form method="POST" style="display: flex; gap: 5px;">
                    <input type="text" name="search_name" placeholder="Search by Name" required style="width: 150px; padding: 5px;">
                    <button type="submit" name="search_booking" style="padding: 5px 10px;">Search</button>
                    <button type="button" id="openBookingModal" style="padding: 5px 10px;">Book</button>
                </form>
            </div>
        </div>
    </div>

    <?php if (isset($_POST['search_booking']) && !$searched_appointment): ?>
        <div class="warning" style="color: red; text-align: center; margin-bottom: 10px;">
            No appointments found for "<?= htmlspecialchars($search_name) ?>".
        </div>
    <?php endif; ?>

    <div class="calendar-container-wrapper">
        <div class="main-content">
            <div class="calendar-wrapper"> <!-- Add this wrapper -->
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
            </div> <!-- Close the wrapper -->
            <div class="reminder-container">
                <h2>Upcoming Appointments</h2>
                <ul id="reminderList">
                    <?php
                    $currentDateTime = new DateTime();
                    $sevenDaysLater = (clone $currentDateTime)->modify('+7 days');
                    $upcomingAppointments = [];

                    // Collect upcoming appointments within the next 7 days
                    foreach ($appointments as $day => $dayAppointments) {
                        foreach ($dayAppointments as $appointment) {
                            $appointmentDateTime = new DateTime($appointment['booking_date'] . ' ' . $appointment['booking_time_from']);
                            if ($appointmentDateTime >= $currentDateTime && $appointmentDateTime <= $sevenDaysLater) {
                                $upcomingAppointments[] = $appointment;
                            }
                        }
                    }

                    // Sort the upcoming appointments by date and time
                    usort($upcomingAppointments, function($a, $b) {
                        $dateTimeA = new DateTime($a['booking_date'] . ' ' . $a['booking_time_from']);
                        $dateTimeB = new DateTime($b['booking_date'] . ' ' . $b['booking_time_from']);
                        return $dateTimeA <=> $dateTimeB;
                    });

                    // Display the sorted upcoming appointments
                    foreach ($upcomingAppointments as $appointment) {
                        $timeFrom = date('g:i A', strtotime($appointment['booking_time_from']));
                        $timeTo = date('g:i A', strtotime($appointment['booking_time_to']));
                        echo '<li class="appointment-item" style="background-color: ' . $appointment['color'] . ';" data-appointment=\'' . json_encode($appointment) . '\'>';
                        echo '<div class="text-container">';
                        echo '<strong>' . $appointment['representative_name'] . '</strong><br>';
                        echo $appointment['department_name'] . '<br>';
                        echo $appointment['booking_date'] . '<br>';
                        echo $timeFrom . ' - ' . $timeTo;
                        echo '</div>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="appointmentModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeAppointmentModal">&times;</span>
        <h2>Appointments</h2>
        <div id="appointmentList">
            <ul id="appointmentList">
                <?php
                // Fetch all appointments
                $allAppointments = [];
                foreach ($appointments as $day => $dayAppointments) {
                    foreach ($dayAppointments as $appointment) {
                        $allAppointments[] = $appointment;
                    }
                }

                // Sort the appointments by date and time
                usort($allAppointments, function($a, $b) {
                    $dateTimeA = new DateTime($a['booking_date'] . ' ' . $a['booking_time_from']);
                    $dateTimeB = new DateTime($b['booking_date'] . ' ' . $b['booking_time_from']);
                    return $dateTimeA <=> $dateTimeB;
                });

                // Display the sorted appointments
                foreach ($allAppointments as $appointment) {
                    $timeFrom = date('g:i A', strtotime($appointment['booking_time_from']));
                    $timeTo = date('g:i A', strtotime($appointment['booking_time_to']));
                    echo '<li class="appointment-item" style="background-color: ' . $appointment['color'] . ';" data-appointment=\'' . json_encode($appointment) . '\'>';
                    echo '<div class="text-container">';
                    echo '<strong>' . $appointment['representative_name'] . '</strong><br>';
                    echo $appointment['department_name'] . '<br>';
                    echo $appointment['booking_date'] . '<br>';
                    echo $timeFrom . ' - ' . $timeTo;
                    echo '</div>';
                    echo '</li>';
                }
                ?>
            </ul>
        </div>
    </div>
</div>

<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeViewModal">&times;</span>
        <h2>Appointment Details</h2>
        <div id="viewContainer"></div>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeEditModal">&times;</span>
        <h2>Edit Appointment</h2>
        <form id="editForm" method="POST" action="api/update_appointment.php" class="form">
            <input type="hidden" name="appointment_id" id="appointment_id">
            <div class="form-grid">
                <select name="edit_department" id="edit_department" required>
                    <option value="">Department</option>
                    <?php
                    $departments->data_seek(0);
                    while ($department = $departments->fetch_assoc()): ?>
                        <option value="<?= $department['id'] ?>"><?= $department['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="edit_name" id="edit_name" placeholder="Research Adviser's Name" required>
                <select name="edit_id_number" id="edit_id_number" required>
                    <option value="">Group Number</option>
                    <?php for ($i = 1; $i <= 200; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <select name="edit_set" id="edit_set" >
                    <option value="">Set</option>
                    <?php foreach (range('A', 'F') as $set): ?>
                        <option value="<?= $set ?>"><?= $set ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="time-picker">
                    <select id="edit_time_from_hour" name="edit_time_from_hour" required>
                        <option value="">Hour</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="edit_time_from_minute" name="edit_time_from_minute" required>
                        <option value="">Minute</option>
                        <?php for ($i = 0; $i < 60; $i++): ?>
                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="edit_time_from_ampm" name="edit_time_from_ampm" required>
                        <option value="">AM/PM</option>
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                </div>
                <div class="time-picker">
                    <select id="edit_time_to_hour" name="edit_time_to_hour" required>
                        <option value="">Hour</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="edit_time_to_minute" name="edit_time_to_minute" required>
                        <option value="">Minute</option>
                        <?php for ($i = 0; $i < 60; $i++): ?>
                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="edit_time_to_ampm" name="edit_time_to_ampm" required>
                        <option value="">AM/PM</option>
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                </div>
                <input type="date" name="edit_date" id="edit_date" required>
                <textarea name="edit_reason" id="edit_reason" placeholder="Agenda" required></textarea>
                <select name="edit_room" id="edit_room" required>
                    <option value="">Room Number</option>
                    <?php
                    $rooms->data_seek(0);
                    while ($room = $rooms->fetch_assoc()): ?>
                        <option value="<?= $room['id'] ?>"><?= $room['name'] ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="edit_representative_name" id="edit_representative_name" placeholder="Representative Name" required>
            </div>
            <textarea name="edit_group_members" id="edit_group_members" placeholder="Remarks" rows="4" ></textarea>
            <div class="form-actions-right">
                <button type="submit" id="save_button">Save Changes</button>
                <button type="button" id="delete_button">Delete Appointment</button>
            </div>
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
                <div class="time-picker">
                    <select id="time_from_hour" name="time_from_hour" required>
                        <option value="">Hour</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="time_from_minute" name="time_from_minute" required>
                        <option value="">Minute</option>
                        <?php for ($i = 0; $i < 60; $i++): ?>
                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="time_from_ampm" name="time_from_ampm" required>
                        <option value="">AM/PM</option>
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                </div>
                <div class="time-picker">
                    <select id="time_to_hour" name="time_to_hour" required>
                        <option value="">Hour</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="time_to_minute" name="time_to_minute" required>
                        <option value="">Minute</option>
                        <?php for ($i = 0; $i < 60; $i++): ?>
                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="time_to_ampm" name="time_to_ampm" required>
                        <option value="">AM/PM</option>
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                </div>
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
            <textarea name="group_members" placeholder="Remarks" rows="4" ></textarea>
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

<!-- Add this element to hold the appointments data -->
<script id="appointmentsData" type="application/json">
    <?= json_encode($appointments) ?>
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script defer src="js/script.js?v=11"></script>

<!-- Add this right before the closing body tag -->
<?php if ($searched_appointment): ?>
<script>
    // Data to pass to the view modal
    const searchedAppointmentData = <?= json_encode($searched_appointment) ?>;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Show the appointment details in the view modal
        const viewContainer = document.getElementById('viewContainer');
        if (viewContainer) {
            const timeFrom = new Date(`2000-01-01T${searchedAppointmentData.booking_time_from}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const timeTo = new Date(`2000-01-01T${searchedAppointmentData.booking_time_to}`).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            viewContainer.innerHTML = `
                <div class="appointment-details">
                    <p><strong>Research Adviser's Name:</strong> ${searchedAppointmentData.name}</p>
                    <p><strong>Group Number:</strong> ${searchedAppointmentData.id_number}</p>
                    <p><strong>Set:</strong> ${searchedAppointmentData.set}</p>
                    <p><strong>Department:</strong> ${searchedAppointmentData.department_name}</p>
                    <p><strong>Room:</strong> ${searchedAppointmentData.room_name}</p>
                    <p><strong>Date:</strong> ${searchedAppointmentData.booking_date}</p>
                    <p><strong>Time:</strong> ${timeFrom} - ${timeTo}</p>
                    <p><strong>Agenda:</strong> ${searchedAppointmentData.reason}</p>
                    <p><strong>Representative:</strong> ${searchedAppointmentData.representative_name}</p>
                    <p><strong>Remarks:</strong> ${searchedAppointmentData.group_members || "None"}</p>
                </div>
                <div class="form-actions-right" style="margin-top: 20px;">
                    <button type="button" class="edit-search-result" data-id="${searchedAppointmentData.id}">Edit Appointment</button>
                </div>
            `;
            
            // Show the view modal automatically
            document.getElementById('viewModal').style.display = 'block';
            
            // Add event listener to the edit button
            document.querySelector('.edit-search-result').addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-id');
                
                // Fill the edit form with the appointment data
                document.getElementById('appointment_id').value = searchedAppointmentData.id;
                document.getElementById('edit_department').value = searchedAppointmentData.department_id;
                document.getElementById('edit_name').value = searchedAppointmentData.name;
                document.getElementById('edit_id_number').value = searchedAppointmentData.id_number;
                document.getElementById('edit_set').value = searchedAppointmentData.set;
                document.getElementById('edit_date').value = searchedAppointmentData.booking_date;
                document.getElementById('edit_reason').value = searchedAppointmentData.reason;
                document.getElementById('edit_room').value = searchedAppointmentData.room_id;
                document.getElementById('edit_representative_name').value = searchedAppointmentData.representative_name;
                document.getElementById('edit_group_members').value = searchedAppointmentData.group_members;
                
                // Time handling - parse the time into components
                const timeFrom = new Date(`2000-01-01T${searchedAppointmentData.booking_time_from}`);
                const timeTo = new Date(`2000-01-01T${searchedAppointmentData.booking_time_to}`);
                
                const fromHour = timeFrom.getHours() % 12 || 12;
                const fromMinute = timeFrom.getMinutes();
                const fromAMPM = timeFrom.getHours() < 12 ? 'AM' : 'PM';
                
                const toHour = timeTo.getHours() % 12 || 12;
                const toMinute = timeTo.getMinutes();
                const toAMPM = timeTo.getHours() < 12 ? 'AM' : 'PM';
                
                document.getElementById('edit_time_from_hour').value = fromHour;
                document.getElementById('edit_time_from_minute').value = fromMinute.toString().padStart(2, '0');
                document.getElementById('edit_time_from_ampm').value = fromAMPM;
                
                document.getElementById('edit_time_to_hour').value = toHour;
                document.getElementById('edit_time_to_minute').value = toMinute.toString().padStart(2, '0');
                document.getElementById('edit_time_to_ampm').value = toAMPM;
                
                // Close the view modal and open the edit modal
                document.getElementById('viewModal').style.display = 'none';
                document.getElementById('editModal').style.display = 'block';
            });
        }
    });
</script>
<?php endif; ?>
</body>
</html>