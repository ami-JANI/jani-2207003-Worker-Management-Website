<?php include "db_connect.php"; ?>
<?php
if (empty($_GET['id'])) { header("Location: workers.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM workers WHERE id = ?");
$stmt->bind_param("i", $_GET['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) { header("Location: workers.php"); exit; }

$name       = htmlspecialchars($row['name']);
$profession = htmlspecialchars($row['profession']);
$skill      = htmlspecialchars($row['skill'] ?? '');
$experience = htmlspecialchars($row['experience']);
$location   = htmlspecialchars($row['location']);
$phone      = htmlspecialchars($row['phone']);
$photo      = $row['photo'] ?? '';
$rating     = (float)($row['rating'] ?? 0);
$avail      = $row['availability'] ?? '';
$initials   = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $row['name']), 0, 2))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $name ?> — WorkForce Manager</title>
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
    --success:  #4ade80;
    --danger:   #f87171;
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
    max-width: 900px;
    margin: 0 auto;
    padding-left: 24px;
    padding-right: 24px;
}

/* Back link */
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

/* ── HERO CARD ── */
.hero {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    overflow: hidden;
    animation: fadeUp .4s ease both;
    margin-bottom: 20px;
}

@keyframes fadeUp {
    from { opacity:0; transform:translateY(18px); }
    to   { opacity:1; transform:translateY(0); }
}

/* Top banner */
.hero-banner {
    height: 130px;
    background: linear-gradient(135deg, #1a2340 0%, #151b2e 40%, #1c1530 100%);
    position: relative;
    overflow: hidden;
}

.hero-banner::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse at 20% 50%, rgba(79,142,247,.18) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 30%, rgba(124,92,252,.15) 0%, transparent 55%);
}

/* Grid pattern overlay */
.hero-banner::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
    background-size: 28px 28px;
}

.hero-body {
    padding: 0 32px 28px;
    display: flex;
    gap: 24px;
    align-items: flex-end;
    margin-top: -52px;
    position: relative;
    z-index: 1;
}

/* Avatar */
.avatar {
    flex-shrink: 0;
    width: 104px;
    height: 104px;
    border-radius: 14px;
    border: 3px solid var(--surface);
    overflow: hidden;
    background: var(--surface2);
    box-shadow: 0 8px 30px rgba(0,0,0,.5);
}

.avatar img { width:100%; height:100%; object-fit:cover; display:block; }

.avatar-fallback {
    width:100%; height:100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1e2845, #1a1e30);
    font-family: 'Syne', sans-serif;
    font-size: 32px;
    font-weight: 800;
    color: rgba(255,255,255,.2);
}

.hero-info {
    flex: 1;
    padding-bottom: 4px;
    padding-top: 56px;
}

.hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.hero-info h1 {
    font-family: 'Syne', sans-serif;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.5px;
    line-height: 1.2;
}

.hero-info .profession-tag {
    font-size: 14px;
    color: var(--accent);
    font-weight: 500;
    margin-top: 4px;
}

.avail-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}

.avail-badge.available {
    background: rgba(74,222,128,.1);
    border: 1px solid rgba(74,222,128,.25);
    color: var(--success);
}

.avail-badge.busy {
    background: rgba(248,113,113,.1);
    border: 1px solid rgba(248,113,113,.25);
    color: var(--danger);
}

.avail-badge .dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: currentColor;
}

/* ── STATS ROW ── */
.stats-row {
    display: flex;
    gap: 12px;
    margin: 20px 32px 0;
    padding-bottom: 24px;
    flex-wrap: wrap;
}

.stat {
    flex: 1;
    min-width: 110px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.stat-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--muted);
    letter-spacing: .8px;
    text-transform: uppercase;
}

.stat-value {
    font-family: 'Syne', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
}

.stat-value.accent { color: var(--accent); }

/* Star rating */
.stars {
    display: flex;
    gap: 2px;
    align-items: center;
}

.star { font-size: 16px; line-height: 1; }
.star.filled  { color: var(--warn); }
.star.half    { color: var(--warn); opacity: .5; }
.star.empty   { color: var(--border); }

/* ── INFO SECTION ── */
.info-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 24px 28px;
    animation: fadeUp .4s ease .1s both;
    margin-bottom: 20px;
}

