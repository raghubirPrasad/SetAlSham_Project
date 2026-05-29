<?php
// ============================================================
// logout.php — end the admin session
// ============================================================
session_start();
session_destroy();
header('Location: login.php');
exit;
