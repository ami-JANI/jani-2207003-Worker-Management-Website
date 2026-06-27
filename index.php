<?php
include "db_connect.php";
include "auth.php";
$unread = isLoggedIn() ? getUnreadCount($conn) : 0;
$su     = sessionUser();

// --- Build query with filters ---
$where = ["approved = 1"]; $binds = [];
if (!empty($_GET['profession'])) { $where[]="profession = :prof"; $binds[':prof']=$_GET['profession']; }
if (!empty($_GET['location']))   { $where[]="location = :loc";    $binds[':loc']=$_GET['location']; }
if (isset($_GET['min_exp']) && $_GET['min_exp']!=='')       { $where[]="experience >= :minexp"; $binds[':minexp']=(int)$_GET['min_exp']; }
if (isset($_GET['min_rating']) && $_GET['min_rating']!=='') { $where[]="rating >= :minrat";     $binds[':minrat']=(float)$_GET['min_rating']; }
if (!empty($_GET['availability'])) { $where[]="availability = :avail"; $binds[':avail']=$_GET['availability']; }

$sort_map = ['name'=>'name ASC','experience'=>'experience DESC','rating'=>'rating DESC','newest'=>'id DESC'];
$sort_col = $sort_map[$_GET['sort']??''] ?? 'name ASC';
$sql = "SELECT * FROM workers WHERE " . implode(" AND ", $where) . " ORDER BY $sort_col";

$workers = db_all($conn, $sql, $binds);
$count   = count($workers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>WorkForce Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
    --bg:#0f1117;--surface:#181c27;--surface2:#1e2333;--border:#2a3045;
    --accent:#4f8ef7;--accent2:#7c5cfc;--text:#e8eaf0;--muted:#8891aa;
    --card-bg:#181c27;--navbar-h:62px;--sidebar-w:270px;
    --danger:#f87171;--success:#4ade80;
}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;}

/* Push footer past the fixed sidebar */
.site-footer{margin-left:var(--sidebar-w);}
@media(max-width:600px){.site-footer{margin-left:0;}}
</style>
<style><?php include "navbar.css"; ?></style>
<style><?php include "footer.css"; ?></style>
<style>
/* ── LAYOUT ── */
.layout{display:flex;padding-top:var(--navbar-h);flex:1;}

