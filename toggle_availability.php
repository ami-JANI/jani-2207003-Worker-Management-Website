<?php
/**
 * toggle_availability.php — flips a worker's own availability status.
 * No admin approval needed. Only the worker themself can do this —
 * not an admin, not anyone else.
 */
include "db_connect.php";
include "auth.php";

requireLogin();

$id = (int)($_POST['id'] ?? 0);

if ($id && isWorker() && $_SESSION['uid'] === $id) {
    $row = db_one($conn, "SELECT availability FROM workers WHERE id = :id", [':id' => $id]);
    if ($row) {
        $new = $row['availability'] === 'available' ? 'busy' : 'available';
        db_exec($conn, "UPDATE workers SET availability = :avail WHERE id = :id",
            [':avail' => $new, ':id' => $id]);
    }
}

header("Location: worker_details.php?id=$id");
exit;
