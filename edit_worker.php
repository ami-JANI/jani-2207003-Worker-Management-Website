<?php
include "db_connect.php";
include "auth.php";

// ── Access control ─────────────────────────────────────────────
// Must be logged in
if (!isLoggedIn()) { header("Location: signin.php"); exit; }

if (empty($_GET['id'])) { header("Location: index.php"); exit; }
$id = (int)$_GET['id'];

// Ensure pending columns exist (auto-creates if missing)
$conn->query("ALTER TABLE workers ADD COLUMN IF NOT EXISTS pending_edit TEXT DEFAULT NULL");
$conn->query("ALTER TABLE workers ADD COLUMN IF NOT EXISTS pending_photo VARCHAR(255) DEFAULT NULL");

// Fetch worker
$stmt = $conn->prepare("SELECT * FROM workers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { header("Location: index.php"); exit; }

// Permission check:
// - Admin can edit anyone
// - Worker can only edit their own profile
// - Users cannot edit at all
if (isUser()) { header("Location: index.php"); exit; }
if (isWorker() && $_SESSION['uid'] !== $row['id']) { header("Location: index.php"); exit; }

$isAdmin  = isAdmin();
$error    = '';
$success  = '';

if (isset($_POST['update'])) {
    $name         = trim($_POST['name']);
    $profession   = trim($_POST['profession']);
    $skill        = trim($_POST['skill']);
    $experience   = (int)$_POST['experience'];
    $location     = trim($_POST['location']);
    $phone        = trim($_POST['phone']);
    $availability = $_POST['availability'] ?? $row['availability'];
    $photo        = $row['photo'];

    // Rating — only admin can change rating
    $rating = $isAdmin ? (float)($_POST['rating'] ?? $row['rating']) : (float)$row['rating'];

    // Handle photo upload
    if (!empty($_FILES['photo']['name'])) {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($_FILES['photo']['type'], $allowed)) {
            $error = "Invalid file type. Only JPG, PNG, WEBP, GIF allowed.";
        } else {
            $ext  = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fn   = uniqid('worker_', true) . '.' . $ext;
            $dest = "uploads/$fn";
            if (!is_dir("uploads")) mkdir("uploads", 0755, true);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $photo = $fn;
            } else {
                $error = "Failed to upload photo.";
            }
        }
    }

    // Handle photo removal
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1' && empty($_FILES['photo']['name'])) {
        $photo = '';
    }

    if (!$error) {
        if ($isAdmin) {
            // Admin: apply changes immediately, clear any pending edit
            $oldPhoto = $row['photo'];
            $stmt2 = $conn->prepare("UPDATE workers SET name=?, profession=?, skill=?, experience=?, location=?, phone=?, photo=?, rating=?, availability=?, pending_edit=NULL, pending_photo=NULL WHERE id=?");
            $stmt2->bind_param("sssisssdsi", $name, $profession, $skill, $experience, $location, $phone, $photo, $rating, $availability, $id);
            if ($stmt2->execute()) {
                // Delete old photo only if replaced
                if ($oldPhoto && $oldPhoto !== $photo && file_exists("uploads/$oldPhoto")) {
                    unlink("uploads/$oldPhoto");
                }
                // Notify worker
                sendNotification($conn, 'worker', $id, "Your profile has been updated by an admin.", "worker_details.php?id=$id");
                header("Location: worker_details.php?id=$id&updated=1");
                exit;
            } else {
                $error = "Database error: " . $conn->error;
            }
        } else {
            // Worker: store as pending edit, set approved=0 until admin reviews
            $pendingData = json_encode([
                'name'         => $name,
                'profession'   => $profession,
                'skill'        => $skill,
                'experience'   => $experience,
                'location'     => $location,
                'phone'        => $phone,
                'availability' => $availability,
            ]);
            $pendingPhoto = ($photo !== $row['photo']) ? $photo : null;

            $stmt2 = $conn->prepare("UPDATE workers SET pending_edit=?, pending_photo=? WHERE id=?");
            $stmt2->bind_param("ssi", $pendingData, $pendingPhoto, $id);
            if ($stmt2->execute()) {
                // Notify all admins
                $admins = $conn->query("SELECT id FROM admins");
                while ($a = $admins->fetch_assoc()) {
                    sendNotification($conn, 'admin', $a['id'], "Worker \"{$row['name']}\" submitted a profile edit — awaiting your approval.", "admin.php");
                }
                header("Location: worker_details.php?id=$id&pending=1");
                exit;
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}

// Use pending data to show in form if worker has pending edit
$pending = null;
if (!$isAdmin && !empty($row['pending_edit'])) {
    $pending = json_decode($row['pending_edit'], true);
}

$display = $pending ?? $row; // show pending values in form if they exist

$name       = htmlspecialchars($display['name']       ?? $row['name']);
$profession = htmlspecialchars($display['profession'] ?? $row['profession']);
$skill      = htmlspecialchars($display['skill']      ?? $row['skill'] ?? '');
$experience = htmlspecialchars($display['experience'] ?? $row['experience']);
$location   = htmlspecialchars($display['location']   ?? $row['location']);
$phone      = htmlspecialchars($display['phone']      ?? $row['phone']);
$avail      = $display['availability']                ?? $row['availability'] ?? 'available';
$rating     = (float)($row['rating'] ?? 0);
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
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--bg:#0f1117;--surface:#181c27;--surface2:#1e2333;--border:#2a3045;--accent:#4f8ef7;--accent2:#7c5cfc;--text:#e8eaf0;--muted:#8891aa;--danger:#f87171;--success:#4ade80;--warn:#f59e0b;--navbar-h:62px;}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;}
</style>
<style><?php include "navbar.css"; ?></style>
<style><?php include "footer.css"; ?></style>
<style>
.page{padding-top:calc(var(--navbar-h)+36px);padding-bottom:60px;max-width:720px;margin:0 auto;padding-left:24px;padding-right:24px;}
.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--muted);font-size:13px;text-decoration:none;margin-bottom:24px;transition:color .2s;}
.back-link:hover{color:var(--text);}
.back-link svg{width:14px;height:14px;}
.form-header{margin-bottom:24px;}
.form-header h1{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;letter-spacing:-0.5px;}
.form-header p{color:var(--muted);font-size:14px;margin-top:5px;}

