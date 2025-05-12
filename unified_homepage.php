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
    $user = $stmt->fetch(mode: PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(query: 'SELECT l.name FROM labels l INNER JOIN note_labels nl ON l.label_id=nl.label_id');

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

    $stmt = $pdo->prepare('
    SELECT nl.note_id, l.label_id, l.name 
    FROM note_labels nl 
    INNER JOIN labels l ON nl.label_id = l.label_id
    ');
    $stmt->execute();
    $noteLabels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $labelsByNote = [];
    foreach ($noteLabels as $row) {
        $labelsByNote[$row['note_id']][] = [
            'label_id' => $row['label_id'],
            'name' => $row['name'],
        ];
    }
    foreach ($notes as &$note) {
    $noteId = $note['note_id'];
    $note['labels'] = $labelsByNote[$noteId] ?? [];  // Empty array if no labels
}
unset($note); // Best practice when modifying by reference
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

$stmt = $pdo->prepare("SELECT * FROM labels WHERE user_id = :id");
$stmt->execute(['id' => $user_id]);
$labels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Font size mapping
$fontSizeCss = match($font_size) {
    'small' => '0.9rem',
    'large' => '1.3rem',
    default => '1rem'
};

$layoutClass = $note_layout === 'grid' ? 'row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3' : 'd-flex flex-column gap-3';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, inital-scale=1.0">
  <title>Note Manangement</title>
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">
  <style>
    .body {
        display: grid;
    }

    .sidebar {
        position: fixed;
        top: 68px; /* Height of Bootstrap navbar */
        left: 0;
        height: calc(100vh - 56px); /* Full height minus navbar */
        width: 200px;

        padding: 1rem;
        overflow-y: auto;
        z-index: 999;
    }

    .main-content {
        margin-left: 240px; /* Equal to sidebar width */
        padding: 20px;
        margin-top: 56px
    }   

    .border-warning {
        border: 5px solid #ffc107;
        background-color: #fffbea;
        box-shadow: #ffc107;
    }

    .note-card {
        margin:20px;
        padding: 3px;
        outline:2px solid #DDD;
        -webkit-transition: margin 0.5s ease-out;
        -moz-transition: margin 0.5s ease-out;
        -o-transition: margin 0.5s ease-out;
    }

    .note-card:hover {
        cursor:pointer;
            margin-top: 5px;
    }

    
    .label-list .list-group-item:hover {
        background-color:rgb(160, 160, 160);
    }

    #note-form-container {
        max-height: 0;
        overflow: hidden;
        transition: max-height 1s ease;
    }

    #note-form-container.show {
      max-height: 1000px; /* Large enough to fit the form */
    }

  </style>
</head>

