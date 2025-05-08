<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $avatar = $_FILES['avatar'] ?? null;

    if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
        $target = 'uploads/avatars/' . uniqid('avatar_') . '.' . $ext;
        move_uploaded_file($avatar['tmp_name'], $target);
    } else {
        $target = $_POST['current_avatar']; // use existing
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, avatar = ? WHERE user_id = ?");
        $stmt->execute([$username, $email, $target, $user_id]);
        $_SESSION["username"] = $username;
        $success = "Profile updated successfully!";
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT username, email, avatar FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Edit Profile</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
  <h2>Edit Profile</h2>
  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label>Username</label>
      <input name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
    </div>

    <div class="mb-3">
      <label>Email</label>
      <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>

    <div class="mb-3">
      <label>Change Avatar</label><br>
      <img src="<?= htmlspecialchars($user['avatar'] ?? 'default_avatar.png') ?>" width="80" height="80" class="mb-2 rounded-circle"><br>
      <input type="file" name="avatar" class="form-control">
      <input type="hidden" name="current_avatar" value="<?= htmlspecialchars($user['avatar']) ?>">
    </div>

    <button class="btn btn-primary">Save Changes</button>
    <a href="homepage.php" class="btn btn-secondary">Back</a>
  </form>
</body>
</html>