/* ── SIDEBAR ── */
.sidebar{width:var(--sidebar-w);min-width:var(--sidebar-w);position:fixed;top:var(--navbar-h);left:0;bottom:0;background:var(--surface);border-right:1px solid var(--border);overflow-y:auto;padding:24px 20px;display:flex;flex-direction:column;gap:20px;z-index:50;}
.sidebar::-webkit-scrollbar{width:4px;}
.sidebar::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px;}
.sidebar-title{font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--muted);padding-bottom:14px;border-bottom:1px solid var(--border);}
.filter-group{display:flex;flex-direction:column;gap:6px;}
.filter-group label{font-size:11.5px;font-weight:600;color:var(--muted);letter-spacing:.5px;text-transform:uppercase;}
.filter-group select,
.filter-group input[type="number"]{background:var(--surface2);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:9px 12px;font-size:13px;font-family:'DM Sans',sans-serif;width:100%;outline:none;transition:border-color .2s;appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238891aa' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;}
.filter-group select:focus,.filter-group input:focus{border-color:var(--accent);}
.filter-group input[type="number"]{background-image:none;}
.sidebar-divider{height:1px;background:var(--border);}
.btn-apply{width:100%;padding:10px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:9px;font-size:13.5px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:opacity .2s;}
.btn-apply:hover{opacity:.85;}
.btn-reset{width:100%;padding:10px;background:var(--surface2);color:var(--muted);border:1px solid var(--border);border-radius:9px;font-size:13.5px;font-weight:500;font-family:'DM Sans',sans-serif;cursor:pointer;transition:color .2s,border-color .2s;}
.btn-reset:hover{color:var(--text);border-color:var(--muted);}

/* ── MAIN ── */
.main{margin-left:var(--sidebar-w);flex:1;padding:28px 28px 40px;}
.main-header{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:22px;}
.main-header h1{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;}
.worker-count{font-size:13px;color:var(--muted);}

/* ── SORT BAR ── */
.sort-bar{display:flex;align-items:center;gap:10px;margin-bottom:22px;}
.sort-bar span{font-size:12.5px;color:var(--muted);font-weight:500;}
.sort-bar select{background:var(--surface2);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:7px 30px 7px 12px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer;appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238891aa' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;}

/* ── WORKER GRID ── */
.worker-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;}

/* ── WORKER CARD ── */
.worker-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;text-decoration:none;color:inherit;display:block;transition:transform .22s,border-color .22s,box-shadow .22s;cursor:pointer;}
.worker-card:hover{transform:translateY(-5px);border-color:#3a4260;box-shadow:0 16px 40px rgba(0,0,0,.4),0 0 0 1px rgba(79,142,247,.15);}
.worker-card:hover .card-overlay{opacity:1;}
.worker-card:hover .card-img-wrap img{transform:scale(1.06);}
.card-img-wrap{width:100%;aspect-ratio:4/3;overflow:hidden;background:var(--surface2);position:relative;}
.card-img-wrap img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s;}
.avatar-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1e2845,#1a1e30);font-family:'Syne',sans-serif;font-size:36px;font-weight:800;color:rgba(255,255,255,.15);}
.card-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(10,12,20,.7) 0%,transparent 55%);opacity:0;transition:opacity .3s;display:flex;align-items:flex-end;justify-content:center;padding-bottom:14px;}
.card-overlay span{font-size:12px;font-weight:600;color:#fff;background:rgba(79,142,247,.85);padding:5px 14px;border-radius:20px;}
.card-info{padding:13px 14px 14px;}
.card-info h3{font-family:'Syne',sans-serif;font-size:14.5px;font-weight:700;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.card-info .profession{font-size:12.5px;color:var(--accent);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.card-meta{display:flex;align-items:center;gap:10px;margin-top:8px;}
.badge{font-size:11px;color:var(--muted);background:var(--surface2);border:1px solid var(--border);padding:3px 8px;border-radius:20px;font-weight:500;}
.badge.avail{color:#4ade80;border-color:rgba(74,222,128,.25);background:rgba(74,222,128,.07);}

/* ── EMPTY STATE ── */
.empty-state{grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--muted);}

/* ── RESPONSIVE ── */
@media(max-width:1200px){.worker-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:880px) {.worker-grid{grid-template-columns:repeat(2,1fr);}.main{padding:20px 16px;}}
@media(max-width:600px) {.sidebar{display:none;}.main{margin-left:0;}.worker-grid{grid-template-columns:repeat(2,1fr);gap:12px;}}
</style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-title">Filters</div>

        <div class="filter-group">
            <label>Profession</label>
            <select id="f-profession">
                <option value="">All Professions</option>
                <?php
                $profs = db_all($conn, "SELECT DISTINCT profession FROM workers WHERE approved=1 ORDER BY profession");
                foreach ($profs as $p) {
                    $sel = (($_GET['profession'] ?? '') === $p['profession']) ? 'selected' : '';
                    echo "<option value='{$p['profession']}' $sel>{$p['profession']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Location</label>
            <select id="f-location">
                <option value="">All Locations</option>
                <?php
                $locs = db_all($conn, "SELECT DISTINCT location FROM workers WHERE approved=1 ORDER BY location");
                foreach ($locs as $l) {
                    $sel = (($_GET['location'] ?? '') === $l['location']) ? 'selected' : '';
                    echo "<option value='{$l['location']}' $sel>{$l['location']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Min Experience (yrs)</label>
            <input type="number" id="f-exp" min="0" placeholder="Any"
                   value="<?= htmlspecialchars($_GET['min_exp'] ?? '') ?>">
        </div>

        <div class="filter-group">
            <label>Min Rating</label>
            <input type="number" id="f-rating" step="0.1" min="0" max="5" placeholder="Any"
                   value="<?= htmlspecialchars($_GET['min_rating'] ?? '') ?>">
        </div>

        <div class="filter-group">
            <label>Availability</label>
            <select id="f-avail">
                <option value="">Any</option>
                <option value="available" <?= (($_GET['availability'] ?? '') === 'available') ? 'selected' : '' ?>>Available</option>
                <option value="busy"      <?= (($_GET['availability'] ?? '') === 'busy')      ? 'selected' : '' ?>>Busy</option>
            </select>
        </div>

        <div class="sidebar-divider"></div>
        <button class="btn-apply" onclick="applyFilters()">Apply Filters</button>
        <button class="btn-reset" onclick="resetFilters()">Reset</button>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">
        <div class="main-header">
            <h1>Workers</h1>
            <span class="worker-count"><?= $count ?> worker<?= $count !== 1 ? 's' : '' ?> found</span>
        </div>

        <div class="sort-bar">
            <span>Sort by</span>
            <select id="f-sort" onchange="applyFilters()">
                <option value="name"       <?= (($_GET['sort'] ?? '') === 'name')       ? 'selected' : '' ?>>Name</option>
                <option value="experience" <?= (($_GET['sort'] ?? '') === 'experience') ? 'selected' : '' ?>>Experience</option>
                <option value="rating"     <?= (($_GET['sort'] ?? '') === 'rating')     ? 'selected' : '' ?>>Rating</option>
                <option value="newest"     <?= (($_GET['sort'] ?? '') === 'newest')     ? 'selected' : '' ?>>Newest</option>
            </select>
        </div>

        <div class="worker-grid">
            <?php if ($count === 0): ?>
                <div class="empty-state"><p>No workers match your filters.</p></div>
            <?php endif; ?>

            <?php foreach ($workers as $row):
                $name     = $row['name'];
                $prof     = htmlspecialchars($row['profession']);
                $exp      = htmlspecialchars($row['experience']);
                $avail    = $row['availability'] ?? '';
                $photo    = !empty($row['photo']) ? htmlspecialchars($row['photo']) : '';
                $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $row['name']), 0, 2))));
                $id       = $row['id'];
            ?>
            <a class="worker-card" href="worker_details.php?id=<?= $id ?>">
                <div class="card-img-wrap">
                    <?php if ($photo): ?>
                        <img src="uploads/<?= $photo ?>" alt="<?= htmlspecialchars($name) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="avatar-fallback"><?= $initials ?></div>
                    <?php endif; ?>
                    <div class="card-overlay"><span>View Profile →</span></div>
                </div>
                <div class="card-info">
                    <h3><?= htmlspecialchars($name) ?></h3>
                    <div class="profession"><?= $prof ?></div>
                    <div class="card-meta">
                        <span class="badge"><?= $exp ?> yrs</span>
                        <?php if ($avail === 'available'): ?>
                            <span class="badge avail">● Available</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </main>

