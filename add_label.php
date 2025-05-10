<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $user_id = $_SESSION['user_id'];

    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO labels (user_id, name) VALUES (?, ?)");
        if ($stmt->execute([$user_id, $name])) {
            $label_id = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'label' => ['label_id' => $label_id, 'name' => $name]]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Name is required']);
    }
}
