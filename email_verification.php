<?php
session_start();
require 'vendor/autoload.php'; // For PHPMailer
require 'db_connection.php';  // For DB connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
date_default_timezone_set('Asia/Ho_Chi_Minh'); 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Please provide your email address.";
    } else {
        // Check if email exists in the database
        $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("DELETE FROM email_verification_tokens WHERE user_id = ? AND expires_at < NOW()");
        $stmt->execute([$user['user_id']]);


        if ($user) {
            // Check if there is an existing token for this user and expire it
            $stmt = $pdo->prepare("SELECT token, expires_at FROM email_verification_tokens WHERE user_id = ? AND expires_at > NOW() LIMIT 1");
            $stmt->execute([$user['user_id']]);
            $existing_token = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("DELETE FROM email_verification_tokens WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);

            // Generate a new token and expiry time (1 hour)
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Insert the new token into the database
            $stmt = $pdo->prepare("INSERT INTO email_verification_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['user_id'], $token, $expires_at]);

            // Send email with the reset link
            $reset_link = "http://localhost/Midterm/reset_password.php?token=" . $token;

            $mail = new PHPMailer(true);
            try {
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'minhtanvu233@gmail.com';  // Replace with your Gmail
                $mail->Password = 'ljbl nwku qwku pyuo';  // Use App Password if 2FA enabled
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('minhtanvu233@gmail.com', 'Password Reset Request');
                $mail->addAddress($email, htmlspecialchars($user['username']));
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "Please click the following link to reset your password: <a href='$reset_link'>Reset Password</a>";

                $mail->send();
                $message = "Please check your email to reset your password.";
            } catch (Exception $e) {
                $error[] = "Password reset email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

        } else {
            $error = "No account found with that email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Password Reset Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Password Reset</h3>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Enter your email address</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Request Reset</button>
    </form>

    <?php if (isset($error)) : ?>
        <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($message)) : ?>
        <div class="alert alert-success mt-3"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</div>
</body>
</html>
