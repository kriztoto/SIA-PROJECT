<?php
include("configs/db.php");

header('Content-Type: application/json');

if(isset($_POST['question_id'])){
    $id = intval($_POST['question_id']);
    
    // Use prepared statements for safety
    $stmt = $conn->prepare("DELETE FROM choices WHERE question_id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error deleting choices']);
        exit;
    }
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error deleting question']);
        exit;
    }
    $stmt->close();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No question ID provided']);
}
?>