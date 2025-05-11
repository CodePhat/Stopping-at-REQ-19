<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION["user_id"];
$note_id = $_POST["note_id"] ?? null;

if (!$note_id) {
    echo json_encode(['success' => false, 'error' => 'Note ID required']);
    exit;
}

try {
    // Ensure the note belongs to the user
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE note_id = :note_id AND user_id = :user_id");
    $stmt->execute(['note_id' => $note_id, 'user_id' => $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Note not found or unauthorized']);
        exit;
    }

    // Delete label associations if they exist
    $pdo->prepare("DELETE FROM note_labels WHERE note_id = :note_id")->execute(['note_id' => $note_id]);

    // Delete the note
    $pdo->prepare("DELETE FROM notes WHERE note_id = :note_id")->execute(['note_id' => $note_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
