<?php
session_start();
include("includes/db.php");

// Check if user is logged in
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student"){
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$user_id = $_SESSION["user_id"];

// Get POST data
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$score = isset($_POST['score']) ? intval($_POST['score']) : 0;
$correct = isset($_POST['correct']) ? intval($_POST['correct']) : 0;
$wrong = isset($_POST['wrong']) ? intval($_POST['wrong']) : 0;
$total = isset($_POST['total']) ? intval($_POST['total']) : 0;
$attempt_number = isset($_POST['attempt_number']) ? intval($_POST['attempt_number']) : 0;
$passed = isset($_POST['passed']) ? intval($_POST['passed']) : 0;

// Validate data
if($category_id == 0 || $total == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Check if this attempt already exists (prevent duplicates)
$check_query = $conn->prepare("
    SELECT id FROM quiz_attempts 
    WHERE user_id = ? AND category_id = ? AND attempt_number = ?
");
$check_query->bind_param("iii", $user_id, $category_id, $attempt_number);
$check_query->execute();
$check_result = $check_query->get_result();

if($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Attempt already recorded']);
    exit();
}

// Insert the quiz attempt
$insert_query = $conn->prepare("
    INSERT INTO quiz_attempts 
    (user_id, category_id, attempt_number, score, correct_answers, wrong_answers, date_taken) 
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$insert_query->bind_param("iiiiii", $user_id, $category_id, $attempt_number, $score, $correct, $wrong);

if($insert_query->execute()) {
    echo json_encode(['success' => true, 'message' => 'Quiz attempt saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>