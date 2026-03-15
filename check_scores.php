<?php
session_start();
include("includes/db.php");

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher"){
    header("Location: index.php");
    exit();
}

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
$sort_category = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Toggle sort order for next click
$next_order = ($sort_order == 'asc') ? 'desc' : 'asc';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATH QUIZ - Student Scores</title>
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

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 30px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h2 {
            color: #333;
            font-size: 28px;
            border-bottom: 3px solid #1a492b;
            padding-bottom: 10px;
            display: inline-block;
        }

        /* Section Filter */
        .section-filter {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .section-filter label {
            font-weight: bold;
            color: #555;
        }

        .section-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .section-btn {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .section-btn:hover {
            background: #1a492b;
            color: white;
            border-color: #1a492b;
        }

        .section-btn.active {
            background: #1a492b;
            color: white;
            border-color: #1a492b;
        }

        /* Sortable Headers */
        .sortable-header {
            cursor: pointer;
            position: relative;
            padding-right: 25px !important;
        }

        .sortable-header:hover {
            background: rgba(255,255,255,0.2);
        }

        .sort-arrow {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
        }

        /* Student Scores Table */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow-x: auto;
            margin-bottom: 30px;
        }

        .scores-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .scores-table thead {
            background: linear-gradient(135deg, #1a492b 0%, #2e7d45 100%);
            color: white;
        }

        .scores-table th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 15px;
            position: relative;
        }

        .scores-table th:first-child {
            padding-left: 25px;
        }

        .scores-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }

        .scores-table tbody tr:hover {
            background: #f8f9fa;
        }

        .scores-table td {
            padding: 15px;
            color: #333;
        }

        .scores-table td:first-child {
            padding-left: 25px;
            font-weight: bold;
        }

        /* Score Container */
        .score-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .score-cell {
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 20px;
            display: inline-block;
            min-width: 70px;
            text-align: center;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .attempt-count {
            font-size: 11px;
            color: #999;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 10px;
            display: inline-block;
        }

        .score-high {
            background: #d4edda;
            color: #155724;
        }

        .score-medium {
            background: #fff3cd;
            color: #856404;
        }

        .score-low {
            background: #f8d7da;
            color: #721c24;
        }

        /* Student Info */
        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            background: #1a492b;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }

        .student-details {
            display: flex;
            flex-direction: column;
        }

        .student-name {
            font-weight: bold;
            color: #333;
        }

        .student-id {
            font-size: 12px;
            color: #999;
        }

        /* Legend */
        .legend {
            display: flex;
            justify-content: flex-end;
            gap: 30px;
            margin-top: 20px;
            padding: 15px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }

        .green {
            background: #d4edda;
            border: 1px solid #155724;
        }

        .yellow {
            background: #fff3cd;
            border: 1px solid #856404;
        }

        .red {
            background: #f8d7da;
            border: 1px solid #721c24;
        }

        .legend-text {
            font-size: 13px;
            color: #666;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .section-filter {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .legend {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
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
        
        <!-- Simple logout button -->
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h2>📋 Student Scores</h2>
        </div>

        <!-- Section Filter -->
        <div class="section-filter">
            <label>Filter by Section:</label>
            <div class="section-buttons">
                <?php
                $sections = $conn->query("SELECT * FROM sections ORDER BY section_name ASC");
                while($sec = $sections->fetch_assoc()): 
                    $active_class = ($section_id == $sec['id']) ? 'active' : '';
                ?>
                    <a href="check_scores.php?section_id=<?php echo $sec['id']; ?>&sort=<?php echo $sort_category; ?>&order=<?php echo $sort_order; ?>"
                        class="section-btn <?php echo $active_class; ?>">
                        <?php echo htmlspecialchars($sec['section_name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- If no section selected, show message -->
        <?php if($section_id == 0): ?>
            <div style="text-align: center; padding: 50px; background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
                <h3 style="color: #666;">Please select a section to view scores</h3>
                <p style="color: #999; margin-top: 10px;">Click on a section button above to see student scores.</p>
            </div>
        <?php else: ?>

        <!-- Student Scores Table -->
        <div class="table-container">
            <table class="scores-table">
                <thead>
                    <tr>
                        <th class="sortable-header" onclick="sortTable('name')">
                            Name
                            <span class="sort-arrow">
                                <?php if($sort_category == 'name'): ?>
                                    <?php echo $sort_order == 'asc' ? '↑' : '↓'; ?>
                                <?php endif; ?>
                            </span>
                        </th>
                        <?php
                        $categories = $conn->query("SELECT * FROM categories ORDER BY id ASC");
                        while($cat = $categories->fetch_assoc()): 
                            $cat_id = $cat['id'];
                        ?>
                        <th class="sortable-header" onclick="sortTable('<?php echo $cat_id; ?>')">
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                            <span class="sort-arrow">
                                <?php if($sort_category == $cat_id): ?>
                                    <?php echo $sort_order == 'asc' ? '↑' : '↓'; ?>
                                <?php endif; ?>
                            </span>
                        </th>
                        <?php endwhile; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Build student data array
                    $students_data = [];
                    
                    $students = $conn->query("
                        SELECT * FROM users
                        WHERE role = 'student'
                        AND section_id = $section_id
                        ORDER BY last_name ASC
                    ");
                    
                    // Get all categories first
                    $categories2 = $conn->query("SELECT * FROM categories ORDER BY id ASC");
                    $all_categories = [];
                    while($cat = $categories2->fetch_assoc()) {
                        $all_categories[] = $cat;
                    }
                    
                    while($student = $students->fetch_assoc()) {
                        $student_scores = [];
                        
                        foreach($all_categories as $cat) {
                            $cat_id = $cat['id'];
                            
                            // Get the highest score and attempt count for this category
                            $score_query = $conn->query("
                                SELECT MAX(score) as best_score, COUNT(*) as attempt_count
                                FROM quiz_attempts
                                WHERE user_id = {$student['id']}
                                AND category_id = $cat_id
                            ");
                            
                            $score_data = $score_query->fetch_assoc();
                            
                            // Get total questions for this category (limit to 10)
                            $total_query = $conn->query("
                                SELECT COUNT(*) as total 
                                FROM questions 
                                WHERE category_id = $cat_id
                            ");
                            $total_row = $total_query->fetch_assoc();
                            $total = min($total_row['total'], 10); // Limit to 10
                            
                            $best_score = $score_data['best_score'] ? min($score_data['best_score'], 10) : 0;
                            $attempt_count = $score_data['attempt_count'] ? $score_data['attempt_count'] : 0;
                            
                            $student_scores[$cat_id] = [
                                'score' => $best_score,
                                'total' => $total,
                                'attempts' => $attempt_count
                            ];
                        }
                        
                        $students_data[] = [
                            'id' => $student['id'],
                            'first_name' => $student['first_name'],
                            'last_name' => $student['last_name'],
                            'student_id' => $student['student_id'],
                            'scores' => $student_scores
                        ];
                    }
                    
                    // Sort students based on selected category
                    if($sort_category == 'name') {
                        usort($students_data, function($a, $b) use ($sort_order) {
                            $name_a = $a['last_name'] . ' ' . $a['first_name'];
                            $name_b = $b['last_name'] . ' ' . $b['first_name'];
                            if($sort_order == 'asc') {
                                return strcmp($name_a, $name_b);
                            } else {
                                return strcmp($name_b, $name_a);
                            }
                        });
                    } else {
                        usort($students_data, function($a, $b) use ($sort_category, $sort_order) {
                            $score_a = isset($a['scores'][$sort_category]) ? $a['scores'][$sort_category]['score'] : 0;
                            $score_b = isset($b['scores'][$sort_category]) ? $b['scores'][$sort_category]['score'] : 0;
                            
                            if($sort_order == 'asc') {
                                return $score_a <=> $score_b;
                            } else {
                                return $score_b <=> $score_a;
                            }
                        });
                    }
                    
                    // Display sorted students
                    foreach($students_data as $student):
                    ?>
                    <tr>
                        <td>
                            <div class="student-info">
                                <span class="student-avatar">
                                    <?php
                                        echo strtoupper(substr($student['first_name'],0,1) .
                                        substr($student['last_name'],0,1));
                                    ?>
                                </span>
                                <div class="student-details">
                                    <span class="student-name">
                                        <?php echo htmlspecialchars($student['first_name'] . " " . $student['last_name']); ?>
                                    </span>
                                    <span class="student-id">
                                        <?php echo htmlspecialchars($student['student_id']); ?>
                                    </span>
                                </div>
                            </div>
                        </td>
                        
                        <?php foreach($all_categories as $cat): 
                            $cat_id = $cat['id'];
                            $score_info = $student['scores'][$cat_id];
                            $score = $score_info['score'];
                            $total = $score_info['total'];
                            $attempts = $score_info['attempts'];
                            
                            $percentage = ($total > 0) ? ($score / $total) * 100 : 0;
                            
                            if($percentage >= 80){
                                $class = "score-high";
                            } elseif($percentage >= 65){
                                $class = "score-medium";
                            } else {
                                $class = "score-low";
                            }
                        ?>
                        <td>
                            <div class="score-container">
                                <span class="score-cell <?php echo $class; ?>"><?php echo $score; ?>/<?php echo $total; ?></span>
                                <?php if($attempts > 0): ?>
                                    <span class="attempt-count"><?php echo $attempts; ?> attempt<?php echo $attempts > 1 ? 's' : ''; ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color green"></div>
                <span class="legend-text">High (80%+)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color yellow"></div>
                <span class="legend-text">Medium (65-79%)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color red"></div>
                <span class="legend-text">Low (Below 65%)</span>
            </div>
        </div>
        
        <?php endif; // End section_id check ?>
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

        // Sort table function - FIXED: Now sorts highest to lowest on first click
        function sortTable(category) {
            var url = new URL(window.location.href);
            var currentSort = url.searchParams.get('sort');
            var currentOrder = url.searchParams.get('order');
            
            // FIX: If clicking a new category, default to descending (highest first)
            // If clicking the same category, toggle the order
            var newOrder;
            
            if (currentSort != category) {
                // New category - sort descending (highest to lowest)
                newOrder = 'desc';
            } else {
                // Same category - toggle order
                newOrder = (currentOrder == 'asc') ? 'desc' : 'asc';
            }
            
            url.searchParams.set('sort', category);
            url.searchParams.set('order', newOrder);
            
            // Preserve section filter
            var sectionId = url.searchParams.get('section_id');
            if (!sectionId) {
                url.searchParams.set('section_id', '<?php echo $section_id; ?>');
            }
            
            window.location.href = url.toString();
        }
    </script>
</body>
</html>