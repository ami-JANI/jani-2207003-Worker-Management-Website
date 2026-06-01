<?php include "db_connect.php"; ?>
<?php
if (empty($_GET['id'])) { header("Location: index.php"); exit; }
$id = (int)$_GET['id'];

// Fetch existing worker
$stmt = $conn->prepare("SELECT * FROM workers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { header("Location: index.php"); exit; }

$error = '';

if (isset($_POST['update'])) {
    $name         = trim($_POST['name']);
    $profession   = trim($_POST['profession']);
    $skill        = trim($_POST['skill']);
    $experience   = (int)$_POST['experience'];
    $location     = trim($_POST['location']);
    $phone        = trim($_POST['phone']);
    $rating       = (float)($_POST['rating'] ?? $row['rating']);
    $availability = $_POST['availability'] ?? $row['availability'];
    $photo        = $row['photo']; // keep existing by default

    // Handle new photo upload
    if (!empty($_FILES['photo']['name'])) {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($_FILES['photo']['type'], $allowed)) {
            $error = "Invalid file type. Only JPG, PNG, WEBP, GIF allowed.";
        } else {
            $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('worker_', true) . '.' . $ext;
            $dest     = "uploads/" . $filename;
            if (!is_dir("uploads")) mkdir("uploads", 0755, true);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                // Delete old photo if exists
                if ($row['photo'] && file_exists("uploads/" . $row['photo'])) {
                    unlink("uploads/" . $row['photo']);
                }
                $photo = $filename;
            } else {
                $error = "Failed to upload photo.";
            }
        }
    }

    // Handle photo removal
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1' && empty($_FILES['photo']['name'])) {
        if ($row['photo'] && file_exists("uploads/" . $row['photo'])) {
            unlink("uploads/" . $row['photo']);
        }
        $photo = '';
    }

    if (!$error) {
        $stmt2 = $conn->prepare("UPDATE workers SET name=?, profession=?, skill=?, experience=?, location=?, phone=?, photo=?, rating=?, availability=? WHERE id=?");
        $stmt2->bind_param("sssisssdsi", $name, $profession, $skill, $experience, $location, $phone, $photo, $rating, $availability, $id);
        if ($stmt2->execute()) {
            header("Location: worker_details.php?id=$id&updated=1");
            exit;
        } else {
            $error = "Database error: " . $conn->error;
        }
    }

    // Re-populate $row with submitted values on error
    $row = array_merge($row, compact('name','profession','skill','experience','location','phone','rating','availability','photo'));
}

$name       = htmlspecialchars($row['name']);
$profession = htmlspecialchars($row['profession']);
$skill      = htmlspecialchars($row['skill'] ?? '');
$experience = htmlspecialchars($row['experience']);
$location   = htmlspecialchars($row['location']);
$phone      = htmlspecialchars($row['phone']);
$rating     = (float)($row['rating'] ?? 0);
$avail      = $row['availability'] ?? 'available';
$photo      = $row['photo'] ?? '';
$initials   = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $row['name']), 0, 2))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit <?= $name ?> — WorkForce Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:       #0f1117;
    --surface:  #181c27;
    --surface2: #1e2333;
    --border:   #2a3045;
    --accent:   #4f8ef7;
    --accent2:  #7c5cfc;
    --text:     #e8eaf0;
    --muted:    #8891aa;
    --danger:   #f87171;
    --success:  #4ade80;
    --warn:     #f59e0b;
    --navbar-h: 62px;
}

body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
}

/* ── NAVBAR ── */
.navbar {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: var(--navbar-h);
    background: rgba(15,17,23,0.92);
    backdrop-filter: blur(14px);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 28px;
    z-index: 100;
}

.navbar-brand {
    font-family: 'Syne', sans-serif;
    font-size: 19px;
    font-weight: 800;
    letter-spacing: -0.5px;
    color: var(--text);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 9px;
}

.navbar-brand .dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
}

.nav-links { display: flex; align-items: center; gap: 6px; }
.nav-links a {
    color: var(--muted);
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 500;
    padding: 7px 14px;
    border-radius: 8px;
    transition: color .2s, background .2s;
}
.nav-links a:hover { color: var(--text); background: var(--surface2); }
.nav-links .btn-add {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff !important;
    font-weight: 600;
}

/* ── PAGE ── */
.page {
    padding-top: calc(var(--navbar-h) + 36px);
    padding-bottom: 60px;
    max-width: 720px;
    margin: 0 auto;
    padding-left: 24px;
    padding-right: 24px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--muted);
    font-size: 13px;
    text-decoration: none;
    margin-bottom: 24px;
    transition: color .2s;
}
.back-link:hover { color: var(--text); }
.back-link svg { width:14px; height:14px; }

