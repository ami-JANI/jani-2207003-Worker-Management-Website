<?php
// Include this at the top of every page AFTER including db_connect.php and auth.php
// Usage: include "navbar.php";
$unread = isLoggedIn() ? getUnreadCount($conn) : 0;
$su     = sessionUser();
?>
<nav class="navbar">
    <a class="navbar-brand" href="index.php">
        <div class="dot"></div>
        WorkForce Manager
    </a>
    <div class="nav-links">
        <a href="index.php">Browse</a>
        <?php if (isLoggedIn()): ?>
            <a href="bookings.php">Bookings</a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
            <a href="admin.php">Admin Panel</a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
            <a href="add_worker.php" class="btn-add">+ Add Worker</a>
        <?php endif; ?>

        <?php if (isLoggedIn()): ?>
            <!-- Notification bell -->
            <button class="notif-btn" id="notifToggle" onclick="toggleNotifPanel()" title="Notifications">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <?php if ($unread > 0): ?>
                    <span class="notif-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
                <?php endif; ?>
            </button>

            <!-- User menu -->
            <div class="user-menu" id="userMenu">
                <button class="user-btn" onclick="toggleUserMenu()">
                    <div class="user-avatar"><?= strtoupper($su['name'][0] ?? '?') ?></div>
                    <span><?= htmlspecialchars($su['name']) ?></span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <span class="dropdown-name"><?= htmlspecialchars($su['name']) ?></span>
                        <span class="dropdown-role"><?= ucfirst($su['role']) ?></span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <?php if (isWorker()): ?>
                        <a href="worker_details.php?id=<?= $su['id'] ?>">My Profile</a>
                    <?php endif; ?>
                    <a href="logout.php" class="dropdown-danger">Sign Out</a>
                </div>
            </div>
        <?php else: ?>
            <a href="signin.php" class="btn-signin">Sign In</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ── NOTIFICATION PANEL ─────────────────────────────────── -->
<?php if (isLoggedIn()):
    $notifs = getNotifications($conn);
?>
<div class="notif-overlay" id="notifOverlay" onclick="closeNotifPanel()"></div>
<div class="notif-panel" id="notifPanel">
    <div class="notif-panel-header">
        <span>Notifications</span>
        <?php if ($unread > 0): ?>
            <a href="mark_read.php" class="mark-read-btn" onclick="return markAllRead()">Mark all read</a>
        <?php endif; ?>
    </div>
    <div class="notif-list">
        <?php if (empty($notifs)): ?>
            <div class="notif-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <p>No notifications yet</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifs as $n): ?>
            <a class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>"
               href="<?= $n['link'] ?: '#' ?>">
                <div class="notif-dot"></div>
                <div class="notif-content">
                    <p><?= htmlspecialchars($n['message']) ?></p>
                    <span><?= date('M j, g:i a', strtotime($n['created_at'])) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
