<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../inc/lib.php'; // тут compute_factors, apply_auto_income, log_click_and_cps

$uid = require_login();
require_csrf();
$pdo = db();

/** Проверяем, готовы ли поля для rate-limit в users; если нет — не лимитируем */
function rl_ready(PDO $pdo): bool {
  try {
    $a = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'rl_sec'")->fetch();
    $b = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'rl_count'")->fetch();
    $c = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'rl_block_until'")->fetch();
    return (bool)$a && (bool)$b && (bool)$c;
  } catch (Throwable $e) { return false; }
}
if (function_exists('rl_allow_click') && rl_ready($pdo)) {
  $rl = rl_allow_click($pdo, $uid);
  if (!$rl['ok']) {
    json_response(['ok'=>false,'error'=>'rate_limit','retry_after'=>(int)($rl['retry_after'] ?? 1)]);
  }
}

/** Считаем авто‑доход (до клика) */
apply_auto_income($uid);

/** Сила клика (в новой экономике) */
$f = compute_factors($uid);
$per_click = (int)max(1, floor((float)$f['manual_mult'])); // всегда >=1 целое

/** Начисляем клик быстро и под замком одной строки stats */
$pdo->beginTransaction();
try {
  $row = $pdo->prepare("SELECT balance,total_clicks FROM `stats` WHERE user_id=? FOR UPDATE");
  $row->execute([$uid]);
  $s = $row->fetch(PDO::FETCH_ASSOC);
  if (!$s) {
    $pdo->prepare("INSERT IGNORE INTO `stats` (user_id,total_clicks,balance,best_cps,last_update_ts,auto_carry)
                   VALUES (?,?,?,?,?,?)")->execute([$uid,0,0,0,microtime(true),0]);
    $row->execute([$uid]);
    $s = $row->fetch(PDO::FETCH_ASSOC);
  }
  $pdo->prepare("UPDATE `stats` SET balance=balance+?, total_clicks=total_clicks+? WHERE user_id=?")
      ->execute([$per_click, $per_click, $uid]);
  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_response(['ok'=>false,'error'=>'db']);
}

/** Логируем CPS по окну 3 сек (считаем клики как события, не очки) */
$cps = log_click_and_cps($uid, 1);

/** Вернём компактный ответ; фронт может потом вызвать /api/state.php для полной инфы */
json_response([
  'ok'=>true,
  'added'=>$per_click,
  'cps'=>round($cps,2),
]);
