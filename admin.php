<?php
include "db_connect.php";
include "auth.php";
requireAdmin();

// Schema is managed by oracle_schema.sql — no runtime table/column creation.

$msg = '';

if (isset($_GET['approve'])) {
    $wid = (int)$_GET['approve'];
    db_exec($conn, "UPDATE workers SET approved=1 WHERE id = :id", [':id' => $wid]);
    sendNotification($conn,'worker',$wid,"Your application has been approved! You can now sign in.","");
    $msg = "Worker approved.";
}
if (isset($_GET['reject'])) {
    $wid = (int)$_GET['reject'];
    db_exec($conn, "DELETE FROM workers WHERE id = :id", [':id' => $wid]);
    $msg = "Worker rejected and removed.";
}
if (isset($_GET['delete_worker'])) {
    $wid = (int)$_GET['delete_worker'];
    db_exec($conn, "DELETE FROM workers WHERE id = :id", [':id' => $wid]);
    $msg = "Worker deleted.";
}
if (isset($_GET['delete_user'])) {
    $uid = (int)$_GET['delete_user'];
    db_exec($conn, "DELETE FROM users WHERE id = :id", [':id' => $uid]);
    $msg = "User deleted.";
}

// Approve a pending profile edit
if (isset($_GET['approve_edit'])) {
    $wid = (int)$_GET['approve_edit'];
    $w   = db_one($conn, "SELECT * FROM workers WHERE id = :id", [':id' => $wid]);
    if ($w && !empty($w['pending_edit'])) {
        $data         = json_decode($w['pending_edit'], true);
        $name         = $data['name']         ?? $w['name'];
        $profession   = $data['profession']   ?? $w['profession'];
        $skill        = $data['skill']        ?? $w['skill'];
        $experience   = (int)($data['experience'] ?? $w['experience']);
        $location     = $data['location']     ?? $w['location'];
        $phone        = $data['phone']        ?? $w['phone'];
        $availability = $data['availability'] ?? $w['availability'];
        $photo        = !empty($w['pending_photo']) ? $w['pending_photo'] : $w['photo'];

        if (!empty($w['pending_photo']) && !empty($w['photo']) && $w['pending_photo'] !== $w['photo'] && file_exists("uploads/".$w['photo'])) {
            unlink("uploads/".$w['photo']);
        }

        db_exec($conn,
            "UPDATE workers SET name=:name, profession=:prof, skill=:skill, experience=:exp,
                location=:loc, phone=:phone, photo=:photo, availability=:avail,
                pending_edit=NULL, pending_photo=NULL
             WHERE id=:id",
            [':name'=>$name, ':prof'=>$profession, ':skill'=>$skill, ':exp'=>$experience,
             ':loc'=>$location, ':phone'=>$phone, ':photo'=>$photo, ':avail'=>$availability, ':id'=>$wid]);
        sendNotification($conn,'worker',$wid,"Your profile edit has been approved and is now live!","worker_details.php?id=$wid");
        $msg = "Profile edit approved and applied.";
    }
}

// Reject a pending profile edit
if (isset($_GET['reject_edit'])) {
    $wid = (int)$_GET['reject_edit'];
    $w   = db_one($conn, "SELECT pending_photo FROM workers WHERE id = :id", [':id' => $wid]);
    if (!empty($w['pending_photo']) && file_exists("uploads/".$w['pending_photo'])) {
        unlink("uploads/".$w['pending_photo']);
    }
    db_exec($conn, "UPDATE workers SET pending_edit=NULL, pending_photo=NULL WHERE id = :id", [':id' => $wid]);
    sendNotification($conn,'worker',$wid,"Your profile edit was not approved. Please contact an admin for details.","");
    $msg = "Profile edit rejected.";
}

// ── Broadcast notification ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notif'])) {
    $target  = $_POST['notif_target'] ?? '';   // 'workers' | 'users' | 'both'
    $message = trim($_POST['notif_message'] ?? '');
    $link    = trim($_POST['notif_link'] ?? '');

    if ($message && in_array($target, ['workers','users','both'])) {
        if ($target === 'workers' || $target === 'both') {
            // target_id = 0 means broadcast to ALL of that role
            sendNotification($conn, 'worker', 0, $message, $link);
        }
        if ($target === 'users' || $target === 'both') {
            sendNotification($conn, 'user', 0, $message, $link);
        }
        $msg = "Notification broadcast to " . ucfirst($target) . " successfully.";
    } else {
        $msg = "Please enter a message and select a target.";
    }
}

