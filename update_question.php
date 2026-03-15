<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log to file
ini_set('log_errors', 1);
ini_set('error_log', 'C:\xampp\php\logs\php_error_log');

header('Content-Type: application/json');

include("includes/db.php");

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Log received POST data
error_log("UPDATE_QUESTION.PHP - Received POST: " . print_r($_POST, true));

// Validate inputs
if (!isset($_POST['question_id']) || !isset($_POST['question_text']) || !isset($_POST['choices'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$question_id = intval($_POST['question_id']);
$question_text = trim($_POST['question_text']);
$correct_letter = $_POST['correct_letter'] ?? 'A';
$choices = $_POST['choices'];

error_log("Question ID: $question_id");
error_log("Question Text: $question_text");
error_log("Correct Letter: $correct_letter");
error_log("Choices: " . print_r($choices, true));

if (empty($question_text)) {
    echo json_encode(['success' => false, 'message' => 'Question text is required']);
    exit;
}

if (count($choices) != 4) {
    echo json_encode(['success' => false, 'message' => '4 choices are required']);
    exit;
}

// Update question text
$stmt = $conn->prepare("UPDATE questions SET question_text = ? WHERE id = ?");
if (!$stmt) {
    error_log("Prepare failed for question update: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("si", $question_text, $question_id);
if (!$stmt->execute()) {
    error_log("Execute failed for question update: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Error updating question: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$stmt->close();
error_log("Question updated successfully");

// Get existing choice IDs
$result = $conn->query("SELECT id FROM choices WHERE question_id = $question_id ORDER BY id ASC");
if (!$result) {
    error_log("Error fetching choices: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Error fetching choices: ' . $conn->error]);
    exit;
}

$choice_ids = [];
while($row = $result->fetch_assoc()) {
    $choice_ids[] = $row['id'];
}

error_log("Found " . count($choice_ids) . " choices");

if (count($choice_ids) != 4) {
    error_log("Expected 4 choices, found " . count($choice_ids));
    echo json_encode(['success' => false, 'message' => 'Expected 4 choices, found ' . count($choice_ids)]);
    exit;
}

// Update choices
$letters = ['A','B','C','D'];
for($i = 0; $i < 4; $i++) {
    if (empty(trim($choices[$i]))) {
        echo json_encode(['success' => false, 'message' => 'All choices must have text']);
        exit;
    }
    
    $choice_text = $choices[$i];
    $is_correct = ($letters[$i] == $correct_letter) ? 1 : 0;
    $choice_id = $choice_ids[$i];
    
    error_log("Updating choice $choice_id: text='$choice_text', is_correct=$is_correct");
    
    $stmt = $conn->prepare("UPDATE choices SET choice_text = ?, is_correct = ? WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed for choices: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Prepare failed for choices']);
        exit;
    }
    
    $stmt->bind_param("sii", $choice_text, $is_correct, $choice_id);
    if (!$stmt->execute()) {
        error_log("Execute failed for choices: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Error updating choices: ' . $stmt->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();
    error_log("Choice $choice_id updated successfully");
}

error_log("All choices updated successfully");
echo json_encode(['success' => true]);
?>