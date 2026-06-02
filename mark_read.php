<?php
include "db_connect.php";
include "auth.php";
if (isLoggedIn()) markAllRead($conn);
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