$pending      = db_all($conn, "SELECT * FROM workers WHERE approved=0 ORDER BY created_at DESC");
$pending_edits= db_all($conn, "SELECT * FROM workers WHERE approved=1 AND pending_edit IS NOT NULL ORDER BY name");
$workers      = db_all($conn, "SELECT * FROM workers WHERE approved=1 ORDER BY name");
$users        = db_all($conn, "SELECT * FROM users ORDER BY created_at DESC");
$notifs       = getNotifications($conn, 50);
// Fetch all broadcast notifications sent by admin (target_id=0)
$broadcasts   = db_all($conn, "SELECT * FROM (SELECT * FROM notifications WHERE target_id=0 ORDER BY created_at DESC) WHERE ROWNUM <= 50");
$unread       = getUnreadCount($conn);
$su           = sessionUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel — WorkForce Manager</title>
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
.page{padding-top:calc(var(--navbar-h)+30px);max-width:1100px;margin:0 auto;padding-left:24px;padding-right:24px;padding-bottom:60px;}
.page-title{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin-bottom:24px;}
.msg{padding:12px 16px;border-radius:9px;font-size:13.5px;font-weight:500;margin-bottom:20px;background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.3);color:var(--success);}

.tabs{display:flex;gap:0;background:var(--surface2);border-radius:10px;padding:4px;margin-bottom:24px;border:1px solid var(--border);width:fit-content;flex-wrap:wrap;}
.tab-btn{padding:9px 18px;border-radius:7px;border:none;background:transparent;color:var(--muted);font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:7px;}
.tab-btn.active{background:var(--surface);color:var(--text);box-shadow:0 2px 8px rgba(0,0,0,.3);}
.badge-count{background:var(--danger);color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:9px;}
.badge-warn{background:var(--warn);color:#000;font-size:10px;font-weight:700;padding:2px 6px;border-radius:9px;}
.tab-pane{display:none;} .tab-pane.active{display:block;}

.tbl-wrap{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;}
table{width:100%;border-collapse:collapse;}
thead tr{border-bottom:1px solid var(--border);}
thead th{padding:13px 16px;text-align:left;font-size:11.5px;font-weight:700;color:var(--muted);letter-spacing:.6px;text-transform:uppercase;}
tbody tr{border-bottom:1px solid var(--border);transition:background .15s;}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:var(--surface2);}
td{padding:13px 16px;font-size:13.5px;color:var(--text);vertical-align:middle;}
.td-muted{color:var(--muted);}

.worker-mini{display:flex;align-items:center;gap:10px;}
.mini-avatar{width:34px;height:34px;border-radius:8px;overflow:hidden;background:var(--surface2);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:13px;font-weight:800;color:var(--muted);}
.mini-avatar img{width:100%;height:100%;object-fit:cover;}
.mini-name{font-weight:600;}
.mini-prof{font-size:12px;color:var(--muted);}

/* Diff view for pending edits */
.diff-cell{font-size:12.5px;line-height:1.6;}
.diff-old{color:var(--danger);text-decoration:line-through;display:block;}
.diff-new{color:var(--success);display:block;}
.diff-same{color:var(--muted);}

.act-btns{display:flex;gap:8px;flex-wrap:wrap;}
.btn-sm{padding:5px 12px;border-radius:7px;font-size:12.5px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px;border:none;transition:opacity .2s;}
.btn-approve{background:rgba(74,222,128,.15);color:var(--success);border:1px solid rgba(74,222,128,.3);}
.btn-approve:hover{background:rgba(74,222,128,.25);}
.btn-reject,.btn-delete{background:rgba(248,113,113,.1);color:var(--danger);border:1px solid rgba(248,113,113,.25);}
.btn-reject:hover,.btn-delete:hover{background:rgba(248,113,113,.2);}
.btn-edit{background:rgba(79,142,247,.1);color:var(--accent);border:1px solid rgba(79,142,247,.25);}
.btn-edit:hover{background:rgba(79,142,247,.2);}

