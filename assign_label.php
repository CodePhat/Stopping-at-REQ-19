<?php
session_start();
require 'db_connection.php';

$note_id = $_POST['note_id'];
$label_id = $_POST['label_id'];

$stmt = $pdo->prepare("INSERT IGNORE INTO note_labels (note_id, label_id) VALUES (?, ?)");
$stmt->execute([$note_id, $label_id]);
?>
