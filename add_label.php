<?php
session_start();
require 'db_connection.php';

$user_id = $_SESSION['user_id'];
$label_name = $_POST['label_name'];

$stmt = $pdo->prepare("INSERT INTO labels (user_id, name) VALUES (?, ?)");
$stmt->execute([$user_id, $label_name]);
?>
