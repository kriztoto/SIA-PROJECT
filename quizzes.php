<?php
session_start();
include("configs/db.php");

// Check if user is logged in and is a student
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student"){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Get student information (for sidebar only)
$student_query = $conn->prepare("
    SELECT u.first_name, u.middle_initial, u.last_name, u.student_id, s.section_name 
    FROM users u 
    LEFT JOIN sections s ON u.section_id = s.id 
    WHERE u.id = ?
");
$student_query->bind_param("i", $user_id);
$student_query->execute();
$student_result = $student_query->get_result();
$student = $student_result->fetch_assoc();

// Get all categories
$categories_query = $conn->query("SELECT * FROM categories ORDER BY id ASC");
$categories = [];
while($cat = $categories_query->fetch_assoc()) {
    $categories[] = $cat;
}

// Get quiz attempts for each category
$attempts = [];
foreach($categories as $category) {
    $cat_id = $category['id'];
    
    // Get the latest attempt for this category
    $attempt_q = $conn->prepare("
        SELECT * FROM quiz_attempts 
        WHERE user_id = ? AND category_id = ? 
        ORDER BY date_taken DESC 
        LIMIT 1
    ");
    $attempt_q->bind_param("ii", $user_id, $cat_id);
    $attempt_q->execute();
    $attempt_result = $attempt_q->get_result();
    
    if($attempt_result->num_rows > 0) {
        $attempt = $attempt_result->fetch_assoc();
        
        // Determine status based on score (10 items max)
        $status = 'taken';
        $status_label = 'TAKEN';
        $score = $attempt['score'];
        
        // Check if failed (score 5 or below)
        if($score <= 5) {
            $status_label = 'FAILED';
        }
        
        $attempts[$cat_id] = [
            'status' => $status,
            'status_label' => $status_label,
            'score' => $score,
            'correct' => $attempt['correct_answers'],
            'wrong' => $attempt['wrong_answers'],
            'total' => 10, // Always show as 10 items
            'attempt_number' => $attempt['attempt_number']
        ];
    } else {
        $attempts[$cat_id] = [
            'status' => 'not_taken',
            'status_label' => 'NOT TAKEN',
            'score' => 0,
            'correct' => 0,
            'wrong' => 0,
            'total' => 10, // Always show as 10 items
            'attempt_number' => 0
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATH QUIZ - Student Quizzes</title>
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

        .student-info-sidebar {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .student-info-sidebar p {
            margin: 5px 0;
            color: #333;
            font-size: 14px;
        }

        .student-info-sidebar strong {
            color: #1a492b;
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
            align-items: center;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
            gap: 10px;
        }

        .menu-link:hover {
            background: #f8f9fa;
            color: #1a492b;
            padding-left: 25px;
        }

        .menu-link.active {
            background: #e8f5e9;
            color: #1a492b;
            border-left: 4px solid #1a492b;
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

        /* Quizzes Container */
        .quizzes-container {
            max-width: 1400px;
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

        /* Quiz Cards - Centered Row */
        .quizzes-row {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
            padding: 20px 0;
        }

        /* Quiz Card - White Background */
        .quiz-card {
            flex: 0 0 280px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .quiz-header {
            background: linear-gradient(135deg, #1a492b 0%, #2e7d45 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .quiz-header h2 {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .quiz-body {
            padding: 20px;
            background: white;
        }

        /* Stats Table */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
        }

        .stats-table tr {
            border-bottom: 1px solid #f0f0f0;
        }

        .stats-table tr:last-child {
            border-bottom: none;
        }

        .stats-table td {
            padding: 12px 0;
            font-size: 15px;
        }

        .stats-table td:first-child {
            font-weight: bold;
            color: #555;
        }

        .stats-table td:last-child {
            text-align: right;
            font-weight: bold;
            font-size: 16px;
        }

        /* Status row with background colors */
        .status-row {
            background-color: #ff9800; /* Default orange for NOT TAKEN */
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .status-row.taken {
            background-color: #00c53e;
        }

        .status-row.failed {
            background-color: #dc3545;
        }

        .status-row td {
            padding: 12px 10px;
            color: white;
            border-bottom: none;
        }

        .status-row td:first-child {
            color: white;
            font-weight: bold;
        }

        .status-row td:last-child {
            color: white;
            font-weight: bold;
        }

        /* Regular stat rows */
        .correct-value {
            color: #00c53e;
            font-weight: bold;
        }

        .wrong-value {
            color: #dc3545;
            font-weight: bold;
        }

        .accuracy-value {
            color: #1a492b;
            font-weight: bold;
        }

        /* Start Quiz Button */
        .start-quiz-btn {
            display: block;
            width: 100%;
            background: #1a492b;
            color: white;
            text-align: center;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            border: 2px solid #1a492b;
            margin-top: 15px;
        }

        .start-quiz-btn:hover {
            background: white;
            color: #1a492b;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo-section h1 {
                font-size: 20px;
            }
            
            .logout-btn span:last-child {
                display: none;
            }
            
            .quiz-card {
                flex: 0 0 260px;
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
            <h3>Student Menu</h3>
        </div>
        
        <div class="student-info-sidebar">
            <p><strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong></p>
            <p>ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
            <p>Section: <?php echo htmlspecialchars($student['section_name'] ?? 'No Section'); ?></p>
        </div>
        
        <ul class="sidebar-menu">
            <li class="menu-item"><a href="student_page.php" class="menu-link">📊 Dashboard</a></li>
            <li class="menu-item"><a href="quizzes.php" class="menu-link active">📝 Quizzes</a></li>
            <li class="menu-item"><a href="leaderboards.php" class="menu-link">🏆 Leaderboard</a></li>
            <li class="menu-item"><a href="student_account.php" class="menu-link">⚙️ Manage Account</a></li>
        </ul>
    </div>

    <!-- Main Content - QUIZZES PAGE -->
    <div class="main-content">
        <div class="quizzes-container">
            <h1 class="page-title">📝 Quizzes</h1>

            <!-- Quiz Cards - Centered Row -->
            <div class="quizzes-row">
                <?php foreach($categories as $category): 
                    $cat_id = $category['id'];
                    $attempt = $attempts[$cat_id];
                    $status_label = $attempt['status_label'];
                    
                    // Determine status class for background color
                    $status_class = '';
                    if($status_label == 'TAKEN') {
                        $status_class = 'taken';
                    } elseif($status_label == 'FAILED') {
                        $status_class = 'failed';
                    } else {
                        $status_class = ''; // Default orange from .status-row
                    }
                    
                    // Calculate accuracy
                    $accuracy = 0;
                    if($attempt['status'] == 'taken') {
                        $accuracy = round(($attempt['correct'] / 10) * 100);
                    }
                ?>
                    <div class="quiz-card">
                        <div class="quiz-header">
                            <h2><?php echo strtoupper(htmlspecialchars($category['category_name'])); ?></h2>
                        </div>
                        
                        <div class="quiz-body">
                            <!-- Stats Table with colored status row -->
                            <table class="stats-table">
                                <tr class="status-row <?php echo $status_class; ?>">
                                    <td><?php echo $status_label; ?></td>
                                    <td><?php echo $attempt['score']; ?>/10</td>
                                </tr>
                                <tr>
                                    <td>CORRECT</td>
                                    <td class="correct-value"><?php echo $attempt['correct']; ?>/10</td>
                                </tr>
                                <tr>
                                    <td>WRONG</td>
                                    <td class="wrong-value"><?php echo $attempt['wrong']; ?>/10</td>
                                </tr>
                                <tr>
                                    <td>ACCURACY</td>
                                    <td class="accuracy-value"><?php echo $accuracy; ?>%</td>
                                </tr>
                            </table>
                            
                            <!-- Start Quiz Button -->
                            <a href="quiz_page.php?category_id=<?php echo $cat_id; ?>" class="start-quiz-btn">
                                START QUIZ
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('show');
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