.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;}
.stat-card .label{font-size:11px;font-weight:700;color:var(--muted);letter-spacing:.8px;text-transform:uppercase;display:block;margin-bottom:6px;}
.stat-card .value{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;}
.accent{color:var(--accent);} .warn-c{color:var(--warn);} .success-c{color:var(--success);}

.empty-note{padding:30px;text-align:center;color:var(--muted);font-size:14px;}

/* Broadcast notification form */
.notif-send-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:24px 28px;margin-bottom:24px;}
.notif-send-card h3{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.notif-send-card h3 svg{width:17px;height:17px;color:var(--accent);}
.notif-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.nfield{display:flex;flex-direction:column;gap:6px;}
.nfield.full{grid-column:1/-1;}
.nfield label{font-size:11.5px;font-weight:600;color:var(--muted);letter-spacing:.5px;text-transform:uppercase;}
.nfield input,.nfield textarea,.nfield select{background:var(--surface2);border:1px solid var(--border);border-radius:9px;color:var(--text);padding:10px 14px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;width:100%;resize:vertical;}
.nfield input:focus,.nfield textarea:focus,.nfield select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,142,247,.12);}
.nfield input::placeholder,.nfield textarea::placeholder{color:#55607a;}
.nfield select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238891aa' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;}

/* Target audience pills */
.target-selector{display:flex;gap:10px;}
.target-opt{flex:1;}
.target-opt input[type="radio"]{display:none;}
.target-opt label{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:12px;border-radius:10px;border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-size:13px;font-weight:600;color:var(--muted);transition:all .2s;text-align:center;text-transform:none;letter-spacing:0;}
.target-opt label .ticon{font-size:22px;}
.target-opt input[type="radio"]:checked + label{border-color:var(--accent);background:rgba(79,142,247,.1);color:var(--text);}
.target-opt.both input[type="radio"]:checked + label{border-color:var(--accent2);background:rgba(124,92,252,.1);color:#c4b5fd;}

.btn-send{display:inline-flex;align-items:center;gap:8px;padding:11px 28px;background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:10px;color:#fff;font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;transition:opacity .2s;}
.btn-send:hover{opacity:.88;}
.btn-send svg{width:16px;height:16px;}

/* Broadcast history table extras */
.role-tag{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600;}
.role-worker{background:rgba(79,142,247,.1);border:1px solid rgba(79,142,247,.2);color:var(--accent);}
.role-user{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.2);color:var(--success);}
.role-both{background:rgba(124,92,252,.1);border:1px solid rgba(124,92,252,.2);color:#a78bfa;}
.msg-preview{max-width:380px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

@media(max-width:600px){.notif-form-grid{grid-template-columns:1fr;}.target-selector{flex-direction:column;}}

@media(max-width:768px){.stats-row{grid-template-columns:1fr 1fr;}table{font-size:12px;}td,th{padding:9px 10px;}}
</style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="page">
    <div class="page-title">Admin Panel</div>

    <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="stats-row">
        <div class="stat-card"><span class="label">Active Workers</span><span class="value accent"><?= count($workers) ?></span></div>
        <div class="stat-card"><span class="label">Pending Approval</span><span class="value warn-c"><?= count($pending) ?></span></div>
        <div class="stat-card"><span class="label">Pending Edits</span><span class="value warn-c"><?= count($pending_edits) ?></span></div>
        <div class="stat-card"><span class="label">Total Users</span><span class="value success-c"><?= count($users) ?></span></div>
    </div>

    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('pending',this)">
            New Workers <?php if(count($pending)): ?><span class="badge-count"><?= count($pending) ?></span><?php endif; ?>
        </button>
        <button class="tab-btn" onclick="showTab('edits',this)">
            Pending Edits <?php if(count($pending_edits)): ?><span class="badge-warn"><?= count($pending_edits) ?></span><?php endif; ?>
        </button>
        <button class="tab-btn" onclick="showTab('workers',this)">Workers</button>
        <button class="tab-btn" onclick="showTab('users',this)">Users</button>
        <button class="tab-btn" onclick="showTab('notifs',this)">Notifications</button>
    </div>

    <!-- Pending new workers -->
    <div class="tab-pane active" id="tab-pending">
        <?php if (empty($pending)): ?>
            <p class="empty-note">No pending worker applications.</p>
        <?php else: ?>
        <div class="tbl-wrap">
            <table>
                <thead><tr><th>Worker</th><th>Contact</th><th>Location</th><th>Rate/hr</th><th>Submitted</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($pending as $w): ?>
                <tr>
                    <td><div class="worker-mini">
                        <div class="mini-avatar"><?php if($w['photo']): ?><img src="uploads/<?= htmlspecialchars($w['photo']) ?>"><?php else: echo strtoupper($w['name'][0]); endif; ?></div>
                        <div><div class="mini-name"><?= htmlspecialchars($w['name']) ?></div><div class="mini-prof"><?= htmlspecialchars($w['profession']) ?></div></div>
                    </div></td>
                    <td class="td-muted"><?= htmlspecialchars($w['email'] ?: $w['phone']) ?></td>
                    <td class="td-muted"><?= htmlspecialchars($w['location']) ?></td>
                    <td>৳<?= number_format($w['hourly_rate'],0) ?></td>
                    <td class="td-muted"><?= date('M j, Y', strtotime($w['created_at'])) ?></td>
                    <td><div class="act-btns">
                        <a href="?approve=<?= $w['id'] ?>" class="btn-sm btn-approve" onclick="return confirm('Approve this worker?')">✓ Approve</a>
                        <a href="?reject=<?= $w['id'] ?>"  class="btn-sm btn-reject"  onclick="return confirm('Reject and remove?')">✕ Reject</a>
                    </div></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pending profile edits -->
    <div class="tab-pane" id="tab-edits">
        <?php if (empty($pending_edits)): ?>
            <p class="empty-note">No pending profile edits.</p>
        <?php else: ?>
        <div class="tbl-wrap">
            <table>
                <thead><tr><th>Worker</th><th>Name</th><th>Profession</th><th>Skills</th><th>Location</th><th>Availability</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($pending_edits as $w):
                    $p = json_decode($w['pending_edit'], true);
                ?>
                <tr>
                    <td><div class="worker-mini">
                        <div class="mini-avatar"><?php if($w['photo']): ?><img src="uploads/<?= htmlspecialchars($w['photo']) ?>"><?php else: echo strtoupper($w['name'][0]); endif; ?></div>
                        <div><div class="mini-name"><?= htmlspecialchars($w['name']) ?></div><div class="mini-prof"><?= htmlspecialchars($w['profession']) ?></div></div>
                    </div></td>
                    <td class="diff-cell">
                        <?php if(($p['name']??'')!==$w['name']): ?>
                            <span class="diff-old"><?= htmlspecialchars($w['name']) ?></span>
                            <span class="diff-new"><?= htmlspecialchars($p['name']??'') ?></span>
                        <?php else: ?><span class="diff-same"><?= htmlspecialchars($w['name']) ?></span><?php endif; ?>
                    </td>
                    <td class="diff-cell">
                        <?php if(($p['profession']??'')!==$w['profession']): ?>
                            <span class="diff-old"><?= htmlspecialchars($w['profession']) ?></span>
                            <span class="diff-new"><?= htmlspecialchars($p['profession']??'') ?></span>
                        <?php else: ?><span class="diff-same"><?= htmlspecialchars($w['profession']) ?></span><?php endif; ?>
                    </td>
                    <td class="diff-cell">
                        <?php if(($p['skill']??'')!==($w['skill']??'')): ?>
                            <span class="diff-old"><?= htmlspecialchars($w['skill'] ?? '') ?></span>
                            <span class="diff-new"><?= htmlspecialchars($p['skill']??'') ?></span>
                        <?php else: ?><span class="diff-same"><?= htmlspecialchars($w['skill'] ?? '') ?></span><?php endif; ?>
                    </td>
                    <td class="diff-cell">
                        <?php if(($p['location']??'')!==$w['location']): ?>
                            <span class="diff-old"><?= htmlspecialchars($w['location']) ?></span>
                            <span class="diff-new"><?= htmlspecialchars($p['location']??'') ?></span>
                        <?php else: ?><span class="diff-same"><?= htmlspecialchars($w['location']) ?></span><?php endif; ?>
                    </td>
                    <td class="diff-cell">
                        <?php if(($p['availability']??'')!==$w['availability']): ?>
                            <span class="diff-old"><?= htmlspecialchars($w['availability']) ?></span>
                            <span class="diff-new"><?= htmlspecialchars($p['availability']??'') ?></span>
                        <?php else: ?><span class="diff-same"><?= htmlspecialchars($w['availability']) ?></span><?php endif; ?>
                    </td>
                    <td><div class="act-btns">
                        <a href="worker_details.php?id=<?= $w['id'] ?>" class="btn-sm btn-edit" target="_blank">👁 View</a>
                        <a href="?approve_edit=<?= $w['id'] ?>" class="btn-sm btn-approve" onclick="return confirm('Apply this edit?')">✓ Approve</a>
                        <a href="?reject_edit=<?= $w['id'] ?>"  class="btn-sm btn-reject"  onclick="return confirm('Reject this edit?')">✕ Reject</a>
                    </div></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Active workers -->
    <div class="tab-pane" id="tab-workers">
        <div class="tbl-wrap">
            <table>
                <thead><tr><th>Worker</th><th>Experience</th><th>Rating</th><th>Rate/hr</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($workers as $w): ?>
                <tr>
                    <td><div class="worker-mini">
                        <div class="mini-avatar"><?php if($w['photo']): ?><img src="uploads/<?= htmlspecialchars($w['photo']) ?>"><?php else: echo strtoupper($w['name'][0]); endif; ?></div>
                        <div><div class="mini-name"><?= htmlspecialchars($w['name']) ?></div><div class="mini-prof"><?= htmlspecialchars($w['profession']) ?></div></div>
                    </div></td>
                    <td class="td-muted"><?= $w['experience'] ?> yrs</td>
                    <td>⭐ <?= number_format($w['rating'],1) ?></td>
                    <td>৳<?= number_format($w['hourly_rate'],0) ?></td>
                    <td><span style="color:var(--success);font-size:12px;font-weight:600;">● Active</span></td>
                    <td><div class="act-btns">
                        <a href="edit_worker.php?id=<?= $w['id'] ?>" class="btn-sm btn-edit">✎ Edit</a>
                        <a href="?delete_worker=<?= $w['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Delete this worker permanently?')">✕ Delete</a>
                    </div></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Users -->
    <div class="tab-pane" id="tab-users">
        <div class="tbl-wrap">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Location</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($u['name']) ?></td>
                    <td class="td-muted"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="td-muted"><?= htmlspecialchars($u['phone']) ?></td>
                    <td class="td-muted"><?= htmlspecialchars($u['location']) ?></td>
                    <td class="td-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td><a href="?delete_user=<?= $u['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Delete this user?')">✕ Delete</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notifications -->
    <div class="tab-pane" id="tab-notifs">

        <!-- ── SEND FORM ── -->
        <div class="notif-send-card">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send Notification
            </h3>
            <form method="POST" id="notifForm">
                <input type="hidden" name="send_notif" value="1">
                <div class="notif-form-grid">
                    <div class="nfield full">
                        <label>Target Audience</label>
                        <div class="target-selector">
                            <div class="target-opt">
                                <input type="radio" name="notif_target" id="t-workers" value="workers" checked>
                                <label for="t-workers">
                                    <span class="ticon">🔧</span>
                                    All Workers
                                </label>
                            </div>
                            <div class="target-opt">
                                <input type="radio" name="notif_target" id="t-users" value="users">
                                <label for="t-users">
                                    <span class="ticon">👤</span>
                                    All Users
                                </label>
                            </div>
                            <div class="target-opt both">
                                <input type="radio" name="notif_target" id="t-both" value="both">
                                <label for="t-both">
                                    <span class="ticon">📢</span>
                                    Everyone
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="nfield full">
                        <label>Message *</label>
                        <textarea name="notif_message" rows="3" placeholder="Type your notification message here..." required id="notifMsg"></textarea>
                    </div>
                    <div class="nfield full">
                        <label>Link <span style="color:var(--muted);text-transform:none;font-weight:400;letter-spacing:0">(optional — e.g. bookings.php)</span></label>
                        <input type="text" name="notif_link" placeholder="e.g. index.php or https://...">
                    </div>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div style="font-size:12.5px;color:var(--muted);">
                        Char count: <span id="charCount" style="color:var(--text);font-weight:600;">0</span> / 500
                    </div>
                    <button type="submit" class="btn-send">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Send Notification
                    </button>
                </div>
            </form>
        </div>

        <!-- ── BROADCAST HISTORY ── -->
        <div style="font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--muted);margin-bottom:14px;display:flex;align-items:center;gap:10px;">
            Sent History
            <span style="flex:1;height:1px;background:var(--border);display:block;"></span>
        </div>

        <?php if (empty($broadcasts)): ?>
            <p class="empty-note">No broadcasts sent yet.</p>
        <?php else: ?>
        <div class="tbl-wrap">
            <table>
                <thead><tr><th>Message</th><th>Sent To</th><th>Link</th><th>Time</th></tr></thead>
                <tbody>
                <?php
                // Group consecutive broadcasts with same message+time as one "both" row
                $seen = [];
                foreach ($broadcasts as $n):
                    $key = $n['message'] . '|' . $n['created_at'];
                    if (isset($seen[$key])) {
                        $seen[$key]['roles'][] = $n['target_role'];
                        continue;
                    }
                    $seen[$key] = $n;
                    $seen[$key]['roles'] = [$n['target_role']];
                endforeach;
                foreach ($seen as $n):
                    $roles = array_unique($n['roles']);
                    sort($roles);
                    if (count($roles) >= 2 || (count($roles)===1 && $roles[0]==='both')) {
                        $roleHtml = '<span class="role-tag role-both">📢 Everyone</span>';
                    } elseif (in_array('worker', $roles)) {
                        $roleHtml = '<span class="role-tag role-worker">🔧 Workers</span>';
                    } else {
                        $roleHtml = '<span class="role-tag role-user">👤 Users</span>';
                    }
                ?>
                <tr>
                    <td class="msg-preview" title="<?= htmlspecialchars($n['message']) ?>"><?= htmlspecialchars($n['message']) ?></td>
                    <td><?= $roleHtml ?></td>
                    <td class="td-muted">
                        <?php if ($n['link']): ?>
                            <a href="<?= htmlspecialchars($n['link']) ?>" style="color:var(--accent);font-size:12.5px;" target="_blank">
                                <?= htmlspecialchars(basename($n['link'])) ?>
                            </a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="td-muted"><?= date('M j, Y g:i a', strtotime($n['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showTab(name,btn){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    btn.classList.add('active');
}

// Char counter for notification message
const notifMsg = document.getElementById('notifMsg');
const charCount = document.getElementById('charCount');
if (notifMsg && charCount) {
    notifMsg.addEventListener('input', () => {
        const len = notifMsg.value.length;
        charCount.textContent = len;
        charCount.style.color = len > 450 ? 'var(--danger)' : len > 350 ? 'var(--warn)' : 'var(--text)';
    });
}

// Auto-open notifications tab if a broadcast was just sent
<?php if (isset($_POST['send_notif'])): ?>
window.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('.tab-btn[onclick*="notifs"]');
    if (btn) showTab('notifs', btn);
});
<?php endif; ?>
function toggleNotifPanel(){document.getElementById('notifPanel')?.classList.toggle('open');document.getElementById('notifOverlay')?.classList.toggle('open');}
function closeNotifPanel(){document.getElementById('notifPanel')?.classList.remove('open');document.getElementById('notifOverlay')?.classList.remove('open');}
function toggleUserMenu(){document.getElementById('userDropdown')?.classList.toggle('open');}
document.addEventListener('click',e=>{const m=document.getElementById('userMenu');if(m&&!m.contains(e.target))document.getElementById('userDropdown')?.classList.remove('open');});
function markAllRead(){fetch('mark_read.php');return true;}
</script>

<?php include "footer.php"; ?>
</body>
</html>