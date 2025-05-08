<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    http_response_code(401);
    exit("Unauthorized");
}

$user_id = $_SESSION["user_id"];
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$images = []; // Collect uploaded image paths here

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!empty($_FILES['images'])) {
    foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
        if (is_uploaded_file($tmpName)) {
            $filename = uniqid() . '_' . basename($_FILES['images']['name'][$i]);
            $targetPath = $uploadDir . $filename;
            move_uploaded_file($tmpName, $targetPath);
            $images[] = $targetPath;
        }
    }
}

try {
    // Get user preferences
    $stmt = $pdo->prepare("SELECT preferences FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $prefs = json_decode($stmt->fetchColumn() ?? '{}', true);
    $note_color = $prefs['note_color'] ?? '#ffffff';
    $font_size = $prefs['font_size'] ?? 'medium';

    $stmt = $pdo->prepare("
        INSERT INTO notes (user_id, title, content, images, note_color, font_size, updated_at)
        VALUES (:user_id, :title, :content, :images, :note_color, :font_size, NOW())
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'title' => $title,
        'content' => $content,
        'images' => implode(',', $images),
        'note_color' => $note_color,
        'font_size' => $font_size
    ]);

    echo "Note saved successfully.";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
}
