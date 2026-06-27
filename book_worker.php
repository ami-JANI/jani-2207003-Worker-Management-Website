<?php
/**
 * book_worker.php — creates a booking and redirects back
 * Called via POST from worker_details.php
 */
include "db_connect.php";
include "auth.php";

// Schema is managed by oracle_schema.sql — no runtime table creation.

if (!isUser()) { header("Location: signin.php"); exit; }

$worker_id = (int)($_POST['worker_id'] ?? 0);
if (!$worker_id) { header("Location: index.php"); exit; }

// Fetch worker
$w = db_one($conn, "SELECT id, name, availability, approved FROM workers WHERE id = :id", [':id' => $worker_id]);

if (!$w || $w['availability'] !== 'available' || !$w['approved']) {
    header("Location: worker_details.php?id=$worker_id&err=unavailable");
    exit;
}

$user_id = (int)$_SESSION['uid'];

// Check if user already has an active (non-cancelled/completed) booking with this worker
$existing = db_one($conn,
    "SELECT id FROM bookings WHERE user_id = :uid AND worker_id = :wid
     AND status IN ('pending','confirmed','awaiting_user')",
    [':uid' => $user_id, ':wid' => $worker_id]);
if ($existing) {
    header("Location: worker_details.php?id=$worker_id&err=already");
    exit;
}

// Create booking
$booking_id = db_insert_id($conn,
    "INSERT INTO bookings (user_id, worker_id, status)
     VALUES (:uid, :wid, 'pending') RETURNING id INTO :new_id",
    [':uid' => $user_id, ':wid' => $worker_id]);

// Notify worker
$user_name = $_SESSION['name'];
sendNotification($conn, 'worker', $worker_id,
    "New booking request from \"$user_name\". Go to Bookings to approve or reject.",
    "bookings.php");

header("Location: bookings.php?booked=1");
exit;