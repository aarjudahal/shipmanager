<?php
session_start();

// If already logged in as admin, redirect
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: ../admin/index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Hardcoded admin credentials
    if ($username === 'admin' && $password === '12345') {
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = 'admin';
        header("Location: ../admin/index.php");
        exit;
    } else {
        $error = "Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login - ShipManager</title>
<style>
body { font-family: Arial, sans-serif; background:#f0f4f8; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
.card { background:#fff; padding:25px; border-radius:12px; box-shadow:0 8px 25px rgba(0,0,0,0.1); width:320px; }
h2 { margin:0 0 15px; color:#004aad; text-align:center; }
input { width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:8px; }
button { width:100%; padding:12px; border:none; border-radius:8px; background:#004aad; color:#fff; font-weight:bold; cursor:pointer; }
button:hover { background:#0066cc; }
.error { background:#ffe5e5; color:#d00; padding:8px; border-radius:6px; margin-bottom:10px; font-size:14px; }
</style>
</head>
<body>
<div class="card">
  <h2>Admin Login</h2>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="text" name="username" placeholder="Admin Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>
</div>
</body>
</html>
