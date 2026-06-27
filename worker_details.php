<?php
include "db_connect.php";
include "auth.php";

// Schema is managed by oracle_schema.sql — no runtime table creation.

if (empty($_GET['id'])) { header("Location: index.php"); exit; }
$id = (int)$_GET['id'];

$row = db_one($conn, "SELECT * FROM workers WHERE id = :id", [':id' => $id]);
if (!$row) { header("Location: index.php"); exit; }

// Handle delete — only admin or the worker themselves
if (isset($_GET['delete'])) {
    $canDelete = isAdmin() || (isWorker() && $_SESSION['uid'] === $id);
    if (!$canDelete) { header("Location: worker_details.php?id=$id"); exit; }
    // Delete photo from disk
    if (!empty($row['photo']) && file_exists("uploads/" . $row['photo'])) {
        unlink("uploads/" . $row['photo']);
    }
    db_exec($conn, "DELETE FROM workers WHERE id = :id", [':id' => $id]);
    header("Location: index.php");
    exit;
}

$name       = htmlspecialchars($row['name']);
$profession = htmlspecialchars($row['profession']);
$skillsList = array_filter(array_map('trim', explode(',', $row['skill'] ?? '')));
$experience = htmlspecialchars($row['experience']);
$location   = htmlspecialchars($row['location']);
$phone      = htmlspecialchars($row['phone']);
$photo      = $row['photo'] ?? '';
$rating       = (float)($row['rating'] ?? 0);
$rating_count = (int)($row['rating_count'] ?? 0);

// Fetch review count directly from ratings table (source of truth)
$review_count = (int) db_scalar($conn, "SELECT COUNT(*) FROM ratings WHERE worker_id = :id", [':id' => $id], 0);
$avail      = $row['availability'] ?? '';
$hourly     = (float)($row['hourly_rate'] ?? 0);
$initials   = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $row['name']), 0, 2))));

// Who can do what
$isOwnProfile = isWorker() && $_SESSION['uid'] === $id;
$canEdit      = isAdmin() || $isOwnProfile;
$canDelete    = isAdmin() || $isOwnProfile;

// Can the logged-in user book this worker?
$canBook = false;
$alreadyBooked = false;
if (isUser() && $row['availability'] === 'available' && $row['approved']) {
    $canBook = true;
    // Check for existing active booking
    $existing = db_one($conn,
        "SELECT id FROM bookings WHERE user_id = :p_uid AND worker_id = :wid
         AND status IN ('pending','confirmed','awaiting_user')",
        [':p_uid' => (int)$_SESSION['uid'], ':wid' => $id]);
    if ($existing) {
        $canBook = false;
        $alreadyBooked = true;
    }
}

// Pending edit notice for the worker themselves
$hasPending   = !empty($row['pending_edit']);

// Flash messages
$flash = '';
if (isset($_GET['pending']))  $flash = 'pending';
if (isset($_GET['updated']))  $flash = 'updated';

$su     = sessionUser();
$unread = isLoggedIn() ? getUnreadCount($conn) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $name ?> — WorkForce Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--bg:#0f1117;--surface:#181c27;--surface2:#1e2333;--border:#2a3045;--accent:#4f8ef7;--accent2:#7c5cfc;--text:#e8eaf0;--muted:#8891aa;--success:#4ade80;--danger:#f87171;--warn:#f59e0b;--navbar-h:62px;}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;}
</style>
<style><?php include "navbar.css"; ?></style>
<style><?php include "footer.css"; ?></style>
<style>
.page{padding-top:calc(var(--navbar-h)+36px);padding-bottom:60px;max-width:900px;margin:0 auto;padding-left:24px;padding-right:24px;}

.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--muted);font-size:13px;text-decoration:none;margin-bottom:24px;transition:color .2s;}
.back-link:hover{color:var(--text);}
.back-link svg{width:14px;height:14px;}

/* Flash banners */
.flash{display:flex;align-items:center;gap:10px;padding:13px 18px;border-radius:12px;font-size:13.5px;font-weight:500;margin-bottom:20px;animation:fadeUp .4s ease;}
.flash.pending{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);color:var(--warn);}
.flash.updated{background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.25);color:var(--success);}
.flash svg{width:18px;height:18px;flex-shrink:0;}

@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

