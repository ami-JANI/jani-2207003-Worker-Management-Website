<?php
include "db_connect.php";
include "auth.php";

if (isLoggedIn()) { header("Location: index.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    if (!$email || !$password) {
        $error = "Please fill in all fields.";
    } else {
        $table = match($role) { 'admin' => 'admins', 'worker' => 'workers', default => 'users' };
        $cols  = "id, name, password" . ($role==='worker' ? ", approved" : "");
        $row   = db_one($conn, "SELECT $cols FROM $table WHERE email = :email", [':email' => $email]);

        if (!$row || !password_verify($password, $row['password'])) {
            $error = "Invalid email or password.";
        } elseif ($role === 'worker' && !$row['approved']) {
            $error = "Your worker account is pending admin approval.";
        } else {
            $_SESSION['uid']  = (int)$row['id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $role;
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign In — WorkForce Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg: #0f1117; --surface: #181c27; --surface2: #1e2333;
    --border: #2a3045; --accent: #4f8ef7; --accent2: #7c5cfc;
    --text: #e8eaf0; --muted: #8891aa; --danger: #f87171;
}
body { background: var(--bg); color: var(--text); font-family: 'DM Sans', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }

.brand {
    position: fixed; top: 0; left: 0; right: 0; height: 62px;
    display: flex; align-items: center; padding: 0 28px;
    background: rgba(15,17,23,.92); backdrop-filter: blur(14px);
    border-bottom: 1px solid var(--border);
}
.brand a {
    font-family: 'Syne', sans-serif; font-size: 19px; font-weight: 800;
    color: var(--text); text-decoration: none; display: flex; align-items: center; gap: 9px;
    white-space: nowrap;
}
.brand .dot { width:8px; height:8px; border-radius:50%; background: linear-gradient(135deg,var(--accent),var(--accent2)); }

.card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 18px; padding: 40px; width: 100%; max-width: 420px;
    margin: 20px; animation: up .4s ease;
}
@keyframes up { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

.card h1 { font-family:'Syne',sans-serif; font-size:24px; font-weight:800; margin-bottom:6px; }
.card .sub { font-size:14px; color:var(--muted); margin-bottom:28px; }

/* Role tabs */
.role-tabs { display:flex; gap:0; background:var(--surface2); border-radius:10px; padding:4px; margin-bottom:24px; border:1px solid var(--border); }
.role-tab {
    flex:1; padding:9px; text-align:center; font-size:13.5px; font-weight:600;
    border-radius:7px; cursor:pointer; border:none; background:transparent;
    color:var(--muted); font-family:'DM Sans',sans-serif; transition:all .2s;
}
.role-tab.active { background:var(--surface); color:var(--text); box-shadow:0 2px 8px rgba(0,0,0,.3); }

.field { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
.field label { font-size:11.5px; font-weight:600; color:var(--muted); letter-spacing:.5px; text-transform:uppercase; }
.field input {
    background:var(--surface2); border:1px solid var(--border); border-radius:9px;
    color:var(--text); padding:11px 14px; font-size:14px; font-family:'DM Sans',sans-serif;
    outline:none; transition:border-color .2s, box-shadow .2s; width:100%;
}
.field input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(79,142,247,.12); }
.field input::placeholder { color:#55607a; }

.pw-wrap { position:relative; }
.pw-wrap input { padding-right:44px; }
.pw-toggle {
    position:absolute; right:12px; top:50%; transform:translateY(-50%);
    background:none; border:none; color:var(--muted); cursor:pointer; padding:4px;
    display:flex; align-items:center;
}
.pw-toggle:hover { color:var(--text); }
.pw-toggle svg { width:17px; height:17px; }

.alert { padding:12px 16px; border-radius:9px; font-size:13.5px; font-weight:500; margin-bottom:18px; background:rgba(248,113,113,.1); border:1px solid rgba(248,113,113,.3); color:var(--danger); }

.btn-submit {
    width:100%; padding:12px; background:linear-gradient(135deg,var(--accent),var(--accent2));
    border:none; border-radius:10px; color:#fff; font-size:14.5px; font-weight:700;
    font-family:'DM Sans',sans-serif; cursor:pointer; margin-top:8px;
    transition:opacity .2s, transform .15s;
}
.btn-submit:hover { opacity:.88; }
.btn-submit:active { transform:scale(.98); }

.footer-link { text-align:center; margin-top:20px; font-size:13.5px; color:var(--muted); }
.footer-link a { color:var(--accent); text-decoration:none; font-weight:500; }
.footer-link a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="brand"><a href="index.php"><div class="dot"></div>WorkForce Manager</a></div>

<div class="card">
    <h1>Welcome back</h1>
    <p class="sub">Sign in to your account</p>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Role selector -->
        <div class="role-tabs">
            <button type="button" class="role-tab <?= ($_POST['role']??'user')==='user'?'active':'' ?>"   onclick="setRole('user')">User</button>
            <button type="button" class="role-tab <?= ($_POST['role']??'')==='worker'?'active':'' ?>" onclick="setRole('worker')">Worker</button>
            <button type="button" class="role-tab <?= ($_POST['role']??'')==='admin'?'active':'' ?>"  onclick="setRole('admin')">Admin</button>
        </div>
        <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($_POST['role']??'user') ?>">

        <div class="field">
            <label>Email</label>
            <input type="email" name="email" placeholder="you@example.com" required
                   value="<?= htmlspecialchars($_POST['email']??'') ?>">
        </div>

        <div class="field">
            <label>Password</label>
            <div class="pw-wrap">
                <input type="password" name="password" id="pw" placeholder="••••••••" required>
                <button type="button" class="pw-toggle" onclick="togglePw('pw',this)">
                    <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-submit">Sign In</button>
    </form>

    <p class="footer-link">Don't have an account? <a href="signup.php">Sign Up</a></p>
</div>

<script>
function setRole(r) {
    document.getElementById('roleInput').value = r;
    document.querySelectorAll('.role-tab').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
}
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