.section-title {
    font-family: 'Syne', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--border);
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-item .label {
    font-size: 11px;
    font-weight: 600;
    color: var(--muted);
    letter-spacing: .6px;
    text-transform: uppercase;
}

.info-item .value {
    font-size: 14.5px;
    color: var(--text);
    font-weight: 400;
}

.info-item .value a {
    color: var(--accent);
    text-decoration: none;
}
.info-item .value a:hover { text-decoration: underline; }

/* Skill pill */
.skill-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(79,142,247,.1);
    border: 1px solid rgba(79,142,247,.2);
    color: var(--accent);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}

/* ── ACTIONS ── */
.actions {
    display: flex;
    gap: 12px;
    animation: fadeUp .4s ease .2s both;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 11px 22px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    text-decoration: none;
    border: none;
    transition: opacity .2s, transform .15s;
}

.btn:active { transform: scale(.97); }

.btn-primary {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff;
}
.btn-primary:hover { opacity: .88; }

.btn-ghost {
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--muted);
}
.btn-ghost:hover { color: var(--text); border-color: var(--muted); }

.btn-danger {
    background: rgba(248,113,113,.1);
    border: 1px solid rgba(248,113,113,.25);
    color: var(--danger);
}
.btn-danger:hover { background: rgba(248,113,113,.18); }

.btn svg { width:15px; height:15px; }

@media (max-width: 600px) {
    .hero-body  { flex-direction: column; align-items: flex-start; padding: 0 20px 24px; margin-top: -44px; }
    .hero-info  { padding-top: 0; width: 100%; }
    .stats-row  { margin: 16px 20px 0; }
    .info-grid  { grid-template-columns: 1fr; }
    .info-section { padding: 20px; }
    .actions    { flex-direction: column; }
    .btn        { justify-content: center; }
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
        <a href="add_worker.php" class="btn-add">+ Add Worker</a>
    </div>
</nav>

<div class="page">

    <a href="workers.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Workers
    </a>

    <!-- HERO CARD -->
    <div class="hero">
        <div class="hero-banner"></div>
        <div class="hero-body">
            <div class="avatar">
                <?php if ($photo): ?>
                    <img src="uploads/<?= $photo ?>" alt="<?= $name ?>">
                <?php else: ?>
                    <div class="avatar-fallback"><?= $initials ?></div>
                <?php endif; ?>
            </div>
            <div class="hero-info">
                <div class="hero-top">
                    <div>
                        <h1><?= $name ?></h1>
                        <div class="profession-tag"><?= $profession ?></div>
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

        <!-- Stats row -->
        <div class="stats-row">
            <div class="stat">
                <span class="stat-label">Experience</span>
                <span class="stat-value accent"><?= $experience ?> <small style="font-size:13px;color:var(--muted)">yrs</small></span>
            </div>
            <div class="stat">
                <span class="stat-label">Rating</span>
                <?php
                $full  = floor($rating);
                $half  = ($rating - $full) >= 0.5 ? 1 : 0;
                $empty = 5 - $full - $half;
                ?>
                <div class="stars" style="margin-top:3px;">
                    <?php for($i=0;$i<$full;$i++)  echo '<span class="star filled">★</span>'; ?>
                    <?php if($half)                  echo '<span class="star half">★</span>'; ?>
                    <?php for($i=0;$i<$empty;$i++) echo '<span class="star empty">★</span>'; ?>
                    <span style="font-size:13px;color:var(--muted);margin-left:5px;"><?= number_format($rating,1) ?></span>
                </div>
            </div>
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

    <!-- INFO SECTION -->
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
                <span class="label">Primary Skill</span>
                <span class="value">
                    <?php if ($skill): ?>
                        <span class="skill-pill">⚡ <?= $skill ?></span>
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

    <!-- ACTIONS -->
    <div class="actions">
        <a href="edit_worker.php?id=<?= (int)$row['id'] ?>" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit Profile
        </a>
        <a href="workers.php" class="btn btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back to List
        </a>
        <a href="delete_worker.php?id=<?= (int)$row['id'] ?>" class="btn btn-danger"
           onclick="return confirm('Delete <?= addslashes($name) ?>? This cannot be undone.')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            Delete Worker
        </a>
    </div>

</div>

</body>
</html>