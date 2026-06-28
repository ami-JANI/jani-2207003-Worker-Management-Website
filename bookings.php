<?php
include "db_connect.php";
include "auth.php";

// Schema is managed by oracle_schema.sql — no runtime table creation.

requireLogin();

$uid  = (int)$_SESSION['uid'];
$role = $_SESSION['role'];

// ── Fetch bookings based on role ─────────────────────────────
if (isUser()) {
    $sql = "
        SELECT b.*, w.name AS worker_name, w.profession, w.photo AS worker_photo,
               w.hourly_rate, w.location AS worker_location,
               (SELECT id    FROM ratings WHERE booking_id=b.id) AS rating_id,
               (SELECT stars FROM ratings WHERE booking_id=b.id) AS rating_stars
        FROM bookings b
        JOIN workers w ON w.id = b.worker_id
        WHERE b.user_id = :p_uid
        ORDER BY b.booked_at DESC";
    $binds = [':p_uid' => $uid];
} elseif (isWorker()) {
    $sql = "
        SELECT b.*, u.name AS user_name, u.phone AS user_phone, u.location AS user_location,
               (SELECT id    FROM ratings WHERE booking_id=b.id) AS rating_id,
               (SELECT stars FROM ratings WHERE booking_id=b.id) AS rating_stars
        FROM bookings b
        JOIN users u ON u.id = b.user_id
        WHERE b.worker_id = :p_uid
        ORDER BY b.booked_at DESC";
    $binds = [':p_uid' => $uid];
} else {
    // Admin: see all
    $sql = "
        SELECT b.*,
               w.name AS worker_name, w.profession, w.photo AS worker_photo,
               u.name AS user_name, u.phone AS user_phone,
               (SELECT id    FROM ratings WHERE booking_id=b.id) AS rating_id,
               (SELECT stars FROM ratings WHERE booking_id=b.id) AS rating_stars
        FROM bookings b
        JOIN workers w ON w.id = b.worker_id
        JOIN users   u ON u.id = b.user_id
        ORDER BY b.booked_at DESC";
    $binds = [];
}
$all = db_all($conn, $sql, $binds);
// Normalize ids to int for strict comparisons (OCI returns NUMBER as string)
foreach ($all as &$_r) {
    $_r['id']        = (int)$_r['id'];
    if (isset($_r['worker_id'])) $_r['worker_id'] = (int)$_r['worker_id'];
    if (isset($_r['user_id']))   $_r['user_id']   = (int)$_r['user_id'];
}
unset($_r);

$active   = array_filter($all, fn($b) => in_array($b['status'], ['pending','confirmed','awaiting_user']));
$previous = array_filter($all, fn($b) => in_array($b['status'], ['completed','cancelled']));

$su     = sessionUser();
$unread = getUnreadCount($conn);

// Flash
$flash = '';
if (isset($_GET['booked'])) $flash = 'booked';
if (isset($_GET['rated']))  $flash = 'rated';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bookings — WorkForce Manager</title>
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
.page{padding-top:32px;padding-bottom:60px;max-width:900px;margin:0 auto;padding-left:24px;padding-right:24px;}

.page-header{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:28px;}
.page-header h1{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;}

/* Flash */
.flash{display:flex;align-items:center;gap:10px;padding:13px 18px;border-radius:12px;font-size:13.5px;font-weight:500;margin-bottom:22px;animation:up .4s ease;}
.flash.ok{background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.25);color:var(--success);}
@keyframes up{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.flash svg{width:18px;height:18px;flex-shrink:0;}

/* Section title */
.section-label{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--muted);margin-bottom:14px;display:flex;align-items:center;gap:10px;}
.section-label::after{content:'';flex:1;height:1px;background:var(--border);}
.count-pill{background:var(--surface2);border:1px solid var(--border);color:var(--muted);font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;letter-spacing:0;}

/* Booking card */
.booking-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:20px 22px;margin-bottom:14px;animation:up .35s ease;transition:border-color .2s;}
.booking-card:hover{border-color:#3a4260;}

.bcard-top{display:flex;align-items:center;gap:14px;margin-bottom:16px;}
.bcard-avatar{width:48px;height:48px;border-radius:10px;overflow:hidden;background:var(--surface2);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--muted);}
.bcard-avatar img{width:100%;height:100%;object-fit:cover;}
.bcard-info{flex:1;}
.bcard-name{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;}
.bcard-sub{font-size:13px;color:var(--muted);margin-top:2px;}
.bcard-status{flex-shrink:0;}

