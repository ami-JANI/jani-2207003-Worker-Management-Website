<?php
/**
 * book_worker.php — creates a booking and redirects back
 * Called via POST from worker_details.php
 */
include "db_connect.php";
include "auth.php";

if (!isUser()) { header("Location: signin.php"); exit; }

$worker_id = (int)($_POST['worker_id'] ?? 0);
if (!$worker_id) { header("Location: index.php"); exit; }

// Fetch worker
$stmt = $conn->prepare("SELECT id, name, availability, approved FROM workers WHERE id=?");
$stmt->bind_param("i", $worker_id);
$stmt->execute();
$w = $stmt->get_result()->fetch_assoc();

if (!$w || $w['availability'] !== 'available' || !$w['approved']) {
    header("Location: worker_details.php?id=$worker_id&err=unavailable");
    exit;
}

$user_id = $_SESSION['uid'];

// Check if user already has an active (non-cancelled/completed) booking with this worker
$stmt2 = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND worker_id=? AND status IN ('pending','confirmed','awaiting_user')");
$stmt2->bind_param("ii", $user_id, $worker_id);
$stmt2->execute();
if ($stmt2->get_result()->num_rows > 0) {
    header("Location: worker_details.php?id=$worker_id&err=already");
    exit;
}

// Create booking
$stmt3 = $conn->prepare("INSERT INTO bookings (user_id, worker_id, status) VALUES (?,?,'pending')");
$stmt3->bind_param("ii", $user_id, $worker_id);
$stmt3->execute();
$booking_id = $conn->insert_id;

// Notify worker
$user_name = $_SESSION['name'];
sendNotification($conn, 'worker', $worker_id,
    "New booking request from \"$user_name\". Go to Bookings to approve or reject.",
    "bookings.php");

header("Location: bookings.php?booked=1");
exit;
