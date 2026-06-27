<?php
/**
 * Thin OCI8 helpers that mirror the patterns the app used with mysqli.
 *
 * Binds use named placeholders (:name). All result rows are normalized:
 *   - column keys are lower-cased (OCI returns them UPPER by default),
 *     so existing $row['name'] style access keeps working;
 *   - CLOB/LOB values are loaded to plain strings.
 *
 * Every bind array is keyed by the placeholder name, e.g.
 *   db_one($conn, "SELECT * FROM workers WHERE id = :id", [':id' => $id]);
 *
 * Note: binding + execute always happen in the same function scope as the
 * caller's bind array, so OCI never binds to a freed variable.
 */

/** Lower-case keys and load any LOB columns to strings. */
function db_normalize_row($row) {
    if ($row === false || $row === null) return null;
    $out = [];
    foreach ($row as $k => $v) {
        if (is_object($v) && $v instanceof OCILob) {
            $v = $v->load();
            if ($v === false) $v = '';
        }
        $out[strtolower($k)] = $v;
    }
    return $out;
}

/** Bind every entry of $binds (by reference) onto a parsed statement. */
function db_bind_all($stmt, array &$binds) {
    foreach ($binds as $name => $unused) {
        oci_bind_by_name($stmt, $name, $binds[$name]);
    }
}

/** Execute an INSERT/UPDATE/DELETE. Returns true on success, false on failure. */
function db_exec($conn, string $sql, array $binds = [], bool $commit = true): bool {
    $stmt = oci_parse($conn, $sql);
    db_bind_all($stmt, $binds);
    return (bool) @oci_execute($stmt, $commit ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT);
}

/**
 * Execute an INSERT ... RETURNING id INTO :new_id and return the new id.
 * The SQL must contain a ":new_id" RETURNING bind. Returns 0 on failure.
 */
function db_insert_id($conn, string $sql, array $binds = []): int {
    $stmt = oci_parse($conn, $sql);
    db_bind_all($stmt, $binds);
    $newId = 0;
    oci_bind_by_name($stmt, ":new_id", $newId, 32);
    $ok = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    return $ok ? (int)$newId : 0;
}

/** Fetch all rows as an array of associative arrays. */
function db_all($conn, string $sql, array $binds = []): array {
    $stmt = oci_parse($conn, $sql);
    db_bind_all($stmt, $binds);
    if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return [];
    $rows = [];
    while (($r = oci_fetch_assoc($stmt)) !== false) {
        $rows[] = db_normalize_row($r);
    }
    return $rows;
}

/** Fetch a single row (associative), or null if none. */
function db_one($conn, string $sql, array $binds = []) {
    $stmt = oci_parse($conn, $sql);
    db_bind_all($stmt, $binds);
    if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return null;
    $r = oci_fetch_assoc($stmt);
    return $r === false ? null : db_normalize_row($r);
}

/** Fetch a single scalar (first column of first row), or $default. */
function db_scalar($conn, string $sql, array $binds = [], $default = null) {
    $row = db_one($conn, $sql, $binds);
    if ($row === null) return $default;
    $vals = array_values($row);
    return $vals[0] ?? $default;
}

/** True if the last OCI error on $h was a unique-constraint violation (ORA-00001). */
function db_is_duplicate($h): bool {
    $e = oci_error($h);
    return $e && (int)$e['code'] === 1;
}
?>
