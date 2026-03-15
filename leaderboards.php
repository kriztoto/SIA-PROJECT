<?php
session_start();
include("configs/db.php");

// Check if user is logged in and is a student
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student"){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Get student information including their section
$student_query = $conn->prepare("
    SELECT u.first_name, u.middle_initial, u.last_name, u.student_id, s.section_name, u.section_id
    FROM users u 
    LEFT JOIN sections s ON u.section_id = s.id 
    WHERE u.id = ?
");
$student_query->bind_param("i", $user_id);
$student_query->execute();
$student_result = $student_query->get_result();
$student = $student_result->fetch_assoc();

$user_section_id = $student['section_id'];
$user_section_name = $student['section_name'] ?? 'No Section';

// Get all categories with their total question counts (max score per category)
$categories_query = $conn->query("
    SELECT c.*, COUNT(q.id) as total_questions 
    FROM categories c 
    LEFT JOIN questions q ON c.id = q.category_id 
    GROUP BY c.id 
    ORDER BY c.id ASC
");
$categories = [];
$category_names = [];
$category_totals = [];
while($cat = $categories_query->fetch_assoc()) {
    $categories[] = $cat;
    $category_names[$cat['id']] = $cat['category_name'];
    // Store the maximum possible score for this category (total questions)
    $category_totals[$cat['id']] = 10; // Default to 10 if no questions
}

// Get selected category from filter (default to 'overall')
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'overall';

// Get only the user's section
$section_data = null;

if($user_section_id) {
    // Get section info
    $section_query = $conn->prepare("SELECT * FROM sections WHERE id = ?");
    $section_query->bind_param("i", $user_section_id);
    $section_query->execute();
    $section_result = $section_query->get_result();
    $section = $section_result->fetch_assoc();
    
    if($section) {
        $section_name = $section['section_name'];
        
        // Get all students in this section
        $students_query = $conn->prepare("
            SELECT id, first_name, last_name, student_id 
            FROM users 
            WHERE section_id = ? AND role = 'student'
            ORDER BY last_name
        ");
        $students_query->bind_param("i", $user_section_id);
        $students_query->execute();
        $students_result = $students_query->get_result();
        
        $students_data = [];
        
        while($student_row = $students_result->fetch_assoc()) {
            $student_id = $student_row['id'];
            
            // Initialize category scores with 0
            $category_scores = [];
            foreach($categories as $category) {
                $cat_id = $category['id'];
                $category_scores[$cat_id] = 0;
            }
            
            // Get the highest score per category (ignore multiple attempts)
            $attempts_query = $conn->prepare("
                SELECT category_id, MAX(score) AS best_score
                FROM quiz_attempts
                WHERE user_id = ?
                GROUP BY category_id
            ");
            $attempts_query->bind_param("i", $student_id);
            $attempts_query->execute();
            $attempts_result = $attempts_query->get_result();

            // Store highest score per category
            while($attempt = $attempts_result->fetch_assoc()) {
                $cat_id = $attempt['category_id'];
                $best_score = min($attempt['best_score'], 10); // prevent score > 10
                
                if(isset($category_scores[$cat_id])) {
                    $category_scores[$cat_id] = $best_score;
                }
            }

            // Calculate total score AFTER collecting best scores
            $total_score = array_sum($category_scores);
            
            // Add student data to the array
            $students_data[] = [
                'id' => $student_id,
                'name' => $student_row['first_name'] . ' ' . $student_row['last_name'],
                'student_id' => $student_row['student_id'],
                'category_scores' => $category_scores,
                'total_score' => $total_score
            ];
        }
        
        // Sort students based on selected category
        if($selected_category == 'overall') {
            // Sort by total score (descending)
            usort($students_data, function($a, $b) {
                return $b['total_score'] <=> $a['total_score'];
            });
        } else {
            // Sort by the selected category score (descending)
            usort($students_data, function($a, $b) use ($selected_category) {
                $score_a = isset($a['category_scores'][$selected_category]) ? $a['category_scores'][$selected_category] : 0;
                $score_b = isset($b['category_scores'][$selected_category]) ? $b['category_scores'][$selected_category] : 0;
                return $score_b <=> $score_a;
            });
        }
        
        $section_data = [
            'section_id' => $user_section_id,
            'section_name' => $section_name,
            'students' => $students_data,
            'student_count' => count($students_data)
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATH QUIZ - My Section Leaderboard</title>
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

        /* Leaderboard Container */
        .leaderboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
            border-bottom: 3px solid #1a492b;
            padding-bottom: 10px;
            display: inline-block;
        }

        /* Filter Section - UPDATED: Buttons instead of dropdown */
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .filter-label {
            font-weight: bold;
            color: #1a492b;
            font-size: 16px;
            margin-bottom: 15px;
            display: block;
        }

        .filter-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: #f8f9fa;
            color: #333;
            border: 2px solid #e0e0e0;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .filter-btn:hover {
            background: #1a492b;
            color: white;
            border-color: #1a492b;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(26,73,43,0.2);
        }

        .filter-btn.active {
            background: #1a492b;
            color: white;
            border-color: #1a492b;
        }

        .filter-btn.overall {
            background: #e8f5e9;
            border-color: #1a492b;
            font-weight: bold;
        }

        .filter-btn.overall:hover,
        .filter-btn.overall.active {
            background: #1a492b;
            color: white;
        }

        /* Section Card */
        .section-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
        }

        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .section-header {
            background: linear-gradient(135deg, #1a492b 0%, #2e7d45 100%);
            color: white;
            padding: 20px;
        }

        .section-title {
            font-size: 24px;
            font-weight: bold;
        }

        .section-body {
            padding: 25px;
            overflow-x: auto;
        }

        /* Leaderboard Table */
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .leaderboard-table th {
            text-align: center;
            padding: 15px 10px;
            background: #f8f9fa;
            color: #333;
            font-size: 15px;
            border-bottom: 2px solid #1a492b;
        }

        .leaderboard-table td {
            text-align: center;
            padding: 15px 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .leaderboard-table th:first-child,
        .leaderboard-table td:first-child {
            text-align: center;
            width: 80px;
        }

        .leaderboard-table th:nth-child(2),
        .leaderboard-table td:nth-child(2) {
            text-align: left;
        }

        .rank-1 {
            background: rgba(255, 215, 0, 0.1);
        }

        .rank-2 {
            background: rgba(192, 192, 192, 0.1);
        }

        .rank-3 {
            background: rgba(205, 127, 50, 0.1);
        }

        .current-user {
            background: rgba(26, 73, 43, 0.1);
            font-weight: bold;
            border-left: 4px solid #1a492b;
        }

        .rank-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
            font-size: 14px;
        }

        .rank-1-badge {
            background: gold;
            color: #333;
        }

        .rank-2-badge {
            background: silver;
            color: #333;
        }

        .rank-3-badge {
            background: #cd7f32;
            color: white;
        }

        .rank-other {
            font-weight: bold;
            color: #666;
        }

        .student-name {
            font-weight: 600;
            color: #333;
        }

        .student-id {
            font-size: 12px;
            color: #666;
        }

        .score-cell {
            font-weight: bold;
            font-size: 16px;
        }

        .score-high {
            color: #00c53e;
        }

        .score-medium {
            color: #ff9800;
        }

        .score-low {
            color: #dc3545;
        }

        .overall-score {
            font-weight: bold;
            background: #1a492b;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
            min-width: 50px;
        }

        /* Empty State */
        .no-data {
            text-align: center;
            padding: 60px;
            color: #999;
            font-style: italic;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .no-data h2 {
            color: #666;
            margin-bottom: 10px;
        }

        .no-data p {
            margin-top: 10px;
            font-size: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo-section h1 {
                font-size: 20px;
            }
            
            .logout-btn span:last-child {
                display: none;
            }
            
            .filter-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .filter-btn {
                width: 100%;
                text-align: center;
            }
            
            .section-body {
                padding: 15px;
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
            <li class="menu-item"><a href="quizzes.php" class="menu-link">📝 Quizzes</a></li>
            <li class="menu-item"><a href="leaderboards.php" class="menu-link active">🏆 Leaderboard</a></li>
            <li class="menu-item"><a href="student_account.php" class="menu-link">⚙️ Manage Account</a></li>
        </ul>
    </div>

    <!-- Main Content - LEADERBOARD PAGE (My Section Only) -->
    <div class="main-content">
        <div class="leaderboard-container">
            <h1 class="page-title">🏆 My Section Leaderboard</h1>

            <!-- Filter Section - UPDATED: Buttons instead of dropdown -->
            <div class="filter-section">
                <span class="filter-label">Filter by Category:</span>
                <div class="filter-buttons">
                    <!-- Overall Button -->
                    <a href="?category=overall" 
                       class="filter-btn overall <?php echo $selected_category == 'overall' ? 'active' : ''; ?>">
                        📊 Overall Score
                    </a>
                    
                    <!-- Category Buttons -->
                    <?php foreach($categories as $category): ?>
                        <a href="?category=<?php echo $category['id']; ?>" 
                           class="filter-btn <?php echo $selected_category == $category['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if($section_data && $section_data['student_count'] > 0): ?>
                <!-- Single Section Card -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title"><?php echo htmlspecialchars($section_data['section_name']); ?></div>
                        <div style="font-size: 14px; opacity: 0.9; margin-top: 5px;">
                            Total Students: <?php echo $section_data['student_count']; ?>
                        </div>
                    </div>
                    
                    <div class="section-body">
                        <table class="leaderboard-table">
                            <thead>
                                <tr>
                                    <th>RANK</th>
                                    <th>STUDENT</th>
                                    <?php foreach($categories as $category): ?>
                                        <th><?php echo htmlspecialchars($category['category_name']); ?></th>
                                    <?php endforeach; ?>
                                    <th>OVERALL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach($section_data['students'] as $student_data): 
                                    $rank_class = '';
                                    $rank_badge_class = 'rank-other';
                                    $rank_display = $rank;
                                    
                                    if($rank == 1) {
                                        $rank_class = 'rank-1';
                                        $rank_badge_class = 'rank-1-badge';
                                    } elseif($rank == 2) {
                                        $rank_class = 'rank-2';
                                        $rank_badge_class = 'rank-2-badge';
                                    } elseif($rank == 3) {
                                        $rank_class = 'rank-3';
                                        $rank_badge_class = 'rank-3-badge';
                                    }
                                    
                                    $is_current_user = ($student_data['id'] == $user_id);
                                ?>
                                    <tr class="<?php echo $rank_class . ' ' . ($is_current_user ? 'current-user' : ''); ?>">
                                        <td>
                                            <span class="rank-badge <?php echo $rank_badge_class; ?>"><?php echo $rank_display; ?></span>
                                        </td>
                                        <td style="text-align: left;">
                                            <div class="student-name"><?php echo htmlspecialchars($student_data['name']); ?></div>
                                            <div class="student-id"><?php echo htmlspecialchars($student_data['student_id']); ?></div>
                                            <?php if($is_current_user): ?>
                                                <span style="font-size: 10px; color: #1a492b; font-weight: bold;">(You)</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php foreach($categories as $category): 
                                            $cat_id = $category['id'];
                                            $score = isset($student_data['category_scores'][$cat_id]) ? $student_data['category_scores'][$cat_id] : 0;
                                            $total = $category_totals[$cat_id];
                                            
                                            // Determine score color based on percentage
                                            $percentage = $total > 0 ? ($score / $total) * 100 : 0;
                                            $score_class = $percentage >= 80 ? 'score-high' : ($percentage >= 60 ? 'score-medium' : 'score-low');
                                        ?>
                                            <td class="score-cell <?php echo $score_class; ?>">
                                                <?php echo $score; ?>/<?php echo $total; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td><span class="overall-score"><?php echo $student_data['total_score']; ?></span></td>
                                    </tr>
                                <?php 
                                $rank++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Legend -->
                <div style="display: flex; justify-content: flex-end; gap: 20px; margin-top: 20px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 12px; height: 12px; background: #00c53e; border-radius: 3px;"></div>
                        <span style="font-size: 12px; color: #666;">High (80%+)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 12px; height: 12px; background: #ff9800; border-radius: 3px;"></div>
                        <span style="font-size: 12px; color: #666;">Medium (60-79%)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 12px; height: 12px; background: #dc3545; border-radius: 3px;"></div>
                        <span style="font-size: 12px; color: #666;">Low (Below 60%)</span>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- No Data Message -->
                <div class="no-data">
                    <h2>😕 No Data Available</h2>
                    <p>You are not assigned to any section or there are no students in your section.</p>
                    <?php if(!$user_section_id): ?>
                        <p style="margin-top: 15px; color: #dc3545;">Please contact your teacher to assign you to a section.</p>
                    <?php else: ?>
                        <p style="margin-top: 15px;">No quiz attempts have been recorded for your section yet.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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