<?php
/**
 * booking_action.php — handles all booking state transitions via GET params
 * Called from notification links or bookings page buttons
 */
include "db_connect.php";
include "auth.php";

// Auto-create tables if missing
$conn->query("CREATE TABLE IF NOT EXISTS bookings (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    worker_id INT NOT NULL,
    status    ENUM('pending','confirmed','awaiting_user','completed','cancelled') DEFAULT 'pending',
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS ratings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    user_id    INT NOT NULL,
    worker_id  INT NOT NULL,
    stars      TINYINT NOT NULL,
    rated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

requireLogin();

$action = $_GET['action'] ?? '';
$bid    = (int)($_GET['bid'] ?? 0);
$back   = $_GET['back'] ?? 'bookings.php';

if (!$bid) { header("Location: $back"); exit; }

// Fetch booking with worker + user info
$stmt = $conn->prepare("
    SELECT b.*,
           w.name AS worker_name, w.availability,
           u.name AS user_name
    FROM bookings b
    JOIN workers w ON w.id = b.worker_id
    JOIN users   u ON u.id = b.user_id
    WHERE b.id = ?
");
$stmt->bind_param("i", $bid);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();
if (!$b) { header("Location: $back"); exit; }

$uid = $_SESSION['uid'];
$role = $_SESSION['role'];

switch ($action) {

    // ── Worker: approve booking request ──────────────────────────
    case 'approve':
        if (!isWorker() || $uid !== $b['worker_id']) break;
        if ($b['status'] !== 'pending') break;
        $conn->query("UPDATE bookings SET status='confirmed' WHERE id=$bid");
        $conn->query("UPDATE workers SET availability='busy' WHERE id={$b['worker_id']}");
        sendNotification($conn, 'user', $b['user_id'],
            "Your booking request for \"{$b['worker_name']}\" has been approved! Work is now confirmed.",
            "bookings.php");
        break;

    // ── Worker: reject booking request ───────────────────────────
    case 'reject':
        if (!isWorker() || $uid !== $b['worker_id']) break;
        if ($b['status'] !== 'pending') break;
        $conn->query("UPDATE bookings SET status='cancelled' WHERE id=$bid");
        sendNotification($conn, 'user', $b['user_id'],
            "Your booking request for \"{$b['worker_name']}\" was declined by the worker.",
            "bookings.php");
        break;

    // ── Worker: mark job as done ─────────────────────────────────
    case 'done':
        if (!isWorker() || $uid !== $b['worker_id']) break;
        if ($b['status'] !== 'confirmed') break;
        $conn->query("UPDATE bookings SET status='awaiting_user' WHERE id=$bid");
        sendNotification($conn, 'user', $b['user_id'],
            "\"{$b['worker_name']}\" has marked the job as done. Please confirm completion.",
            "bookings.php?action=confirm&bid=$bid");
        break;

    // ── User: confirm completion ──────────────────────────────────
    case 'confirm':
        if (!isUser() || $uid !== $b['user_id']) break;
        if ($b['status'] !== 'awaiting_user') break;
        $conn->query("UPDATE bookings SET status='completed' WHERE id=$bid");
        $conn->query("UPDATE workers SET availability='available' WHERE id={$b['worker_id']}");
        sendNotification($conn, 'worker', $b['worker_id'],
            "\"{$b['user_name']}\" confirmed the job is complete. You are now available again!",
            "bookings.php");
        break;

    // ── User: cancel their own pending request ───────────────────
    case 'cancel':
        if (!isUser() || $uid !== $b['user_id']) break;
        if ($b['status'] !== 'pending') break;
        $conn->query("UPDATE bookings SET status='cancelled' WHERE id=$bid");
        sendNotification($conn, 'worker', $b['worker_id'],
            "\"{$b['user_name']}\" cancelled their booking request.",
            "bookings.php");
        break;
}

header("Location: bookings.php");
exit;