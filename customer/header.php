<?php
// ============================================================
// header.php (customer) — top navigation bar. Pages set $page_title
// and must have started the session + loaded helpers before this.
// ============================================================
$current = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? 'Set Al Sham';
$n = function_exists('cart_count') ? cart_count() : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set Al Sham — <?= h($page_title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Karla:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php">
    <span class="ar">سِت الشام</span>
    <span class="en">Set Al Sham</span>
  </a>
  <nav class="topnav">
    <a href="index.php"    class="<?= $current==='index.php'?'on':'' ?>">Menu</a>
    <a href="reserve.php"  class="<?= $current==='reserve.php'?'on':'' ?>">Reserve</a>
    <a href="feedback.php" class="<?= $current==='feedback.php'?'on':'' ?>">Feedback</a>
    <a href="cart.php" class="cart-link <?= $current==='cart.php'?'on':'' ?>">
      Cart<?php if ($n): ?> <span class="cart-badge"><?= (int)$n ?></span><?php endif; ?>
    </a>
  </nav>
</header>
<main class="wrap">