/* Hero card */
.hero{background:var(--surface);border:1px solid var(--border);border-radius:18px;overflow:hidden;animation:fadeUp .4s ease;margin-bottom:20px;}
.hero-banner{height:130px;background:linear-gradient(135deg,#1a2340 0%,#151b2e 40%,#1c1530 100%);position:relative;overflow:hidden;}
.hero-banner::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 20% 50%,rgba(79,142,247,.18) 0%,transparent 60%),radial-gradient(ellipse at 80% 30%,rgba(124,92,252,.15) 0%,transparent 55%);}
.hero-banner::after{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:28px 28px;}
.hero-body{padding:0 32px 28px;display:flex;gap:24px;align-items:flex-end;margin-top:-52px;position:relative;z-index:1;}
.avatar{flex-shrink:0;width:104px;height:104px;border-radius:14px;border:3px solid var(--surface);overflow:hidden;background:var(--surface2);box-shadow:0 8px 30px rgba(0,0,0,.5);}
.avatar img{width:100%;height:100%;object-fit:cover;display:block;}
.avatar-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1e2845,#1a1e30);font-family:'Syne',sans-serif;font-size:32px;font-weight:800;color:rgba(255,255,255,.2);}
.hero-info{flex:1;padding-bottom:4px;padding-top:56px;}
.hero-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.hero-info h1{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;letter-spacing:-0.5px;line-height:1.2;}
.hero-info .profession-tag{font-size:14px;color:var(--accent);font-weight:500;margin-top:4px;}
.avail-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;flex-shrink:0;}
.avail-badge.available{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.25);color:var(--success);}
.avail-badge.busy{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:var(--danger);}
.avail-badge .dot{width:7px;height:7px;border-radius:50%;background:currentColor;}

/* Pending edit badge (for own profile) */
.pending-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);color:var(--warn);margin-top:8px;}

/* Stats row */
.stats-row{display:flex;gap:12px;margin:20px 32px 0;padding-bottom:24px;flex-wrap:wrap;}
.stat{flex:1;min-width:110px;background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:14px 16px;display:flex;flex-direction:column;gap:5px;}
.stat-label{font-size:11px;font-weight:600;color:var(--muted);letter-spacing:.8px;text-transform:uppercase;}
.stat-value{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;}
.stat-value.accent{color:var(--accent);}
.stars{display:flex;gap:2px;align-items:center;}
.star{font-size:16px;line-height:1;}
.star.filled{color:var(--warn);}
.star.half{color:var(--warn);opacity:.5;}
.star.empty{color:var(--border);}

/* Info section */
.info-section{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px 28px;animation:fadeUp .4s ease .1s both;margin-bottom:20px;}
.section-title{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--muted);margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.info-item{display:flex;flex-direction:column;gap:5px;}
.info-item .label{font-size:11px;font-weight:600;color:var(--muted);letter-spacing:.6px;text-transform:uppercase;}
.info-item .value{font-size:14.5px;color:var(--text);}
.info-item .value a{color:var(--accent);text-decoration:none;}
.info-item .value a:hover{text-decoration:underline;}
.skill-pill{display:inline-flex;align-items:center;gap:5px;background:rgba(79,142,247,.1);border:1px solid rgba(79,142,247,.2);color:var(--accent);padding:4px 12px;border-radius:20px;font-size:13px;font-weight:500;}

/* Actions — only shown to admin or own worker */
.actions{display:flex;gap:12px;animation:fadeUp .4s ease .2s both;flex-wrap:wrap;}
.btn{display:inline-flex;align-items:center;gap:7px;padding:11px 22px;border-radius:10px;font-size:14px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;text-decoration:none;border:none;transition:opacity .2s,transform .15s;}
.btn:active{transform:scale(.97);}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;}
.btn-primary:hover{opacity:.88;}
.btn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--muted);}
.btn-ghost:hover{color:var(--text);border-color:var(--muted);}
.btn-danger{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:var(--danger);}
.btn-danger:hover{background:rgba(248,113,113,.18);}
.btn svg{width:15px;height:15px;}

