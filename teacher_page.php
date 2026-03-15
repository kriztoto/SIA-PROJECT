<?php
session_start();
include("includes/db.php");

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher"){
    header("Location: index.php");
    exit();
}

$sections_query = "SELECT * FROM sections";
$sections_result = $conn->query($sections_query);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATH QUIZ - Check Scores</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: #f4f4f4;
        }

        /* Top Navigation Bar */
        .top-nav {
            background: #1a492b;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-section h1 {
            color: white;
            font-size: 24px;
        }

        .hamburger {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .hamburger:hover {
            background: rgba(255,255,255,0.2);
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 70px;
            left: -300px;
            width: 280px;
            height: calc(100vh - 70px);
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: left 0.3s ease;
            overflow-y: auto;
            z-index: 999;
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-header {
            padding: 20px;
            background: #1a492b;
            color: white;
        }

        .sidebar-menu {
            list-style: none;
            padding: 15px 0;
        }

        .menu-item {
            border-bottom: 1px solid #f0f0f0;
        }

        .menu-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }

        .menu-link:hover {
            background: #f8f9fa;
            color: #1a492b;
        }

        .dropdown-arrow {
            transition: transform 0.3s;
            font-size: 14px;
        }

        .dropdown.active .dropdown-arrow {
            transform: rotate(90deg);
        }

        .dropdown-menu {
            list-style: none;
            background: #f8f9fa;
            display: none;
            padding: 10px 0;
        }

        .dropdown.active .dropdown-menu {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 12px 20px 12px 40px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .dropdown-item:hover {
            background: #e9ecef;
            color: #1a492b;
        }

        .overlay {
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 998;
        }

        .overlay.show {
            display: block;
        }

        /* Main Content Area */
        .main-content {
            margin-top: 70px;
            padding: 30px;
        }

        /* Check Scores Page Styles */
        .scores-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            border-bottom: 3px solid #1a492b;
            padding-bottom: 10px;
            display: inline-block;
        }

        .scores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        /* Section Cards */
        .section-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .section-header {
            background: linear-gradient(135deg, #1a492b 0%, #2e7d45 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .section-header h2 {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .section-body {
            padding: 25px;
        }

        /* Score Items */
        .score-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .score-item:last-child {
            border-bottom: none;
        }

        .score-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: #555;
        }

        .score-label i {
            font-size: 20px;
        }

        .score-value {
            font-size: 24px;
            font-weight: bold;
            color: #1a492b;
        }

        .take-score .score-value {
            color: #00c53e;
        }

        .not-take-score .score-value {
            color: #dc3545;
        }

        /* Progress Bar */
        .progress-container {
            margin: 20px 0 10px;
        }

        .progress-bar {
            height: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #1a492b, #2e7d45);
            border-radius: 5px;
            width: 50%; /* 10/20 = 50% */
        }

        .progress-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 14px;
            color: #666;
        }

        /* Summary Stats */
        .summary-stats {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }

        .stat-box {
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #1a492b;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo-section h1 {
                font-size: 20px;
            }
            
            .logout-btn span:last-child {
                display: none;
            }
            
            .scores-grid {
                grid-template-columns: 1fr;
            }
            
            .section-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <div class="top-nav">
        <div class="logo-section">
            <button class="hamburger" onclick="toggleSidebar()">☰</button>
            <h1>MATH QUIZ</h1>
        </div>
        
        <button class="logout-btn" onclick="return confirm('Are you sure you want to log out?') ? window.location.href='index.php' : false;">
            <span>🚪</span>
            <span>LOG OUT</span>
        </button>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Menu</h3>
        </div>
        
        <ul class="sidebar-menu">
            <li class="menu-item"><a href="teacher_page.php" class="menu-link">📊 Dashboard</a></li>
            <li class="menu-item"><a href="check_scores.php" class="menu-link">📊 Check Scores</a></li>
            <li class="menu-item"><a href="manage_quiz.php" class="menu-link">📝 Manage Quiz</a></li>
            <li class="menu-item"><a href="manage_students.php" class="menu-link">👥 Manage Students</a></li>
        </ul>
    </div>

    <!-- Main Content - CHECK SCORES PAGE -->
    <div class="main-content">
        <div class="scores-container">
            <h1 class="page-title">📊 Dashboard</h1>

            <!-- Sections Grid - Exactly like your image -->
            <div class="scores-grid">
                <!-- Section A -->
                <?php while($section = $sections_result->fetch_assoc()): ?>

            <?php
                $section_id = $section['id'];

                /* Total students in this section */
                $total_students_query = "
                SELECT COUNT(*) as total 
                FROM users 
                WHERE section_id = $section_id 
                AND role = 'student'
                ";
                $total_students = $conn->query($total_students_query)->fetch_assoc()['total'];

                /* Students who took at least 1 quiz */
                $taken_query = "
                SELECT COUNT(DISTINCT u.id) as taken
                FROM users u
                JOIN quiz_attempts qa ON u.id = qa.user_id
                WHERE u.section_id = $section_id
                AND u.role = 'student'
                ";
                $taken = $conn->query($taken_query)->fetch_assoc()['taken'];

                $not_taken = $total_students - $taken;

                $percentage = $total_students > 0   
                ? round(($taken / $total_students) * 100) 
                : 0;
            ?>

    <div class="section-card">
        <div class="section-header">
            <h2><?php echo strtoupper($section['section_name']); ?></h2>
        </div>

        <div class="section-body">

            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" 
                         style="width: <?php echo $percentage; ?>%">
                    </div>
                </div>
                <div class="progress-stats">
                    <span><?php echo $taken . "/" . $total_students; ?> taken</span>
                    <span><?php echo $percentage; ?>%</span>
                </div>
            </div>

            <div class="score-item take-score">
                <span class="score-label">
                    <span>✅</span> Take:
                </span>
                <span class="score-value">
                    <?php echo $taken . "/" . $total_students; ?>
                </span>
            </div>

            <div class="score-item not-take-score">
                <span class="score-label">
                    <span>❌</span> Not Take:
                </span>
                <span class="score-value">
                    <?php echo $not_taken . "/" . $total_students; ?>
                </span>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
                </div> 
        </div> 
    </div> 

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('show');
        }

        function toggleDropdown(event, element) {
            event.preventDefault();
            var dropdown = element.closest('.dropdown');
            if (dropdown) {
                dropdown.classList.toggle('active');
                
                var otherDropdowns = document.querySelectorAll('.dropdown');
                otherDropdowns.forEach(function(item) {
                    if (item !== dropdown) {
                        item.classList.remove('active');
                    }
                });
            }
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            var sidebar = document.getElementById('sidebar');
            var hamburger = document.querySelector('.hamburger');
            
            if (sidebar && hamburger) {
                if (!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
                    sidebar.classList.remove('open');
                    document.getElementById('overlay').classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>