<?php
include "db_connect.php";
include "auth.php";

// Schema is managed by oracle_schema.sql — no runtime table creation.

if (!isUser()) { header("Location: signin.php"); exit; }

$bid   = (int)($_POST['booking_id'] ?? 0);
$stars = (int)($_POST['stars'] ?? 0);

if (!$bid || $stars < 1 || $stars > 5) { header("Location: bookings.php"); exit; }

// Verify booking belongs to this user and is completed
$uid = (int)$_SESSION['uid'];
$b = db_one($conn,
    "SELECT b.*, w.name AS worker_name, w.rating, w.rating_count
     FROM bookings b JOIN workers w ON w.id = b.worker_id
     WHERE b.id = :bid AND b.user_id = :p_uid AND b.status = 'completed'",
    [':bid' => $bid, ':p_uid' => $uid]);
if (!$b) { header("Location: bookings.php"); exit; }
$worker_id = (int)$b['worker_id'];

// Check not already rated
if (db_one($conn, "SELECT id FROM ratings WHERE booking_id = :bid", [':bid' => $bid])) {
    header("Location: bookings.php?err=rated"); exit;
}

// Insert rating
db_exec($conn,
    "INSERT INTO ratings (booking_id, user_id, worker_id, stars)
     VALUES (:bid, :p_uid, :wid, :stars)",
    [':bid' => $bid, ':p_uid' => $uid, ':wid' => $worker_id, ':stars' => $stars]);

// Recalculate worker's average rating
$new_count  = (int)$b['rating_count'] + 1;
$new_rating = (((float)$b['rating'] * (int)$b['rating_count']) + $stars) / $new_count;
db_exec($conn,
    "UPDATE workers SET rating = :rating, rating_count = :rcount WHERE id = :wid",
    [':rating' => $new_rating, ':rcount' => $new_count, ':wid' => $worker_id]);

// Notify worker
sendNotification($conn, 'worker', $worker_id,
    "{$_SESSION['name']} rated you $stars/5 stars. New average: " . number_format($new_rating,1),
    "bookings.php");

header("Location: bookings.php?rated=1");
exit;