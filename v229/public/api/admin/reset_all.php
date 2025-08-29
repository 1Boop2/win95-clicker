<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';

require_admin(); 
require_csrf();

$in = json_decode(file_get_contents('php://input'), true) ?? [];
$wipe_bal = array_key_exists('balances', $in) ? (bool)$in['balances'] : true;     // по-умолчанию сбрасываем
$wipe_upg = array_key_exists('upgrades', $in) ? (bool)$in['upgrades'] : true;     // по-умолчанию сбрасываем
$wipe_ach = (bool)($in['achievements'] ?? false);                                 // ачивки — по галочке

$pdo = db();

/** helpers */
function table_exists(PDO $pdo, string $name): bool {
  try { $pdo->query("SELECT 1 FROM `{$name}` LIMIT 1"); return true; }
  catch (Throwable $e) { return false; }
}
function column_exists(PDO $pdo, string $table, string $col): bool {
  try {
    $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $st->execute([$col]);
    return (bool)$st->fetchColumn();
  } catch (Throwable $e) { return false; }
}

try {
  // balances / counters
  if ($wipe_bal) {
    // Основная реальная схема: stats
    if (table_exists($pdo, 'stats')) {
      // last_update_ts (DOUBLE) — сбросим на NOW(), auto_carry в ноль
      $pdo->exec("UPDATE `stats` 
                  SET `balance`=0, `total_clicks`=0, `best_cps`=0, 
                      `auto_carry`=0, `last_update_ts`=UNIX_TIMESTAMP()");
    }

    // На случай если где-то баланс/счётчики попадали в users (некоторые версии патчей так делали)
    if (table_exists($pdo, 'users')) {
      $sets = [];
      if (column_exists($pdo, 'users', 'balance'))     $sets[] = "`balance`=0";
      if (column_exists($pdo, 'users', 'total_clicks'))$sets[] = "`total_clicks`=0";
      if (column_exists($pdo, 'users', 'best_cps'))    $sets[] = "`best_cps`=0";
      if (!empty($sets)) {
        $pdo->exec("UPDATE `users` SET " . implode(',', $sets));
      }
    }

    // Очистим историю кликов для CPS, чтобы окно 3с стало чистым
    if (table_exists($pdo, 'clicks_log')) {
      $pdo->exec("DELETE FROM `clicks_log`");
    }
  }

  // upgrades
  if ($wipe_upg && table_exists($pdo, 'user_upgrades')) {
    // DELETE вместо TRUNCATE — не делает implicit commit и дружелюбнее к FK
    $pdo->exec("DELETE FROM `user_upgrades`");
  }

  // achievements
  if ($wipe_ach) {
    // Основная схема
    if (table_exists($pdo, 'user_achievements')) {
      $pdo->exec("DELETE FROM `user_achievements`");
    }
    // На всякий случай попробуем популярные альтернативы
    foreach (['ach_users','ach_user','users_achievements'] as $alt) {
      if (table_exists($pdo, $alt)) {
        $pdo->exec("DELETE FROM `{$alt}`");
      }
    }
  }

  json_response(['ok' => true]);

} catch (Throwable $e) {
  json_response(['ok'=>false, 'error'=>'db']);
}
