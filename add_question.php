<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header first
header('Content-Type: application/json');

session_start();
include("includes/db.php");

// Check if database connection exists
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Make sure we received POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required fields
if (!isset($_POST['category_id']) || !isset($_POST['question_text']) || !isset($_POST['choices'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$category_id = intval($_POST['category_id']);
$question_text = trim($_POST['question_text']);
$correct_letter = $_POST['correct_letter'] ?? 'A';
$choices = $_POST['choices'] ?? [];

if (empty($question_text)) {
    echo json_encode(['success' => false, 'message' => 'Question text is required']);
    exit;
}

if (count($choices) != 4) {
    echo json_encode(['success' => false, 'message' => '4 choices are required']);
    exit;
}

// Insert question
$stmt = $conn->prepare("INSERT INTO questions (category_id, question_text) VALUES (?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("is", $category_id, $question_text);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}

$question_id = $stmt->insert_id;
$stmt->close();

// Insert choices
$letters = ['A','B','C','D'];
foreach ($choices as $i => $choice_text) {
    if (empty(trim($choice_text))) {
        echo json_encode(['success' => false, 'message' => 'All choices must have text']);
        exit;
    }
    
    $is_correct = ($letters[$i] === $correct_letter) ? 1 : 0;
    $stmt = $conn->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed for choices']);
        exit;
    }
    
    $stmt->bind_param("isi", $question_id, $choice_text, $is_correct);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Execute failed for choices: ' . $stmt->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();
}

echo json_encode(['success' => true]);
exit;
?>