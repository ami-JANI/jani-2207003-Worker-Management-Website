<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Helpers ───────────────────────────────────────────────────

function isAdmin()  { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function isWorker() { return isset($_SESSION['role']) && $_SESSION['role'] === 'worker'; }
function isUser()   { return isset($_SESSION['role']) && $_SESSION['role'] === 'user'; }
function isLoggedIn(){ return isset($_SESSION['role']); }

function requireLogin($redirect = 'signin.php') {
    if (!isLoggedIn()) { header("Location: $redirect"); exit; }
}
function requireAdmin($redirect = 'index.php') {
    if (!isAdmin())    { header("Location: $redirect"); exit; }
}

function sessionUser() {
    return [
        'id'   => $_SESSION['uid']  ?? 0,
        'name' => $_SESSION['name'] ?? '',
        'role' => $_SESSION['role'] ?? '',
    ];
}

// ── Password validator ─────────────────────────────────────────
function validatePassword(string $pw): string {
    if (strlen($pw) < 8)                   return "Password must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $pw))       return "Password must contain an uppercase letter.";
    if (!preg_match('/[a-z]/', $pw))       return "Password must contain a lowercase letter.";
    if (!preg_match('/[0-9]/', $pw))       return "Password must contain a number.";
    if (!preg_match('/[\W_]/', $pw))       return "Password must contain a special character.";
    return '';
}

// ── Notification helpers (OCI8) ────────────────────────────────
function sendNotification($conn, string $role, int $targetId, string $message, string $link = '') {
    db_exec($conn,
        "INSERT INTO notifications (target_role, target_id, message, link)
         VALUES (:role, :tid, :msg, :lnk)",
        [':role' => $role, ':tid' => $targetId, ':msg' => $message, ':lnk' => $link]);
}

function getUnreadCount($conn): int {
    if (!isLoggedIn()) return 0;
    $role = $_SESSION['role'];
    $uid  = (int)$_SESSION['uid'];
    return (int) db_scalar($conn,
        "SELECT COUNT(*) FROM notifications
         WHERE target_role = :role AND (target_id = :uid OR target_id = 0) AND is_read = 0",
        [':role' => $role, ':uid' => $uid], 0);
}

function getNotifications($conn, int $limit = 30): array {
    if (!isLoggedIn()) return [];
    $role = $_SESSION['role'];
    $uid  = (int)$_SESSION['uid'];
    return db_all($conn,
        "SELECT * FROM (
            SELECT * FROM notifications
            WHERE target_role = :role AND (target_id = :uid OR target_id = 0)
            ORDER BY created_at DESC
         ) WHERE ROWNUM <= :lim",
        [':role' => $role, ':uid' => $uid, ':lim' => $limit]);
}

function markAllRead($conn) {
    if (!isLoggedIn()) return;
    $role = $_SESSION['role'];
    $uid  = (int)$_SESSION['uid'];
    db_exec($conn,
        "UPDATE notifications SET is_read = 1
         WHERE target_role = :role AND (target_id = :uid OR target_id = 0)",
        [':role' => $role, ':uid' => $uid]);
}