<?php
session_start();
include("includes/db.php");

// Check if user is logged in and is a student
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student"){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = '';
$message_type = '';

// Get student information (for display only)
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

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to validate password policy
function validatePassword($password) {
    $errors = [];
    
    // Check minimum length
    if (strlen($password) < 6) {
        $errors[] = "At least 6 characters";
    }
    
    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "At least one capital letter (A-Z)";
    }
    
    // Check for number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "At least one number (0-9)";
    }
    
    // Check for spaces
    if (preg_match('/\s/', $password)) {
        $errors[] = "No spaces allowed";
    }
    
    return $errors;
}

// Handle password change form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current user's password from database
    $password_query = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $password_query->bind_param("i", $user_id);
    $password_query->execute();
    $password_result = $password_query->get_result();
    $user_data = $password_result->fetch_assoc();
    
    // Verify current password using password_verify
    if(password_verify($current_password, $user_data['password'])) {
        
        // Check if new password and confirm password match
        if($new_password == $confirm_password) {
            
            // Validate password policy
            $password_errors = validatePassword($new_password);
            
            if(empty($password_errors)) {
                // HASH THE NEW PASSWORD
                $hashed_password = hashPassword($new_password);
                
                // Update password in database with hashed version
                $update_query = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_query->bind_param("si", $hashed_password, $user_id);
                
                if($update_query->execute()) {
                    $message = "Password changed successfully!";
                    $message_type = "success";
                    
                    // Clear password fields
                    $new_password = $confirm_password = "";
                } else {
                    $message = "Error updating password. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "Password does not meet requirements:<br>" . implode("<br>", $password_errors);
                $message_type = "error";
            }
            
        } else {
            $message = "New password and confirm password do not match.";
            $message_type = "error";
        }
        
    } else {
        $message = "Current password is incorrect.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATH QUIZ - Manage Account</title>
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

        /* Logout Modal */
        .logout-modal {
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

        .logout-modal.show {
            display: flex;
        }

        .logout-modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .logout-modal-content h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .logout-modal-content p {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .logout-modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .logout-modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .logout-modal-btn.cancel {
            background: #6c757d;
            color: white;
        }

        .logout-modal-btn.cancel:hover {
            background: #5a6268;
        }

        .logout-modal-btn.confirm {
            background: #dc3545;
            color: white;
        }

        .logout-modal-btn.confirm:hover {
            background: #c82333;
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

        /* Account Container */
        .account-container {
            max-width: 600px;
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

        /* Account Info Card */
        .info-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .info-header {
            background: linear-gradient(135deg, #1a492b 0%, #2e7d45 100%);
            color: white;
            padding: 20px;
        }

        .info-header h2 {
            font-size: 20px;
            font-weight: bold;
        }

        .info-body {
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .info-row {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            width: 120px;
        }

        .info-value {
            color: #333;
            flex: 1;
        }

        /* Password Change Card */
        .password-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .password-header {
            background: linear-gradient(135deg, #1a492b 0%, #2e7d45 100%);
            color: white;
            padding: 20px;
        }

        .password-header h2 {
            font-size: 20px;
            font-weight: bold;
        }

        .password-body {
            padding: 25px;
        }

        /* Message Styles */
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1a492b;
        }

        .form-group input[type="password"] {
            font-family: 'Arial', sans-serif;
        }

        /* Password Policy */
        .password-policy {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0 20px;
        }

        .password-policy h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .policy-list {
            list-style: none;
        }

        .policy-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 0;
            color: #666;
            font-size: 14px;
        }

        .policy-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: default;
            accent-color: #1a492b;
        }

        .policy-item.valid {
            color: #00c53e;
        }

        .policy-item.invalid {
            color: #dc3545;
        }

        .btn-change {
            background: #1a492b;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            border: 2px solid #1a492b;
        }

        .btn-change:hover {
            background: white;
            color: #1a492b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26,73,43,0.3);
        }

        .btn-change:active {
            transform: translateY(0);
        }

        /* Info Note */
        .info-note {
            background: #e8f5e9;
            border-left: 4px solid #1a492b;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            color: #1a492b;
        }

        .info-note p {
            margin: 5px 0;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo-section h1 {
                font-size: 20px;
            }
            
            .logout-btn span:last-child {
                display: none;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .info-label {
                width: auto;
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
        
        <button class="logout-btn" id="logoutBtn">
            <span>🚪</span>
            <span>LOG OUT</span>
        </button>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="logout-modal" id="logoutModal">
        <div class="logout-modal-content">
            <h3>🚪 Log Out</h3>
            <p>Are you sure you want to log out?</p>
            <div class="logout-modal-buttons">
                <button class="logout-modal-btn cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="logout-modal-btn confirm" onclick="confirmLogout()">Log Out</button>
            </div>
        </div>
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
            <li class="menu-item"><a href="leaderboards.php" class="menu-link">🏆 Leaderboard</a></li>
            <li class="menu-item"><a href="student_account.php" class="menu-link active">⚙️ Manage Account</a></li>
        </ul>
    </div>

    <!-- Main Content - MANAGE ACCOUNT PAGE -->
    <div class="main-content">
        <div class="account-container">
            <h1 class="page-title">⚙️ Manage Account</h1>

            <!-- Display Message -->
            <?php if($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Account Information Card (View Only) -->
            <div class="info-card">
                <div class="info-header">
                    <h2>Account Information</h2>
                </div>
                <div class="info-body">
                    <div class="info-row">
                        <span class="info-label">Full Name:</span>
                        <span class="info-value">
                            <?php 
                            echo htmlspecialchars($student['first_name'] . ' ' . 
                                 (!empty($student['middle_initial']) ? $student['middle_initial'] . '. ' : '') . 
                                 $student['last_name']); 
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Student ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Section:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['section_name'] ?? 'No Section'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="password-card">
                <div class="password-header">
                    <h2>Change Password</h2>
                </div>
                <div class="password-body">
                    <form method="POST" action="" id="changePasswordForm">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <!-- Password Policy -->
                        <div class="password-policy">
                            <h4>Password must contain:</h4>
                            <ul class="policy-list" id="passwordPolicy">
                                <li class="policy-item" id="length">
                                    <input type="checkbox" id="lengthCheck" disabled> At least 6 characters
                                </li>
                                <li class="policy-item" id="uppercase">
                                    <input type="checkbox" id="uppercaseCheck" disabled> At least one capital letter (A-Z)
                                </li>
                                <li class="policy-item" id="number">
                                    <input type="checkbox" id="numberCheck" disabled> At least one number (0-9)
                                </li>
                                <li class="policy-item" id="space">
                                    <input type="checkbox" id="spaceCheck" disabled> No spaces allowed
                                </li>
                            </ul>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-change">
                            CHANGE PASSWORD
                        </button>
                    </form>
                    
                    <div class="info-note">
                        <p><strong>Note:</strong> Only password can be changed. For other account information changes (name, ID, section), please contact your teacher or administrator.</p>
                    </div>
                </div>
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

        // Logout modal functions
        function showLogoutModal() {
            document.getElementById('logoutModal').classList.add('show');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('show');
        }

        function confirmLogout() {
            window.location.href = 'index.php';
        }

        // Attach logout button event
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showLogoutModal();
                });
            }
        });

        // Real-time password policy validation
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            
            // Check length
            const lengthValid = password.length >= 6;
            document.getElementById('lengthCheck').checked = lengthValid;
            document.getElementById('length').className = 'policy-item ' + (lengthValid ? 'valid' : 'invalid');
            
            // Check uppercase
            const uppercaseValid = /[A-Z]/.test(password);
            document.getElementById('uppercaseCheck').checked = uppercaseValid;
            document.getElementById('uppercase').className = 'policy-item ' + (uppercaseValid ? 'valid' : 'invalid');
            
            // Check number
            const numberValid = /[0-9]/.test(password);
            document.getElementById('numberCheck').checked = numberValid;
            document.getElementById('number').className = 'policy-item ' + (numberValid ? 'valid' : 'invalid');
            
            // Check spaces
            const spaceValid = !/\s/.test(password);
            document.getElementById('spaceCheck').checked = spaceValid;
            document.getElementById('space').className = 'policy-item ' + (spaceValid ? 'valid' : 'invalid');
        });
        
        // Form validation before submit
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            const lengthValid = newPassword.length >= 6;
            const uppercaseValid = /[A-Z]/.test(newPassword);
            const numberValid = /[0-9]/.test(newPassword);
            const spaceValid = !/\s/.test(newPassword);
            
            if (!lengthValid || !uppercaseValid || !numberValid || !spaceValid) {
                e.preventDefault();
                alert('Please make sure your password meets all the requirements:\n' +
                      '- At least 6 characters\n' +
                      '- At least one capital letter (A-Z)\n' +
                      '- At least one number (0-9)\n' +
                      '- No spaces allowed');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New password and confirm password do not match.');
                return false;
            }
            
            return true;
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('logoutModal');
            if (event.target === modal) {
                closeLogoutModal();
            }
        });
    </script>
</body>
</html>