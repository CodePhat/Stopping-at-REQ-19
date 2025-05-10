<?php
// get_labels.php
session_start();
require 'db_connection.php';

if (!isset($_SESSION["logged_in"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$user_id = $_SESSION["user_id"];
$stmt = $pdo->prepare("SELECT label_id, name FROM labels WHERE user_id = ?");
$stmt->execute([$user_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

