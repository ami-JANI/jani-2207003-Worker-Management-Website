<?php include "db_connect.php"; ?>
<?php
$success = false;
$error   = '';

if (isset($_POST['submit'])) {
    $name       = trim($_POST['name']);
    $profession = trim($_POST['profession']);
    $skill      = trim($_POST['skill']);
    $experience = (int)$_POST['experience'];
    $location   = trim($_POST['location']);
    $phone      = trim($_POST['phone']);
    $rating     = isset($_POST['rating']) ? (float)$_POST['rating'] : 0;
    $availability = $_POST['availability'] ?? 'available';

    // Handle photo upload
    $photo = '';
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
                $photo = $filename;
            } else {
                $error = "Failed to upload photo.";
            }
        }
    }

    if (!$error) {
        // Admin-added workers are approved immediately and have no login password.
        $ins = oci_parse($conn, "INSERT INTO workers
            (name, profession, skill, experience, location, phone, photo, rating, availability, approved)
            VALUES (:name, :prof, :skill, :exp, :loc, :phone, :photo, :rating, :avail, 1)");
        oci_bind_by_name($ins, ':name',   $name);
        oci_bind_by_name($ins, ':prof',   $profession);
        oci_bind_by_name($ins, ':skill',  $skill);
        oci_bind_by_name($ins, ':exp',    $experience);
        oci_bind_by_name($ins, ':loc',    $location);
        oci_bind_by_name($ins, ':phone',  $phone);
        oci_bind_by_name($ins, ':photo',  $photo);
        oci_bind_by_name($ins, ':rating', $rating);
        oci_bind_by_name($ins, ':avail',  $availability);
        if (@oci_execute($ins, OCI_COMMIT_ON_SUCCESS)) {
            header("Location: workers.php?added=1");
            exit;
        } else {
            $e = oci_error($ins);
            $error = "Database error: " . ($e['message'] ?? 'insert failed');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Worker — WorkForce Manager</title>
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
.nav-links .active { color: var(--text); background: var(--surface2); }

/* ── PAGE ── */
.page {
    padding-top: calc(var(--navbar-h) + 40px);
    padding-bottom: 60px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
}

.form-card {
    width: 100%;
    max-width: 720px;
    margin: 0 20px;
    animation: slideUp .4s ease both;
}

@keyframes slideUp {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}

.form-header {
    margin-bottom: 28px;
}

.form-header .back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--muted);
    font-size: 13px;
    text-decoration: none;
    margin-bottom: 16px;
    transition: color .2s;
}
.form-header .back-link:hover { color: var(--text); }
.form-header .back-link svg { width:14px; height:14px; }

.form-header h1 {
    font-family: 'Syne', sans-serif;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.form-header p {
    color: var(--muted);
    font-size: 14px;
    margin-top: 5px;
}

/* Error/Success banner */
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
.alert.error   { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.3); color: var(--danger); }
.alert.success { background: rgba(74,222,128,.1);  border: 1px solid rgba(74,222,128,.3);  color: var(--success); }

/* ── FORM SHELL ── */
form {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
}

/* Photo upload area at top */
.photo-section {
    background: var(--surface2);
    border-bottom: 1px solid var(--border);
    padding: 28px;
    display: flex;
    align-items: center;
    gap: 24px;
}

.photo-preview-wrap {
    flex-shrink: 0;
    width: 96px;
    height: 96px;
    border-radius: 12px;
    overflow: hidden;
    background: var(--bg);
    border: 2px dashed var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color .2s;
}

.photo-preview-wrap.has-img { border-style: solid; border-color: var(--accent); }

.photo-preview-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}

.photo-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    color: var(--muted);
}

.photo-placeholder svg { width:28px; height:28px; opacity:.5; }
.photo-placeholder span { font-size: 11px; }

.photo-upload-info h3 {
    font-family: 'Syne', sans-serif;
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 4px;
}

.photo-upload-info p {
    font-size: 12.5px;
    color: var(--muted);
    line-height: 1.5;
    margin-bottom: 12px;
}

.btn-upload-trigger {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
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
.btn-upload-trigger svg { width:14px; height:14px; }

input[type="file"] { display: none; }

/* Fields grid */
.fields {
    padding: 28px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.field.full { grid-column: 1 / -1; }

.field label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--muted);
    letter-spacing: .5px;
    text-transform: uppercase;
}

.field input,
.field select,
.field textarea {
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
.field select:focus,
.field textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79,142,247,.12);
}

.field input::placeholder,
.field textarea::placeholder { color: #55607a; }

.field select {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238891aa' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    cursor: pointer;
}

/* Rating stars */
.star-group {
    display: flex;
    gap: 6px;
    align-items: center;
}

.star-group input[type="radio"] { display: none; }

.star-group label.star {
    font-size: 24px;
    cursor: pointer;
    color: var(--border);
    transition: color .15s, transform .15s;
    text-transform: none;
    letter-spacing: 0;
}

.star-group input[type="radio"]:checked ~ label.star { color: var(--border); }
.star-group label.star:hover,
.star-group label.star:hover ~ label.star { color: var(--border); }

/* JS will handle the coloring; fallback CSS trick (RTL) */
.star-group {
    direction: rtl;
    display: inline-flex;
}

.star-group label.star:hover,
.star-group input[type="radio"]:checked ~ label.star {
    color: #f59e0b;
}

.star-group label.star:hover ~ label.star {
    color: #f59e0b !important;
}

.rating-display {
    font-size: 13px;
    color: var(--muted);
    margin-left: 8px;
    direction: ltr;
}

/* Availability toggle */
.avail-toggle {
    display: flex;
    gap: 10px;
}

.avail-option {
    flex: 1;
    position: relative;
}

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

.avail-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
}

