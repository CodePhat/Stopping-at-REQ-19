<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Fetch notes, labels, and preferences
$stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY pinned_at DESC, updated_at DESC");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM labels WHERE user_id = ?");
$stmt->execute([$user_id]);
$labels = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT preferences FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$prefs = json_decode($stmt->fetchColumn(), true);
$note_layout = $prefs["note_layout"] ?? "grid";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>My Notes</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <style>
    .note-card { transition: all 0.2s ease; }
    .note-card:hover { transform: scale(1.01); box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .grid .note-card { width: calc(33% - 20px); display: inline-block; vertical-align: top; margin: 10px; }
    .list .note-card { width: 100%; margin-bottom: 10px; }
  </style>
</head>
<body class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Your Notes</h2>
    <button class="btn btn-primary" onclick="openNoteEditor()">+ New Note</button>
  </div>

  <input id="searchBox" type="text" class="form-control mb-3" placeholder="Search notes...">

  <div class="mb-3">
    <label>Filter by label:</label>
    <select id="labelFilter" class="form-select">
      <option value="">All</option>
      <?php foreach ($labels as $label): ?>
        <option value="<?= htmlspecialchars($label['name']) ?>"><?= htmlspecialchars($label['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div id="noteContainer" class="<?= $note_layout ?>">
    <?php foreach ($notes as $note): ?>
      <div class="card note-card" data-title="<?= htmlspecialchars($note['title']) ?>" data-content="<?= htmlspecialchars($note['content']) ?>" data-labels="<?= htmlspecialchars($note['labels']) ?>">
        <div class="card-body">
          <h5 class="card-title"><?= htmlspecialchars($note['title']) ?></h5>
          <p class="card-text"><?= nl2br(htmlspecialchars($note['content'])) ?></p>
          <?php if (!empty($note['images'])): ?>
            <div>
              <?php foreach (json_decode($note['images'], true) as $img): ?>
                <img src="uploads/<?= htmlspecialchars($img) ?>" style="max-width: 100px; margin: 5px;">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <button class="btn btn-sm btn-outline-secondary" onclick="editNote(<?= $note['id'] ?>)">Edit</button>
          <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $note['id'] ?>)">Delete</button>
          <button class="btn btn-sm btn-outline-warning" onclick="togglePin(<?= $note['id'] ?>)">Pin</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div id="noteEditor" style="display: none;">
    <input id="noteTitle" class="form-control mb-2" placeholder="Title">
    <textarea id="noteContent" class="form-control mb-2" placeholder="Content"></textarea>
    <input type="file" id="noteImages" multiple class="form-control mb-2">
    <button class="btn btn-secondary" onclick="closeNoteEditor()">Close</button>
  </div>

  <script>
    let timer;
    $('#searchBox').on('input', function () {
      clearTimeout(timer);
      const query = this.value.toLowerCase();
      timer = setTimeout(() => {
        $('.note-card').each(function () {
          const title = $(this).data('title').toLowerCase();
          const content = $(this).data('content').toLowerCase();
          $(this).toggle(title.includes(query) || content.includes(query));
        });
      }, 300);
    });

    $('#labelFilter').on('change', function () {
      const selected = this.value;
      $('.note-card').each(function () {
        const labels = $(this).data('labels');
        $(this).toggle(!selected || labels.includes(selected));
      });
    });

    function confirmDelete(id) {
      if (confirm('Are you sure you want to delete this note?')) {
        window.location.href = 'delete_note.php?id=' + id;
      }
    }

    function openNoteEditor(id = null) {
      $('#noteEditor').show();
      // Load existing data for editing
    }

    function closeNoteEditor() {
      $('#noteEditor').hide();
    }

    function togglePin(id) {
      window.location.href = 'pin_note.php?id=' + id;
    }
  </script>
</body>
</html>
