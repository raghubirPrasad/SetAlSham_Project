<?php
require 'auth_check.php';
require 'db.php';
require 'config_tables.php';
require 'lib.php';

$key = $_GET['t'] ?? '';
$cfg = table_cfg($key);
require_group_access($cfg);                 // block forbidden sections by role
$canWrite = role_can_write($CURRENT_ROLE);  // read-only roles see no add/edit/delete
$page_title = $cfg['label'];
$msg = ''; $err = '';

// Handle a delete request
if (isset($_POST['delete_id']) && $canWrite && can($cfg, 'delete')) {
    [$ok, $e] = delete_row($conn, $cfg, (int)$_POST['delete_id']);
    if ($ok) $msg = "Record deleted."; else $err = $e;
}

$rows = mysqli_query($conn, list_query($cfg));
$listCols = array_filter($cfg['cols'], fn($c) => ($c['list'] ?? true) !== false);

require 'header.php';
?>

<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

<div class="toolbar">
  <span class="muted-text"><?= mysqli_num_rows($rows) ?> record(s)</span>
  <?php if ($canWrite && can($cfg, 'add')):
    $singular = $cfg['singular'] ?? rtrim($cfg['label'], 's'); ?>
    <a class="btn btn-primary" href="form.php?t=<?= h($key) ?>">+ Add <?= h($singular) ?></a>
  <?php endif; ?>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <?php foreach ($listCols as $c): ?><th><?= h($c['label']) ?></th><?php endforeach; ?>
        <?php if ($canWrite && (can($cfg,'edit') || can($cfg,'delete'))): ?><th></th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (mysqli_num_rows($rows) === 0): ?>
        <tr><td colspan="20" class="muted-text">No records yet.</td></tr>
      <?php else: while ($row = mysqli_fetch_assoc($rows)): $pk = $row[$cfg['pk']] ?? 0; ?>
        <tr>
          <?php foreach ($listCols as $c): ?>
            <td class="<?= (($c['type']??'')==='decimal'||($c['type']??'')==='int'||!empty($c['money']))?'num':'' ?>"><?= cell($c, $row) ?></td>
          <?php endforeach; ?>
          <?php if ($canWrite && (can($cfg,'edit') || can($cfg,'delete'))): ?>
            <td style="white-space:nowrap;text-align:right">
              <?php if (can($cfg,'edit')): ?>
                <a class="btn btn-sm" href="form.php?t=<?= h($key) ?>&id=<?= (int)$pk ?>">Edit</a>
              <?php endif; ?>
              <?php if (can($cfg,'delete')): ?>
                <form method="post" class="inline" onsubmit="return confirm('Delete this record?');">
                  <input type="hidden" name="delete_id" value="<?= (int)$pk ?>">
                  <button class="btn btn-sm btn-danger">Delete</button>
                </form>
              <?php endif; ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endwhile; endif; ?>
    </tbody>
  </table>
</div>

<?php require 'footer.php'; ?>
