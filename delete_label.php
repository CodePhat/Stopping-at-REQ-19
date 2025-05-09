<?php
session_start();
require 'db_connection.php';

$user_id = $_SESSION["user_id"] ?? 0;
$label_id = $_POST['label_id'] ?? null;

if (!$label_id) {
    echo json_encode(['success' => false, 'error' => 'Missing label ID']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM labels WHERE label_id = ? AND user_id = ?");
$success = $stmt->execute([$label_id, $user_id]);

echo json_encode(['success' => $success]);