.form-header { margin-bottom: 24px; }

.form-header h1 {
    font-family: 'Syne', sans-serif;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.form-header p { color: var(--muted); font-size: 14px; margin-top: 5px; }

/* Alert */
.alert {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 500;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.alert.error { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.3); color: var(--danger); }

/* ── FORM ── */
form {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    animation: fadeUp .4s ease both;
}

@keyframes fadeUp {
    from { opacity:0; transform:translateY(16px); }
    to   { opacity:1; transform:translateY(0); }
}

/* Photo section */
.photo-section {
    background: var(--surface2);
    border-bottom: 1px solid var(--border);
    padding: 24px 28px;
    display: flex;
    align-items: center;
    gap: 22px;
}

.photo-preview-wrap {
    flex-shrink: 0;
    width: 96px;
    height: 96px;
    border-radius: 12px;
    overflow: hidden;
    background: var(--bg);
    border: 2px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: border-color .2s;
}

.photo-preview-wrap.has-img { border-color: var(--accent); border-style: solid; }

.photo-preview-wrap img { width:100%; height:100%; object-fit:cover; display:block; }

.avatar-fallback {
    width:100%; height:100%;
    display:flex; align-items:center; justify-content:center;
    background: linear-gradient(135deg, #1e2845, #1a1e30);
    font-family: 'Syne', sans-serif;
    font-size: 30px; font-weight: 800;
    color: rgba(255,255,255,.18);
}

.photo-actions { display: flex; flex-direction: column; gap: 10px; flex: 1; }

.photo-actions h3 {
    font-family: 'Syne', sans-serif;
    font-size: 15px; font-weight: 700;
}

.photo-actions p { font-size: 12.5px; color: var(--muted); line-height: 1.5; }

.photo-btns { display: flex; gap: 8px; flex-wrap: wrap; }

.btn-upload-trigger {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-size: 13px;
    font-weight: 500;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: border-color .2s, background .2s;
}
.btn-upload-trigger:hover { border-color: var(--accent); background: rgba(79,142,247,.07); }
.btn-upload-trigger svg { width:13px; height:13px; }

.btn-remove-photo {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: transparent;
    border: 1px solid rgba(248,113,113,.3);
    border-radius: 8px;
    color: var(--danger);
    font-size: 13px;
    font-weight: 500;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: background .2s;
}
.btn-remove-photo:hover { background: rgba(248,113,113,.08); }
.btn-remove-photo svg { width:13px; height:13px; }

input[type="file"] { display: none; }

/* Fields */
.fields {
    padding: 28px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.field { display: flex; flex-direction: column; gap: 7px; }
.field.full { grid-column: 1 / -1; }

.field label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--muted);
    letter-spacing: .5px;
    text-transform: uppercase;
}

.field input,
.field select {
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 9px;
    color: var(--text);
    padding: 10px 14px;
    font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    width: 100%;
}
.field input:focus,
.field select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79,142,247,.12);
}
.field input::placeholder { color: #55607a; }

.field select {
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238891aa' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    cursor: pointer;
}

/* Changed indicator */
.field input.changed,
.field select.changed {
    border-color: rgba(245,158,11,.5);
    box-shadow: 0 0 0 3px rgba(245,158,11,.08);
}

/* Star rating */
.star-group {
    direction: rtl;
    display: inline-flex;
    gap: 4px;
}
.star-group input[type="radio"] { display: none; }
.star-group label.star {
    font-size: 24px;
    cursor: pointer;
    color: var(--border);
    transition: color .15s;
    text-transform: none;
    letter-spacing: 0;
}
.star-group label.star:hover,
.star-group label.star:hover ~ label.star,
.star-group input[type="radio"]:checked ~ label.star {
    color: var(--warn);
}
.rating-val {
    font-size: 13px;
    color: var(--muted);
    margin-left: 8px;
    direction: ltr;
    align-self: center;
}

/* Availability toggle */
.avail-toggle { display: flex; gap: 10px; }
.avail-option { flex: 1; position: relative; }
.avail-option input[type="radio"] { display: none; }
.avail-option label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 12px;
    border-radius: 9px;
    border: 1px solid var(--border);
    background: var(--surface2);
    cursor: pointer;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--muted);
    transition: all .2s;
    text-transform: none;
    letter-spacing: 0;
}
.avail-option input[type="radio"]:checked + label {
    border-color: var(--accent);
    background: rgba(79,142,247,.1);
    color: var(--text);
}
.avail-option.avail-busy input[type="radio"]:checked + label {
    border-color: var(--danger);
    background: rgba(248,113,113,.08);
    color: var(--danger);
}
.avail-dot { width:8px; height:8px; border-radius:50%; background:currentColor; }
.avail-available .avail-dot { background: var(--success); }
.avail-busy      .avail-dot { background: var(--danger); }

