<?php
session_start();
require 'vendor/autoload.php';
require 'db_connection.php';

use OTPHP\TOTP;

if (!isset($_SESSION["2fa_totp_user"])) {
    header("Location: login.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? "");
    $userId = $_SESSION["2fa_totp_user"];

    $stmt = $pdo->prepare("SELECT username, totp_secret FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $totp = TOTP::create($user['totp_secret']);

        if ($totp->verify($code)) {
            // Success
            $_SESSION["logged_in"] = true;
            $_SESSION["username"] = $user['username'];
            $_SESSION["user_id"] = $userId;
            unset($_SESSION["2fa_totp_user"]);
            unset($_SESSION["temp_username"]);
            header("Location: homepage.php");
            exit();
        } else {
            $error = "Invalid authentication code.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Verify 2FA Code</title></head>
<body>
<h3>Enter your authentication code</h3>
<form method="POST">
    <input type="text" name="code" required pattern="\d{6}" placeholder="6-digit code">
    <button type="submit">Verify</button>
</form>
<?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
</body>
</html>
