<?php
// ============================================================
// header.php — page shell + sidebar nav (built from config_tables.php),
// filtered by the current user's ROLE.
// ============================================================
require_once 'config_tables.php';
require_once 'roles.php';
$current = basename($_SERVER['PHP_SELF']);
$curT    = $_GET['t'] ?? '';
$page_title = $page_title ?? 'Admin';
$role = $CURRENT_ROLE ?? 'Admin';
$user = $CURRENT_USER ?? 'admin';

// Group the tables for the sidebar, keeping only groups this role may see.
$groups = [];
foreach ($TABLES as $navKey => $navCfg) {
    if (role_sees_group($role, $navCfg['group'])) $groups[$navCfg['group']][$navKey] = $navCfg['label'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set Al Sham — <?= h($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Karla:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="brand">
      <span class="brand-mark">سِت الشام</span>
      <span class="brand-name">Set Al Sham</span>
      <span class="brand-sub">Admin Panel</span>
    </div>
    <nav class="nav">
      <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
      <?php foreach ($groups as $gname => $items): ?>
        <div class="nav-group"><?= h($gname) ?></div>
        <?php foreach ($items as $navKey => $navLabel):
          $active = (($current === 'list.php' || $current === 'form.php') && $curT === $navKey) ? 'active' : ''; ?>
          <a href="list.php?t=<?= h($navKey) ?>" class="<?= $active ?>"><?= h($navLabel) ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="who">
      <span class="who-user"><?= h($user) ?></span>
      <span class="who-role"><?= h($ROLES[$role]['label'] ?? $role) ?></span>
    </div>
    <a class="logout" href="logout.php">Log out</a>
  </aside>
  <main class="content">
    <header class="page-head">
      <h1><?= h($page_title) ?></h1>
    </header>