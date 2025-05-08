<?php
//Test message Remove later
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
require 'db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
      $error[] = "Invalid email address.";
    }

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $count = $stmt->fetchColumn();
    
    if ($_POST['password'] !== $_POST['confirm_password']) {
      $error[] = "Passwords do not match.";
  }
  
    if ($count > 0) {
        $error[] = "The username or email already exists. Please choose a different one.";
    } else {
        if(empty($error)){
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database with temporary unverified status
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_verified) VALUES (?, ?, ?, 0)");
        
        if ($stmt->execute([$username, $email, $hashedPassword])) {
            $userId = $pdo->lastInsertId();

            // Generate and save a verification token
            $verifyToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $verifyLink = "http://localhost/Midterm/verify.php?token=$verifyToken";

            // Save token in database
            $stmt = $pdo->prepare("INSERT INTO email_verification_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $verifyToken, $expiresAt]);

            // Send verification email
            $mail = new PHPMailer(true);
            try {
                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'minhtanvu233@gmail.com';       // Replace with your Gmail
                $mail->Password = 'ljbl nwku qwku pyuo';          // Use App Password if 2FA enabled
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('minhtanvu233@gmail.com', 'Verify your email');
                $mail->addAddress($email, htmlspecialchars($username));
                $mail->isHTML(true);
                $mail->Subject = 'Verify your account';
                $mail->Body    = "Please click the following link to verify your account: <a href='$verifyLink'>Verify Account</a>";

                $mail->send();
                $success = "Registration successful! Please check your email to verify your account.";
            } catch (Exception $e) {
                $error[] = "Verification email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $error[] = "Registration failed. Please try again.";
        }
      }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Sign Up</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  
  <!-- jQuery and Bootstrap JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <style>
    body {
      background-color: #f0f2f5;
    }
    .signup-container {
      margin-top: 5rem;
    }
  </style>
</head>
<body>

<div class="container signup-container">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <h3 class="text-center text-secondary mb-4">Create an Account</h3>
      
      <form method="POST" action="signup.php" class="border rounded w-100 mx-auto p-4 bg-white shadow">
        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" class="form-control" placeholder="Choose a username" required>
        </div>

        <div class="form-group">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" class="form-control" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" class="form-control" placeholder="Create a password" required>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input id="confirm_password" name="confirm_password" type="password" class="form-control" placeholder="Confirm your password" required>
        </div>

        <!-- Error/Success Messages -->
        <?php if (!empty($error)) : ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($error as $e) : ?>
                <li><?php echo htmlspecialchars($e); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php elseif (!empty($success)) : ?>
          <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="form-group text-center">
          <button type="submit" name ="register" class="btn btn-primary px-5">Sign Up</button>
        </div>

        <div class="form-group text-center">
          <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
        </div>
      </form>

    </div>
  </div>
</div>  

</body>
</html>
