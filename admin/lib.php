<?php
// ============================================================
// lib.php — generic helpers used by list.php and form.php.
// Every database write goes through a prepared statement here.
// ============================================================

// Look up a table definition by its URL key, or stop with a clean message.
function table_cfg($key) {
    global $TABLES;
    if (!isset($TABLES[$key])) { http_response_code(404); die('Unknown table.'); }
    return $TABLES[$key];
}

// Is an operation allowed for this table? (defaults to yes)
function can($cfg, $op) {
    return $cfg['can'][$op] ?? true;
}

// Columns that belong in the add/edit form (skip auto PK and 'form'=>false).
function form_cols($cfg) {
    return array_filter($cfg['cols'], function ($c) {
        return ($c['type'] ?? '') !== 'pk' && ($c['form'] ?? true) !== false;
    });
}

// Build the SELECT for a list view, LEFT JOINing each foreign key so we can
// show a readable label (e.g. the category name) instead of a raw id.
function list_query($cfg) {
    $sel = ['base.*'];
    $joins = [];
    $i = 0;
    foreach ($cfg['cols'] as $c) {
        if (($c['type'] ?? '') === 'fk') {
            $a   = "j$i";
            $ft  = $c['fk']['table'];
            $fpk = $c['fk']['pk'];
            $parts = array_map(fn($lc) => "$a.`$lc`", $c['fk']['labelCols']);
            $expr = count($parts) > 1 ? "CONCAT_WS(' ', " . implode(',', $parts) . ")" : $parts[0];
            $sel[]   = "$expr AS `__fk_{$c['name']}`";
            $joins[] = "LEFT JOIN `$ft` $a ON base.`{$c['name']}` = $a.`$fpk`";
            $i++;
        }
    }
    return "SELECT " . implode(', ', $sel) . " FROM `{$cfg['table']}` base "
         . implode(' ', $joins) . " ORDER BY base.`{$cfg['pk']}` DESC";
}

// Options for a foreign-key dropdown: [id => label].
function fk_options($conn, $c) {
    $ft  = $c['fk']['table'];
    $fpk = $c['fk']['pk'];
    $parts = array_map(fn($lc) => "`$lc`", $c['fk']['labelCols']);
    $expr = count($parts) > 1 ? "CONCAT_WS(' ', " . implode(',', $parts) . ")" : $parts[0];
    $res = mysqli_query($conn, "SELECT `$fpk` AS id, $expr AS label FROM `$ft` ORDER BY label");
    $out = [];
    while ($r = mysqli_fetch_assoc($res)) $out[$r['id']] = $r['label'];
    return $out;
}

// Render one cell in a list table.
function cell($c, $row) {
    $type = $c['type'] ?? 'text';
    if ($type === 'fk') {
        $lbl = $row["__fk_{$c['name']}"] ?? null;
        return ($lbl !== null && $lbl !== '') ? h($lbl) : '<span class="muted-text">—</span>';
    }
    $v = $row[$c['name']] ?? null;
    if ($v === null || $v === '') return '<span class="muted-text">—</span>';
    if ($type === 'bool')    return $v ? '<span class="badge ok">Yes</span>' : '<span class="badge muted">No</span>';
    if (!empty($c['money'])) return money($v);
    if ($type === 'decimal') return number_format((float)$v, 2);
    if ($type === 'time')    return h(substr($v, 0, 5));
    return h($v);
}

