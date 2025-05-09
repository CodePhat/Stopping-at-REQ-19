<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION["user_id"];
$note_id = $_POST['note_id'] ?? null;
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';

if (!$note_id) {
    echo json_encode(['success' => false, 'error' => 'Note ID required']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE notes SET title = :title, content = :content, updated_at = NOW() WHERE note_id = :note_id AND user_id = :user_id");
    $stmt->execute([
        'title' => $title,
        'content' => $content,
        'note_id' => $note_id,
        'user_id' => $user_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Note auto-saved']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
