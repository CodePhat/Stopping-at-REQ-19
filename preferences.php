<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $prefs = json_encode([
        "theme" => $_POST['theme'],
        "note_layout" => $_POST['note_layout'],
        "font_size" => $_POST['font_size'],
        "note_color" => $_POST['note_color']
    ]);

    $stmt = $pdo->prepare("UPDATE users SET preferences = ? WHERE user_id = ?");
    $stmt->execute([$prefs, $user_id]);
    $success = "Preferences saved.";
}

$stmt = $pdo->prepare("SELECT preferences FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$prefs = json_decode($stmt->fetchColumn(), true) ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>User Preferences</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
  <h2>User Preferences</h2>
  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <form method="POST">
  <div class="mb-3">
    <label>Theme</label>
    <select name="theme" class="form-control">
      <option value="light" <?= ($prefs['theme'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
      <option value="dark" <?= ($prefs['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Dark</option>
    </select>
  </div>

  <div class="mb-3">
    <label>Notes Layout</label>
    <select name="note_layout" class="form-control">
      <option value="grid" <?= ($prefs['note_layout'] ?? '') === 'grid' ? 'selected' : '' ?>>Grid</option>
      <option value="list" <?= ($prefs['note_layout'] ?? '') === 'list' ? 'selected' : '' ?>>List</option>
    </select>
  </div>

  <div class="mb-3">
    <label>Font Size</label>
    <select name="font_size" class="form-control">
      <option value="small" <?= ($prefs['font_size'] ?? '') === 'small' ? 'selected' : '' ?>>Small</option>
      <option value="medium" <?= ($prefs['font_size'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
      <option value="large" <?= ($prefs['font_size'] ?? '') === 'large' ? 'selected' : '' ?>>Large</option>
    </select>
  </div>

  <div class="mb-3">
    <label>Note Color</label>
    <input type="color" name="note_color" class="form-control form-control-color"
           value="<?= htmlspecialchars($prefs['note_color'] ?? '#ffffff') ?>">
  </div>

  <button class="btn btn-primary">Save Preferences</button>
  <a href="homepage.php" class="btn btn-secondary">Back</a>
</form>
</body>
</html>
