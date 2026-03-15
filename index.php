<?php
session_start();
include("includes/db.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $school_id = $_POST["student_id"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE student_id = ?");
    $stmt->bind_param("s", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1){

        $user = $result->fetch_assoc();

        // Use password_verify() to check hashed password
        if(password_verify($password, $user['password'])){

            $_SESSION["user_id"] = $user['id'];
            $_SESSION["role"] = $user['role'];
            $_SESSION["name"] = $user['first_name'];

            // Redirect based on role
            if($user['role'] == 'teacher'){
                header("Location: teacher_page.php");
            } else {
                header("Location: student_page.php");
            }
            exit();

        } else {
            $error = "Invalid password!";
        }

    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>MATH QUIZ - Login</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-image: url('bg_green.jpg');
            background-size: cover; 
            background-size: contain;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            font-family: 'Arial', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            flex-direction: column;  
        }

        #logo {
            color: white;
            font-size: 48px;
            margin-bottom: 20px;
            position: relative;
            top: -20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        #login {
            background: #eaeae4;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }

        #login h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }

        #login label {
            display: block;
            color: #333;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        #login input[type="text"],
        #login input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            margin-bottom: 20px;  
            transition: border-color 0.3s;
        }

        #login input:focus {
            outline: none;
            border-color: #1a492b;
        }

        #login button {
            background: #1a492b;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        #login button:hover {
            background: #2e7d45;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(26,73,43,0.4);
        }

        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }

        @media (max-width: 480px) {
            #logo {
                font-size: 28px;
            }
            
            #login {
                padding: 30px 20px;
                margin: 15px;
            }
        }
    </style>

    <!-- Add logout confirmation modal CSS if needed on other pages -->
    <style>
        /* Logout Modal - for other pages, not needed here */
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
    </style>
</head>

<body>
    <h1 id="logo">MATH QUIZ</h1>

    <div id="login">
        <h2>Login</h2>

        <?php if(isset($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <label>Student ID:</label>
            <input type="text" name="student_id" required autocomplete="off"
                   value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">

            <label>Password:</label>
            <input type="password" name="password" required autocomplete="off">

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>