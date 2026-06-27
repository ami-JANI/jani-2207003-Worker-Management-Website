<?php
/**
 * Oracle (OCI8) database connection.
 *
 * Requires the PHP OCI8 extension + Oracle Instant Client.
 * Adjust the username, password and connection string to match
 * your Oracle install:
 *   - Oracle XE 21c (pluggable):  "localhost/XEPDB1"
 *   - Oracle XE 11g (SID):        "localhost/XE"
 *   - Full host/port/service:     "//localhost:1521/XEPDB1"
 */
$oracle_user = "workforce";
$oracle_pass = "workforce";
$oracle_dsn  = "localhost/XE";

$conn = @oci_connect($oracle_user, $oracle_pass, $oracle_dsn, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    die("Database connection failed: " . ($e['message'] ?? 'unknown error'));
}

// Return DATE/TIMESTAMP columns as ISO strings so PHP's strtotime() can parse them.
oci_execute(oci_parse($conn, "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'"));
oci_execute(oci_parse($conn, "ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'"));

require_once __DIR__ . "/db_helper.php";
?>
