<?php
// ============================================================
// setup_admins.php — run ONCE to create the four demo admin
// accounts with correctly-hashed passwords.
//   Usage:  php setup_admins.php   (from inside the admin/ folder)
// Safe to re-run: it clears and re-seeds the accounts.
// ============================================================
require 'db.php';

$accounts = [
    ['admin',   'admin123',   'Admin'],
    ['manager', 'manager123', 'Manager'],
    ['cashier', 'cashier123', 'Cashier'],
    ['waiter',  'waiter123',  'Waiter'],
];

mysqli_query($conn, "DELETE FROM AdminUsers");

$stmt = mysqli_prepare($conn,
    "INSERT INTO AdminUsers (Username, PasswordHash, Role) VALUES (?,?,?)");

foreach ($accounts as [$user, $pass, $role]) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    mysqli_stmt_execute($stmt, [$user, $hash, $role]);
    echo "Created  $user / $pass   ($role)\n";
}

echo "\nDone. You can delete this file now if you like.\n";
