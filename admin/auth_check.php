<?php
// ============================================================
// auth_check.php — top of every protected page.
// Requires login, and exposes the current role + access helpers.
// ============================================================
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
require_once 'roles.php';

$CURRENT_ROLE = $_SESSION['admin_role'] ?? 'Admin';
$CURRENT_USER = $_SESSION['admin_user'] ?? 'admin';

// Stop a page if the current role may not access this table's group.
function require_group_access($cfg) {
    global $CURRENT_ROLE;
    if (!role_sees_group($CURRENT_ROLE, $cfg['group'])) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;padding:40px">Your role ('
            . htmlspecialchars($CURRENT_ROLE) . ') does not have access to this section. '
            . '<a href="dashboard.php">Back to dashboard</a></p>');
    }
}
