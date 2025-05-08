<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

try {
    $stmt = $pdo->prepare("SELECT username, email, avatar, preferences FROM users WHERE user_id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found.");
    }

    $username = $user['username'];
    $email = $user['email'];
    $avatar = $user['avatar'] ?? 'default_avatar.png';

    $prefs = json_decode($user['preferences'] ?? '{}', true);
    $theme = $prefs['theme'] ?? 'light';
    $font_size = $prefs['font_size'] ?? 'medium';
    $note_color = $prefs['note_color'] ?? '#ffffff';
    $note_layout = $prefs['note_layout'] ?? 'grid';

    $stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = :id ORDER BY pinned_at DESC, updated_at DESC");
    $stmt->execute(['id' => $user_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Font size mapping
$fontSizeCss = match($font_size) {
    'small' => '0.9rem',
    'large' => '1.3rem',
    default => '1rem'
};

$layoutClass = $note_layout === 'grid' ? 'row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3' : 'd-flex flex-column gap-3';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Homepage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .note-card textarea {
            resize: none;
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: inherit;
        }
        .note-card img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body style="background-color: <?= $theme === 'dark' ? '#1e1e1e' : '#ffffff' ?>; color: <?= $theme === 'dark' ? '#f5f5f5' : '#000000' ?>">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Welcome, <?= htmlspecialchars($username) ?>!</h2>
        <div>
            <a href="?logged_out=true" class="btn btn-outline-secondary">Log Out</a>
        </div>
    </div>

    <!-- Profile Card -->
    <div class="card mb-4">
        <div class="card-header">Your Profile</div>
        <div class="card-body d-flex align-items-center">
            <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="rounded-circle me-3" width="80" height="80">
            <div>
                <p><strong>Username:</strong> <?= htmlspecialchars($username) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                <a href="edit_profile.php" class="btn btn-sm btn-outline-primary">Edit Profile</a>
                <a href="preferences.php" class="btn btn-sm btn-outline-secondary ms-2">User Preferences</a>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="card mb-4">
        <div class="card-header">Change Password</div>
        <div class="card-body">
            <p>To change your password, please verify your identity by clicking the button below.</p>
            <a href="email_verification.php" class="btn btn-primary">Verify Email to Change Password</a>
        </div>
    </div>

    <!-- New Note -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>New Note</span>
            <input type="text" id="searchBox" class="form-control w-25" placeholder="Search notes...">
        </div>
        <div class="card-body">
            <input type="text" id="note-title" class="form-control mb-2" placeholder="Title">
            <textarea id="note-content" class="form-control mb-2" rows="4" placeholder="Write your note..."></textarea>
            <input type="file" id="note-images" multiple class="form-control mb-2">
            <div id="autosave-status" class="text-muted" style="font-size: 0.9rem;"></div>
            <button class="btn btn-success mb-3" onclick="addNote()">Add Note</button>
        </div>
    </div>

    <div class="<?= $layoutClass ?>" id="notes-container">
    <div class="row">
        <?php foreach ($notes as $note): ?>
            <div class="col-md-4 mb-4">
                <div class="card position-relative note-card <?= $note['pinned_at'] ? 'border-warning' : '' ?>" id="note-<?= $note['note_id'] ?>" style="background-color: <?= htmlspecialchars($note_color) ?>; font-size: <?= $fontSizeCss ?>;">
                    <div class="card-body">
                        <input type="text" class="form-control editable-title mb-2" data-id="<?= $note['note_id'] ?>" value="<?= htmlspecialchars($note['title']) ?>">
                        <textarea class="form-control editable-content mb-2" data-id="<?= $note['note_id'] ?>" rows="3"><?= htmlspecialchars($note['content']) ?></textarea>
                        
                        <?php if (!empty($note['images'])): ?>
                            <?php foreach (explode(',', $note['images']) as $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" class="img-fluid my-2" style="max-height: 200px; object-fit: contain;" />
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between">
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteNote(<?= $note['note_id'] ?>)">Delete</button>
                            <button class="btn btn-sm btn-outline-warning" onclick="togglePin(<?= $note['note_id'] ?>)">
                                <?= $note['pinned_at'] ? 'Unpin' : 'Pin' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>


<style>
    .border-warning {
    border: 2px solid #ffc107 !important;
}

</style>
<script>    




function deleteNote(noteId) {
    if (confirm('Are you sure you want to delete this note?')) {
        $.post('delete_note.php', { note_id: noteId }, function(response) {
            $('#note-' + noteId).remove();
        });
    }
}

function togglePin(noteId) {
    $.post('toggle_pin.php', { note_id: noteId }, function(response) {
        const data = JSON.parse(response);
        if (data.success) {
            location.reload(); 
        } else {
            alert("Failed to toggle pin.");
        }
    });
}


let searchTimeout;

$('#searchBox').on('input', function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function () {
        const query = $('#searchBox').val().trim();
        $.post('search_notes.php', { query: query }, function (response) {
            let data;
            try {
                data = typeof response === "object" ? response : JSON.parse(response);
            } catch (e) {
                alert("Invalid response from server.");
                return;
            }

            if (data.success) {
                const notesHtml = data.notes.map(note => {
                    const noteColor = note.note_color || '<?= $note_color ?>';
                    const fontSize = note.font_size || '<?= $fontSizeCss ?>';
                    const pinned = note.pinned_at ? 'Unpin' : 'Pin';
                    const borderClass = note.pinned_at ? 'border-warning' : '';

                    const imagesHtml = note.images
                        ? note.images.split(',').map(img => `<img src="${img}" class="img-fluid my-2" />`).join('')
                        : '';

                    return `
                        <div class="col">
                            <div class="card note-card ${borderClass}" id="note-${note.note_id}" style="background-color: ${noteColor}; font-size: ${fontSize};">
                                <div class="card-body">
                                    <input type="text" class="form-control editable-title mb-2" data-id="${note.note_id}" value="${note.title}">
                                    <textarea class="form-control editable-content" data-id="${note.note_id}" rows="3">${note.content}</textarea>
                                    ${imagesHtml}
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteNote(${note.note_id})">Delete</button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="togglePin(${note.note_id})">${pinned}</button>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                $('#notes-container').html(notesHtml);
            } else {
                alert("Search failed: " + (data.error || "Unknown error"));
            }
        });
    }, 300);
});





function addNote() {
    const title = $('#note-title').val().trim();
    const content = $('#note-content').val().trim();
    const images = $('#note-images')[0].files;

    if (!title && !content && images.length === 0) {
        alert("Note is empty.");
        return;
    }

    const formData = new FormData();
    formData.append('title', title);
    formData.append('content', content);
    for (let i = 0; i < images.length; i++) {
        formData.append('images[]', images[i]);
    }

    $.ajax({
        url: 'save_note.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
            console.log('Note saved:', res);
            location.reload(); 
        },
        error: function () {
            alert("Failed to add note.");
        }
    });
}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
