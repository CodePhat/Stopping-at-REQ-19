<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION["user_id"];
$note_id = $_POST["note_id"] ?? null;

if (!$note_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing note ID']);
    exit();
}

try {
    // Get current pinned_at value
    $stmt = $pdo->prepare("SELECT pinned_at FROM notes WHERE note_id = :note_id AND user_id = :user_id");
    $stmt->execute(['note_id' => $note_id, 'user_id' => $user_id]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$note) {
        http_response_code(404);
        echo json_encode(['error' => 'Note not found']);
        exit();
    }

    // Toggle pinned_at
    if ($note['pinned_at']) {
        $stmt = $pdo->prepare("UPDATE notes SET pinned_at = NULL WHERE note_id = :note_id AND user_id = :user_id");
        $stmt->execute(['note_id' => $note_id, 'user_id' => $user_id]);
        echo json_encode(['success' => true, 'pinned' => false]);
    } else {
        $stmt = $pdo->prepare("UPDATE notes SET pinned_at = NOW() WHERE note_id = :note_id AND user_id = :user_id");
        $stmt->execute(['note_id' => $note_id, 'user_id' => $user_id]);
        echo json_encode(['success' => true, 'pinned' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
