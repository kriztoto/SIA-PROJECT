<?php
session_start();
include("includes/db.php");

// Check if user is teacher
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "teacher"){
    header("Location: index.php");
    exit();
}

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to generate next student ID
function generateStudentId($conn) {
    // Query to get the latest student ID
    $query = "SELECT student_id FROM users WHERE role = 'student' AND student_id LIKE 's%' ORDER BY student_id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_id = $row['student_id'];
        // Extract the number part (after 's')
        $number = intval(substr($last_id, 1));
        $new_number = $number + 1;
    } else {
        // Start with s2601001 if no existing students
        $new_number = 2601001;
    }
    
    return 's' . $new_number;
}

// Function to check if student ID exists
function isStudentIdExists($conn, $student_id, $exclude_id = null) {
    $sql = "SELECT id FROM users WHERE student_id = ? AND role = 'student'";
    if ($exclude_id) {
        $sql .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($sql);
    if ($exclude_id) {
        $stmt->bind_param("si", $student_id, $exclude_id);
    } else {
        $stmt->bind_param("s", $student_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Function to validate password policy
function validatePassword($password) {
    $errors = [];
    
    // Check if password contains spaces
    if (strpos($password, ' ') !== false) {
        $errors[] = "Password cannot contain spaces";
    }
    
    // Check if password contains at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one capital letter";
    }
    
    // Check if password contains at least one number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    // Check minimum length (optional)
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    return $errors;
}

// Handle student addition
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // Handle add
    if($_POST['action'] == 'add') {
        $first_name = trim($_POST['first_name']);
        $middle_initial = trim($_POST['middle_initial']);
        $last_name = trim($_POST['last_name']);
        
        // Use the generated student ID
        $student_id = $_POST['student_id']; // This will be the auto-generated ID
        
        $password = trim($_POST['password']);
        $section_id = $_POST['section_id'];
        
        // Validate required fields
        $errors = [];
        
        if(empty($first_name)) {
            $errors[] = "First name is required";
        }
        if(empty($last_name)) {
            $errors[] = "Last name is required";
        }
        if(empty($student_id)) {
            $errors[] = "Student ID is required";
        }
        if(empty($password)) {
            $errors[] = "Password is required";
        }
        if(empty($section_id)) {
            $errors[] = "Section is required";
        }
        
        // Validate password policy
        if(empty($errors) && !empty($password)) {
            $password_errors = validatePassword($password);
            if (!empty($password_errors)) {
                $errors = array_merge($errors, $password_errors);
            }
        }
        
        // Check if student ID already exists
        if(empty($errors) && isStudentIdExists($conn, $student_id)) {
            $errors[] = "Student ID '$student_id' is already taken. Please try again.";
        }
        
        if(empty($errors)) {
            // HASH THE PASSWORD BEFORE STORING
            $hashed_password = hashPassword($password);
            
            $stmt = $conn->prepare("INSERT INTO users (first_name, middle_initial, last_name, student_id, password, role, section_id) VALUES (?, ?, ?, ?, ?, 'student', ?)");
            $stmt->bind_param("sssssi", $first_name, $middle_initial, $last_name, $student_id, $hashed_password, $section_id);
            
            if($stmt->execute()) {
                $success = "Student added successfully! Student ID: " . $student_id;
                // Clear POST data to prevent resubmission
                $_POST = array();
            } else {
                $error = "Error adding student: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
    // Handle edit
    if($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $first_name = trim($_POST['first_name']);
        $middle_initial = trim($_POST['middle_initial']);
        $last_name = trim($_POST['last_name']);
        // Student ID is NOT updated - we keep the original
        $section_id = $_POST['section_id'];
        $password = trim($_POST['password']);
        
        // Validate required fields
        $errors = [];
        
        if(empty($first_name)) {
            $errors[] = "First name is required";
        }
        if(empty($last_name)) {
            $errors[] = "Last name is required";
        }
        if(empty($section_id)) {
            $errors[] = "Section is required";
        }
        
        // Validate password policy only if a new password is provided
        if(!empty($password)) {
            $password_errors = validatePassword($password);
            if (!empty($password_errors)) {
                $errors = array_merge($errors, $password_errors);
            }
        }
        
        if(empty($errors)) {
            if(!empty($password)) {
                // HASH THE NEW PASSWORD BEFORE UPDATING
                $hashed_password = hashPassword($password);
                
                // Update with password change
                $stmt = $conn->prepare("UPDATE users SET first_name=?, middle_initial=?, last_name=?, password=?, section_id=? WHERE id=? AND role='student'");
                $stmt->bind_param("ssssii", $first_name, $middle_initial, $last_name, $hashed_password, $section_id, $id);
            } else {
                // Update without password change
                $stmt = $conn->prepare("UPDATE users SET first_name=?, middle_initial=?, last_name=?, section_id=? WHERE id=? AND role='student'");
                $stmt->bind_param("sssii", $first_name, $middle_initial, $last_name, $section_id, $id);
            }
            
            if($stmt->execute()) {
                $success = "Student updated successfully!";
                // Clear POST data
                $_POST = array();
            } else {
                $error = "Error updating student: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
    // Handle delete
    if($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        
        // Optional: Check if student has quiz attempts before deleting
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM quiz_attempts WHERE user_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $attempts = $result->fetch_assoc()['count'];
        $check_stmt->close();
        
        if($attempts > 0) {
            $error = "Cannot delete student because they have $attempts quiz attempt(s). Consider deactivating instead.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='student'");
            $stmt->bind_param("i", $id);
            
            if($stmt->execute()) {
                $success = "Student deleted successfully!";
            } else {
                $error = "Error deleting student: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all sections
$sections = $conn->query("SELECT * FROM sections ORDER BY section_name ASC");

// Get selected section from filter (default to first section)
$selected_section_id = isset($_GET['section']) ? intval($_GET['section']) : 0;

// If no section selected, get the first section
if($selected_section_id == 0) {
    $sections->data_seek(0);
    $first_section = $sections->fetch_assoc();
    $selected_section_id = $first_section ? $first_section['id'] : 0;
    $sections->data_seek(0); // Reset pointer
}

// Fetch students based on selected section
if($selected_section_id > 0) {
    $students_query = "
        SELECT u.*, s.section_name 
        FROM users u 
        LEFT JOIN sections s ON u.section_id = s.id 
        WHERE u.role = 'student' AND u.section_id = $selected_section_id
        ORDER BY u.last_name ASC, u.first_name ASC
    ";
} else {
    $students_query = "
        SELECT u.*, s.section_name 
        FROM users u 
        LEFT JOIN sections s ON u.section_id = s.id 
        WHERE u.role = 'student'
        ORDER BY u.last_name ASC, u.first_name ASC
    ";
}
$students = $conn->query($students_query);

// Generate a new student ID for the add form
$new_student_id = generateStudentId($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATH QUIZ - Manage Students</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        /* Hide autofill background and suggestions */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px white inset !important;
            box-shadow: 0 0 0 30px white inset !important;
            -webkit-text-fill-color: #333 !important;
            caret-color: #333;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Hide autofill icons and buttons */
        input::-webkit-contacts-auto-fill-button,
        input::-webkit-credentials-auto-fill-button,
        input::-webkit-autofill-button,
        input::-webkit-autofill-preview,
        input::-webkit-autofill-selected {
            visibility: hidden;
            display: none !important;
            pointer-events: none;
            height: 0;
            width: 0;
            margin: 0;
            opacity: 0;
        }

        /* For Firefox */
        input {
            filter: none;
        }

        /* Prevent browser from remembering form data */
        form {
            autocomplete: off;
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
            text-decoration: none;
            border: none;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Sidebar */
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
            display: block;
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
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            color: #333;
            font-size: 28px;
            border-bottom: 3px solid #1a492b;
            padding-bottom: 10px;
        }

        .add-student-btn {
            background: #1a492b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .add-student-btn:hover {
            background: #2e7d45;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Search and Filter Section - UPDATED with search bar */
        .filter-section {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .section-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .section-label {
            font-weight: bold;
            color: #1a492b;
            margin-right: 5px;
        }

        .section-filter-btn {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .section-filter-btn:hover {
            background: #1a492b;
            color: white;
            border-color: #1a492b;
        }

        .section-filter-btn.active {
            background: #1a492b;
            color: white;
            border-color: #1a492b;
        }

        .search-box {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #1a492b;
        }

        .search-box button {
            background: #1a492b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .search-box button:hover {
            background: #2e7d45;
        }

        .search-box button.reset-btn {
            background: #6c757d;
        }

        .search-box button.reset-btn:hover {
            background: #5a6268;
        }

        /* Student Table */
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .student-table thead {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 10;
        }

        .student-table th {
            color: #555;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 10px;
            text-align: left;
            border-bottom: 2px solid #e0e0e0;
        }

        .student-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 14px;
        }

        .student-table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Number column */
        .number-col {
            font-weight: bold;
            color: #1a492b;
            width: 50px;
            text-align: center;
        }

        /* Password column */
        .password-col {
            font-family: monospace;
        }

        .password-dots {
            font-size: 16px;
            color: #666;
            cursor: default;
            pointer-events: none;
        }

        /* Section badge */
        .section-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: #e8f5e9;
            color: #1a492b;
        }

        /* Action buttons */
        .action-btn {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin: 0 3px;
            padding: 5px;
            border-radius: 3px;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #f0f0f0;
        }

        .action-btn.edit:hover {
            color: #ffc107;
        }

        .action-btn.delete:hover {
            color: #dc3545;
        }

        /* Validation styles */
        .validation-error {
            border-color: #dc3545 !important;
        }
        
        .validation-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        
        .validation-message.show {
            display: block;
        }
        
        .input-group {
            position: relative;
        }
        
        .checking-indicator {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #999;
            display: none;
        }
        
        .checking-indicator.show {
            display: inline;
        }

        /* Password policy styles */
        .password-policy {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-top: 5px;
            font-size: 12px;
        }

        .policy-item {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 3px;
            color: #666;
        }

        .policy-item.valid {
            color: #28a745;
        }

        .policy-item.invalid {
            color: #dc3545;
        }

        .policy-icon {
            font-size: 14px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #333;
            font-size: 20px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        .close-btn:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1a492b;
        }

        .form-group input:disabled,
        .form-group input[readonly] {
            background-color: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-primary {
            background: #1a492b;
            color: white;
        }

        .btn-primary:hover {
            background: #2e7d45;
        }

        .btn-primary:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        small {
            color: #999;
            font-size: 12px;
        }

        /* Auto-generate badge */
        .auto-generate-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #1a492b;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 5px;
        }

        /* Student ID display */
        .student-id-display {
            background: #f5f5f5;
            padding: 10px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-weight: bold;
            color: #1a492b;
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
        
        <!-- Simplified logout button -->
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
            <h2>👥 Manage Students</h2>
            <button class="add-student-btn" onclick="openAddModal()">
                <span>➕</span> Add Student
            </button>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Filter Section with Section Buttons and Search Bar -->
        <div class="filter-section">
            <div class="section-buttons">
                <span class="section-label">Filter by Section:</span>
                <?php 
                $sections->data_seek(0);
                while($section = $sections->fetch_assoc()): 
                    $active_class = ($section['id'] == $selected_section_id) ? 'active' : '';
                ?>
                    <a href="manage_students.php?section=<?php echo $section['id']; ?>" 
                       class="section-filter-btn <?php echo $active_class; ?>">
                        <?php echo htmlspecialchars($section['section_name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
            
            <!-- Search Bar -->
            <div class="search-box">
                <input type="text" placeholder="Search by name or ID..." id="searchInput" onkeyup="filterTable()">
                <button onclick="filterTable()">Search</button>
                <button class="reset-btn" onclick="resetFilters()">Clear</button>
            </div>
        </div>

        <!-- Students Table -->
        <div class="table-container">
            <table class="student-table" id="studentTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>MI</th>
                        <th>Student ID</th>
                        <th>Password</th>
                        <th>Section</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php 
                    $counter = 1;
                    if($students->num_rows > 0):
                        while($student = $students->fetch_assoc()): 
                    ?>
                    <tr id="student-<?php echo $student['id']; ?>">
                        <td class="number-col"><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['middle_initial']); ?></td>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                        <td class="password-col">
                            <span class="password-dots">••••••••</span>
                        </td>
                        <td>
                            <span class="section-badge">
                                <?php echo htmlspecialchars($student['section_name'] ?? 'No Section'); ?>
                            </span>
                        </td>
                        <td>
                            <button class="action-btn edit" onclick="editStudent(<?php echo $student['id']; ?>)">✏️</button>
                            <button class="action-btn delete" onclick="deleteStudent(<?php echo $student['id']; ?>)">🗑️</button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 50px; color: #999;">
                            No students found in this section.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Student Modal -->
    <div class="modal" id="studentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Student</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="studentForm" onsubmit="return validateForm()" autocomplete="off">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="studentId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" id="lastName" required 
                               autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" id="firstName" required 
                               autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>
                    <div class="form-group">
                        <label>MI</label>
                        <input type="text" name="middle_initial" id="middleInitial" maxlength="5"
                               autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>
                </div>

                <div class="form-group" id="studentIdFieldGroup">
                    <label>Student ID *</label>
                    <div id="studentIdDisplay" class="student-id-display"></div>
                    <input type="hidden" name="student_id" id="studentIdField">
                    <small>Auto-generated ID <span class="auto-generate-badge">Auto</span></small>
                </div>

                <div class="form-group">
                    <label>Password <span id="passwordRequired">*</span></label>
                    <input type="text" name="password" id="passwordField" placeholder="Enter password" onkeyup="checkPasswordPolicy()" oninput="checkPasswordPolicy()"
                           autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                    <small id="passwordHelp">Required for new students</small>
                    
                    <!-- Password Policy Indicator -->
                    <div class="password-policy" id="passwordPolicy">
                        <div class="policy-item" id="policyLength">
                            <span class="policy-icon">⚪</span>
                            <span>At least 6 characters</span>
                        </div>
                        <div class="policy-item" id="policyUppercase">
                            <span class="policy-icon">⚪</span>
                            <span>At least one capital letter (A-Z)</span>
                        </div>
                        <div class="policy-item" id="policyNumber">
                            <span class="policy-icon">⚪</span>
                            <span>At least one number (0-9)</span>
                        </div>
                        <div class="policy-item" id="policyNoSpaces">
                            <span class="policy-icon">⚪</span>
                            <span>No spaces allowed</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Section *</label>
                    <select name="section_id" id="sectionSelect" required>
                        <option value="">Select Section</option>
                        <?php 
                        $sections->data_seek(0);
                        while($section = $sections->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $section['id']; ?>">
                                <?php echo htmlspecialchars($section['section_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <p style="margin: 20px 0;">Are you sure you want to delete this student?</p>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteStudentId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #dc3545;">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Store all rows data for filtering
        let allRows = [];

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('#studentTable tbody tr');
            rows.forEach((row, index) => {
                if (row.cells.length > 1 && row.cells[0].textContent !== 'No students found in this section.') {
                    allRows.push({
                        element: row,
                        index: index,
                        text: row.textContent.toLowerCase()
                    });
                }
            });
            console.log('Rows loaded for search:', allRows.length);
            
            // Initialize password policy check
            checkPasswordPolicy();
        });

        // Toggle sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('show');
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.querySelector('.hamburger');
            
            if (sidebar && hamburger) {
                if (!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
                    sidebar.classList.remove('open');
                    document.getElementById('overlay').classList.remove('show');
                }
            }
        });

        // Search function
        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            if (allRows.length === 0) return;
            
            allRows.forEach(row => {
                if (searchTerm && !row.text.includes(searchTerm)) {
                    row.element.style.display = 'none';
                } else {
                    row.element.style.display = '';
                }
            });
        }

        // Reset search
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            allRows.forEach(row => {
                row.element.style.display = '';
            });
        }

        // Check password policy
        function checkPasswordPolicy() {
            const password = document.getElementById('passwordField').value;
            const action = document.getElementById('formAction').value;
            
            if (action === 'edit' && !password) {
                document.getElementById('policyLength').className = 'policy-item';
                document.getElementById('policyUppercase').className = 'policy-item';
                document.getElementById('policyNumber').className = 'policy-item';
                document.getElementById('policyNoSpaces').className = 'policy-item';
                
                document.querySelectorAll('.policy-icon').forEach(icon => {
                    icon.textContent = '⚪';
                });
                return;
            }
            
            const lengthValid = password.length >= 6;
            const lengthItem = document.getElementById('policyLength');
            lengthItem.className = 'policy-item ' + (lengthValid ? 'valid' : 'invalid');
            lengthItem.querySelector('.policy-icon').textContent = lengthValid ? '✅' : '❌';
            
            const uppercaseValid = /[A-Z]/.test(password);
            const uppercaseItem = document.getElementById('policyUppercase');
            uppercaseItem.className = 'policy-item ' + (uppercaseValid ? 'valid' : 'invalid');
            uppercaseItem.querySelector('.policy-icon').textContent = uppercaseValid ? '✅' : '❌';
            
            const numberValid = /[0-9]/.test(password);
            const numberItem = document.getElementById('policyNumber');
            numberItem.className = 'policy-item ' + (numberValid ? 'valid' : 'invalid');
            numberItem.querySelector('.policy-icon').textContent = numberValid ? '✅' : '❌';
            
            const noSpacesValid = !password.includes(' ');
            const noSpacesItem = document.getElementById('policyNoSpaces');
            noSpacesItem.className = 'policy-item ' + (noSpacesValid ? 'valid' : 'invalid');
            noSpacesItem.querySelector('.policy-icon').textContent = noSpacesValid ? '✅' : '❌';
        }

        // Validate form before submit
        function validateForm() {
            const action = document.getElementById('formAction').value;
            const password = document.getElementById('passwordField').value;
            
            if (action === 'add') {
                if (!password) {
                    alert('Password is required for new students');
                    return false;
                }
                
                const lengthValid = password.length >= 6;
                const uppercaseValid = /[A-Z]/.test(password);
                const numberValid = /[0-9]/.test(password);
                const noSpacesValid = !password.includes(' ');
                
                if (!lengthValid || !uppercaseValid || !numberValid || !noSpacesValid) {
                    alert('Password must meet all requirements:\n' +
                          '- At least 6 characters long\n' +
                          '- At least one capital letter\n' +
                          '- At least one number\n' +
                          '- No spaces allowed');
                    return false;
                }
            } else if (action === 'edit' && password) {
                const lengthValid = password.length >= 6;
                const uppercaseValid = /[A-Z]/.test(password);
                const numberValid = /[0-9]/.test(password);
                const noSpacesValid = !password.includes(' ');
                
                if (!lengthValid || !uppercaseValid || !numberValid || !noSpacesValid) {
                    alert('New password must meet all requirements:\n' +
                          '- At least 6 characters long\n' +
                          '- At least one capital letter\n' +
                          '- At least one number\n' +
                          '- No spaces allowed');
                    return false;
                }
            }
            
            return true;
        }

        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Student';
            document.getElementById('formAction').value = 'add';
            document.getElementById('studentForm').reset();
            document.getElementById('studentId').value = '';
            
            const newId = '<?php echo $new_student_id; ?>';
            document.getElementById('studentIdField').value = newId;
            document.getElementById('studentIdDisplay').textContent = newId;
            
            document.getElementById('passwordField').required = true;
            document.getElementById('passwordRequired').style.display = 'inline';
            document.getElementById('passwordHelp').textContent = 'Required for new students';
            
            document.getElementById('policyLength').className = 'policy-item';
            document.getElementById('policyUppercase').className = 'policy-item';
            document.getElementById('policyNumber').className = 'policy-item';
            document.getElementById('policyNoSpaces').className = 'policy-item';
            document.querySelectorAll('.policy-icon').forEach(icon => {
                icon.textContent = '⚪';
            });
            
            document.getElementById('studentModal').classList.add('show');
        }

        function editStudent(id) {
            document.getElementById('modalTitle').textContent = 'Edit Student';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('studentId').value = id;
            
            const row = document.getElementById(`student-${id}`);
            const cells = row.cells;
            
            document.getElementById('lastName').value = cells[1].textContent;
            document.getElementById('firstName').value = cells[2].textContent;
            document.getElementById('middleInitial').value = cells[3].textContent;
            
            const studentIdValue = cells[4].textContent;
            document.getElementById('studentIdField').value = studentIdValue;
            document.getElementById('studentIdDisplay').textContent = studentIdValue;
            
            document.getElementById('passwordField').value = '';
            document.getElementById('passwordField').required = false;
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('passwordHelp').textContent = 'Leave blank to keep current password';
            
            document.getElementById('policyLength').className = 'policy-item';
            document.getElementById('policyUppercase').className = 'policy-item';
            document.getElementById('policyNumber').className = 'policy-item';
            document.getElementById('policyNoSpaces').className = 'policy-item';
            document.querySelectorAll('.policy-icon').forEach(icon => {
                icon.textContent = '⚪';
            });
            
            const sectionText = cells[6].textContent.trim();
            const select = document.getElementById('sectionSelect');
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].text === sectionText) {
                    select.value = select.options[i].value;
                    break;
                }
            }
            
            document.getElementById('studentModal').classList.add('show');
        }

        function deleteStudent(id) {
            document.getElementById('deleteStudentId').value = id;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('studentModal').classList.remove('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('studentModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === modal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>