/* Divider */
.fields-divider {
    margin: 0 28px;
    height: 1px;
    background: var(--border);
}

/* Form footer */
.form-footer {
    padding: 20px 28px 28px;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 12px;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.footer-left { display: flex; gap: 10px; align-items: center; }

.change-note {
    font-size: 12px;
    color: var(--muted);
    display: none;
    align-items: center;
    gap: 5px;
}
.change-note.visible { display: flex; }
.change-dot { width:6px; height:6px; border-radius:50%; background:var(--warn); }

.btn-cancel {
    padding: 11px 22px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 9px;
    color: var(--muted);
    font-size: 14px;
    font-weight: 500;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: color .2s, border-color .2s;
}
.btn-cancel:hover { color: var(--text); border-color: var(--muted); }

.btn-submit {
    padding: 11px 28px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border: none;
    border-radius: 9px;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: opacity .2s, transform .15s;
}
.btn-submit:hover  { opacity: .88; }
.btn-submit:active { transform: scale(.97); }
.btn-submit svg { width:15px; height:15px; }

@media (max-width: 600px) {
    .fields { grid-template-columns: 1fr; }
    .field.full { grid-column: 1; }
    .photo-section { flex-direction: column; text-align: center; }
    .photo-btns { justify-content: center; }
    .form-footer { flex-direction: column-reverse; }
    .btn-cancel, .btn-submit { width:100%; justify-content:center; }
}
</style>
</head>
<body>

<nav class="navbar">
    <a class="navbar-brand" href="index.php">
        <div class="dot"></div>
        WorkForce Manager
    </a>
    <div class="nav-links">
        <a href="index.php">Browse</a>
        <a href="#">Bookings</a>
        <a href="#">Analytics</a>
        <a href="add_worker.php" class="btn-add">+ Add Worker</a>
    </div>
</nav>

<div class="page">

    <a href="worker_details.php?id=<?= $id ?>" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Profile
    </a>

    <div class="form-header">
        <h1>Edit Worker</h1>
        <p>Update the details for <strong style="color:var(--text)"><?= $name ?></strong>.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12" y2="16.5"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="editForm">
        <input type="hidden" name="remove_photo" id="removePhotoInput" value="0">

        <!-- Photo section -->
        <div class="photo-section">
            <div class="photo-preview-wrap <?= $photo ? 'has-img' : '' ?>" id="previewWrap">
                <?php if ($photo): ?>
                    <img id="photoPreview" src="uploads/<?= htmlspecialchars($photo) ?>" alt="<?= $name ?>">
                    <div class="avatar-fallback" id="photoFallback" style="display:none"><?= $initials ?></div>
                <?php else: ?>
                    <img id="photoPreview" src="" alt="" style="display:none">
                    <div class="avatar-fallback" id="photoFallback"><?= $initials ?></div>
                <?php endif; ?>
            </div>
            <div class="photo-actions">
                <h3>Profile Photo</h3>
                <p>Upload a new photo to replace the current one.<br>JPG, PNG or WEBP · Max 5MB recommended.</p>
                <div class="photo-btns">
                    <button type="button" class="btn-upload-trigger" onclick="document.getElementById('photoInput').click()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <?= $photo ? 'Change Photo' : 'Upload Photo' ?>
                    </button>
                    <?php if ($photo): ?>
                    <button type="button" class="btn-remove-photo" id="removePhotoBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Remove
                    </button>
                    <?php endif; ?>
                </div>
                <input type="file" id="photoInput" name="photo" accept="image/*" onchange="previewPhoto(this)">
            </div>
        </div>

        <!-- Fields -->
        <div class="fields">

            <div class="field">
                <label>Full Name</label>
                <input type="text" name="name" required
                       value="<?= $name ?>"
                       data-original="<?= $name ?>"
                       oninput="markChanged(this)">
            </div>

            <div class="field">
                <label>Profession</label>
                <input type="text" name="profession" required
                       value="<?= $profession ?>"
                       data-original="<?= $profession ?>"
                       oninput="markChanged(this)">
            </div>

            <div class="field">
                <label>Primary Skill</label>
                <input type="text" name="skill"
                       value="<?= $skill ?>"
                       data-original="<?= $skill ?>"
                       oninput="markChanged(this)">
            </div>

            <div class="field">
                <label>Experience (years)</label>
                <input type="number" name="experience" min="0" max="60"
                       value="<?= $experience ?>"
                       data-original="<?= $experience ?>"
                       oninput="markChanged(this)">
            </div>

            <div class="field">
                <label>Location</label>
                <input type="text" name="location"
                       value="<?= $location ?>"
                       data-original="<?= $location ?>"
                       oninput="markChanged(this)">
            </div>

            <div class="field">
                <label>Phone Number</label>
                <input type="text" name="phone"
                       value="<?= $phone ?>"
                       data-original="<?= $phone ?>"
                       oninput="markChanged(this)">
            </div>

            <!-- Rating -->
            <div class="field">
                <label>Rating</label>
                <div style="display:flex; align-items:center;">
                    <div class="star-group" id="starGroup">
                        <?php for($i=5; $i>=1; $i--): ?>
                        <input type="radio" name="rating" id="star<?=$i?>" value="<?=$i?>"
                               <?= (round($rating) == $i) ? 'checked' : '' ?>>
                        <label class="star" for="star<?=$i?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-val" id="ratingLabel">
                        <?= $rating > 0 ? number_format($rating,1).' / 5' : 'Not rated' ?>
                    </span>
                </div>
            </div>

            <!-- Availability -->
            <div class="field">
                <label>Availability</label>
                <div class="avail-toggle">
                    <div class="avail-option avail-available">
                        <input type="radio" name="availability" id="avail-yes" value="available"
                               <?= ($avail === 'available') ? 'checked' : '' ?>>
                        <label for="avail-yes"><span class="avail-dot"></span> Available</label>
                    </div>
                    <div class="avail-option avail-busy">
                        <input type="radio" name="availability" id="avail-no" value="busy"
                               <?= ($avail === 'busy') ? 'checked' : '' ?>>
                        <label for="avail-no"><span class="avail-dot"></span> Busy</label>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="form-footer">
            <div class="footer-left">
                <span class="change-note" id="changeNote">
                    <span class="change-dot"></span> Unsaved changes
                </span>
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="worker_details.php?id=<?= $id ?>" class="btn-cancel">Cancel</a>
                <button type="submit" name="update" class="btn-submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save Changes
                </button>
            </div>
        </div>
    </form>
</div>

<script>
// Photo preview
function previewPhoto(input) {
    const wrap = document.getElementById('previewWrap');
    const img  = document.getElementById('photoPreview');
    const fb   = document.getElementById('photoFallback');
    const ri   = document.getElementById('removePhotoInput');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            img.style.display = 'block';
            if (fb) fb.style.display = 'none';
            wrap.classList.add('has-img');
            ri.value = '0';
        };
        reader.readAsDataURL(input.files[0]);
        markDirty();
    }
}

