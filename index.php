<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Calendar System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 150px;
            background-color: #0056b3;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            padding: 15px;
            text-align: center;
            text-decoration: none;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .sidebar a:hover {
            background-color: #003f7a;
        }
        .search-container {
            margin-bottom: 20px;
        }
        .search-container input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: calc(100% - 22px);
        }
        .search-container button {
            padding: 10px;
            background-color: #00509e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 5px;
        }
        .search-container button:hover {
            background-color: #0073e6;
        }
    </style>
</head>
<body>

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'crad_crad', 'crad', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle form submissions
if (isset($_POST['add_booking'])) {
    $name = $_POST['name'];
    $id_number = $_POST['id_number'];
    $department = $_POST['department'];
    $room = $_POST['room'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO bookings (name, id_number, department_id, room_id, booking_date, booking_time, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiisss", $name, $id_number, $department, $room, $date, $time, $reason);
    $stmt->execute();
    $stmt->close();

    header('Location: index.php');
    exit();
}

// Handle department addition
if (isset($_POST['add_department'])) {
    $department_name = $_POST['department_name'];
    $color = $_POST['color']; 
    $stmt = $conn->prepare("INSERT INTO departments (name, color) VALUES (?, ?)");
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
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE name LIKE ?");
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

<div class="sidebar">
    <a href="home.php">HOME</a>
    <a href="index.php">BOOKING</a>
    <a href="hr.php">HR</a>
    <a href="faculty.php">ITS</a>
    <a href="osas.php">OSAS</a>
    <a href="faculty.php">FACULTY</a>
</div>

<div class="container" style="margin-left: 170px;">
    <!-- Rest of your HTML content remains the same -->
    <!-- ... -->
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const closeModal = document.getElementsByClassName("close")[0];
    const editForm = document.getElementById("editForm");
    const deleteButton = document.getElementById("delete_button");

    // Add Department Modal
    const addDepartmentModal = document.getElementById("addDepartmentModal");
    const addDepartmentButton = document.getElementById("add_department_button");
    const closeAddDepartmentModal = document.getElementById("closeAddDepartmentModal");

    // Add Room Modal
    const addRoomModal = document.getElementById("addRoomModal");
    const addRoomButton = document.getElementById("add_room_button");
    const closeAddRoomModal = document.getElementById("closeAddRoomModal");

    // Show Add Department modal
    addDepartmentButton.onclick = function () {
        addDepartmentModal.style.display = "block"; 
    }

    // Show Add Room modal
    addRoomButton.onclick = function () {
        addRoomModal.style.display = "block"; 
    }

    // Close Add Department modal
    closeAddDepartmentModal.onclick = function () {
        addDepartmentModal.style.display = "none"; 
    }

    // Close Add Room modal
    closeAddRoomModal.onclick = function () {
        addRoomModal.style.display = "none"; 
    }

    // Close modals when clicking outside
    window.onclick = function (event) {
        if (event.target === addDepartmentModal) {
            addDepartmentModal.style.display = "none"; 
        }
        if (event.target === addRoomModal) {
            addRoomModal.style.display = "none"; 
        }
    }

    // Existing appointment modal functionality
    document.querySelectorAll(".appointment").forEach(item => {
        item.addEventListener("click", event => {
            const appointmentId = item.getAttribute("data-id");
            fetch(`api/get_appointment.php?id=${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById("appointment_id").value = data.id;
                    document.getElementById("edit_name").value = data.name;
                    document.getElementById("edit_id_number").value = data.id_number;
                    document.getElementById("edit_date").value = data.booking_date;
                    document.getElementById("edit_time").value = data.booking_time;
                    document.getElementById("edit_reason").value = data.reason;
                    document.getElementById("edit_department").value = data.department_id;
                    document.getElementById("edit_room").value = data.room_id;
                });
            modal.style.display = "block";
        });
    });

    // Close existing appointment modal
    closeModal.onclick = function () {
        modal.style.display = "none"; 
    }

    // Handle save changes for existing appointments
    editForm.addEventListener("submit", function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        fetch('api/update_appointment.php', {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                alert('Appointment updated successfully');
                location.reload();
            } else {
                alert('Failed to update appointment');
            }
        }).catch(error => {
            console.error('Error updating appointment:', error);
            alert('An error occurred while updating the appointment.');
        });
    });

    // Handle delete appointment
    deleteButton.addEventListener("click", function () {
        const appointmentId = document.getElementById("appointment_id").value;
        if (confirm('Are you sure you want to delete this appointment?')) {
            fetch(`api/delete_appointment.php?id=${appointmentId}`, {
                method: 'DELETE'
            }).then(response => {
                if (response.ok) {
                    alert('Appointment deleted successfully');
                    location.reload();
                } else {
                    alert('Failed to delete appointment');
                }
            }).catch(error => {
                console.error('Error deleting appointment:', error);
                alert('An error occurred while deleting the appointment.');
            });
        }
    });
});

// Open the edit modal if a searched appointment is found
<?php if ($searched_appointment): ?>
    document.getElementById('editModal').style.display = 'block';
<?php endif; ?>
</script>

</body>
</html>