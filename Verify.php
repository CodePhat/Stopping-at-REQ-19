<?php
require 'db_connection.php';

// Get token from URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token exists and is not expired
    $stmt = $pdo->prepare("
        SELECT evt.user_id, evt.token, evt.expires_at, u.is_verified
        FROM email_verification_tokens evt
        JOIN users u ON evt.user_id = u.user_id
        WHERE evt.token = ?
    ");
    $stmt->execute([$token]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        if ($record['is_verified']) {
            echo "This account is already verified.";
        } elseif (strtotime($record['expires_at']) < time()) {
            echo "The verification token has expired.";
        } else {
            // Mark the user as verified
            $updateStmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
            $updateStmt->execute([$record['user_id']]);

            // Delete the token after successful verification
            $deleteStmt = $pdo->prepare("DELETE FROM email_verification_tokens WHERE token = ?");
            $deleteStmt->execute([$token]);

            echo "Your account has been successfully verified! You can now log in.";
        }
    } else {
        echo "Invalid verification token or it has already been used.";
    }
} else {
    echo "No verification token was provided.";
}
?>
