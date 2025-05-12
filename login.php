<?php
session_start();

require 'vendor/autoload.php'; // Load Composer libraries (like PHPMailer, etc.)
require 'db_connection.php';   // Database connection ($pdo should be defined here)

// Optional: Hardcoded Admin Login
define("ADMIN_USERNAME", "admin");
define("ADMIN_PASSWORD", "password123");

// If already logged in, redirect to homepage
if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    header("Location: unified_homepage.php");
    exit();
}

$error = "";
$saved_username = $_COOKIE["remember_username"] ?? "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? "");
    $password = $_POST['password'] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please fill in both username and password.";
    } else {
        // Admin login fallback
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $_SESSION["logged_in"] = true;
            $_SESSION["username"] = $username;
            handleRememberMe($username);
            header("Location: unified_homepage.php");
            exit();
        }

        // Regular user login
        $stmt = $pdo->prepare("SELECT user_id, password, is_verified, two_factor_enabled FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ((int)$user['is_verified'] !== 1) {
                $error = "Your account has not been verified yet. Please check your email for the verification link.";
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION["temp_user_id"] = $user['user_id'];
                $_SESSION["temp_username"] = $username;
                $_SESSION["remember_me"] = isset($_POST["remember"]);

                // Check if two-factor authentication is enabled
                if ($user['two_factor_enabled'] === 1) {
                    // Redirect to 2FA page if TOTP is set
                    $stmt = $pdo->prepare("SELECT totp_secret FROM users WHERE user_id = ?");
                    $stmt->execute([$user['user_id']]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!empty($row['totp_secret'])) {
                        $_SESSION["totp_secret"] = $row['totp_secret'];
                        $_SESSION["2fa_totp_user"] = $user['user_id'];
                        header("Location: verify_totp.php");
                        exit();
                    }
                } else {
                    // If 2FA is not enabled, log in directly
                    $_SESSION["logged_in"] = true;
                    $_SESSION["username"] = $username;
                    $_SESSION["user_id"] = $user['user_id'];
                    handleRememberMe($username);
                    header("Location: unified_homepage.php");
                    exit();
                }
            } else {
                $error = "Incorrect username or password.";
            }
        } else {
            $error = "Incorrect username or password.";
        }
    }
}

// Handle the "Remember Me" functionality
function handleRememberMe($username) {
    if (isset($_POST["remember"])) {
        setcookie("remember_username", $username, time() + (86400 * 30), "/"); // 30 days
    } else {
        setcookie("remember_username", "", time() - 3600, "/"); // Expire cookie
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Login</title>
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
    .login-container {
      margin-top: 5rem;
    }
  </style>
</head>
<body>

<div class="container login-container">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <h3 class="text-center text-secondary mb-4">User Login</h3>
      
      <form method="POST" action="" class="border rounded w-100 mx-auto p-4 bg-white shadow">
        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" class="form-control" placeholder="Enter username" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" class="form-control" placeholder="Enter password" required>
        </div>

        <div class="form-group custom-control custom-checkbox">
          <input type="checkbox" name="remember" class="custom-control-input" id="remember">
          <label class="custom-control-label" for="remember">Remember me</label>
        </div>

        <!-- Error message block (only shown if $error is not empty) -->
        <?php if (!empty($error)) : ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-group text-center">
          <button type="submit" name="login" class="btn btn-success px-5">Login</button>
        </div>

        <div class="form-group text-center">
          <p class="mb-0">Forgot password? <a href="email_verification.php">Click here</a></p>
          <p class="mb-0">Don't have an account? <a href="signup.php">Sign up here</a></p>
        </div>
      </form>

    </div>
  </div>
</div>

</body>
</html>