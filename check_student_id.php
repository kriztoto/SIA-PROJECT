<?php
session_start();
include("configs/db.php");

header('Content-Type: application/json');

if (!isset($_POST['student_id'])) {
    echo json_encode(['exists' => false, 'error' => 'No student ID provided']);
    exit;
}

$student_id = trim($_POST['student_id']);
$exclude_id = isset($_POST['exclude_id']) && !empty($_POST['exclude_id']) ? intval($_POST['exclude_id']) : null;

// Check if student ID exists
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

echo json_encode(['exists' => $exists]);
?>