/* Pending banner */
.pending-banner{display:flex;align-items:center;gap:10px;padding:13px 16px;border-radius:10px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);color:var(--warn);font-size:13.5px;font-weight:500;margin-bottom:20px;}
.pending-banner svg{width:18px;height:18px;flex-shrink:0;}

/* Admin badge */
.admin-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(124,92,252,.1);border:1px solid rgba(124,92,252,.25);color:var(--accent2);margin-bottom:20px;}

.alert{padding:12px 16px;border-radius:10px;font-size:13.5px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.alert.error  {background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:var(--danger);}
.alert.success{background:rgba(74,222,128,.1); border:1px solid rgba(74,222,128,.3); color:var(--success);}

form{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;animation:fadeUp .4s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

.photo-section{background:var(--surface2);border-bottom:1px solid var(--border);padding:24px 28px;display:flex;align-items:center;gap:22px;}
.photo-preview-wrap{flex-shrink:0;width:96px;height:96px;border-radius:12px;overflow:hidden;background:var(--bg);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;transition:border-color .2s;}
.photo-preview-wrap.has-img{border-color:var(--accent);border-style:solid;}
.photo-preview-wrap img{width:100%;height:100%;object-fit:cover;display:block;}
.avatar-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1e2845,#1a1e30);font-family:'Syne',sans-serif;font-size:30px;font-weight:800;color:rgba(255,255,255,.18);}
.photo-actions{display:flex;flex-direction:column;gap:10px;flex:1;}
.photo-actions h3{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;}
.photo-actions p{font-size:12.5px;color:var(--muted);line-height:1.5;}
.photo-btns{display:flex;gap:8px;flex-wrap:wrap;}
.btn-upload-trigger{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;font-weight:500;font-family:'DM Sans',sans-serif;cursor:pointer;transition:border-color .2s;}
.btn-upload-trigger:hover{border-color:var(--accent);}
.btn-upload-trigger svg{width:13px;height:13px;}
.btn-remove-photo{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:transparent;border:1px solid rgba(248,113,113,.3);border-radius:8px;color:var(--danger);font-size:13px;font-weight:500;font-family:'DM Sans',sans-serif;cursor:pointer;transition:background .2s;}
.btn-remove-photo:hover{background:rgba(248,113,113,.08);}
input[type="file"]{display:none;}

.fields{padding:28px;display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.field{display:flex;flex-direction:column;gap:7px;}
.field.full{grid-column:1/-1;}
.field label{font-size:11.5px;font-weight:600;color:var(--muted);letter-spacing:.5px;text-transform:uppercase;}
.field input,.field select{background:var(--surface2);border:1px solid var(--border);border-radius:9px;color:var(--text);padding:10px 14px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;width:100%;}
.field input:focus,.field select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,142,247,.12);}
.field input::placeholder{color:#55607a;}
.field input:disabled,.field select:disabled{opacity:.45;cursor:not-allowed;}
.field select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238891aa' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;cursor:pointer;}

.field input.changed,.field select.changed{border-color:rgba(245,158,11,.5);box-shadow:0 0 0 3px rgba(245,158,11,.08);}

/* Stars */
.star-group{direction:rtl;display:inline-flex;gap:4px;}
.star-group input[type="radio"]{display:none;}
.star-group label.star{font-size:24px;cursor:pointer;color:var(--border);transition:color .15s;text-transform:none;letter-spacing:0;}
.star-group label.star:hover,.star-group label.star:hover~label.star,.star-group input[type="radio"]:checked~label.star{color:var(--warn);}
.rating-val{font-size:13px;color:var(--muted);margin-left:8px;direction:ltr;align-self:center;}

/* Avail toggle */
.avail-toggle{display:flex;gap:10px;}
.avail-option{flex:1;}
.avail-option input[type="radio"]{display:none;}
.avail-option label{display:flex;align-items:center;justify-content:center;gap:6px;padding:9px 12px;border-radius:9px;border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-size:13.5px;font-weight:500;color:var(--muted);transition:all .2s;text-transform:none;letter-spacing:0;}
.avail-option input[type="radio"]:checked+label{border-color:var(--accent);background:rgba(79,142,247,.1);color:var(--text);}
.avail-option.avail-busy input[type="radio"]:checked+label{border-color:var(--danger);background:rgba(248,113,113,.08);color:var(--danger);}
.avail-dot{width:8px;height:8px;border-radius:50%;}
.avail-available .avail-dot{background:var(--success);}
.avail-busy      .avail-dot{background:var(--danger);}

.form-footer{padding:20px 28px 28px;border-top:1px solid var(--border);display:flex;gap:12px;justify-content:space-between;align-items:center;flex-wrap:wrap;}
.change-note{font-size:12px;color:var(--muted);display:none;align-items:center;gap:5px;}
.change-note.visible{display:flex;}
.change-dot{width:6px;height:6px;border-radius:50%;background:var(--warn);}
.btn-cancel{padding:11px 22px;background:var(--surface2);border:1px solid var(--border);border-radius:9px;color:var(--muted);font-size:14px;font-weight:500;font-family:'DM Sans',sans-serif;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;transition:color .2s,border-color .2s;}
.btn-cancel:hover{color:var(--text);border-color:var(--muted);}
.btn-submit{padding:11px 28px;background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:9px;color:#fff;font-size:14px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:opacity .2s,transform .15s;}
.btn-submit:hover{opacity:.88;}
.btn-submit:active{transform:scale(.97);}
.btn-submit svg{width:15px;height:15px;}

@media(max-width:600px){.fields{grid-template-columns:1fr;}.field.full{grid-column:1;}.photo-section{flex-direction:column;}.form-footer{flex-direction:column-reverse;}.btn-cancel,.btn-submit{width:100%;justify-content:center;}}
</style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="page">

    <a href="worker_details.php?id=<?= $id ?>" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Profile
    </a>

    <div class="form-header">
        <h1>Edit Worker</h1>
        <p>Update the details for <strong style="color:var(--text)"><?= $name ?></strong>.</p>
    </div>

    <?php if ($isAdmin): ?>
        <div class="admin-badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Admin mode — changes apply immediately
        </div>
    <?php elseif (!empty($row['pending_edit'])): ?>
        <div class="pending-banner">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12" y2="16.5"/></svg>
            You have a pending edit awaiting admin approval. You can update it below.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12" y2="16.5"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="editForm">
        <input type="hidden" name="remove_photo" id="removePhotoInput" value="0">

        <!-- Photo -->
        <div class="photo-section">
            <div class="photo-preview-wrap <?= $photo ? 'has-img' : '' ?>" id="previewWrap">
                <?php if ($photo): ?>
                    <img id="photoPreview" src="uploads/<?= htmlspecialchars($photo) ?>" alt="">
                    <div class="avatar-fallback" id="photoFallback" style="display:none"><?= $initials ?></div>
                <?php else: ?>
                    <img id="photoPreview" src="" alt="" style="display:none">
                    <div class="avatar-fallback" id="photoFallback"><?= $initials ?></div>
                <?php endif; ?>
            </div>
            <div class="photo-actions">
                <h3>Profile Photo</h3>
                <p>Upload a new photo to replace the current one.<br>JPG, PNG or WEBP · Max 5MB.</p>
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
                <input type="text" name="name" required value="<?= $name ?>" data-original="<?= $name ?>" oninput="markChanged(this)">
            </div>
            <div class="field">
                <label>Profession</label>
                <input type="text" name="profession" required value="<?= $profession ?>" data-original="<?= $profession ?>" oninput="markChanged(this)">
            </div>
            <div class="field">
                <label>Primary Skill</label>
                <input type="text" name="skill" value="<?= $skill ?>" data-original="<?= $skill ?>" oninput="markChanged(this)">
            </div>
            <div class="field">
                <label>Experience (years)</label>
                <input type="number" name="experience" min="0" max="60" value="<?= $experience ?>" data-original="<?= $experience ?>" oninput="markChanged(this)">
            </div>
            <div class="field">
                <label>Location</label>
                <input type="text" name="location" value="<?= $location ?>" data-original="<?= $location ?>" oninput="markChanged(this)">
            </div>
            <div class="field">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= $phone ?>" data-original="<?= $phone ?>" oninput="markChanged(this)">
            </div>

            <!-- Rating — admin only -->
            <div class="field" <?= !$isAdmin ? 'title="Only admins can change rating"' : '' ?>>
                <label>Rating <?= !$isAdmin ? '<span style="color:var(--muted);font-size:10px;text-transform:none;letter-spacing:0">(admin only)</span>' : '' ?></label>
                <div style="display:flex;align-items:center;">
                    <div class="star-group" id="starGroup">
                        <?php for($i=5;$i>=1;$i--): ?>
                        <input type="radio" name="rating" id="star<?=$i?>" value="<?=$i?>"
                               <?= (round($rating)==$i)?'checked':'' ?>
                               <?= !$isAdmin?'disabled':'' ?>>
                        <label class="star" for="star<?=$i?>" style="<?= !$isAdmin?'cursor:not-allowed;opacity:.4':'' ?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-val" id="ratingLabel">
                        <?= $rating>0 ? number_format($rating,1).' / 5' : 'Not rated' ?>
                    </span>
                </div>
            </div>

            <!-- Availability -->
            <div class="field">
                <label>Availability</label>
                <div class="avail-toggle">
                    <div class="avail-option avail-available">
                        <input type="radio" name="availability" id="avail-yes" value="available" <?= ($avail==='available')?'checked':'' ?>>
                        <label for="avail-yes"><span class="avail-dot"></span> Available</label>
                    </div>
                    <div class="avail-option avail-busy">
                        <input type="radio" name="availability" id="avail-no" value="busy" <?= ($avail==='busy')?'checked':'' ?>>
                        <label for="avail-no"><span class="avail-dot"></span> Busy</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-footer">
            <div>
                <span class="change-note" id="changeNote"><span class="change-dot"></span> Unsaved changes</span>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="worker_details.php?id=<?= $id ?>" class="btn-cancel">Cancel</a>
                <button type="submit" name="update" class="btn-submit">
                    <?php if ($isAdmin): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Changes
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Submit for Approval
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function previewPhoto(input) {
    const wrap=document.getElementById('previewWrap'),img=document.getElementById('photoPreview'),fb=document.getElementById('photoFallback'),ri=document.getElementById('removePhotoInput');
    if(input.files&&input.files[0]){
        const r=new FileReader();
        r.onload=e=>{img.src=e.target.result;img.style.display='block';if(fb)fb.style.display='none';wrap.classList.add('has-img');ri.value='0';};
        r.readAsDataURL(input.files[0]); markDirty();
    }
}

const removeBtn=document.getElementById('removePhotoBtn');
if(removeBtn){
    removeBtn.addEventListener('click',()=>{
        const wrap=document.getElementById('previewWrap'),img=document.getElementById('photoPreview'),fb=document.getElementById('photoFallback'),ri=document.getElementById('removePhotoInput');
        img.src='';img.style.display='none';if(fb)fb.style.display='flex';
        wrap.classList.remove('has-img');ri.value='1';
        document.getElementById('photoInput').value=''; markDirty();
    });
}

document.querySelectorAll('input[name="rating"]').forEach(s=>s.addEventListener('change',()=>{
    const v=document.querySelector('input[name="rating"]:checked')?.value;
    document.getElementById('ratingLabel').textContent=v?v+'.0 / 5':'Not rated'; markDirty();
}));
document.querySelectorAll('input[name="availability"]').forEach(r=>r.addEventListener('change',markDirty));

let dirty=false;
function markDirty(){dirty=true;document.getElementById('changeNote').classList.add('visible');}
function markChanged(el){
    el.classList.toggle('changed',el.value!==el.dataset.original);
    if(el.value!==el.dataset.original) markDirty();
}
window.addEventListener('beforeunload',e=>{if(dirty){e.preventDefault();e.returnValue='';}});
document.querySelector('form').addEventListener('submit',()=>{dirty=false;});
document.querySelector('.btn-cancel').addEventListener('click',()=>{dirty=false;});

// Panel JS (required by navbar.php)
function toggleNotifPanel(){document.getElementById('notifPanel')?.classList.toggle('open');document.getElementById('notifOverlay')?.classList.toggle('open');}
function closeNotifPanel(){document.getElementById('notifPanel')?.classList.remove('open');document.getElementById('notifOverlay')?.classList.remove('open');}
function toggleUserMenu(){document.getElementById('userDropdown')?.classList.toggle('open');}
document.addEventListener('click',e=>{const m=document.getElementById('userMenu');if(m&&!m.contains(e.target))document.getElementById('userDropdown')?.classList.remove('open');});
function markAllRead(){fetch('mark_read.php');return true;}
</script>

<?php include "footer.php"; ?>
</body>
</html>