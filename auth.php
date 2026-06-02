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

// ── Auto-create notifications table if missing ────────────────
function ensureNotificationsTable($conn) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS notifications (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            target_role ENUM('admin','worker','user') NOT NULL,
            target_id   INT          NOT NULL,
            message     VARCHAR(500) NOT NULL,
            link        VARCHAR(300) DEFAULT '',
            is_read     TINYINT(1)   DEFAULT 0,
            created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

// ── Notification helpers ───────────────────────────────────────
function sendNotification($conn, string $role, int $targetId, string $message, string $link = '') {
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (target_role, target_id, message, link) VALUES (?,?,?,?)");
        $stmt->bind_param("siss", $role, $targetId, $message, $link);
        $stmt->execute();
    } catch (Exception $e) {
        ensureNotificationsTable($conn);
    }
}

function getUnreadCount($conn): int {
    if (!isLoggedIn()) return 0;
    try {
        $role = $_SESSION['role'];
        $uid  = $_SESSION['uid'];
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE target_role=? AND (target_id=? OR target_id=0) AND is_read=0");
        $stmt->bind_param("si", $role, $uid);
        $stmt->execute();
        $stmt->bind_result($c);
        $stmt->fetch();
        return (int)$c;
    } catch (Exception $e) {
        ensureNotificationsTable($conn);
        return 0;
    }
}

function getNotifications($conn, int $limit = 30): array {
    if (!isLoggedIn()) return [];
    try {
        $role = $_SESSION['role'];
        $uid  = $_SESSION['uid'];
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE target_role=? AND (target_id=? OR target_id=0) ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("sii", $role, $uid, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        ensureNotificationsTable($conn);
        return [];
    }
}

function markAllRead($conn) {
    if (!isLoggedIn()) return;
    try {
        $role = $_SESSION['role'];
        $uid  = $_SESSION['uid'];
        $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE target_role=? AND (target_id=? OR target_id=0)");
        $stmt->bind_param("si", $role, $uid);
        $stmt->execute();
    } catch (Exception $e) {
        ensureNotificationsTable($conn);
    }
}