/* Status pills */
.status-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 13px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.3px;}
.status-pill .sdot{width:7px;height:7px;border-radius:50%;background:currentColor;}
.s-pending       {background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);color:var(--warn);}
.s-confirmed     {background:rgba(79,142,247,.1); border:1px solid rgba(79,142,247,.25); color:var(--accent);}
.s-awaiting_user {background:rgba(124,92,252,.1); border:1px solid rgba(124,92,252,.25); color:#a78bfa;}
.s-completed     {background:rgba(74,222,128,.1); border:1px solid rgba(74,222,128,.25); color:var(--success);}
.s-cancelled     {background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:var(--danger);}

/* Meta row */
.bcard-meta{display:flex;gap:18px;flex-wrap:wrap;margin-bottom:16px;}
.meta-item{font-size:12.5px;color:var(--muted);display:flex;align-items:center;gap:5px;}
.meta-item svg{width:13px;height:13px;}
.meta-item strong{color:var(--text);}

/* Status description */
.status-desc{font-size:13px;color:var(--muted);background:var(--surface2);border-radius:8px;padding:10px 14px;margin-bottom:16px;line-height:1.5;border-left:3px solid var(--border);}
.status-desc.warn-border{border-color:var(--warn);}
.status-desc.accent-border{border-color:var(--accent);}
.status-desc.purple-border{border-color:#7c5cfc;}

/* Actions */
.bcard-actions{display:flex;gap:10px;flex-wrap:wrap;}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:9px;font-size:13.5px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;text-decoration:none;border:none;transition:opacity .2s,transform .15s;}
.btn:active{transform:scale(.97);}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;}
.btn-primary:hover{opacity:.88;}
.btn-success{background:rgba(74,222,128,.15);border:1px solid rgba(74,222,128,.3);color:var(--success);}
.btn-success:hover{background:rgba(74,222,128,.25);}
.btn-danger{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:var(--danger);}
.btn-danger:hover{background:rgba(248,113,113,.2);}
.btn-ghost{background:var(--surface2);border:1px solid var(--border);color:var(--muted);}
.btn-ghost:hover{color:var(--text);border-color:var(--muted);}
.btn svg{width:14px;height:14px;}

/* Rating widget */
.rate-form{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.star-pick{direction:rtl;display:inline-flex;gap:3px;}
.star-pick input{display:none;}
.star-pick label{font-size:22px;cursor:pointer;color:var(--border);transition:color .15s;}
.star-pick label:hover,
.star-pick label:hover~label,
.star-pick input:checked~label{color:var(--warn);}

/* Empty state */
.empty{text-align:center;padding:48px 20px;color:var(--muted);}
.empty svg{width:40px;height:40px;opacity:.3;margin-bottom:12px;}
.empty p{font-size:14px;}
.empty a{color:var(--accent);text-decoration:none;font-weight:500;}

.mb-section{margin-bottom:36px;}

/* Rating display in previous bookings */
.rating-section{margin-top:4px;}

.rating-display-row{display:flex;align-items:center;gap:12px;padding:10px 14px;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.18);border-radius:10px;}
.rating-given-label{font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;flex-shrink:0;}
.given-stars{display:flex;align-items:center;gap:3px;}
.gstar{font-size:20px;line-height:1;}
.gfilled{color:var(--warn);}
.gempty{color:var(--border);}
.given-val{font-size:13px;font-weight:700;color:var(--warn);margin-left:6px;}

.rate-box{padding:14px 16px;background:rgba(79,142,247,.05);border:1px solid rgba(79,142,247,.15);border-radius:10px;}
.rate-box-title{font-size:13px;font-weight:600;color:var(--text);margin-bottom:10px;}

