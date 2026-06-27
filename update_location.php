<?php
/**
 * update_location.php — lets a logged-in user save their own location.
 * Used by the "My Location" box on index.php to filter the worker
 * directory by where the user actually lives.
 */
include "db_connect.php";
include "auth.php";

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isUser()) {
    $location = trim($_POST['location'] ?? '');
    if ($location !== '') {
        db_exec($conn, "UPDATE users SET location = :loc WHERE id = :p_uid",
            [':loc' => $location, ':p_uid' => (int)$_SESSION['uid']]);
        header("Location: index.php?location=" . rawurlencode($location));
        exit;
    }
}

header("Location: index.php");
exit;
