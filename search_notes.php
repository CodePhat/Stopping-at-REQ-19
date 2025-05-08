<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

$user_id = $_SESSION["user_id"];
$query = $_POST['query'] ?? '';

try {
    $stmt = $pdo->prepare("
        SELECT note_id, title, content, images, pinned_at, updated_at, note_color, font_size
        FROM notes
        WHERE user_id = :id AND (title LIKE :query OR content LIKE :query)
        ORDER BY pinned_at DESC, updated_at DESC
    ");
    $searchTerm = '%' . $query . '%';
    $stmt->execute(['id' => $user_id, 'query' => $searchTerm]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitize and prepare notes
    $processedNotes = array_map(function ($note) {
        return [
            'note_id' => (int)$note['note_id'],
            'title' => $note['title'],
            'content' => $note['content'],
            'images' => $note['images'] ?? '',
            'pinned_at' => $note['pinned_at'],
            'note_color' => $note['note_color'] ?? '#ffffff',
            'font_size' => $note['font_size'] ?? '1rem'
        ];
    }, $notes);

    echo json_encode([
        "success" => true,
        "notes" => $processedNotes
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error."]);
    // Optional: Log $e->getMessage() to server log for debugging
}
        