<?php
session_start();
require 'db_connection.php';

$user_id = $_SESSION["user_id"] ?? 0;
$label_id = $_POST['label_id'] ?? null;
$new_name = trim($_POST['name'] ?? '');

if (!$label_id || !$new_name) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$stmt = $pdo->prepare("UPDATE labels SET name = ? WHERE label_id = ? AND user_id = ?");
$success = $stmt->execute([$new_name, $label_id, $user_id]);

echo json_encode(['success' => $success]);
