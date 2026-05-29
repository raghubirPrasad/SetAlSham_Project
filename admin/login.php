<?php
// ============================================================
// login.php — admin login, checked against the AdminUsers table.
// ============================================================
session_start();
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT AdminID, Username, PasswordHash, Role FROM AdminUsers WHERE Username=?");
    mysqli_stmt_execute($stmt, [$u]);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($row && password_verify($p, $row['PasswordHash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user']      = $row['Username'];
        $_SESSION['admin_role']      = $row['Role'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set Al Sham — Admin Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Karla:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
  <div class="login-card">
    <div class="login-brand">
      <span class="brand-mark">سِت الشام</span>
      <span class="brand-name">Set Al Sham</span>
      <span class="brand-sub">Admin Panel</span>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="post" class="login-form">
      <label>Username
        <input type="text" name="username" autofocus required>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    </form>
    <p class="login-hint">Demo: admin / manager / cashier / waiter &nbsp;(password = name + "123")</p>
  </div>
</body>
</html>
