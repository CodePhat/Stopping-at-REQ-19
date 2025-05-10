<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION["user_id"];
$label = $_POST['label'] ?? '';

try {
    $stmt = $pdo->prepare("
        SELECT n.*
        FROM notes n
        JOIN note_labels nl ON n.note_id = nl.note_id
        JOIN labels l ON nl.label_id = l.label_id
        WHERE n.user_id = :user_id AND l.name = :label
        ORDER BY n.pinned_at DESC, n.updated_at DESC
    ");
    $stmt->execute(['user_id' => $user_id, 'label' => $label]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'notes' => $notes]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
