<?php
include "db_connect.php";
include "auth.php";
requireAdmin();

$msg = '';

if (isset($_GET['approve'])) {
    $wid = (int)$_GET['approve'];
    $conn->query("UPDATE workers SET approved=1 WHERE id=$wid");
    sendNotification($conn,'worker',$wid,"Your application has been approved! You can now sign in.","");
    $msg = "Worker approved.";
}
if (isset($_GET['reject'])) {
    $wid = (int)$_GET['reject'];
    $conn->query("DELETE FROM workers WHERE id=$wid");
    $msg = "Worker rejected and removed.";
}
if (isset($_GET['delete_worker'])) {
    $wid = (int)$_GET['delete_worker'];
    $conn->query("DELETE FROM workers WHERE id=$wid");
    $msg = "Worker deleted.";
}
if (isset($_GET['delete_user'])) {
    $uid = (int)$_GET['delete_user'];
    $conn->query("DELETE FROM users WHERE id=$uid");
    $msg = "User deleted.";
}

// Approve a pending profile edit
if (isset($_GET['approve_edit'])) {
    $wid = (int)$_GET['approve_edit'];
    $w   = $conn->query("SELECT * FROM workers WHERE id=$wid")->fetch_assoc();
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

        $stmt2 = $conn->prepare("UPDATE workers SET name=?,profession=?,skill=?,experience=?,location=?,phone=?,photo=?,availability=?,pending_edit=NULL,pending_photo=NULL WHERE id=?");
        $stmt2->bind_param("sssissssi", $name,$profession,$skill,$experience,$location,$phone,$photo,$availability,$wid);
        $stmt2->execute();
        sendNotification($conn,'worker',$wid,"Your profile edit has been approved and is now live!","worker_details.php?id=$wid");
        $msg = "Profile edit approved and applied.";
    }
}

// Reject a pending profile edit
if (isset($_GET['reject_edit'])) {
    $wid = (int)$_GET['reject_edit'];
    $w   = $conn->query("SELECT pending_photo FROM workers WHERE id=$wid")->fetch_assoc();
    if (!empty($w['pending_photo']) && file_exists("uploads/".$w['pending_photo'])) {
        unlink("uploads/".$w['pending_photo']);
    }
    $conn->query("UPDATE workers SET pending_edit=NULL, pending_photo=NULL WHERE id=$wid");
    sendNotification($conn,'worker',$wid,"Your profile edit was not approved. Please contact an admin for details.","");
    $msg = "Profile edit rejected.";
}

// Ensure pending columns exist
$conn->query("ALTER TABLE workers ADD COLUMN IF NOT EXISTS pending_edit TEXT DEFAULT NULL");
$conn->query("ALTER TABLE workers ADD COLUMN IF NOT EXISTS pending_photo VARCHAR(255) DEFAULT NULL");

$pending      = $conn->query("SELECT * FROM workers WHERE approved=0 ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$pending_edits= $conn->query("SELECT * FROM workers WHERE approved=1 AND pending_edit IS NOT NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$workers      = $conn->query("SELECT * FROM workers WHERE approved=1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$users        = $conn->query("SELECT * FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$notifs       = getNotifications($conn, 50);
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
                <thead><tr><th>Worker</th><th>Name</th><th>Profession</th><th>Location</th><th>Availability</th><th>Actions</th></tr></thead>
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
        <?php if(empty($notifs)): ?>
            <p class="empty-note">No notifications yet.</p>
        <?php else: ?>
        <div class="tbl-wrap">
            <table>
                <thead><tr><th>Message</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($notifs as $n): ?>
                <tr>
                    <td><?= htmlspecialchars($n['message']) ?></td>
                    <td class="td-muted"><?= date('M j, g:i a', strtotime($n['created_at'])) ?></td>
                    <td><?= $n['is_read']?'<span class="td-muted">Read</span>':'<span style="color:var(--accent);font-weight:600;">New</span>' ?></td>
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
function toggleNotifPanel(){document.getElementById('notifPanel')?.classList.toggle('open');document.getElementById('notifOverlay')?.classList.toggle('open');}
function closeNotifPanel(){document.getElementById('notifPanel')?.classList.remove('open');document.getElementById('notifOverlay')?.classList.remove('open');}
function toggleUserMenu(){document.getElementById('userDropdown')?.classList.toggle('open');}
document.addEventListener('click',e=>{const m=document.getElementById('userMenu');if(m&&!m.contains(e.target))document.getElementById('userDropdown')?.classList.remove('open');});
function markAllRead(){fetch('mark_read.php');return true;}
</script>
</body>
</html>