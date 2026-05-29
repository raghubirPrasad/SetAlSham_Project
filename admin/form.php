<?php
require 'auth_check.php';
require 'db.php';
require 'config_tables.php';
require 'lib.php';

$key = $_GET['t'] ?? '';
$cfg = table_cfg($key);
require_group_access($cfg);                 // block forbidden sections by role
if (!role_can_write($CURRENT_ROLE)) {
    die('<p style="font-family:sans-serif;padding:40px">Your role is read-only and cannot add or edit records. <a href="list.php?t=' . h($key) . '">Back</a></p>');
}
$id  = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$err = '';

// Block disallowed operations
if ($editing && !can($cfg, 'edit'))  { die('Editing is not allowed for this table.'); }
if (!$editing && !can($cfg, 'add'))  { die('Adding is not allowed for this table.'); }

$page_title = ($editing ? 'Edit ' : 'Add ') . rtrim($cfg['label'], 's');

// Current values: start from defaults, load row if editing, override with POST on error
$values = [];
if ($editing) {
    $row = get_row($conn, $cfg, $id);
    if (!$row) die('Record not found.');
    $values = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $e] = save_row($conn, $cfg, $_POST, $editing ? $id : null);
    if ($ok) { header("Location: list.php?t=" . urlencode($key)); exit; }
    $err = $e;
    $values = $_POST;            // keep what the user typed
}

require 'header.php';
?>

<p><a href="list.php?t=<?= h($key) ?>">&larr; Back to <?= h($cfg['label']) ?></a></p>
<?php if ($err): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

<div class="card" style="max-width:680px">
  <form method="post">
    <div class="form-grid">
      <?php foreach (form_cols($cfg) as $c):
        $val = $values[$c['name']] ?? '';
        if (($c['type'] ?? '') === 'bool'): ?>
          <label style="flex-direction:row;align-items:center;gap:8px;text-transform:none;letter-spacing:normal">
            <?= field_input($conn, $c, $val) ?> <?= h($c['label']) ?>
          </label>
        <?php else: ?>
          <label><?= h($c['label']) ?><?= !empty($c['req']) ? ' *' : '' ?>
            <?= field_input($conn, $c, $val) ?>
          </label>
        <?php endif;
      endforeach; ?>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Add Record' ?></button>
      <a class="btn" href="list.php?t=<?= h($key) ?>">Cancel</a>
    </div>
  </form>
</div>

<?php require 'footer.php'; ?>