<body class="bg-<?= $theme === 'dark' ? 'dark text-light' : 'light text-dark' ?>">
    <nav class="navbar shadow-sm">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h2 class="mb-0 text-primary">🗒️ Note Management</h2>
            <div class="d-flex align-items-center gap-3">
                <span class="fw-semibold text-dark">Welcome, <?= htmlspecialchars($user['username']) ?></span>
                <a href="edit_profile.php">
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="rounded-circle" width="50" height="50">
                </a>
                <a href="preferences.php" title="Settings"><i class="fas fa-cog"></i></a>
                <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
            </div>
        </div>
    </nav>

     <!--Sidebar-->

    <nav class="sidebar shadow-sm d-flex flex-column p-3 bg-<?=$theme === 'dark' ? 'dark text-light' : 'light text-dark' ?> " style="height: 100vh; width: 240px;">
        <div class="mb-4">
            <button class="btn btn-primary w-100 mb-2" onclick="toggleForm()">
                <i class="fas fa-plus"></i> Add Note
            </button>
        </div>

        <h5 class="text-uppercase text-secondary mb-3">Labels</h5>
        <ul class="label-list mb-3">
            <?php foreach ($labels as $label): ?>
            <li id="label-<?= $label['label_id'] ?>" class="list-group-item d-flex justify-content-between align-items-center mb-2" onclick="filterByLabel('<?= htmlspecialchars($label['name']) ?>')">
                <span class="label-name ps-2" style="cursor: pointer;" >
                    #<?= htmlspecialchars($label['name']) ?>
                </span>
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-secondary" title="Rename" onclick="renameLabelPrompt('<?= $label['label_id'] ?>', '<?= htmlspecialchars($label['name']) ?>')">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="btn btn-outline-danger" title="Delete" onclick="deleteLabel('<?= $label['label_id'] ?>')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        
        <button class="btn btn-outline-secondary w-100 mb-2" onclick="loadAllNotes()">Clear Filter</button>
        <button class="btn btn-primary w-100 mb-2" onclick="addLabelPrompt()">
            <i class="fas fa-tag me-2"></i> Add Label
        </button>
    </nav>

    <main class="main-content">
        <header class="main-header">
            <input
                    type="text"
                    id="searchBox"
                    class="form-control w-100 mb-3"
                    placeholder="Search notes..."
                    aria-label="Search notes"
                />
        </header>

        <div id="note-form-container" class="card-body">
            <form id="note-form" onsubmit="event.preventDefault(); addNote();">
                <div class="mb-3">
                    <label for="note-title" class="form-label">Title</label>
                    <input type="text" id="note-title" class="form-control" placeholder="Enter title" required />
                </div>
                <div class="mb-3">
                    <label for="note-content" class="form-label">Content</label>
                    <textarea id="note-content" class="form-control" rows="4" placeholder="Write your note..." required></textarea>
                </div>
                <div class="mb-3">
                    <label for="note-images" class="form-label">Attach Images</label>
                    <input type="file" id="note-images" class="form-control" multiple accept="image/*" />
                </div>
                <div id="autosave-status" class="form-text text-muted mb-3" aria-live="polite"></div>
                <button type="submit" class="btn btn-success">Add Note</button>
                <div id="image-preview" class="mb-3"></div>
            </form>
        </div>

        <!-- Notes Display -->
        <div class="<?= $layoutClass ?>" id="notes-container">
            <?php foreach ($notes as $note): ?>
                <div class="col mb-4">
                    <div class="card note-card <?= $note['pinned_at'] ? 'border-warning' : '' ?>" id="note-<?= $note['note_id'] ?>" style="background-color: <?= htmlspecialchars($note_color) ?>; font-size: <?= $fontSizeCss ?>;">
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

                            <!-- Display Labels as Hashtags -->
                            <?php if (!empty($note['labels'])): ?>
                                <div class="mb-2">
                                    <?php foreach ($note['labels'] as $label): ?>
                                        <span class="badge bg-primary me-1">#<?= htmlspecialchars($label['name']) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

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

                            <!-- Note Actions -->
                            <div class="note-actions">
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteNote(<?= $note['note_id'] ?>)"><i class="fas fa-trash-alt"></i></button>
                                <button class="btn btn-sm <?= $note['pinned_at'] ? 'btn-warning' : 'btn-outline-warning' ?>"onclick="togglePin(<?= $note['note_id'] ?>)" title="<?= $note['pinned_at'] ? 'Unpin Note' : 'Pin Note' ?>"><i class="fas fa-thumbtack <?= $note['pinned_at'] ? '' : 'opacity-50' ?>"></i></button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="addLabelToNote(<?= $note['note_id'] ?>)"><i class="fas fa-tag"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>


    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="scripts.js"></script>      
                                 
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

    //Search fully function - DO NOT DELETE ANYTHING
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
                if (data.notes.length === 0) {
                    $('#notes-container').html('<div class="col"><div class="alert alert-info">No notes found.</div></div>');
                    return;
                }

                const notesHtml = data.notes.map(note => {
                const noteColor = note.note_color || '#fff';
                const fontSize = note.font_size || '1rem';
                const pinned = note.pinned_at ? true : false;
                const borderClass = pinned ? 'border-warning' : '';
                const pinBtnClass = pinned ? 'btn-warning' : 'btn-outline-warning';
                const pinIconOpacity = pinned ? '' : 'opacity-50';
                const pinTitle = pinned ? 'Unpin Note' : 'Pin Note';
                const labelsHtml = note.labels && note.labels.length
                        ? `<div class="mb-2">` + note.labels.map(label => `
                            <span class="badge bg-primary me-1">#${label.name}</span>
                        `).join('') + `</div>`
                        : '';

                    const imagesHtml = note.images
                        ? note.images.split(',').map(img => `
                            <img src="${img.trim()}" 
                                class="img-fluid rounded border my-1" 
                                style="max-height: 200px; object-fit: contain;" 
                                alt="Attached image for note ${note.note_id}" />
                        `).join('')
                        : '';

                    return `
                        <div class="col mb-4">
                            <div class="card note-card ${borderClass}" id="note-${note.note_id}" style="background-color: ${noteColor}; font-size: ${fontSize};">
                                <div class="card-body">
                                    <div class="mb-2">
                                        <input type="text" class="form-control editable-title" data-id="${note.note_id}" id="title-${note.note_id}" value="${note.title}" placeholder="Note title">
                                    </div>
                                    <div class="mb-2">
                                        <textarea class="form-control editable-content" data-id="${note.note_id}" id="content-${note.note_id}" rows="3" placeholder="Write your note...">${note.content}</textarea>
                                    </div>
                                    ${labelsHtml}
                                    <div class="mb-2">${imagesHtml}</div>
                                    <div class="note-actions">
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteNote(${note.note_id})"><i class="fas fa-trash-alt"></i></button>
                                        <button class="btn btn-sm ${pinBtnClass}" onclick="togglePin(${note.note_id})" title="${pinTitle}">
                                            <i class="fas fa-thumbtack ${pinIconOpacity}"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm" onclick="addLabelToNote(${note.note_id})"><i class="fas fa-tag"></i></button>
                                    </div>
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

    // Example data counts
    $(document).ready(() => {
        fetch('note_stats.php').then(res => res.json()).then(data => {
            $('#noteCount').text(data.total);
            $('#pinnedCount').text(data.pinned);
            $('#labelCount').text(data.labels);
        });

        // Render chart
        new Chart(document.getElementById('notesChart'), {
            type: 'bar',
            data: {
                labels: ['Pinned', 'Unpinned'],
                datasets: [{ label: 'Notes', data: [5, 15], backgroundColor: ['#007bff', '#6c757d'] }]
            }
        });

        // Load notes
        loadNotes();
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

    $('#addLabelBtn').on('click', addLabelPrompt);
    function addLabelPrompt() {
        const labelName = prompt("Enter new label name:");
        if (!labelName) return;

        // Send the label data using AJAX
        $.ajax({
            url: 'add_label.php',  // The PHP file to handle the request
            method: 'POST',        // HTTP method
            data: { name: labelName },  // Data to send (label name)
            dataType: 'json',      // Expected response type
            success: function (response) {
                if (response.success && response.label) {
                    const { label_id, name } = response.label;

                    // Create new label badge
                    const badge = $(`
                        <div id="label-${label_id}" class="badge bg-secondary d-flex align-items-center me-2">
                            <span class="label-name me-2" onclick="filterByLabel('${name}')">${name}</span>
                            <button class="btn btn-sm btn-light me-1" onclick="renameLabelPrompt('${label_id}', '${name}')">✎</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteLabel('${label_id}')">×</button>
                        </div>
                    `);

                    // Append the new badge to the label list
                    $('#label-list').append(badge);
                } else {
                    alert(response.error || "Failed to add label.");
                }
            },
            error: function (xhr, status, error) {
                // Handle any errors from the AJAX request
                console.error("AJAX Error: ", status, error);
                alert("An error occurred while adding the label. Please try again.");
            }
        });
    }

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

    $('#addLabelBtn').on('click', addLabelPrompt);
    function addLabelPrompt() {
        console.log("Filtering notes by label:", labelName);
        const labelName = prompt("Enter new label name:");
        if (!labelName) return;

        // Send the label data using AJAX
        $.ajax({
            url: 'add_label.php',  // The PHP file to handle the request
            method: 'POST',        // HTTP method
            data: { name: labelName },  // Data to send (label name)
            dataType: 'json',      // Expected response type
            success: function (response) {
                if (response.success && response.label) {
                    const { label_id, name } = response.label;

                    // Create new label badge
                    const badge = $(`
                        <div id="label-${label_id}" class="badge bg-secondary d-flex align-items-center me-2">
                            <span class="label-name me-2" onclick="filterByLabel('${name}')">${name}</span>
                            <button class="btn btn-sm btn-light me-2" onclick="renameLabelPrompt('${label_id}', '${name}')"><i class="fas fa-pencil-alt"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deleteLabel('${label_id}')"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    `);

                    // Append the new badge to the label list
                    $('.label-list').append(badge);
                } else {
                    alert(response.error || "Failed to add label.");
                }
            },
            error: function (xhr, status, error) {
                // Handle any errors from the AJAX request
                console.error("AJAX Error: ", status, error);
                alert("An error occurred while adding the label. Please try again.");
            }
        });
    }

    function renameLabelPrompt(labelId, currentName) {
        const newName = prompt("Enter new label name:", currentName);
        if (!newName || newName === currentName) return;

        $.post('rename_label.php', { label_id: labelId, new_name: newName }, function (response) {
            if (response.success) {
                $(`#label-${labelId} .label-name`).text(newName);
                $(`#label-${labelId} .label-name`).attr("onclick", `filterByLabel('${newName}')`);
            } else {
                alert(response.error || "Failed to rename label.");
            }
        }, 'json');
    }

    function deleteLabel(labelId) {
        if (!confirm("Are you sure you want to delete this label?")) return;

        $.post('delete_label.php', { label_id: labelId }, function(response) {
            if (response.success) {
                $(`#label-${labelId}`).remove();
            } else {
                alert(response.error || "Failed to delete label.");
            }
        }, 'json');
    }


    function filterByLabel(labelName) {
        $.post('filter_notes_by_labels.php', { label: labelName }, function(response) {
            try {
                const data = typeof response === "object" ? response : JSON.parse(response);
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
                    alert(data.error || "Failed to filter by label");
                }
            } catch (e) {
                alert("Invalid response from server.");
            }
        });
    }

    function loadAllNotes() {
    $.post('search_notes.php', { query: '' }, function (response) {
        let data;
        try {
            data = typeof response === "object" ? response : JSON.parse(response);
        } catch (e) {
            alert("Invalid response from server.");
            return;
        }

        if (data.success) {
           if (data.notes.length === 0) {
                    $('#notes-container').html('<div class="col"><div class="alert alert-info">No notes found.</div></div>');
                    return;
                }

                const notesHtml = data.notes.map(note => {
                const noteColor = note.note_color || '#fff';
                const fontSize = note.font_size || '1rem';
                const pinned = note.pinned_at ? true : false;
                const borderClass = pinned ? 'border-warning' : '';
                const pinBtnClass = pinned ? 'btn-warning' : 'btn-outline-warning';
                const pinIconOpacity = pinned ? '' : 'opacity-50';
                const pinTitle = pinned ? 'Unpin Note' : 'Pin Note';
                const labelsHtml = note.labels && note.labels.length
                        ? `<div class="mb-2">` + note.labels.map(label => `
                            <span class="badge bg-primary me-1">#${label.name}</span>
                        `).join('') + `</div>`
                        : '';

                    const imagesHtml = note.images
                        ? note.images.split(',').map(img => `
                            <img src="${img.trim()}" 
                                class="img-fluid rounded border my-1" 
                                style="max-height: 200px; object-fit: contain;" 
                                alt="Attached image for note ${note.note_id}" />
                        `).join('')
                        : '';

                    return `
                        <div class="col mb-4">
                            <div class="card note-card ${borderClass}" id="note-${note.note_id}" style="background-color: ${noteColor}; font-size: ${fontSize};">
                                <div class="card-body">
                                    <div class="mb-2">
                                        <input type="text" class="form-control editable-title" data-id="${note.note_id}" id="title-${note.note_id}" value="${note.title}" placeholder="Note title">
                                    </div>
                                    <div class="mb-2">
                                        <textarea class="form-control editable-content" data-id="${note.note_id}" id="content-${note.note_id}" rows="3" placeholder="Write your note...">${note.content}</textarea>
                                    </div>
                                    ${labelsHtml}
                                    <div class="mb-2">${imagesHtml}</div>
                                    <div class="note-actions">
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteNote(${note.note_id})"><i class="fas fa-trash-alt"></i></button>
                                        <button class="btn btn-sm ${pinBtnClass}" onclick="togglePin(${note.note_id})" title="${pinTitle}">
                                            <i class="fas fa-thumbtack ${pinIconOpacity}"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm" onclick="addLabelToNote(${note.note_id})"><i class="fas fa-tag"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
            }).join('');

            $('#notes-container').html(notesHtml || '<div class="col"><div class="alert alert-info">No notes found.</div></div>');
        } else {
            alert(data.error || "Failed to load notes");
        }
    });
}

    function addLabelToNote(noteId) {
        const labelName = prompt("Enter the label ID to assign to the note:");

        if (labelName) {
            $.ajax({
                url: 'assign_label.php',
                method: 'POST',
                data: {
                    note_id: noteId,
                    name: labelName
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert("Label added to the note successfully!");
                        // Optionally update the UI to reflect the new label
                    } else {
                        alert(response.error || "Failed to add label.");
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                    alert("An error occurred while adding the label to the note. Please try again.");
                }
            });
        }
    }

    function toggleForm() {
        const form = document.getElementById("note-form-container");
        form.classList.toggle("show");
    }

    document.getElementById('note-images').addEventListener('change', function () {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = ''; // Clear previous
        const files = this.files;

        for (const file of files) {
            const reader = new FileReader();
            reader.onload = function (e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'me-2 mb-2';
            img.style.maxWidth = '100px';
            img.style.maxHeight = '100px';
            preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    });
</script>
</body>
</html>
