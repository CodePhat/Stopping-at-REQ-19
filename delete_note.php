<?php
session_start();
require 'db_connection.php';

$user_id = $_SESSION['user_id'];
$note_id = $_POST['note_id'];

$stmt = $pdo->prepare("DELETE FROM notes WHERE note_id = ? AND user_id = ?");
$stmt->execute([$note_id, $user_id]);
?>
