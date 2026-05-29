<?php
// ============================================================
// db.php  —  Database connection + small shared helpers
// ============================================================

$host   = 'localhost';
$user   = 'root';
$pass   = 'RaghuSQL@2005';              // XAMPP/WAMP default root password is empty. Change if you set one.
$dbname = 'SetAlShamDB';

// Connect with friendly error reporting OFF so a bad connection shows a clean message...
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}
// ...then switch ON exceptions so query errors (foreign keys, triggers, CHECKs)
// can be caught with try/catch and shown nicely instead of crashing.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- Basic admin login (demo only; hard-coded as agreed) ---
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

// --- Tiny helpers used across pages ---
function money($n) { return 'AED ' . number_format((float)$n, 2); }
function h($s)     { return htmlspecialchars($s ?? '', ENT_QUOTES); }
