    <style>
        #sidebar {
            height: 100vh; /* Full height */
            width: 250px; /* Fixed width */
            position: fixed; /* Fixed position */
            top: 0;
            left: 0;
            background-color: #f8f9fa; /* Light background */
            transition: transform 0.3s ease; /* Smooth slide effect */
        }

        #sidebar.collapsed {
            transform: translateX(-100%); /* Hide sidebar */
        }

        #page-content {
            margin-left: 250px; /* Space for sidebar */
            transition: margin-left 0.3s ease; /* Smooth transition */
        }

        #page-content.collapsed {
            margin-left: 0; /* No margin when sidebar is hidden */
        }
    </style>
<head>
    <script src="./js/jquery/jquery.min.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar" class="bg-light text-center shadow">
        <div class="p-3">
        <img src="./css/bcp_logo.png" alt="Logo" class="logo">
            <h4 class="mb-4">Dashboard</h4>
            <ul class="nav flex-column">
                <li class="nav-item mb-1">
                    <a class="nav-link active rounded" href="dashboard_admin.php">Home</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="index.php">Student List</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="picture.php">Pictures</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="logs.php">Logbook</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="users.php">Users</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="registrar.php">Registrar</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="hr.php">HR</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link rounded" href="its.php">ITS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded text-danger" href="#" id="logout">Logout</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content">
        <nav>
            <button class="btn shadow" type="button" id="toggle-sidebar">
                ☰ Menu
            </button>
        </nav>
        <div class="container-fluid">
            <h1 class="mt-4">Welcome to Social Media Department</h1>
            <p>Welcome Admin. <br> This is Social Media Department. It is a system where you can add, edit, view, and update all data of Student's List you need. <br> You can view the pictures of all gradutes here. <br> You can also view the logs of other users of the system. <br> And as an admin, you can also manages the user accounts the system. <br> You can send a request on HR and ITS (Information Technology System).</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var sidebar = document.getElementById('sidebar');
            var content = document.getElementById('page-content');
            var toggleButton = document.getElementById('toggle-sidebar');

            toggleButton.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('collapsed');
            });
        });
    </script>
    
</body>
</html>