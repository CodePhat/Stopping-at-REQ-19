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
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
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
    <h5 class="mb-0">New Note</h5>
    <input
      type="text"
      id="searchBox"
      class="form-control w-25"
      placeholder="Search notes..."
      aria-label="Search notes"
    />
  </div>
  <div class="card-body">
    <form id="note-form" onsubmit="event.preventDefault(); addNote();">
      <div class="mb-3">
        <label for="note-title" class="form-label">Title</label>
        <input type="text" id="note-title" class="form-control" placeholder="Enter title" required />
      </div>

      <div class="mb-3">
        <label for="note-labels" class="form-label">Labels</label>
        <select id="note-labels" class="form-select" multiple aria-label="Select labels">
          <!-- Options should be populated dynamically -->
        </select>
      </div>

      <div class="mb-3">
        <label for="note-content" class="form-label">Content</label>
        <textarea
          id="note-content"
          class="form-control"
          rows="4"
          placeholder="Write your note..."
          required
        ></textarea>
      </div>

      <div class="mb-3">
        <label for="note-images" class="form-label">Attach Images</label>
        <input type="file" id="note-images" class="form-control" multiple accept="image/*" />
      </div>

      <div id="autosave-status" class="form-text text-muted mb-3" aria-live="polite"></div>

      <button type="submit" class="btn btn-success">Add Note</button>
    </form>
  </div>
</div>

        <!-- Label Management Modal -->
<div class="modal fade" id="labelModal" tabindex="-1" aria-labelledby="labelModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manage Labels</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group" id="labelList"></ul>
        <div class="input-group mt-3">
          <input type="text" id="newLabelName" class="form-control" placeholder="New label name">
          <button class="btn btn-primary" id="addLabelBtn">Add</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Button to open label modal -->
<div class="mb-3">
  <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#labelModal">Manage Labels</button>
