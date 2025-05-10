<?php
require 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $labelId = $_POST['label_id'];
    $userId = $_SESSION['user_id'];

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("DELETE FROM note_labels WHERE label_id = ? AND note_id IN (SELECT note_id FROM notes WHERE user_id = ?)");
        $stmt->execute([$labelId, $userId]);

        $stmt = $pdo->prepare("DELETE FROM labels WHERE label_id = ? AND user_id = ?");
        $stmt->execute([$labelId, $userId]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Delete failed']);
    }
}