// Render one form field, pre-filled with $val.
function field_input($conn, $c, $val) {
    $name = $c['name'];
    $type = $c['type'] ?? 'text';
    $req  = !empty($c['req']) ? 'required' : '';

    if ($type === 'bool') {
        return "<input type='checkbox' name='" . h($name) . "' value='1' " . ($val ? 'checked' : '') . ">";
    }
    if ($type === 'enum' || $type === 'fk') {
        $opts = $type === 'fk' ? fk_options($conn, $c) : array_combine($c['opts'], $c['opts']);
        $html = "<select name='" . h($name) . "' $req>";
        if (empty($c['req'])) $html .= "<option value=''>— none —</option>";
        foreach ($opts as $id => $lbl) {
            $sel = ((string)$val === (string)$id) ? 'selected' : '';
            $shown = $type === 'fk' ? h($lbl) . " (#" . h($id) . ")" : h($lbl);
            $html .= "<option value='" . h($id) . "' $sel>$shown</option>";
        }
        return $html . "</select>";
    }
    if ($type === 'textarea') {
        return "<textarea name='" . h($name) . "' rows='3' $req>" . h($val) . "</textarea>";
    }
    if ($type === 'password') {
        // Never echo a stored value; show a hint instead.
        return "<input type='password' name='" . h($name) . "' value='' autocomplete='new-password' "
             . "placeholder='leave blank to keep current' $req>";
    }
    $itype = ['int'=>'number','decimal'=>'number','date'=>'date','time'=>'time'][$type] ?? 'text';
    $step  = $type === 'decimal' ? "step='0.01'" : ($type === 'int' ? "step='1'" : '');
    return "<input type='$itype' name='" . h($name) . "' value='" . h($val) . "' $step $req>";
}

// Turn a raw MySQL error into something human-friendly.
function friendly_db_error($m) {
    if (stripos($m, 'foreign key constraint fails') !== false)
        return "This record is linked to other data, so the database blocked the change (foreign key constraint).";
    if (stripos($m, 'Duplicate entry') !== false)
        return "That value must be unique — it already exists.";
    return $m;   // trigger SIGNAL messages and CHECK errors are already readable
}

// Insert (when $id is null) or update a row. Returns [ok, errorMessage].
function save_row($conn, $cfg, $post, $id = null) {
    $cols = []; $vals = [];
    foreach (form_cols($cfg) as $c) {
        $name = $c['name'];
        $type = $c['type'] ?? 'text';

        // Virtual password field -> hash into the PasswordHash column.
        if ($type === 'password') {
            $raw = isset($post[$name]) ? trim($post[$name]) : '';
            if ($raw === '') {
                // New account needs a password; editing may leave it blank to keep current.
                if ($id === null) return [false, "Password is required for a new account."];
                continue;
            }
            $cols[] = 'PasswordHash';
            $vals[] = password_hash($raw, PASSWORD_DEFAULT);
            continue;
        }

        if ($type === 'bool') { $cols[] = $name; $vals[] = !empty($post[$name]) ? 1 : 0; continue; }
        $raw = isset($post[$name]) ? trim($post[$name]) : '';
        if ($raw === '') {
            if (!empty($c['req'])) return [false, ($c['label'] ?? $name) . " is required."];
            $cols[] = $name; $vals[] = null;        // optional + empty -> NULL
            continue;
        }
        $cols[] = $name; $vals[] = $raw;
    }
    if (!$cols) return [false, "Nothing to save."];
    try {
        if ($id === null) {
            $ph  = implode(',', array_fill(0, count($cols), '?'));
            $set = implode(',', array_map(fn($x) => "`$x`", $cols));
            $stmt = mysqli_prepare($conn, "INSERT INTO `{$cfg['table']}` ($set) VALUES ($ph)");
            mysqli_stmt_execute($stmt, $vals);
        } else {
            $set = implode(', ', array_map(fn($x) => "`$x`=?", $cols));
            $vals[] = $id;
            $stmt = mysqli_prepare($conn, "UPDATE `{$cfg['table']}` SET $set WHERE `{$cfg['pk']}`=?");
            mysqli_stmt_execute($stmt, $vals);
        }
        return [true, ''];
    } catch (mysqli_sql_exception $e) {
        return [false, friendly_db_error($e->getMessage())];
    }
}

// Delete a row by primary key. Returns [ok, errorMessage].
function delete_row($conn, $cfg, $id) {
    try {
        $stmt = mysqli_prepare($conn, "DELETE FROM `{$cfg['table']}` WHERE `{$cfg['pk']}`=?");
        mysqli_stmt_execute($stmt, [$id]);
        return [true, ''];
    } catch (mysqli_sql_exception $e) {
        return [false, friendly_db_error($e->getMessage())];
    }
}

// Fetch a single row (for editing).
function get_row($conn, $cfg, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM `{$cfg['table']}` WHERE `{$cfg['pk']}`=?");
    mysqli_stmt_execute($stmt, [$id]);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}
