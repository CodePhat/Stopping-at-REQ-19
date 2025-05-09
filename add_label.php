<?php
session_start();
require 'db_connection.php';

$user_id = $_SESSION["user_id"] ?? 0;
$label_name = trim($_POST['name'] ?? '');

if (!$label_name) {
    echo json_encode(['success' => false, 'error' => 'Label name is required']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO labels (user_id, name) VALUES (?, ?)");
$success = $stmt->execute([$user_id, $label_name]);

echo json_encode(['success' => $success]);
