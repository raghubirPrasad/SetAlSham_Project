<?php
// ============================================================
// roles.php — what each admin ROLE may see and do in the panel.
// 'groups' => which sidebar groups are visible (matches the
//             'group' field in config_tables.php). '*' = all.
// 'write'  => may add/edit/delete (false = read-only).
// ============================================================

$ROLES = [
    'Admin' => [
        'label'  => 'Administrator',
        'groups' => '*',          // everything, including AdminUsers
        'write'  => true,
    ],
    'Manager' => [
        'label'  => 'Manager',
        'groups' => ['Sales','Front of House','Menu','People','Supply Chain','Logs'],
        'write'  => true,
    ],
    'Cashier' => [
        'label'  => 'Cashier',
        'groups' => ['Sales'],    // orders, items, payments, feedback
        'write'  => true,
    ],
    'Waiter' => [
        'label'  => 'Waiter',
        'groups' => ['Front of House','Menu'],
        'write'  => false,        // can view menu + reservations, not change them
    ],
];

// Can the current role see a given group?
function role_sees_group($role, $group) {
    global $ROLES;
    $r = $ROLES[$role] ?? null;
    if (!$r) return false;
    return $r['groups'] === '*' || in_array($group, $r['groups'], true);
}

// Can the current role write (add/edit/delete)?
function role_can_write($role) {
    global $ROLES;
    return !empty($ROLES[$role]['write']);
}