</div>

    <!-- Filter Notes by Label -->
    <div class="card mb-4">
        <div class="card-header">
            Filter Notes by Label:
            <select id="labelFilter" class="form-select w-auto d-inline-block">
                <option value="">-- All --</option>
            </select>
        </div>
    </div>


    <div class="<?= $layoutClass ?>" id="notes-container">
    <?php foreach ($notes as $note): ?>
        <div class="col mb-4">
            <div class="card position-relative note-card <?= $note['pinned_at'] ? 'border-warning' : '' ?>"
                id="note-<?= $note['note_id'] ?>"
                style="background-color: <?= htmlspecialchars($note_color) ?>; font-size: <?= $fontSizeCss ?>;">
                <div class="card-body">
                    <!-- Editable Title -->
                    <div class="mb-2">
                        <label for="title-<?= $note['note_id'] ?>" class="form-label visually-hidden">Title</label>
                        <input type="text"
                               id="title-<?= $note['note_id'] ?>"
                               class="form-control editable-title"
                               data-id="<?= $note['note_id'] ?>"
                               value="<?= htmlspecialchars($note['title']) ?>"
                               placeholder="Note title">
                    </div>

                    <!-- Editable Content -->
                    <div class="mb-2">
                        <label for="content-<?= $note['note_id'] ?>" class="form-label visually-hidden">Content</label>
                        <textarea id="content-<?= $note['note_id'] ?>"
                                  class="form-control editable-content"
                                  data-id="<?= $note['note_id'] ?>"
                                  rows="3"
                                  placeholder="Write your note..."><?= htmlspecialchars($note['content']) ?></textarea>
                    </div>

                    <!-- Attached Images -->
                    <?php if (!empty($note['images'])): ?>
                        <div class="mb-2">
                            <?php foreach (explode(',', $note['images']) as $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>"
                                     class="img-fluid rounded border my-1"
                                     style="max-height: 200px; object-fit: contain;"
                                     alt="Attached image for note <?= $note['note_id'] ?>" />
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Labels & Actions -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="flex-grow-1 me-2 mb-2">
                            <select class="form-select form-select-sm note-label-select"
                                    data-note-id="<?= $note['note_id'] ?>" multiple>
                                <?php foreach ($labels as $label): ?>
                                    <option value="<?= $label['label_id'] ?>"
                                        <?= in_array($label['label_id'], array_column($note['labels'], 'label_id')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="btn-group btn-group-sm mb-2">
                            <button class="btn btn-outline-danger" onclick="deleteNote(<?= $note['note_id'] ?>)">
                                Delete
                            </button>
                            <button class="btn btn-outline-warning" onclick="togglePin(<?= $note['note_id'] ?>)">
                                <?= $note['pinned_at'] ? 'Unpin' : 'Pin' ?>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
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

                $('#notes-container').html(`<div class="row">${notesHtml}</div>`);
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
function loadLabels() {
    $.get('get_labels.php', function(res) {
        const data = typeof res === "object" ? res : JSON.parse(res);
        if (data.success) {
            const select = $('#labelFilter');
            select.empty().append('<option value="">-- All --</option>');
            data.labels.forEach(label => {
                select.append(`<option value="${label.label_id}">${label.name}</option>`);
            });
        }
    });
}

$('#labelFilter').on('change', function () {
    const labelId = $(this).val();
    if (!labelId) {
        location.reload(); // Show all
        return;
    }

    $.post('filter_notes_by_label.php', { label_id: labelId }, function (res) {
        const data = typeof res === "object" ? res : JSON.parse(res);
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
                    <div class="col mb-4">
                        <div class="card note-card ${borderClass}" style="background-color: ${noteColor}; font-size: ${fontSize};">
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
            $('#notes-container').html(`<div class="<?= $layoutClass ?>">${notesHtml}</div>`);
        }
    });
});

// Initial call to populate the dropdown
loadLabels();
function refreshLabelList() {
    $.get('get_labels.php', function (res) {
        const data = typeof res === "object" ? res : JSON.parse(res);
        const list = $('#labelList');
        list.empty();
        data.labels.forEach(label => {
            list.append(`
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <input type="text" class="form-control form-control-sm me-2 flex-grow-1" value="${label.name}" data-id="${label.label_id}" onchange="renameLabel(${label.label_id}, this.value)">
                    <button class="btn btn-sm btn-danger" onclick="deleteLabel(${label.label_id})">&times;</button>
                </li>
            `);
        });
        loadLabels(); // Refresh dropdown
    });
}

function renameLabel(labelId, newName) {
    $.post('rename_label.php', { label_id: labelId, name: newName }, function (res) {
        const data = typeof res === "object" ? res : JSON.parse(res);
        if (!data.success) alert(data.error || 'Failed to rename label');
        loadLabels();
    });
}

function deleteLabel(labelId) {
    if (!confirm('Are you sure you want to delete this label?')) return;
    $.post('delete_label.php', { label_id: labelId }, function (res) {
        const data = typeof res === "object" ? res : JSON.parse(res);
        if (!data.success) alert(data.error || 'Failed to delete label');
        refreshLabelList();
        loadLabels();
    });
}

$('#addLabelBtn').on('click', function () {
    const labelName = $('#newLabelName').val().trim();
    if (!labelName) return alert('Label name required');
    $.post('add_label.php', { name: labelName }, function (res) {
        const data = typeof res === "object" ? res : JSON.parse(res);
        if (!data.success) alert(data.error || 'Failed to add label');
        $('#newLabelName').val('');
        refreshLabelList();
        loadLabels();
    });
});

$('#labelModal').on('shown.bs.modal', refreshLabelList);

function populateNoteLabelSelector() {
    $.get('get_labels.php', function (res) {
        const data = typeof res === "object" ? res : JSON.parse(res);
        const select = $('#note-labels');
        select.empty();
        data.labels.forEach(label => {
            select.append(`<option value="${label.label_id}">${label.name}</option>`);
        });
    });
}
populateNoteLabelSelector();

$('.note-label-select').on('change', function () {
    const noteId = $(this).data('note-id');
    const selectedLabels = $(this).val(); // array of selected label IDs

    $.ajax({
        type: 'POST',
        url: 'update_note_labels.php',
        data: {
            note_id: noteId,
            labels: JSON.stringify(selectedLabels)
        },
        success: function (response) {
            console.log('Labels updated:', response);
        },
        error: function () {
            alert('Failed to update labels');
        }
    });
});

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<script>
let autoSaveTimer;

function autoSaveNote(noteId, fieldType, value) {
    clearTimeout(autoSaveTimer);

    autoSaveTimer = setTimeout(() => {
        const title = $(`.editable-title[data-id="${noteId}"]`).val();
        const content = $(`.editable-content[data-id="${noteId}"]`).val();

        $.post('autosave_note.php', {
            note_id: noteId,
            title: title,
            content: content
        }, function(response) {
            const data = typeof response === "object" ? response : JSON.parse(response);
            if (data.success) {
                $('#autosave-status').text("Auto-saved at " + new Date().toLocaleTimeString());
            } else {
                console.error(data.error || "Auto-save failed");
            }
        });
    }, 1000); 
}

$(document).on('input', '.editable-title, .editable-content', function () {
    const noteId = $(this).data('id');
    const value = $(this).val();
    autoSaveNote(noteId, $(this).hasClass('editable-title') ? 'title' : 'content', value);
});
</script>