@media(max-width:600px){.hero-body{flex-direction:column;align-items:flex-start;padding:0 20px 24px;margin-top:-44px;}.hero-info{padding-top:0;width:100%;}.stats-row{margin:16px 20px 0;}.info-grid{grid-template-columns:1fr;}.info-section{padding:20px;}.actions{flex-direction:column;}.btn{justify-content:center;}}
</style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="page">

    <a href="index.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Workers
    </a>

    <!-- Flash messages -->
    <?php if ($flash === 'pending'): ?>
    <div class="flash pending">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12" y2="16.5"/></svg>
        Your edit has been submitted and is awaiting admin approval.
    </div>
    <?php elseif ($flash === 'updated'): ?>
    <div class="flash updated">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        Profile updated successfully.
    </div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="hero">
        <div class="hero-banner"></div>
        <div class="hero-body">
            <div class="avatar">
                <?php if ($photo): ?>
                    <img src="uploads/<?= htmlspecialchars($photo) ?>" alt="<?= $name ?>">
                <?php else: ?>
                    <div class="avatar-fallback"><?= $initials ?></div>
                <?php endif; ?>
            </div>
            <div class="hero-info">
                <div class="hero-top">
                    <div>
                        <h1><?= $name ?></h1>
                        <div class="profession-tag"><?= $profession ?></div>
                        <?php if ($isOwnProfile && $hasPending): ?>
                            <div class="pending-badge">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12" y2="16.5"/></svg>
                                Edit pending approval
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($avail): ?>
                    <div class="avail-badge <?= $avail === 'available' ? 'available' : 'busy' ?>">
                        <span class="dot"></span>
                        <?= ucfirst($avail) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat">
                <span class="stat-label">Experience</span>
                <span class="stat-value accent"><?= $experience ?> <small style="font-size:13px;color:var(--muted)">yrs</small></span>
            </div>
            <div class="stat">
                <span class="stat-label">Rating</span>
                <?php
                $full=$rating>0?floor($rating):0; $half=($rating-$full)>=0.5?1:0; $empty=5-$full-$half;
                ?>
                <div class="stars" style="margin-top:3px;">
                    <?php for($i=0;$i<$full;$i++) echo '<span class="star filled">★</span>'; ?>
                    <?php if($half) echo '<span class="star half">★</span>'; ?>
                    <?php for($i=0;$i<$empty;$i++) echo '<span class="star empty">★</span>'; ?>
                    <span style="font-size:13px;color:var(--muted);margin-left:5px;"><?= $rating>0?number_format($rating,1):'—' ?></span>
                </div>
                <div style="font-size:11.5px;color:var(--muted);margin-top:5px;display:flex;align-items:center;gap:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <?= $review_count ?> <?= $review_count === 1 ? 'review' : 'reviews' ?>
                </div>
            </div>
            <?php if ($hourly > 0): ?>
            <div class="stat">
                <span class="stat-label">Hourly Rate</span>
                <span class="stat-value" style="font-size:18px;">৳<?= number_format($hourly,0) ?></span>
            </div>
            <?php endif; ?>
            <div class="stat">
                <span class="stat-label">Location</span>
                <span class="stat-value" style="font-size:15px;"><?= $location ?></span>
            </div>
            <div class="stat">
                <span class="stat-label">Phone</span>
                <span class="stat-value" style="font-size:15px;">
                    <a href="tel:<?= $phone ?>" style="color:var(--accent);text-decoration:none;"><?= $phone ?></a>
                </span>
            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="info-section">
        <div class="section-title">Details</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Full Name</span>
                <span class="value"><?= $name ?></span>
            </div>
            <div class="info-item">
                <span class="label">Profession</span>
                <span class="value"><?= $profession ?></span>
            </div>
            <div class="info-item">
                <span class="label">Skills</span>
                <span class="value" style="display:flex;flex-wrap:wrap;gap:6px;">
                    <?php if ($skillsList): ?>
                        <?php foreach ($skillsList as $sk): ?>
                            <span class="skill-pill">⚡ <?= htmlspecialchars($sk) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color:var(--muted)">—</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-item">
                <span class="label">Experience</span>
                <span class="value"><?= $experience ?> years</span>
            </div>
            <div class="info-item">
                <span class="label">Location</span>
                <span class="value"><?= $location ?></span>
            </div>
            <div class="info-item">
                <span class="label">Contact</span>
                <span class="value">
                    <a href="tel:<?= $phone ?>"><?= $phone ?></a>
                </span>
            </div>
        </div>
    </div>

    <!-- Actions — only for admin or the worker themselves -->
    <?php if ($canEdit || $canDelete): ?>
    <div class="actions">
        <?php if ($canEdit): ?>
        <a href="edit_worker.php?id=<?= $id ?>" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <?= $isOwnProfile ? 'Edit My Profile' : 'Edit Profile' ?>
        </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to List
        </a>
        <?php if ($canDelete): ?>
        <a href="worker_details.php?id=<?= $id ?>&delete=1" class="btn btn-danger"
           onclick="return confirm('Delete <?= addslashes($name) ?>? This cannot be undone.')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            Delete Worker
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Regular visitor — back button only -->
    <div class="actions">
        <a href="index.php" class="btn btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to Workers
        </a>
        <?php if ($canBook): ?>
        <form method="POST" action="book_worker.php" style="display:inline;">
            <input type="hidden" name="worker_id" value="<?= $id ?>">
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Book Now
            </button>
        </form>
        <?php elseif ($alreadyBooked): ?>
        <span class="btn btn-ghost" style="cursor:default;opacity:.6;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Already Booked
        </span>
        <?php elseif (isLoggedIn() === false): ?>
        <a href="signin.php" class="btn btn-primary">Sign in to Book</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<script>
function toggleNotifPanel(){document.getElementById('notifPanel')?.classList.toggle('open');document.getElementById('notifOverlay')?.classList.toggle('open');}
function closeNotifPanel(){document.getElementById('notifPanel')?.classList.remove('open');document.getElementById('notifOverlay')?.classList.remove('open');}
function toggleUserMenu(){document.getElementById('userDropdown')?.classList.toggle('open');}
document.addEventListener('click',e=>{const m=document.getElementById('userMenu');if(m&&!m.contains(e.target))document.getElementById('userDropdown')?.classList.remove('open');});
function markAllRead(){fetch('mark_read.php');return true;}
</script>

<?php include "footer.php"; ?>
</body>
</html>