// Remove photo
const removeBtn = document.getElementById('removePhotoBtn');
if (removeBtn) {
    removeBtn.addEventListener('click', () => {
        const wrap = document.getElementById('previewWrap');
        const img  = document.getElementById('photoPreview');
        const fb   = document.getElementById('photoFallback');
        const ri   = document.getElementById('removePhotoInput');
        img.src = ''; img.style.display = 'none';
        if (fb) fb.style.display = 'flex';
        wrap.classList.remove('has-img');
        ri.value = '1';
        document.getElementById('photoInput').value = '';
        markDirty();
    });
}

// Star rating label
document.querySelectorAll('input[name="rating"]').forEach(s =>
    s.addEventListener('change', () => {
        const v = document.querySelector('input[name="rating"]:checked')?.value;
        document.getElementById('ratingLabel').textContent = v ? v + '.0 / 5' : 'Not rated';
        markDirty();
    })
);

// Availability change
document.querySelectorAll('input[name="availability"]').forEach(r =>
    r.addEventListener('change', markDirty)
);

// Unsaved changes indicator
let dirty = false;
function markDirty() {
    dirty = true;
    document.getElementById('changeNote').classList.add('visible');
}

function markChanged(el) {
    const changed = el.value !== el.dataset.original;
    el.classList.toggle('changed', changed);
    if (changed) markDirty();
    // If all fields are back to original, hide note
    const anyChanged = [...document.querySelectorAll('[data-original]')]
        .some(i => i.value !== i.dataset.original);
    if (!anyChanged && !dirty) {
        document.getElementById('changeNote').classList.remove('visible');
    }
}

// Warn on leaving with unsaved changes
window.addEventListener('beforeunload', e => {
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
});
document.querySelector('form').addEventListener('submit', () => { dirty = false; });
document.querySelector('.btn-cancel').addEventListener('click', () => { dirty = false; });
</script>

</body>
</html>