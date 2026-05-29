<?php
require 'auth_check.php';
require 'db.php';
$page_title = 'Dashboard';

// Headline figures (mirrors AdvancedQueries #3) — completed orders only
$kpi = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS orders, COALESCE(SUM(TotalAmount),0) AS revenue,
            COALESCE(AVG(TotalAmount),0) AS avg_val
     FROM Orders WHERE Status='Completed'"));

$pending  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM Orders WHERE Status='In Progress'"));

$lowstock = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM Inventory WHERE StockQuantity <= ReorderLevel"));

$rating   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(AVG(Rating),0) AS avg_r, COUNT(*) AS c FROM Feedback"));

// Daily sales — straight from the DailySalesReport VIEW
$daily  = mysqli_query($conn, "SELECT * FROM DailySalesReport ORDER BY SaleDate DESC LIMIT 7");

// Most recent orders (LEFT JOIN so walk-in/null customers still show)
$recent = mysqli_query($conn,
    "SELECT o.OrderID, COALESCE(c.FirstName,'Walk-in') AS cust, o.OrderType,
            o.TotalAmount, o.Status
     FROM Orders o LEFT JOIN Customers c ON o.CustomerID=c.CustomerID
     ORDER BY o.OrderID DESC LIMIT 6");

function status_badge($s) {
    $map = ['Completed'=>'completed','In Progress'=>'progress','Cancelled'=>'cancelled'];
    $cls = $map[$s] ?? 'muted';
    return '<span class="badge '.$cls.'">'.h($s).'</span>';
}

require 'header.php';
?>

<div class="kpi-grid">
  <div class="kpi">
    <div class="label">Total Revenue</div>
    <div class="value"><?= money($kpi['revenue']) ?></div>
  </div>
  <div class="kpi">
    <div class="label">Completed Orders</div>
    <div class="value"><?= (int)$kpi['orders'] ?></div>
  </div>
  <div class="kpi">
    <div class="label">Avg. Order Value</div>
    <div class="value"><?= money($kpi['avg_val']) ?></div>
  </div>
  <div class="kpi">
    <div class="label">In Progress</div>
    <div class="value"><?= (int)$pending['c'] ?></div>
  </div>
  <div class="kpi <?= $lowstock['c'] > 0 ? 'alert-kpi' : '' ?>">
    <div class="label">Low-Stock Items</div>
    <div class="value"><?= (int)$lowstock['c'] ?></div>
  </div>
  <div class="kpi">
    <div class="label">Avg. Rating</div>
    <div class="value"><?= number_format($rating['avg_r'],1) ?> <span style="font-size:16px;color:var(--muted)">/ 5</span></div>
  </div>
</div>

<div class="card">
  <h2>Daily Sales <span class="muted-text" style="font-size:13px;font-weight:400">(from the DailySalesReport view)</span></h2>
  <table>
    <thead><tr><th>Date</th><th class="num">Orders</th><th class="num">Revenue</th></tr></thead>
    <tbody>
      <?php if (mysqli_num_rows($daily) === 0): ?>
        <tr><td colspan="3" class="muted-text">No completed sales yet.</td></tr>
      <?php else: while ($r = mysqli_fetch_assoc($daily)): ?>
        <tr>
          <td><?= h($r['SaleDate']) ?></td>
          <td class="num"><?= (int)$r['TotalOrders'] ?></td>
          <td class="num"><?= money($r['DailyRevenue']) ?></td>
        </tr>
      <?php endwhile; endif; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Recent Orders</h2>
  <table>
    <thead><tr><th>#</th><th>Customer</th><th>Type</th><th class="num">Total</th><th>Status</th></tr></thead>
    <tbody>
      <?php while ($r = mysqli_fetch_assoc($recent)): ?>
        <tr>
          <td><a href="form.php?t=orders&id=<?= (int)$r['OrderID'] ?>">#<?= (int)$r['OrderID'] ?></a></td>
          <td><?= h($r['cust']) ?></td>
          <td><?= h($r['OrderType']) ?></td>
          <td class="num"><?= money($r['TotalAmount']) ?></td>
          <td><?= status_badge($r['Status']) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php require 'footer.php'; ?>
