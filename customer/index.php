<?php
session_start();
require 'db.php';
require 'helpers.php';

// Add an item to the cart, then redirect (so a refresh doesn't re-add).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $iid = (int)$_POST['add_item'];
    if ($iid > 0) {
        $_SESSION['cart'][$iid] = (cart()[$iid] ?? 0) + 1;
        $_SESSION['flash'] = 'Added to your cart.';
    }
    header('Location: index.php');
    exit;
}

$page_title = 'Menu';

// All available items, grouped by category (done in PHP after one query).
$res = mysqli_query($conn,
    "SELECT m.ItemID, m.ItemName, m.BasePrice, m.ItemType, c.CategoryName
     FROM MenuItems m JOIN Categories c ON m.CategoryID = c.CategoryID
     WHERE m.IsAvailable = 1
     ORDER BY c.CategoryName, m.ItemName");
$byCat = [];
while ($r = mysqli_fetch_assoc($res)) $byCat[$r['CategoryName']][] = $r;

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
require 'header.php';
?>

<section class="hero">
  <div class="ar">سِت الشام</div>
  <h1>Set Al Sham</h1>
  <p>Authentic Levantine grills, shawarma, fresh juices and more — browse the menu and build your order.</p>
</section>

<?php if ($flash): ?><div class="alert alert-success"><?= h($flash) ?></div><?php endif; ?>

<?php foreach ($byCat as $cat => $items): ?>
  <div class="cat-head"><h2><?= h($cat) ?></h2><span class="rule"></span></div>
  <div class="menu-grid">
    <?php foreach ($items as $it): ?>
      <div class="dish">
        <span class="name"><?= h($it['ItemName']) ?></span>
        <span class="type"><?= h($it['ItemType']) ?></span>
        <div class="row">
          <span class="price"><?= money($it['BasePrice']) ?></span>
          <form method="post" class="inline">
            <input type="hidden" name="add_item" value="<?= (int)$it['ItemID'] ?>">
            <button class="btn btn-sm btn-primary">Add</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

<?php require 'footer.php'; ?>
