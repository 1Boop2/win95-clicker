<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';

require_admin(); require_csrf();
$in = json_decode(file_get_contents('php://input'), true) ?? [];
$wipe_bal = !isset($in['balances']) ? true : (bool)$in['balances'];
$wipe_upg = !isset($in['upgrades']) ? true : (bool)$in['upgrades'];
$wipe_ach = (bool)($in['achievements'] ?? false);

$pdo = db();
$pdo->beginTransaction();
try {
  if ($wipe_bal) {
    $pdo->exec("UPDATE users SET balance=0, total_clicks=0, best_cps=0");
  }
  if ($wipe_upg) {
    try { $pdo->exec("TRUNCATE TABLE user_upgrades"); } catch (Throwable $e) {}
  }
  if ($wipe_ach) {
    // пробуем несколько вариантов названия
    foreach (['user_achievements','ach_users','ach_user','users_achievements'] as $tbl) {
      try { $pdo->exec("TRUNCATE TABLE `$tbl`"); } catch (Throwable $e) {}
    }
  }
  $pdo->commit();
  json_response(['ok'=>true]);
} catch (Throwable $e) {
  $pdo->rollBack();
  json_response(['ok'=>false,'error'=>'db']);
}
