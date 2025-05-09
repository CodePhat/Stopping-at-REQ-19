<?php
require 'db.php';

$note_id = $_POST['note_id'];
$labels = json_decode($_POST['labels'], true);

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Delete old labels
    $stmt = $pdo->prepare("DELETE FROM note_labels WHERE note_id = ?");
    $stmt->execute([$note_id]);

    // Insert new labels
    $stmt = $pdo->prepare("INSERT INTO note_labels (note_id, label_id) VALUES (?, ?)");
    foreach ($labels as $label_id) {
        $stmt->execute([$note_id, $label_id]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
