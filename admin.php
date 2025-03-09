<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'crad_crad', 'crad2025', 'crad_calendar_booking');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle form submissions
$success_message = '';
$error_message = '';

// Add Group
if (isset($_POST['add_group'])) {
    $group_name = $_POST['group_name'];
    
    // Check if group already exists
    $check = $conn->prepare("SELECT * FROM groups WHERE group_name = ?");
    $check->bind_param("s", $group_name);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $error_message = "Group already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO groups (group_name) VALUES (?)");
        $stmt->bind_param("s", $group_name);
        
        if ($stmt->execute()) {
            $success_message = "Group added successfully!";
        } else {
            $error_message = "Error adding group: " . $conn->error;
        }
    }
}

// Add Set
if (isset($_POST['add_set'])) {
    $set_name = $_POST['set_name'];
    
    // Check if set already exists
    $check = $conn->prepare("SELECT * FROM sets WHERE set_name = ?");
    $check->bind_param("s", $set_name);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $error_message = "Set already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO sets (set_name) VALUES (?)");
        $stmt->bind_param("s", $set_name);
        
        if ($stmt->execute()) {
            $success_message = "Set added successfully!";
        } else {
            $error_message = "Error adding set: " . $conn->error;
        }
    }
}

// Add Adviser
if (isset($_POST['add_adviser'])) {
    $adviser_name = $_POST['adviser_name'];
    $adviser_department = $_POST['adviser_department'];
    
    // Check if adviser already exists
    $check = $conn->prepare("SELECT * FROM advisers WHERE adviser_name = ?");
    $check->bind_param("s", $adviser_name);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $error_message = "Adviser already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO advisers (adviser_name, department_id) VALUES (?, ?)");
        $stmt->bind_param("si", $adviser_name, $adviser_department);
        
        if ($stmt->execute()) {
            $success_message = "Adviser added successfully!";
        } else {
            $error_message = "Error adding adviser: " . $conn->error;
        }
    }
}

// Fetch data for display
$rooms = $conn->query("SELECT id, name FROM rooms ORDER BY name");
$departments = $conn->query("SELECT * FROM departments ORDER BY name");
$groups = $conn->query("SELECT * FROM groups ORDER BY created_at");
$sets = $conn->query("SELECT * FROM sets ORDER BY name");
$advisers = $conn->query("SELECT a.*, d.name as department_name FROM advisers a LEFT JOIN departments d ON a.department_id = d.id ORDER BY a.adviser_name");

// Create tables if they don't exist
$create_tables = [
    "CREATE TABLE IF NOT EXISTS rooms (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS departments (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        color VARCHAR(20) DEFAULT '#3788d8'
    )",
    "CREATE TABLE IF NOT EXISTS groups (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        group_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS sets (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        set_name VARCHAR(100) NOT NULL
    )",
    "CREATE TABLE IF NOT EXISTS advisers (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        adviser_name VARCHAR(100) NOT NULL,
        department_id INT(11),
        FOREIGN KEY (department_id) REFERENCES departments(id)
    )"
];

