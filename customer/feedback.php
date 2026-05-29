<?php
session_start();
require 'db.php';
require 'helpers.php';
$page_title = 'Leave Feedback';
$err = ''; $ok = '';
$phone = trim($_POST['phone'] ?? '');
$custId = null; $orders = [];

// ---- Stage 2: submit the feedback ----
if (isset($_POST['rating'])) {
    $custId = (int)($_POST['customer_id'] ?? 0);
    $orderId = (int)($_POST['order_id'] ?? 0);
    $rating  = (int)$_POST['rating'];
    $comment = trim($_POST['comment'] ?? '');
    if (!$custId || !$orderId || $rating < 1 || $rating > 5) {
        $err = 'Please pick an order and a rating from 1 to 5.';
    } else {
        try {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO Feedback (CustomerID, OrderID, Rating, Comment) VALUES (?,?,?,?)");
            mysqli_stmt_execute($stmt, [$custId, $orderId, $rating, ($comment !== '' ? $comment : null)]);
            $ok = 'Thank you for your feedback!';
            $phone = '';   // reset the form
        } catch (mysqli_sql_exception $e) {
            $err = cust_error($e->getMessage());
        }
    }
}

// ---- Stage 1: look the customer up by phone, list orders without feedback ----
if ($phone !== '' && !$ok) {
    $custId = find_customer($conn, $phone);
    if (!$custId) {
        $err = 'No customer found with that phone number.';
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT o.OrderID, o.TotalAmount, o.OrderDateTime
             FROM Orders o
             WHERE o.CustomerID = ?
               AND NOT EXISTS (SELECT 1 FROM Feedback f WHERE f.OrderID = o.OrderID)
             ORDER BY o.OrderID DESC");
        mysqli_stmt_execute($stmt, [$custId]);
        $res = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($res)) $orders[] = $r;
        if (!$orders) $err = 'All of your orders already have feedback — thank you!';
    }
}

require 'header.php';
?>

<div class="card" style="max-width:620px;margin:0 auto">
  <h2>Leave Feedback</h2>
  <p class="lead">Enter the phone number you ordered with, then rate your order.</p>

  <?php if ($ok):  ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

  <?php if (!$orders): ?>
    <!-- Stage 1: phone lookup -->
    <form method="post">
      <label class="field">Phone Number <input type="text" name="phone" value="<?= h($phone) ?>" required></label>
      <button class="btn btn-primary">Find my orders</button>
    </form>
  <?php else: ?>
    <!-- Stage 2: choose an order and rate it -->
    <form method="post">
      <input type="hidden" name="customer_id" value="<?= (int)$custId ?>">
      <input type="hidden" name="phone" value="<?= h($phone) ?>">
      <label class="field">Which order?
        <select name="order_id" required>
          <?php foreach ($orders as $o): ?>
            <option value="<?= (int)$o['OrderID'] ?>">
              Order #<?= (int)$o['OrderID'] ?> — <?= money($o['TotalAmount']) ?> (<?= h(substr($o['OrderDateTime'],0,10)) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">Rating
        <select name="rating" required>
          <option value="5">★★★★★ (5)</option>
          <option value="4">★★★★ (4)</option>
          <option value="3">★★★ (3)</option>
          <option value="2">★★ (2)</option>
          <option value="1">★ (1)</option>
        </select>
      </label>
      <label class="field">Comment (optional)
        <textarea name="comment" rows="3"></textarea>
      </label>
      <button class="btn btn-primary btn-block">Submit Feedback</button>
    </form>
  <?php endif; ?>
</div>

<?php require 'footer.php'; ?>
