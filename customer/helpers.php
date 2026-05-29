<?php
// ============================================================
// helpers.php (customer) — small functions shared by the customer pages.
// ============================================================

// Turn raw DB errors (incl. trigger SIGNAL messages) into friendly text.
function cust_error($m) {
    if (stripos($m, 'foreign key') !== false)   return "Something referenced is no longer available. Please try again.";
    if (stripos($m, 'Duplicate entry') !== false) return "This looks like it was already submitted.";
    return $m;   // capacity / double-booking / stock trigger messages are already readable
}

// Find a customer by phone (unique) or create a new one. Returns CustomerID.
function find_or_create_customer($conn, $first, $last, $phone, $email = null) {
    $stmt = mysqli_prepare($conn, "SELECT CustomerID FROM Customers WHERE Phone=?");
    mysqli_stmt_execute($stmt, [$phone]);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row) return (int)$row['CustomerID'];

    $stmt = mysqli_prepare($conn, "INSERT INTO Customers (FirstName, LastName, Phone, Email) VALUES (?,?,?,?)");
    mysqli_stmt_execute($stmt, [$first, $last, $phone, ($email !== '' ? $email : null)]);
    return mysqli_insert_id($conn);
}

// Just look up a customer id by phone (no creation). Returns id or null.
function find_customer($conn, $phone) {
    $stmt = mysqli_prepare($conn, "SELECT CustomerID FROM Customers WHERE Phone=?");
    mysqli_stmt_execute($stmt, [$phone]);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row ? (int)$row['CustomerID'] : null;
}

// Online orders need an employee on file (Orders.EmployeeID is required).
// Pick a Cashier if one exists, otherwise the first employee.
function default_employee($conn) {
    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT EmployeeID FROM Employees ORDER BY (Role='Cashier') DESC, EmployeeID LIMIT 1"));
    return $row ? (int)$row['EmployeeID'] : null;
}

// --- Cart (stored in the session as [ItemID => quantity]) ---
function cart() { return $_SESSION['cart'] ?? []; }
function cart_count() { return array_sum(cart()); }
