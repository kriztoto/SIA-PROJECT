<?php
session_start();
include("includes/db.php");

// Check if user is logged in and is a student
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student"){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Get student information
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
        $attempts[$cat_id] = [
            'status' => 'taken',
            'score' => $attempt['score'],
            'correct' => $attempt['correct_answers'],
            'wrong' => $attempt['wrong_answers'],
            'attempt_number' => $attempt['attempt_number']
        ];
    } else {
        $attempts[$cat_id] = [
            'status' => 'not_taken',
            'score' => 0,
            'correct' => 0,
            'wrong' => 0,
            'attempt_number' => 0
        ];
    }
}

// Calculate statistics
$total_quizzes = count($categories);
$completed_quizzes = 0;
$total_score = 0;
$total_percentage = 0;

foreach($attempts as $attempt) {
    if($attempt['status'] == 'taken') {
        $completed_quizzes++;
        $total_score += $attempt['score'];
        $total_percentage += ($attempt['score'] / 10) * 100;
    }
}

$average_score = $completed_quizzes > 0 ? round($total_score / $completed_quizzes, 1) : 0;
$average_percentage = $completed_quizzes > 0 ? round($total_percentage / $completed_quizzes) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATH QUIZ - Student Dashboard</title>
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

        /* Dashboard Container */
        .dashboard-container {
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

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #1a492b 0%, #2e7d45 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .welcome-text h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .welcome-text p {
            font-size: 16px;
            opacity: 0.9;
        }

        .start-quiz-btn {
            background: white;
            color: #1a492b;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            text-decoration: none;
        }

        .start-quiz-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.3);
            background: #f8f9fa;
        }

        .start-quiz-btn:active {
            transform: translateY(-1px);
        }

        /* Summary Stats */
        .summary-stats {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s;
        }

        .stat-box:hover {
            transform: translateY(-5px);
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

        /* Scores Table */
        .scores-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .scores-title {
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1a492b;
        }

        .scores-table {
            width: 100%;
            border-collapse: collapse;
        }

        .scores-table th {
            text-align: left;
            padding: 15px;
            background: #f8f9fa;
            color: #333;
            font-weight: bold;
            border-bottom: 2px solid #1a492b;
        }

        .scores-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .scores-table tr:hover {
            background: #f8f9fa;
        }

        .category-name {
            font-weight: bold;
            color: #1a492b;
        }

        .score-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 16px;
            min-width: 80px;
            text-align: center;
        }

        .score-high {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .score-medium {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .score-low {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
        }

        .status-taken {
            background: #d4edda;
            color: #155724;
        }

        .status-not-taken {
            background: #f8d7da;
            color: #721c24;
        }

        .attempt-info {
            font-size: 14px;
            color: #666;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo-section h1 {
                font-size: 20px;
            }
            
            .logout-btn span:last-child {
                display: none;
            }
            
            .summary-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .welcome-section {
                flex-direction: column;
                text-align: center;
            }
            
            .scores-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 480px) {
            .summary-stats {
                grid-template-columns: 1fr;
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
            <li class="menu-item"><a href="student_page.php" class="menu-link active">📊 Dashboard</a></li>
            <li class="menu-item"><a href="quizzes.php" class="menu-link">📝 Quizzes</a></li>
            <li class="menu-item"><a href="leaderboards.php" class="menu-link">🏆 Leaderboard</a></li>
            <li class="menu-item"><a href="student_account.php" class="menu-link">⚙️ Manage Account</a></li>
        </ul>
    </div>

    <!-- Main Content - DASHBOARD PAGE -->
    <div class="main-content">
        <div class="dashboard-container">
            <h1 class="page-title">📊 Dashboard</h1>

            <!-- Welcome Section with Start Quiz Button -->
            <div class="welcome-section">
                <div class="welcome-text">
                    <h2>Welcome, <?php echo htmlspecialchars($student['first_name']); ?>! 👋</h2>
                    <p>Ready to test your math skills? Click the button to start a quiz.</p>
                </div>
                
                <a href="quizzes.php" class="start-quiz-btn">
                    <span>▶️</span>
                    <span>START QUIZ</span>
                </a>
            </div>

            <!-- Summary Statistics -->
            <div class="summary-stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_quizzes; ?></div>
                    <div class="stat-label">Total Quizzes</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $completed_quizzes; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $average_score; ?>/10</div>
                    <div class="stat-label">Average Score</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $average_percentage; ?>%</div>
                    <div class="stat-label">Average Percentage</div>
                </div>
            </div>

            <!-- Scores Table -->
            <div class="scores-container">
                <h2 class="scores-title">📝 Quiz Scores</h2>
                
                <table class="scores-table">
                    <thead>
                        <tr>
                            <th>Quiz Category</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Attempts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $category): 
                            $cat_id = $category['id'];
                            $attempt = $attempts[$cat_id];
                            $has_taken = $attempt['status'] == 'taken';
                            $score = $attempt['score'];
                            
                            // Determine score class for styling
                            $score_class = '';
                            if($has_taken) {
                                if($score >= 8) $score_class = 'score-high';
                                elseif($score >= 5) $score_class = 'score-medium';
                                else $score_class = 'score-low';
                            }
                        ?>
                            <tr>
                                <td class="category-name">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </td>
                                
                                <td>
                                    <?php if($has_taken): ?>
                                        <span class="status-badge status-taken">✓ Taken</span>
                                    <?php else: ?>
                                        <span class="status-badge status-not-taken">✗ Not Taken</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if($has_taken): ?>
                                        <span class="score-badge <?php echo $score_class; ?>">
                                            <?php echo $score; ?>/10
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">--/10</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="attempt-info">
                                    <?php if($has_taken): ?>
                                        Attempt #<?php echo $attempt['attempt_number']; ?>
                                        <div style="font-size: 12px; color: #999;">
                                            ✓ <?php echo $attempt['correct']; ?> correct | 
                                            ✗ <?php echo $attempt['wrong']; ?> wrong
                                        </div>
                                    <?php else: ?>
                                        No attempts yet
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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