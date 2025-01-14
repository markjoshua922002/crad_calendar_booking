<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - BCP CRAD</title>
    <style>
        /* Body and Background Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #e6f7ff;
            background-image: url('../assets/bcpbg1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh; /* Full height */
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 250px;
            background-color: #0056b3;
            transform: translateX(-250px);
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
        }

        .sidebar.open {
            transform: translateX(0);
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
            display: block;
        }

        .sidebar a:hover {
            background-color: #003f7a;
        }

        /* Hamburger Menu Styles */
        .hamburger-menu {
            position: fixed;
            left: 20px;
            top: 20px;
            z-index: 1001;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
        }

        .hamburger-menu span {
            display: block;
            width: 25px;
            height: 3px;
            background-color: #0056b3;
            margin: 5px 0;
            transition: 0.3s;
        }

        /* Animation for hamburger menu */
        .hamburger-menu.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger-menu.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger-menu.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        /* Content Shift Styles */
        .container {
            transition: margin-left 0.3s ease-in-out;
            margin-left: 20px;
        }

        .container.shifted {
            margin-left: 270px;
        }

        /* Centered Content Styles */
        .content {
            text-align: center;
            color: #333;
        }

        /* Logo Styles */
        .logo {
            width: 150px;
            margin-bottom: 20px;
        }

        /* Title Styles */
        .title {
            font-size: 48px;
            font-weight: bold;
            color: #0056b3;
            margin: 0;
            padding: 10px;
            border-radius: 5px;
            background-color: rgba(255, 255, 255, 0.9);
            display: inline-block;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-transform: uppercase;
        }

        /* Logout Button Styles */
        .logout-button {
            padding: 10px 15px;
            background-color: #FF4C4C;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .logout-button:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>

    <!-- Hamburger Menu Button -->
    <button class="hamburger-menu" id="toggleMenu">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <a href="home.php">HOME</a>
        <a href="index.php">BOOKING</a>
        <a href="hr.php">HR</a>
        <a href="its.php">ITS</a>
        <a href="osas.php">OSAS</a>
        <a href="faculty.php">FACULTY</a>
    </div>

    <!-- Logout Button -->
    <header>
        <button class="logout-button" onclick="location.href='logout.php'">Logout</button>
    </header>

    <!-- Centered Content -->
    <div class="content">
        <img src="../assets/bcplogo.png" alt="Logo" class="logo">
        <h1 class="title">ITS INTEG4 test</h1>
    </div>

    <script>
        // JavaScript to handle the hamburger menu toggle
        document.getElementById('toggleMenu').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const container = document.getElementById('mainContainer');
            const hamburger = document.getElementById('toggleMenu');
            
            sidebar.classList.toggle('open');
            container.classList.toggle('shifted');
            hamburger.classList.toggle('active');
        });

        // Mobile responsive enhancement
        function adjustForMobile() {
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('open');
                document.getElementById('mainContainer').classList.remove('shifted');
            }
        }

        window.addEventListener('resize', adjustForMobile);
        window.addEventListener('load', adjustForMobile);

        // Auto-close sidebar when clicking a link on mobile
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.sidebar a').forEach(link => {
                link.addEventListener('click', () => {
                    document.getElementById('sidebar').classList.remove('open');
                    document.getElementById('mainContainer').classList.remove('shifted');
                    document.getElementById('toggleMenu').classList.remove('active');
                });
            });
        }
    </script>

</body>
</html>