.avail-available .avail-dot { background: var(--success); }
.avail-busy      .avail-dot { background: var(--danger); }

/* Form footer */
.form-footer {
    padding: 20px 28px 28px;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

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
.btn-submit:hover   { opacity: .88; }
.btn-submit:active  { transform: scale(.98); }
.btn-submit svg     { width:16px; height:16px; }

@media (max-width: 600px) {
    .fields { grid-template-columns: 1fr; }
    .field.full { grid-column: 1; }
    .photo-section { flex-direction: column; text-align: center; }
    .form-footer { flex-direction: column-reverse; }
    .btn-cancel, .btn-submit { width:100%; justify-content:center; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a class="navbar-brand" href="workers.php">
        <div class="dot"></div>
        WorkForce Manager
    </a>
    <div class="nav-links">
        <a href="workers.php">Browse</a>
        <a href="#">Bookings</a>
        <a href="#">Analytics</a>
        <a href="add_worker.php" class="active">+ Add Worker</a>
    </div>
</nav>

<div class="page">
    <div class="form-card">

        <div class="form-header">
            <a href="workers.php" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Workers
            </a>
            <h1>Add New Worker</h1>
            <p>Fill in the details to register a new worker profile.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12" y2="16"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <!-- Photo Upload -->
            <div class="photo-section">
                <div class="photo-preview-wrap" id="previewWrap">
                    <div class="photo-placeholder" id="photoPlaceholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>No photo</span>
                    </div>
                    <img id="photoPreview" src="" alt="" style="display:none;">
                </div>
                <div class="photo-upload-info">
                    <h3>Worker Photo</h3>
                    <p>Upload a clear profile photo.<br>JPG, PNG or WEBP · Max 5MB recommended.</p>
                    <button type="button" class="btn-upload-trigger" onclick="document.getElementById('photoInput').click()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Choose Photo
                    </button>
                    <input type="file" id="photoInput" name="photo" accept="image/*" onchange="previewPhoto(this)">
                </div>
            </div>

            <!-- Fields -->
            <div class="fields">

                <div class="field">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="e.g. Rafiq Ahmed" required
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Profession</label>
                    <input type="text" name="profession" placeholder="e.g. Electrician" required
                           value="<?= htmlspecialchars($_POST['profession'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Primary Skill</label>
                    <input type="text" name="skill" placeholder="e.g. Wiring, Solar panels"
                           value="<?= htmlspecialchars($_POST['skill'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Experience (years)</label>
                    <input type="number" name="experience" min="0" max="60" placeholder="e.g. 5"
                           value="<?= htmlspecialchars($_POST['experience'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g. Khulna"
                           value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="e.g. 017XXXXXXXX"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <!-- Rating -->
                <div class="field">
                    <label>Rating</label>
                    <div style="display:flex;align-items:center;gap:4px;">
                        <div class="star-group" id="starGroup">
                            <?php for($i=5;$i>=1;$i--): ?>
                            <input type="radio" name="rating" id="star<?=$i?>" value="<?=$i?>"
                                   <?= (($_POST['rating'] ?? 0) == $i) ? 'checked' : '' ?>>
                            <label class="star" for="star<?=$i?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-display" id="ratingLabel">Not rated</span>
                    </div>
                </div>

                <!-- Availability -->
                <div class="field">
                    <label>Availability</label>
                    <div class="avail-toggle">
                        <div class="avail-option avail-available">
                            <input type="radio" name="availability" id="avail-yes" value="available"
                                   <?= (($_POST['availability'] ?? 'available') === 'available') ? 'checked' : '' ?>>
                            <label for="avail-yes">
                                <span class="avail-dot"></span> Available
                            </label>
                        </div>
                        <div class="avail-option avail-busy">
                            <input type="radio" name="availability" id="avail-no" value="busy"
                                   <?= (($_POST['availability'] ?? '') === 'busy') ? 'checked' : '' ?>>
                            <label for="avail-no">
                                <span class="avail-dot"></span> Busy
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer Actions -->
            <div class="form-footer">
                <a href="workers.php" class="btn-cancel">Cancel</a>
                <button type="submit" name="submit" class="btn-submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                    Add Worker
                </button>
            </div>

        </form>
    </div>
</div>

<script>
// Photo preview
function previewPhoto(input) {
    const wrap = document.getElementById('previewWrap');
    const img  = document.getElementById('photoPreview');
    const ph   = document.getElementById('photoPlaceholder');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            img.style.display = 'block';
            ph.style.display  = 'none';
            wrap.classList.add('has-img');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Star rating label
const stars  = document.querySelectorAll('input[name="rating"]');
const label  = document.getElementById('ratingLabel');

function updateLabel() {
    const checked = document.querySelector('input[name="rating"]:checked');
    label.textContent = checked ? checked.value + '.0 / 5' : 'Not rated';
}

stars.forEach(s => s.addEventListener('change', updateLabel));
updateLabel();
</script>

</body>
</html>