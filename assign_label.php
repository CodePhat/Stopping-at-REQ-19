<?php
require 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate the inputs
    $note_id = (int)$_POST['note_id'];  // ID of the note
    $label_name = (string)$_POST['name'];  // Name of the label

    $user_id = $_SESSION['user_id'];  // Ensure the user is logged in

    // Ensure the note belongs to the user
    $stmt = $pdo->prepare("SELECT 1 FROM notes WHERE note_id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);

    if ($stmt->fetch()) {
        // Check if the label exists in the labels table
        $stmt = $pdo->prepare("SELECT label_id FROM labels WHERE name = ?");
        $stmt->execute([$label_name]);

        $label = $stmt->fetch();

        if (!$label) {
            // If the label doesn't exist, return an error
            echo json_encode(['success' => false, 'error' => 'Label does not exist.']);
            exit;
        }

        // Check if the label is already assigned to this note
        $stmt = $pdo->prepare("SELECT 1 FROM note_labels WHERE note_id = ? AND label_id = ?");
        $stmt->execute([$note_id, $label['label_id']]);

        if ($stmt->fetch()) {
            // If the label is already assigned to the note, send an error response
            echo json_encode(['success' => false, 'error' => 'This label is already assigned to the note.']);
        } else {
            // Insert the label for the note
            $stmt = $pdo->prepare("INSERT INTO note_labels (note_id, label_id) VALUES (?, ?)");
            if ($stmt->execute([$note_id, $label['label_id']])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add label to note.']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Note not found or does not belong to the user.']);
    }
}
?>
