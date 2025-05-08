<?php
session_start();
require 'db_connection.php';

$user_id = $_SESSION['user_id'];
$label_id = $_GET['label_id'];

$stmt = $pdo->prepare("
    SELECT n.* FROM notes n
    JOIN note_labels nl ON n.note_id = nl.note_id
    WHERE nl.label_id = ? AND n.user_id = ?
    ORDER BY n.is_pinned DESC, n.pinned_at DESC, n.updated_at DESC
");
$stmt->execute([$label_id, $user_id]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Render notes as HTML
foreach ($notes as $note) {
    echo "<div class='note'>";
    echo "<h5>" . htmlspecialchars($note['title']) . "</h5>";
    echo "<p>" . htmlspecialchars($note['content']) . "</p>";
    echo "</div>";
}
?>
