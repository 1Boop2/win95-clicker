<?php
declare(strict_types=1);

/**
 * Simple per-user rate limiting for /api/click.php
 * Max clicks per second (default: 50), with short cooldown on overflow.
 *
 * DB requirements (see db/patch_rate_limit_v3_4.sql):
 *   ALTER TABLE users ADD COLUMN rl_sec INT UNSIGNED NOT NULL DEFAULT 0;
 *   ALTER TABLE users ADD COLUMN rl_count SMALLINT UNSIGNED NOT NULL DEFAULT 0;
 *   ALTER TABLE users ADD COLUMN rl_block_until INT UNSIGNED NOT NULL DEFAULT 0;
 */

if (!defined('CLICK_RATE_LIMIT'))    define('CLICK_RATE_LIMIT', 50);
if (!defined('CLICK_RATE_COOLDOWN')) define('CLICK_RATE_COOLDOWN', 1); // seconds

/**
 * @return array{ok:bool, retry_after?:int, remaining?:int}
 */
function rl_allow_click(PDO $pdo, int $uid, int $limit = CLICK_RATE_LIMIT): array {
  $now = time();
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare("SELECT rl_sec, rl_count, rl_block_until FROM users WHERE id=? FOR UPDATE");
    $stmt->execute([$uid]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) throw new Exception('user?');

    $sec   = (int)$r['rl_sec'];
    $count = (int)$r['rl_count'];
    $block = (int)$r['rl_block_until'];

    if ($block > $now) {
      $pdo->commit();
      return ['ok'=>false, 'retry_after'=> ($block - $now)];
    }

    if ($sec === $now) {
      if ($count >= $limit) {
        $block_until = $now + CLICK_RATE_COOLDOWN;
        $upd = $pdo->prepare("UPDATE users SET rl_block_until=? WHERE id=?");
        $upd->execute([$block_until, $uid]);
        $pdo->commit();
        return ['ok'=>false, 'retry_after'=> CLICK_RATE_COOLDOWN];
      }
      $count++;
      $upd = $pdo->prepare("UPDATE users SET rl_count=? WHERE id=?");
      $upd->execute([$count, $uid]);
      $pdo->commit();
      return ['ok'=>true, 'remaining'=> max(0, $limit - $count)];
    } else {
      $upd = $pdo->prepare("UPDATE users SET rl_sec=?, rl_count=1 WHERE id=?");
      $upd->execute([$now, $uid]);
      $pdo->commit();
      return ['ok'=>true, 'remaining'=> $limit - 1];
    }
  } catch (Throwable $e) {
    $pdo->rollBack();
    return ['ok'=>false, 'retry_after'=>1];
  }
}
