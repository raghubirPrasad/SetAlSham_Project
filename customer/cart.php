<?php
session_start();
require 'db.php';
require 'helpers.php';
$page_title = 'Your Cart';
$err = '';

// ---- Show a confirmation after a successful checkout ----
$done = $_SESSION['order_done'] ?? null;
unset($_SESSION['order_done']);

// ---- Update quantities / remove items ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach (($_POST['qty'] ?? []) as $iid => $q) {
        $iid = (int)$iid; $q = (int)$q;
        if ($q > 0) $_SESSION['cart'][$iid] = $q; else unset($_SESSION['cart'][$iid]);
    }
    header('Location: cart.php'); exit;
}
if (isset($_POST['remove'])) {
    unset($_SESSION['cart'][(int)$_POST['remove']]);
    header('Location: cart.php'); exit;
}

// ---- Checkout: place the order ----
if (isset($_POST['checkout'])) {
    $first = trim($_POST['first'] ?? '');
    $last  = trim($_POST['last'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $otype = $_POST['order_type'] ?? '';
    $pmeth = $_POST['payment_method'] ?? '';
    $items = cart();

    if (!$items)                     $err = 'Your cart is empty.';
    elseif ($first==='' || $last==='' || $phone==='') $err = 'Please enter your name and phone number.';
    elseif (!in_array($otype, ['Dine-in','Takeaway','Delivery'], true)) $err = 'Please choose an order type.';
    elseif (!in_array($pmeth, ['Cash','Card','Online'], true)) $err = 'Please choose a payment method.';
    else {
        mysqli_begin_transaction($conn);
        try {
            $custId = find_or_create_customer($conn, $first, $last, $phone);
            $empId  = default_employee($conn);

            // 1) order header
            $stmt = mysqli_prepare($conn,
                "INSERT INTO Orders (CustomerID, EmployeeID, TableID, OrderType, Status)
                 VALUES (?,?,NULL,?, 'In Progress')");
            mysqli_stmt_execute($stmt, [$custId, $empId, $otype]);
            $orderId = mysqli_insert_id($conn);

            // 2) line items (price comes from the DB, not the browser).
            //    Each insert fires the total + inventory triggers.
            $priceStmt = mysqli_prepare($conn, "SELECT BasePrice FROM MenuItems WHERE ItemID=?");
            $itemStmt  = mysqli_prepare($conn,
                "INSERT INTO OrderItems (OrderID, ItemID, Quantity, Subtotal) VALUES (?,?,?,?)");
            foreach ($items as $iid => $qty) {
                mysqli_stmt_execute($priceStmt, [(int)$iid]);
                $p = mysqli_fetch_assoc(mysqli_stmt_get_result($priceStmt));
                if (!$p) continue;                       // item vanished — skip
                $subtotal = $p['BasePrice'] * (int)$qty;
                mysqli_stmt_execute($itemStmt, [$orderId, (int)$iid, (int)$qty, $subtotal]);
            }

            // 3) read the total the trigger just computed
            $tStmt = mysqli_prepare($conn, "SELECT TotalAmount FROM Orders WHERE OrderID=?");
            mysqli_stmt_execute($tStmt, [$orderId]);
            $total = mysqli_fetch_assoc(mysqli_stmt_get_result($tStmt))['TotalAmount'];

            // 4) payment (fires loyalty + audit triggers), then mark completed
            $payStmt = mysqli_prepare($conn,
                "INSERT INTO Payments (OrderID, PaymentMethod, AmountPaid) VALUES (?,?,?)");
            mysqli_stmt_execute($payStmt, [$orderId, $pmeth, $total]);
            mysqli_stmt_execute(
                mysqli_prepare($conn, "UPDATE Orders SET Status='Completed' WHERE OrderID=?"), [$orderId]);

            mysqli_commit($conn);
            $_SESSION['cart'] = [];
            $_SESSION['order_done'] = ['id' => $orderId, 'total' => $total];
            header('Location: cart.php'); exit;
        } catch (mysqli_sql_exception $e) {
            mysqli_rollback($conn);
            $err = cust_error($e->getMessage());
        }
    }
}

// ---- Build the display list from the cart ----
$lines = []; $cartTotal = 0;
$ids = array_map('intval', array_keys(cart()));
if ($ids) {
    $res = mysqli_query($conn,
        "SELECT ItemID, ItemName, BasePrice FROM MenuItems WHERE ItemID IN (" . implode(',', $ids) . ")");
    while ($r = mysqli_fetch_assoc($res)) {
        $qty = (int)cart()[$r['ItemID']];
        $sub = $r['BasePrice'] * $qty;
        $cartTotal += $sub;
        $lines[] = ['id'=>$r['ItemID'],'name'=>$r['ItemName'],'price'=>$r['BasePrice'],'qty'=>$qty,'sub'=>$sub];
    }
}

require 'header.php';
?>

<?php if ($done): ?>
  <div class="card confirm">
    <div class="tick">✓</div>
    <h2>Order confirmed!</h2>
    <p class="lead">Your order <strong>#<?= (int)$done['id'] ?></strong> for <strong><?= money($done['total']) ?></strong> has been placed.</p>
    <a class="btn btn-primary" href="index.php">Back to menu</a>
  </div>
<?php endif; ?>

<?php if ($err): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

<?php if (!$lines && !$done): ?>
  <div class="card">
    <h2>Your cart is empty</h2>
    <p class="lead">Add some dishes from the menu to get started.</p>
    <a class="btn btn-primary" href="index.php">Browse the menu</a>
  </div>
<?php elseif ($lines): ?>
  <div class="card">
    <h2>Your Cart</h2>
    <form method="post">
      <table>
        <thead><tr><th>Item</th><th class="num">Price</th><th>Qty</th><th class="num">Subtotal</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($lines as $l): ?>
            <tr>
              <td><?= h($l['name']) ?></td>
              <td class="num"><?= money($l['price']) ?></td>
              <td><input class="qty-input" type="number" name="qty[<?= $l['id'] ?>]" value="<?= $l['qty'] ?>" min="0"></td>
              <td class="num"><?= money($l['sub']) ?></td>
              <td>
                <button class="btn btn-sm btn-danger" name="remove" value="<?= $l['id'] ?>">Remove</button>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr><td colspan="3" class="num"><strong>Total</strong></td><td class="num"><strong><?= money($cartTotal) ?></strong></td><td></td></tr>
        </tbody>
      </table>
      <div style="margin-top:14px"><button class="btn" name="update" value="1">Update quantities</button></div>
    </form>
  </div>

  <div class="card">
    <h2>Checkout</h2>
    <p class="lead">Enter your details to place the order. We'll look you up by phone or add you as a new customer.</p>
    <form method="post">
      <div class="form-2">
        <label class="field">First Name <input type="text" name="first" required></label>
        <label class="field">Last Name <input type="text" name="last" required></label>
        <label class="field">Phone <input type="text" name="phone" required></label>
        <label class="field">Order Type
          <select name="order_type" required>
            <option value="Takeaway">Takeaway</option>
            <option value="Delivery">Delivery</option>
            <option value="Dine-in">Dine-in</option>
          </select>
        </label>
        <label class="field">Payment Method
          <select name="payment_method" required>
            <option value="Card">Card</option>
            <option value="Online">Online</option>
            <option value="Cash">Cash</option>
          </select>
        </label>
      </div>
      <button class="btn btn-primary btn-block" name="checkout" value="1">Place Order — <?= money($cartTotal) ?></button>
    </form>
  </div>
<?php endif; ?>

<?php require 'footer.php'; ?>