foreach ($create_tables as $sql) {
    $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Smart Scheduling System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/bcplogo.png" type="image/png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="mycss/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="mycss/form.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/admin.css?v=<?= time() ?>">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-size: 12px;
            transform: scale(0.9);
            transform-origin: top left;
            width: 111.11%;
            height: 111.11%;
            font-family: 'Poppins', sans-serif;
            background-color: #C9E6F0;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="assets/bcplogo.png" alt="BCP Logo" class="sidebar-logo">
                <h2>BCP CRAD</h2>
            </div>
            
            <div class="sidebar-menu">
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendar</span>
                </a>
                <a href="form.php" class="<?= basename($_SERVER['PHP_SELF']) == 'form.php' ? 'active' : '' ?>">
                    <i class="fas fa-book"></i>
                    <span>Logbook</span>
                </a>
                <a href="analytics.php" class="<?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
                <a href="admin.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>">
                    <i class="fas fa-cogs"></i>
                    <span>Admin</span>
                </a>
            </div>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-button">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <div class="content-header">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><i class="fas fa-cogs"></i> Admin Dashboard</h1>
            </div>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?= $success_message ?>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?= $error_message ?>
            </div>
            <?php endif; ?>

            <div class="admin-container">
                <div class="admin-cards">
                    <!-- Rooms Card -->
                    <div class="admin-card">
                        <h3><i class="fas fa-door-open"></i> Rooms</h3>
                        <p>Manage rooms available for booking</p>
                        <button class="btn" onclick="openModal('roomModal')"><i class="fas fa-plus"></i> Add Room</button>
                        
                        <?php if ($rooms->num_rows > 0): ?>
                        <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Room Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($room = $rooms->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($room['name']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <p>No rooms added yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Departments Card -->
                    <div class="admin-card">
                        <h3><i class="fas fa-building"></i> Departments</h3>
                        <p>Manage academic departments</p>
                        <button class="btn" onclick="openModal('departmentModal')"><i class="fas fa-plus"></i> Add Department</button>
                        
                        <?php if ($departments->num_rows > 0): ?>
                        <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Department Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($department = $departments->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($department['name']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <p>No departments added yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Groups Card -->
                    <div class="admin-card">
                        <h3><i class="fas fa-users"></i> Groups</h3>
                        <p>Manage student groups</p>
                        <button class="btn" onclick="openModal('groupModal')"><i class="fas fa-plus"></i> Add Group</button>
                        
                        <?php if ($groups->num_rows > 0): ?>
                        <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Group Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($group = $groups->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($group['group_name']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <p>No groups added yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sets Card -->
                    <div class="admin-card">
                        <h3><i class="fas fa-layer-group"></i> Sets</h3>
                        <p>Manage student sets</p>
                        <button class="btn" onclick="openModal('setModal')"><i class="fas fa-plus"></i> Add Set</button>
                        
                        <?php if ($sets->num_rows > 0): ?>
                        <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Set Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($set = $sets->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($set['name']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <p>No sets added yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Advisers Card -->
                    <div class="admin-card">
                        <h3><i class="fas fa-user-tie"></i> Research Advisers</h3>
                        <p>Manage research advisers</p>
                        <button class="btn" onclick="openModal('adviserModal')"><i class="fas fa-plus"></i> Add Adviser</button>
                        
                        <?php if ($advisers->num_rows > 0): ?>
                        <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Adviser Name</th>
                                    <th>Department</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($adviser = $advisers->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($adviser['adviser_name']) ?></td>
                                    <td><?= htmlspecialchars($adviser['department_name'] ?? 'N/A') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <p>No advisers added yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Modal -->
    <div id="roomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-door-open"></i> Add Room</h2>
                <span class="close" onclick="closeModal('roomModal')">&times;</span>
            </div>
            <form id="addRoomForm" method="POST">
                <div class="form-group">
                    <label for="name">Room Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="primary-button">
                        <i class="fas fa-plus"></i> Add Room
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Department Modal -->
    <div id="departmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-building"></i> Add Department</h2>
                <span class="close" onclick="closeModal('departmentModal')">&times;</span>
            </div>
            <form id="addDepartmentForm" method="POST">
                <div class="form-group">
                    <label for="department_name">Department Name</label>
                    <input type="text" id="department_name" name="department_name" required>
                </div>
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="color" id="color" name="color" value="#3788d8" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="primary-button">
                        <i class="fas fa-plus"></i> Add Department
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Group Modal -->
    <div id="groupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-users"></i> Add Group</h2>
                <span class="close" onclick="closeModal('groupModal')">&times;</span>
            </div>
            <form method="post" action="">
                <div class="form-group">
                    <label for="group_name">Group Name</label>
                    <input type="text" id="group_name" name="group_name" required>
                </div>
                <button type="submit" name="add_group" class="submit-btn">Add Group</button>
            </form>
        </div>
    </div>

    <!-- Set Modal -->
    <div id="setModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-layer-group"></i> Add Set</h2>
                <span class="close" onclick="closeModal('setModal')">&times;</span>
            </div>
            <form method="post" action="">
                <div class="form-group">
                    <label for="set_name">Set Name</label>
                    <input type="text" id="set_name" name="set_name" required>
                </div>
                <button type="submit" name="add_set" class="submit-btn">Add Set</button>
            </form>
        </div>
    </div>

    <!-- Adviser Modal -->
    <div id="adviserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-tie"></i> Add Research Adviser</h2>
                <span class="close" onclick="closeModal('adviserModal')">&times;</span>
            </div>
            <form method="post" action="">
                <div class="form-group">
                    <label for="adviser_name">Adviser Name</label>
                    <input type="text" id="adviser_name" name="adviser_name" required>
                </div>
                <div class="form-group">
                    <label for="adviser_department">Department</label>
                    <select id="adviser_department" name="adviser_department" required>
                        <option value="">Select Department</option>
                        <?php 
                        $dept_list = $conn->query("SELECT * FROM departments ORDER BY name");
                        while ($dept = $dept_list->fetch_assoc()): 
                        ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" name="add_adviser" class="submit-btn">Add Adviser</button>
            </form>
        </div>
    </div>

    <!-- Add Department Modal -->
    <div id="addDepartmentModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2>Add Department</h2>
                <button class="close-button" id="closeAddDepartmentModal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="addDepartmentForm" method="POST">
                    <div class="form-group">
                        <label for="department_name">Department Name</label>
                        <input type="text" name="department_name" id="department_name" required>
                    </div>
                    <div class="form-group">
                        <label for="color">Color</label>
                        <input type="color" name="color" id="color" value="#3788d8" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="primary-button">
                            <i class="fas fa-plus"></i> Add Department
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div id="addRoomModal" class="modal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2>Add Room</h2>
                <button class="close-button" id="closeAddRoomModal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="addRoomForm" method="POST">
                    <div class="form-group">
                        <label for="name">Room Name</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="primary-button">
                            <i class="fas fa-plus"></i> Add Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js?v=<?= time() ?>"></script>
    <script src="js/admin.js?v=<?= time() ?>"></script>
    <script>
        // Handle room form submission
        document.getElementById('addRoomForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/add_room.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Room added successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || data.error));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });

        // Handle department form submission
        document.getElementById('addDepartmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/add_department.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Department added successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || data.error));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });

        // Close modal functions
        document.getElementById('closeAddRoomModal').addEventListener('click', function() {
            document.getElementById('addRoomModal').style.display = 'none';
        });

        document.getElementById('closeAddDepartmentModal').addEventListener('click', function() {
            document.getElementById('addDepartmentModal').style.display = 'none';
        });

        // When clicking outside of the modal, close it
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });
    </script>
</body>
</html> 