@media(max-width:600px){.bcard-top{flex-wrap:wrap;}.bcard-actions{flex-direction:column;}.btn{justify-content:center;}}
</style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="page">
    <div class="page-header">
        <h1>Bookings</h1>
        <?php if (isUser()): ?>
            <a href="index.php" class="btn btn-ghost" style="padding:8px 16px;font-size:13px;">Browse Workers</a>
        <?php endif; ?>
    </div>

    <?php if ($flash === 'booked'): ?>
    <div class="flash ok">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        Booking request sent! The worker will be notified and can approve or reject it.
    </div>
    <?php elseif ($flash === 'rated'): ?>
    <div class="flash ok">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        Rating submitted! Thank you for your feedback.
    </div>
    <?php endif; ?>

    <!-- ── ACTIVE BOOKINGS ── -->
    <div class="mb-section">
        <div class="section-label">
            Active <span class="count-pill"><?= count($active) ?></span>
        </div>

        <?php if (empty($active)): ?>
        <div class="empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <p>No active bookings.<?php if(isUser()): ?> <a href="index.php">Browse workers →</a><?php endif; ?></p>
        </div>
        <?php endif; ?>

        <?php foreach ($active as $b):
            $status = $b['status'];
            $initials = isUser()
                ? strtoupper(implode('',array_map(fn($w)=>$w[0],array_slice(explode(' ',$b['worker_name']),0,2))))
                : strtoupper(implode('',array_map(fn($w)=>$w[0],array_slice(explode(' ',$b['user_name']??'?'),0,2))));
            $photoPath = (!empty($b['worker_photo'])) ? "uploads/{$b['worker_photo']}" : '';
        ?>
        <div class="booking-card">
            <div class="bcard-top">
                <div class="bcard-avatar">
                    <?php if (isUser() && $photoPath): ?><img src="<?= htmlspecialchars($photoPath) ?>"><?php
                    else: echo $initials; endif; ?>
                </div>
                <div class="bcard-info">
                    <?php if (isUser()): ?>
                        <div class="bcard-name"><?= htmlspecialchars($b['worker_name']) ?></div>
                        <div class="bcard-sub"><?= htmlspecialchars($b['profession'] ?? '') ?></div>
                    <?php elseif (isWorker()): ?>
                        <div class="bcard-name"><?= htmlspecialchars($b['user_name']) ?></div>
                        <div class="bcard-sub"><?= htmlspecialchars($b['user_location'] ?? '') ?></div>
                    <?php else: ?>
                        <div class="bcard-name"><?= htmlspecialchars($b['worker_name']) ?> ← <?= htmlspecialchars($b['user_name']) ?></div>
                        <div class="bcard-sub"><?= htmlspecialchars($b['profession'] ?? '') ?></div>
                    <?php endif; ?>
                </div>
                <div class="bcard-status">
                    <div class="status-pill s-<?= $status ?>">
                        <span class="sdot"></span>
                        <?= match($status) {
                            'pending'        => 'Awaiting Worker',
                            'confirmed'      => 'In Progress',
                            'awaiting_user'  => 'Awaiting Your Approval',
                            default          => ucfirst($status)
                        } ?>
                    </div>
                </div>
            </div>

            <div class="bcard-meta">
                <span class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <strong><?= date('M j, Y g:i a', strtotime($b['booked_at'])) ?></strong>
                </span>
                <?php if (isUser() && !empty($b['hourly_rate'])): ?>
                <span class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <strong>৳<?= number_format($b['hourly_rate'],0) ?>/hr</strong>
                </span>
                <?php endif; ?>
                <?php if (isWorker() && !empty($b['user_phone'])): ?>
                <span class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6.06 6.06l.9-.9a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <a href="tel:<?= htmlspecialchars($b['user_phone']) ?>" style="color:var(--accent)"><?= htmlspecialchars($b['user_phone']) ?></a>
                </span>
                <?php endif; ?>
            </div>

            <!-- Status description -->
            <?php if ($status === 'pending'): ?>
                <div class="status-desc warn-border">
                    <?php if (isUser()): ?>
                        ⏳ Waiting for the worker to accept or decline your request.
                    <?php elseif (isWorker()): ?>
                        📩 You have a new booking request. Please approve or reject below.
                    <?php else: ?>
                        ⏳ Pending worker response.
                    <?php endif; ?>
                </div>
            <?php elseif ($status === 'confirmed'): ?>
                <div class="status-desc accent-border">
                    <?php if (isUser()): ?>
                        🔧 Work is in progress. You'll be notified when the worker marks it as done.
                    <?php elseif (isWorker()): ?>
                        🔧 Job is confirmed and in progress. Mark as done when finished.
                    <?php else: ?>
                        🔧 Work in progress.
                    <?php endif; ?>
                </div>
            <?php elseif ($status === 'awaiting_user'): ?>
                <div class="status-desc purple-border">
                    <?php if (isUser()): ?>
                        ✅ The worker says the job is done! Please confirm completion below. You can rate afterward.
                    <?php elseif (isWorker()): ?>
                        ⏳ Waiting for the user to confirm completion.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Action buttons -->
            <div class="bcard-actions">
                <?php if ($status === 'pending' && isWorker() && $_SESSION['uid'] === $b['worker_id']): ?>
                    <a href="booking_action.php?action=approve&bid=<?= $b['id'] ?>" class="btn btn-success"
                       onclick="return confirm('Accept this booking?')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Approve
                    </a>
                    <a href="booking_action.php?action=reject&bid=<?= $b['id'] ?>" class="btn btn-danger"
                       onclick="return confirm('Reject this booking?')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Reject
                    </a>
                <?php endif; ?>

                <?php if ($status === 'pending' && isUser() && $_SESSION['uid'] === $b['user_id']): ?>
                    <a href="booking_action.php?action=cancel&bid=<?= $b['id'] ?>" class="btn btn-danger"
                       onclick="return confirm('Cancel this booking request?')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Cancel Request
                    </a>
                <?php endif; ?>

                <?php if ($status === 'confirmed' && isWorker() && $_SESSION['uid'] === $b['worker_id']): ?>
                    <a href="booking_action.php?action=done&bid=<?= $b['id'] ?>" class="btn btn-primary"
                       onclick="return confirm('Mark this job as done?')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Mark as Done
                    </a>
                <?php endif; ?>

                <?php if ($status === 'awaiting_user' && isUser() && $_SESSION['uid'] === $b['user_id']): ?>
                    <a href="booking_action.php?action=confirm&bid=<?= $b['id'] ?>" class="btn btn-success"
                       onclick="return confirm('Confirm job completion? The worker will be marked as available again.')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Confirm Completion
                    </a>
                <?php endif; ?>

                <a href="<?= isUser() ? "worker_details.php?id={$b['worker_id']}" : "worker_details.php?id={$b['worker_id']}" ?>"
                   class="btn btn-ghost">View Profile</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── PREVIOUS BOOKINGS ── -->
    <div>
        <div class="section-label">
            Previous <span class="count-pill"><?= count($previous) ?></span>
        </div>

        <?php if (empty($previous)): ?>
        <div class="empty"><p style="color:var(--muted);font-size:13.5px;">No previous bookings yet.</p></div>
        <?php endif; ?>

        <?php foreach ($previous as $b):
            $status = $b['status'];
            $initials = isUser()
                ? strtoupper(implode('',array_map(fn($w)=>$w[0],array_slice(explode(' ',$b['worker_name']),0,2))))
                : strtoupper(implode('',array_map(fn($w)=>$w[0],array_slice(explode(' ',$b['user_name']??'?'),0,2))));
            $photoPath = (!empty($b['worker_photo'])) ? "uploads/{$b['worker_photo']}" : '';
            $alreadyRated = !empty($b['rating_id']);
        ?>
        <div class="booking-card" style="opacity:<?= $status==='cancelled'?.6:1 ?>;">
            <div class="bcard-top">
                <div class="bcard-avatar">
                    <?php if (isUser() && $photoPath): ?><img src="<?= htmlspecialchars($photoPath) ?>"><?php
                    else: echo $initials; endif; ?>
                </div>
                <div class="bcard-info">
                    <?php if (isUser()): ?>
                        <div class="bcard-name"><?= htmlspecialchars($b['worker_name']) ?></div>
                        <div class="bcard-sub"><?= htmlspecialchars($b['profession'] ?? '') ?></div>
                    <?php elseif (isWorker()): ?>
                        <div class="bcard-name"><?= htmlspecialchars($b['user_name']) ?></div>
                        <div class="bcard-sub"><?= htmlspecialchars($b['user_location'] ?? '') ?></div>
                    <?php else: ?>
                        <div class="bcard-name"><?= htmlspecialchars($b['worker_name']) ?> ← <?= htmlspecialchars($b['user_name']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="bcard-status">
                    <div class="status-pill s-<?= $status ?>">
                        <span class="sdot"></span>
                        <?= ucfirst($status) ?>
                    </div>
                </div>
            </div>

            <div class="bcard-meta">
                <span class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?= date('M j, Y', strtotime($b['booked_at'])) ?>
                </span>
            </div>

            <!-- Rating section for completed bookings -->
            <?php if ($status === 'completed'): ?>
            <div class="rating-section">
                <?php if ($alreadyRated): ?>
                    <div class="rating-display-row">
                        <span class="rating-given-label">
                            <?= isWorker() ? 'Rating received' : 'Your rating' ?>
                        </span>
                        <div class="given-stars">
                            <?php
                            $gs = (int)($b['rating_stars'] ?? 0);
                            for ($si=1; $si<=5; $si++) {
                                $cls = $si <= $gs ? 'gfilled' : 'gempty';
                                echo "<span class='gstar $cls'>★</span>";
                            }
                            ?>
                            <span class="given-val"><?= $gs ?>/5</span>
                        </div>
                    </div>
                <?php elseif (isUser()): ?>
                    <div class="rate-box">
                        <div class="rate-box-title">Rate this worker</div>
                        <form method="POST" action="rate_worker.php" class="rate-form">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <div class="star-pick">
                                <?php for($i=5;$i>=1;$i--): ?>
                                <input type="radio" name="stars" id="s<?= $b['id'].'_'.$i ?>" value="<?= $i ?>" required>
                                <label for="s<?= $b['id'].'_'.$i ?>">★</label>
                                <?php endfor; ?>
                            </div>
                            <button type="submit" class="btn btn-primary" style="padding:8px 18px;font-size:13px;">Submit</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="font-size:12.5px;color:var(--muted);font-style:italic;">No rating submitted yet</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

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