<?php
// ============================================================
// db.php (customer) — reuse the SAME connection + helpers as the
// admin side, so the database password only ever lives in one file
// (admin/db.php). Gives us $conn, money() and h().
// ============================================================
require __DIR__ . '/../admin/db.php';
