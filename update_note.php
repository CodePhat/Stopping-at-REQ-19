<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$user_id = $_SESSION['user_id'];
$note_id = $_POST['note_id'] ?? null;
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

if (!$note_id) {
    http_response_code(400);
    echo 'Missing note ID';
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE notes SET title = :title, content = :content, updated_at = NOW()
                           WHERE note_id = :note_id AND user_id = :user_id");
    $stmt->execute([
        'title' => $title,
        'content' => $content,
        'note_id' => $note_id,
        'user_id' => $user_id
    ]);
    echo 'Updated';
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
?>
