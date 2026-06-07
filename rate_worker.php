<?php
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
    booking_id INT NOT NULL,
    user_id    INT NOT NULL,
    worker_id  INT NOT NULL,
    stars      TINYINT NOT NULL,
    rated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_booking (booking_id)
)");
// Ensure no stale unique constraint on user+worker combo
$conn->query("ALTER TABLE ratings DROP INDEX IF EXISTS unique_user_worker");
// Ensure booking_id unique index exists
$conn->query("ALTER IGNORE TABLE ratings ADD UNIQUE INDEX IF NOT EXISTS unique_booking (booking_id)");


if (!isUser()) { header("Location: signin.php"); exit; }

$bid   = (int)($_POST['booking_id'] ?? 0);
$stars = (int)($_POST['stars'] ?? 0);

if (!$bid || $stars < 1 || $stars > 5) { header("Location: bookings.php"); exit; }

// Verify booking belongs to this user and is completed
$uid  = $_SESSION['uid'];
$stmt = $conn->prepare("SELECT b.*, w.name AS worker_name, w.rating, w.rating_count FROM bookings b JOIN workers w ON w.id=b.worker_id WHERE b.id=? AND b.user_id=? AND b.status='completed'");
$stmt->bind_param("ii", $bid, $uid);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();
if (!$b) { header("Location: bookings.php"); exit; }

// Check not already rated
$r = $conn->prepare("SELECT id FROM ratings WHERE booking_id=?");
$r->bind_param("i", $bid);
$r->execute();
if ($r->get_result()->num_rows > 0) { header("Location: bookings.php?err=rated"); exit; }

// Insert rating
$stmt2 = $conn->prepare("INSERT INTO ratings (booking_id, user_id, worker_id, stars) VALUES (?,?,?,?)");
$stmt2->bind_param("iiii", $bid, $uid, $b['worker_id'], $stars);
$stmt2->execute();

// Recalculate worker's average rating
$new_count  = $b['rating_count'] + 1;
$new_rating = (($b['rating'] * $b['rating_count']) + $stars) / $new_count;
$stmt3 = $conn->prepare("UPDATE workers SET rating=?, rating_count=? WHERE id=?");
$stmt3->bind_param("dii", $new_rating, $new_count, $b['worker_id']);
$stmt3->execute();

// Notify worker
sendNotification($conn, 'worker', $b['worker_id'],
    "{$_SESSION['name']} rated you $stars/5 stars. New average: " . number_format($new_rating,1),
    "bookings.php");

header("Location: bookings.php?rated=1");
exit;