<?php
require 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $labelId = $_POST['label_id'];
    $newName = trim($_POST['new_name']);
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("UPDATE labels SET name = ? WHERE label_id = ? AND user_id = ?");
    if ($stmt->execute([$newName, $labelId, $userId])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update label']);
    }
}
