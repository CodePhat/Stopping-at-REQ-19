<?php
session_start();
require 'db_connection.php';

$user_id = $_SESSION["user_id"] ?? 0;

$stmt = $pdo->prepare("SELECT label_id, name FROM labels WHERE user_id = ?");
$stmt->execute([$user_id]);
$labels = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'labels' => $labels]);
