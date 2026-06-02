<?php
/**
 * Run this ONCE in your browser: http://localhost/DB/generate_admin.php
 * It creates the default admin account, then delete this file.
 */
include "db_connect.php";

$name     = "Super Admin";
$email    = "admin@workforce.com";
$password = "Admin@1234";
$hash     = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO admins (name, email, password) VALUES (?,?,?) ON DUPLICATE KEY UPDATE password=VALUES(password)");
$stmt->bind_param("sss", $name, $email, $hash);

if ($stmt->execute()) {
    echo "<h2 style='font-family:sans-serif;color:green'>✅ Admin created!</h2>";
    echo "<p style='font-family:sans-serif'>Email: <b>$email</b><br>Password: <b>$password</b></p>";
    echo "<p style='font-family:sans-serif;color:red'><b>Delete this file after use!</b></p>";
} else {
    echo "Error: " . $conn->error;
}