</div><!-- end .layout -->

<?php include "footer.php"; ?>

<script>
function applyFilters() {
    const p = new URLSearchParams();
    const v = id => document.getElementById(id)?.value;
    if (v('f-profession')) p.set('profession', v('f-profession'));
    if (v('f-location'))   p.set('location',   v('f-location'));
    if (v('f-exp'))        p.set('min_exp',     v('f-exp'));
    if (v('f-rating'))     p.set('min_rating',  v('f-rating'));
    if (v('f-avail'))      p.set('availability',v('f-avail'));
    if (v('f-sort'))       p.set('sort',        v('f-sort'));
    window.location.search = p.toString();
}
function resetFilters() { window.location.href = window.location.pathname; }

document.querySelectorAll('.worker-card').forEach((c, i) => {
    c.style.opacity = '0';
    c.style.transform = 'translateY(18px)';
    c.style.transition = 'opacity .3s ease, transform .3s ease, border-color .22s, box-shadow .22s';
    setTimeout(() => { c.style.opacity = '1'; c.style.transform = 'translateY(0)'; }, 60 + i * 45);
});

function toggleNotifPanel() {
    document.getElementById('notifPanel')?.classList.toggle('open');
    document.getElementById('notifOverlay')?.classList.toggle('open');
}
function closeNotifPanel() {
    document.getElementById('notifPanel')?.classList.remove('open');
    document.getElementById('notifOverlay')?.classList.remove('open');
}
function toggleUserMenu() {
    document.getElementById('userDropdown')?.classList.toggle('open');
}
document.addEventListener('click', e => {
    const m = document.getElementById('userMenu');
    if (m && !m.contains(e.target)) document.getElementById('userDropdown')?.classList.remove('open');
});
function markAllRead() { fetch('mark_read.php'); return true; }
</script>
</body>
</html>