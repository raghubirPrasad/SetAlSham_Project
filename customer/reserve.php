<?php
session_start();
require 'db.php';
require 'helpers.php';
$page_title = 'Reserve a Table';
$err = ''; $ok = '';

// Tables for the dropdown (with capacity so guests can pick a fit)
$tables = mysqli_query($conn, "SELECT TableID, TableNumber, Capacity FROM DiningTables ORDER BY TableNumber");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first'] ?? '');
    $last  = trim($_POST['last'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $table = (int)($_POST['table'] ?? 0);
    $date  = $_POST['date'] ?? '';
    $time  = $_POST['time'] ?? '';
    $guests= (int)($_POST['guests'] ?? 0);

    if ($first==='' || $last==='' || $phone==='' || !$table || !$date || !$time || $guests<1) {
        $err = 'Please fill in every field.';
    } else {
        try {
            $custId = find_or_create_customer($conn, $first, $last, $phone);
            // CreateReservation checks table capacity and the double-booking
            // trigger fires on insert — either may raise a clear error.
            $stmt = mysqli_prepare($conn, "CALL CreateReservation(?,?,?,?,?)");
            mysqli_stmt_execute($stmt, [$custId, $table, $date, $time, $guests]);
            mysqli_stmt_close($stmt);
            $ok = "Thank you! Your table is reserved for $date at $time for $guests guest(s).";
        } catch (mysqli_sql_exception $e) {
            $err = cust_error($e->getMessage());
        }
    }
}

require 'header.php';
?>

<div class="card" style="max-width:640px;margin:0 auto">
  <h2>Reserve a Table</h2>
  <p class="lead">Pick a table and time. We'll confirm if it's available for your party size.</p>

  <?php if ($ok):  ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

  <form method="post">
    <div class="form-2">
      <label class="field">First Name <input type="text" name="first" required></label>
      <label class="field">Last Name <input type="text" name="last" required></label>
      <label class="field">Phone <input type="text" name="phone" required></label>
      <label class="field">Table
        <select name="table" required>
          <option value="">— choose a table —</option>
          <?php while ($t = mysqli_fetch_assoc($tables)): ?>
            <option value="<?= (int)$t['TableID'] ?>">Table <?= (int)$t['TableNumber'] ?> — seats <?= (int)$t['Capacity'] ?></option>
          <?php endwhile; ?>
        </select>
      </label>
      <label class="field">Date <input type="date" name="date" required></label>
      <label class="field">Time <input type="time" name="time" required></label>
      <label class="field">Guests <input type="number" name="guests" min="1" required></label>
    </div>
    <button class="btn btn-primary btn-block">Confirm Reservation</button>
  </form>
</div>

<?php require 'footer.php'; ?>
