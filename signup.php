<?php
include "db_connect.php";
include "auth.php";

if (isLoggedIn()) { header("Location: index.php"); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type     = $_POST['type'] ?? 'user';
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // Validate
    if (!$name || !$email || !$password) { $error = "Please fill in all required fields."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = "Invalid email address."; }
    elseif ($password !== $confirm) { $error = "Passwords do not match."; }
    else {
        $pwErr = validatePassword($password);
        if ($pwErr) { $error = $pwErr; }
    }

    if (!$error) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        if ($type === 'user') {
            $ins = oci_parse($conn, "INSERT INTO users (name, phone, email, location, password)
                                     VALUES (:name, :phone, :email, :loc, :pw)");
            oci_bind_by_name($ins, ':name',  $name);
            oci_bind_by_name($ins, ':phone', $phone);
            oci_bind_by_name($ins, ':email', $email);
            oci_bind_by_name($ins, ':loc',   $location);
            oci_bind_by_name($ins, ':pw',    $hash);
            if (@oci_execute($ins, OCI_COMMIT_ON_SUCCESS)) {
                // Notify admins
                foreach (db_all($conn, "SELECT id FROM admins") as $a) {
                    sendNotification($conn, 'admin', (int)$a['id'], "New user registered: $name", "");
                }
                $success = "Account created! You can now sign in.";
            } else {
                $error = db_is_duplicate($ins) ? "Email or phone already registered." : "Registration failed.";
            }
        } else {
            // Worker
            $profession  = trim($_POST['profession'] ?? '');
            $skill       = trim($_POST['skill'] ?? '');
            $experience  = (int)($_POST['experience'] ?? 0);
            $hourly_rate = (float)($_POST['hourly_rate'] ?? 0);
            $availability = $_POST['availability'] ?? 'available';

            // Photo upload
            $photo = '';
            if (!empty($_FILES['photo']['name'])) {
                $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
                if (in_array($_FILES['photo']['type'], $allowed)) {
                    $ext  = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $fn   = uniqid('worker_',true).'.'.$ext;
                    if (!is_dir("uploads")) mkdir("uploads",0755,true);
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/$fn")) $photo = $fn;
                }
            }

            $ins = oci_parse($conn, "INSERT INTO workers
                (name, profession, skill, experience, location, phone, email, photo, hourly_rate, availability, password, approved)
                VALUES (:name, :prof, :skill, :exp, :loc, :phone, :email, :photo, :rate, :avail, :pw, 0)");
            oci_bind_by_name($ins, ':name',  $name);
            oci_bind_by_name($ins, ':prof',  $profession);
            oci_bind_by_name($ins, ':skill', $skill);
            oci_bind_by_name($ins, ':exp',   $experience);
            oci_bind_by_name($ins, ':loc',   $location);
            oci_bind_by_name($ins, ':phone', $phone);
            oci_bind_by_name($ins, ':email', $email);
            oci_bind_by_name($ins, ':photo', $photo);
            oci_bind_by_name($ins, ':rate',  $hourly_rate);
            oci_bind_by_name($ins, ':avail', $availability);
            oci_bind_by_name($ins, ':pw',    $hash);
            if (@oci_execute($ins, OCI_COMMIT_ON_SUCCESS)) {
                // Notify all admins
                foreach (db_all($conn, "SELECT id FROM admins") as $a) {
                    sendNotification($conn, 'admin', (int)$a['id'], "New worker application: $name ($profession) — awaiting approval.", "admin.php");
                }
                $success = "Application submitted! Please wait for admin approval before signing in.";
            } else {
                $error = db_is_duplicate($ins) ? "Email or phone already registered." : "Registration failed.";
            }
        }
    }
}

$type = $_POST['type'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign Up — WorkForce Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg:#0f1117; --surface:#181c27; --surface2:#1e2333; --border:#2a3045;
    --accent:#4f8ef7; --accent2:#7c5cfc; --text:#e8eaf0; --muted:#8891aa;
    --danger:#f87171; --success:#4ade80; --warn:#f59e0b;
}
body { background:var(--bg); color:var(--text); font-family:'DM Sans',sans-serif; min-height:100vh; padding-top:82px; padding-bottom:60px; display:flex; flex-direction:column; align-items:center; }

.brand { position:fixed; top:0; left:0; right:0; height:62px; display:flex; align-items:center; padding:0 28px; background:rgba(15,17,23,.92); backdrop-filter:blur(14px); border-bottom:1px solid var(--border); z-index:10; }
.brand a { font-family:'Syne',sans-serif; font-size:19px; font-weight:800; color:var(--text); text-decoration:none; display:flex; align-items:center; gap:9px; }
.brand .dot { width:8px; height:8px; border-radius:50%; background:linear-gradient(135deg,var(--accent),var(--accent2)); }

.page-head { width:100%; max-width:560px; padding:0 20px; margin-bottom:24px; }
.page-head h1 { font-family:'Syne',sans-serif; font-size:24px; font-weight:800; }
.page-head p  { color:var(--muted); font-size:14px; margin-top:5px; }

/* Type selector */
.type-selector { display:flex; gap:14px; margin-bottom:28px; }
.type-card {
    flex:1; padding:18px; border-radius:12px; border:2px solid var(--border);
    background:var(--surface2); cursor:pointer; text-align:center;
    transition:border-color .2s, background .2s; position:relative;
}
.type-card.active { border-color:var(--accent); background:rgba(79,142,247,.07); }
.type-card input[type="radio"] { display:none; }
.type-card .icon { font-size:28px; margin-bottom:6px; }
.type-card h3 { font-family:'Syne',sans-serif; font-size:15px; font-weight:700; }
.type-card p  { font-size:12px; color:var(--muted); margin-top:3px; }
.type-check { position:absolute; top:10px; right:10px; width:18px; height:18px; border-radius:50%; background:var(--accent); display:none; align-items:center; justify-content:center; }
.type-card.active .type-check { display:flex; }
.type-check svg { width:11px; height:11px; color:#fff; }

/* Card */
.card { background:var(--surface); border:1px solid var(--border); border-radius:18px; overflow:hidden; width:100%; max-width:560px; margin:0 20px; animation:up .4s ease; }
@keyframes up { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

/* Photo upload */
.photo-section { background:var(--surface2); border-bottom:1px solid var(--border); padding:22px 28px; display:flex; align-items:center; gap:20px; }
.photo-preview-wrap { flex-shrink:0; width:80px; height:80px; border-radius:10px; overflow:hidden; background:var(--bg); border:2px dashed var(--border); display:flex; align-items:center; justify-content:center; transition:border-color .2s; }
.photo-preview-wrap.has-img { border-style:solid; border-color:var(--accent); }
.photo-preview-wrap img { width:100%; height:100%; object-fit:cover; }
.photo-placeholder { display:flex; flex-direction:column; align-items:center; gap:4px; color:var(--muted); }
.photo-placeholder svg { width:22px; height:22px; opacity:.5; }
.photo-placeholder span { font-size:10px; }
.photo-upload-info h3 { font-family:'Syne',sans-serif; font-size:14px; font-weight:700; margin-bottom:4px; }
.photo-upload-info p  { font-size:12px; color:var(--muted); margin-bottom:10px; }
.btn-upload-trigger { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; background:var(--bg); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:13px; font-weight:500; font-family:'DM Sans',sans-serif; cursor:pointer; transition:border-color .2s; }
.btn-upload-trigger:hover { border-color:var(--accent); }
.btn-upload-trigger svg { width:13px; height:13px; }
input[type="file"] { display:none; }

/* Fields */
.fields { padding:24px 28px; display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.field { display:flex; flex-direction:column; gap:6px; }
.field.full { grid-column:1/-1; }
.field label { font-size:11.5px; font-weight:600; color:var(--muted); letter-spacing:.5px; text-transform:uppercase; }
.field input, .field select {
    background:var(--surface2); border:1px solid var(--border); border-radius:9px;
    color:var(--text); padding:10px 14px; font-size:14px; font-family:'DM Sans',sans-serif;
    outline:none; transition:border-color .2s, box-shadow .2s; width:100%;
}
.field input:focus, .field select:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(79,142,247,.12); }
.field input::placeholder { color:#55607a; }
.field select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238891aa' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; }

/* Password strength */
.pw-wrap { position:relative; }
.pw-wrap input { padding-right:44px; }
.pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); cursor:pointer; padding:4px; display:flex; align-items:center; }
.pw-toggle:hover { color:var(--text); }
.pw-toggle svg { width:16px; height:16px; }

.strength-bar { display:flex; gap:4px; margin-top:6px; }
.strength-seg { flex:1; height:3px; border-radius:2px; background:var(--border); transition:background .3s; }
.strength-label { font-size:11px; color:var(--muted); margin-top:4px; }

/* Avail toggle */
.avail-toggle { display:flex; gap:10px; }
.avail-option { flex:1; }
.avail-option input[type="radio"] { display:none; }
.avail-option label { display:flex; align-items:center; justify-content:center; gap:6px; padding:9px; border-radius:9px; border:1px solid var(--border); background:var(--surface2); cursor:pointer; font-size:13.5px; font-weight:500; color:var(--muted); transition:all .2s; text-transform:none; letter-spacing:0; }
.avail-option input[type="radio"]:checked + label { border-color:var(--accent); background:rgba(79,142,247,.1); color:var(--text); }
.avail-option.avail-busy input[type="radio"]:checked + label { border-color:var(--danger); background:rgba(248,113,113,.08); color:var(--danger); }
.avail-dot { width:8px; height:8px; border-radius:50%; }
.avail-available .avail-dot { background:var(--success); }
.avail-busy      .avail-dot { background:var(--danger); }

/* Footer */
.form-footer { padding:20px 28px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; }
.btn-submit { padding:11px 32px; background:linear-gradient(135deg,var(--accent),var(--accent2)); border:none; border-radius:10px; color:#fff; font-size:14.5px; font-weight:700; font-family:'DM Sans',sans-serif; cursor:pointer; transition:opacity .2s, transform .15s; }
.btn-submit:hover { opacity:.88; }
.btn-submit:active { transform:scale(.98); }

/* Alert */
.alert { padding:12px 16px; border-radius:9px; font-size:13.5px; font-weight:500; margin:0 28px 4px; }
.alert.error   { background:rgba(248,113,113,.1); border:1px solid rgba(248,113,113,.3); color:var(--danger); }
.alert.success { background:rgba(74,222,128,.1);  border:1px solid rgba(74,222,128,.3);  color:var(--success); }

.signin-link { text-align:center; margin-top:20px; font-size:13.5px; color:var(--muted); }
.signin-link a { color:var(--accent); text-decoration:none; font-weight:500; }

/* Worker-only fields toggle */
.worker-fields { display:none; }
.worker-fields.visible { display:contents; }

@media(max-width:600px) {
    .fields { grid-template-columns:1fr; }
    .field.full { grid-column:1; }
    .photo-section { flex-direction:column; text-align:center; }
}
</style>
</head>
<body>
<div class="brand"><a href="index.php"><div class="dot"></div>WorkForce Manager</a></div>

<div class="page-head">
    <h1>Create Account</h1>
    <p>Join as a user looking for help, or register as a worker.</p>
</div>

<div class="card">
    <?php if ($success): ?>
        <div style="padding:40px 28px; text-align:center;">
            <div style="font-size:40px;margin-bottom:12px;">✅</div>
            <h2 style="font-family:'Syne',sans-serif;font-size:20px;margin-bottom:8px;">
                <?= $type==='worker' ? 'Application Submitted!' : 'Account Created!' ?>
            </h2>
            <p style="color:var(--muted);font-size:14px;line-height:1.6;"><?= htmlspecialchars($success) ?></p>
            <a href="signin.php" style="display:inline-block;margin-top:20px;padding:10px 24px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border-radius:9px;text-decoration:none;font-weight:600;font-size:14px;">Sign In</a>
        </div>
    <?php else: ?>

    <form method="POST" enctype="multipart/form-data" id="signupForm">
        <div style="padding:24px 28px 0;">
            <!-- Type selector -->
            <div class="type-selector">
                <label class="type-card <?= $type==='user'?'active':'' ?>">
                    <input type="radio" name="type" value="user" <?= $type==='user'?'checked':'' ?> onchange="switchType('user')">
                    <div class="type-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <div class="icon">👤</div>
                    <h3>User</h3>
                    <p>Find & book workers</p>
                </label>
                <label class="type-card <?= $type==='worker'?'active':'' ?>">
                    <input type="radio" name="type" value="worker" <?= $type==='worker'?'checked':'' ?> onchange="switchType('worker')">
                    <div class="type-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <div class="icon">🔧</div>
                    <h3>Worker</h3>
                    <p>Offer your services</p>
                </label>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Worker photo (shown only for worker) -->
        <div class="photo-section" id="photoSection" style="<?= $type!=='worker'?'display:none':'' ?>">
            <div class="photo-preview-wrap" id="previewWrap">
                <div class="photo-placeholder" id="photoPlaceholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>No photo</span>
                </div>
                <img id="photoPreview" src="" alt="" style="display:none">
            </div>
            <div class="photo-upload-info">
                <h3>Profile Photo</h3>
                <p>JPG, PNG or WEBP · Max 5MB</p>
                <button type="button" class="btn-upload-trigger" onclick="document.getElementById('photoInput').click()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Choose Photo
                </button>
                <input type="file" id="photoInput" name="photo" accept="image/*" onchange="previewPhoto(this)">
            </div>
        </div>

        <!-- Common + type-specific fields -->
        <div class="fields">
            <div class="field full">
                <label>Full Name *</label>
                <input type="text" name="name" placeholder="Your full name" required value="<?= htmlspecialchars($_POST['name']??'') ?>">
            </div>
            <div class="field">
                <label>Email *</label>
                <input type="email" name="email" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
            </div>
            <div class="field">
                <label>Phone</label>
                <input type="text" name="phone" placeholder="01XXXXXXXXX" value="<?= htmlspecialchars($_POST['phone']??'') ?>">
            </div>
            <div class="field full">
                <label>Location</label>
                <input type="text" name="location" placeholder="City / District" value="<?= htmlspecialchars($_POST['location']??'') ?>">
            </div>

            <!-- Worker-only fields -->
            <div class="field worker-fields <?= $type==='worker'?'visible':'' ?>">
                <label>Profession *</label>
                <input type="text" name="profession" placeholder="e.g. Electrician" value="<?= htmlspecialchars($_POST['profession']??'') ?>">
            </div>
            <div class="field worker-fields <?= $type==='worker'?'visible':'' ?>">
                <label>Primary Skill</label>
                <input type="text" name="skill" placeholder="e.g. Solar panels" value="<?= htmlspecialchars($_POST['skill']??'') ?>">
            </div>
            <div class="field worker-fields <?= $type==='worker'?'visible':'' ?>">
                <label>Experience (years)</label>
                <input type="number" name="experience" min="0" max="60" placeholder="e.g. 5" value="<?= htmlspecialchars($_POST['experience']??'') ?>">
            </div>
            <div class="field worker-fields <?= $type==='worker'?'visible':'' ?>">
                <label>Hourly Rate (৳)</label>
                <input type="number" name="hourly_rate" min="0" step="50" placeholder="e.g. 500" value="<?= htmlspecialchars($_POST['hourly_rate']??'') ?>">
            </div>
            <div class="field full worker-fields <?= $type==='worker'?'visible':'' ?>">
                <label>Availability</label>
                <div class="avail-toggle">
                    <div class="avail-option avail-available">
                        <input type="radio" name="availability" id="avail-y" value="available" checked>
                        <label for="avail-y"><span class="avail-dot"></span> Available</label>
                    </div>
                    <div class="avail-option avail-busy">
                        <input type="radio" name="availability" id="avail-n" value="busy">
                        <label for="avail-n"><span class="avail-dot"></span> Busy</label>
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div class="field">
                <label>Password *</label>
                <div class="pw-wrap">
                    <input type="password" name="password" id="pw" placeholder="Min 8 chars" required oninput="checkStrength(this.value)">
                    <button type="button" class="pw-toggle" onclick="togglePw('pw')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>
                <div class="strength-bar"><div class="strength-seg" id="s1"></div><div class="strength-seg" id="s2"></div><div class="strength-seg" id="s3"></div><div class="strength-seg" id="s4"></div></div>
                <div class="strength-label" id="strengthLabel">Must have uppercase, lowercase, number, special char</div>
            </div>
            <div class="field">
                <label>Confirm Password *</label>
                <div class="pw-wrap">
                    <input type="password" name="confirm" id="pwc" placeholder="Repeat password" required>
                    <button type="button" class="pw-toggle" onclick="togglePw('pwc')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>
            </div>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn-submit" id="submitBtn">Create Account</button>
        </div>
    </form>

    <?php endif; ?>
</div>

<p class="signin-link" style="margin-top:20px;">Already have an account? <a href="signin.php">Sign In</a></p>

<script>
function switchType(t) {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('active'));
    document.querySelector(`.type-card input[value="${t}"]`).closest('.type-card').classList.add('active');
    const wf = document.querySelectorAll('.worker-fields');
    const ps = document.getElementById('photoSection');
    if (t === 'worker') {
        wf.forEach(f => f.classList.add('visible'));
        ps.style.display = 'flex';
        document.getElementById('submitBtn').textContent = 'Submit Application';
    } else {
        wf.forEach(f => f.classList.remove('visible'));
        ps.style.display = 'none';
        document.getElementById('submitBtn').textContent = 'Create Account';
    }
}

function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            const img = document.getElementById('photoPreview');
            img.src = e.target.result; img.style.display = 'block';
            document.getElementById('photoPlaceholder').style.display = 'none';
            document.getElementById('previewWrap').classList.add('has-img');
        };
        r.readAsDataURL(input.files[0]);
    }
}

function togglePw(id) {
    const i = document.getElementById(id);
    i.type = i.type === 'password' ? 'text' : 'password';
}

function checkStrength(pw) {
    const segs   = [document.getElementById('s1'),document.getElementById('s2'),document.getElementById('s3'),document.getElementById('s4')];
    const label  = document.getElementById('strengthLabel');
    const colors = ['#f87171','#f59e0b','#4ade80','#4f8ef7'];
    const labels = ['Too weak','Fair','Good','Strong'];
    let score = 0;
    if (pw.length >= 8)          score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if (/[0-9]/.test(pw))        score++;
    if (/[\W_]/.test(pw))        score++;
    segs.forEach((s,i) => s.style.background = i < score ? colors[score-1] : 'var(--border)');
    label.textContent = score > 0 ? labels[score-1] : 'Must have uppercase, lowercase, number, special char';
    label.style.color = score > 0 ? colors[score-1] : 'var(--muted)';
}

// Init state
const currentType = document.querySelector('input[name="type"]:checked')?.value || 'user';
switchType(currentType);
</script>
</body>
</html>
