<?php
declare(strict_types=1);

/**
 * Balanced economy (v3.3): less OP upgrades, soft caps, ascending order.
 * Include in inc/lib.php:  require_once __DIR__ . '/econ.php';
 */

function econ_defs(): array {
  return [
    'click_power' => [
      'title' => 'Сила клика',
      'cat' => 'click',
      'base' => 10,          // базовая цена
      'scale' => 1.18,       // рост цены за уровень
      'max' => 200,          // технический предел
      'effect' => 'add_click',
      'softcap' => 50,       // после 50 уровней отдача снижается
      'softcap_factor' => 0.5, // в 2 раза слабее после капа
      'desc' => '+1 за клик за уровень (после 50 — +0.5)'
    ],
    'click_mult' => [
      'title' => 'Оптимизатор DOS',
      'cat' => 'multi',
      'base' => 500,
      'scale' => 1.33,
      'max' => 20,
      'effect' => 'mul_click',
      'per' => 0.05, // +5% к клику за уровень (мультипликативно)
      'desc' => '+5% к силе клика (до 20 ур.)'
    ],
    'autoclicker' => [
      'title' => 'Автокликер',
      'cat' => 'auto',
      'base' => 50,
      'scale' => 1.22,
      'max' => 500,
      'effect' => 'auto_cps',
      'per' => 0.2, // 0.2 к/с за штуку
      'softcap' => 50,
      'softcap_factor' => 0.5, // после 50 шт. — половинная отдача
      'desc' => '+0.2 кликов/сек за шт. (после 50 — +0.1)'
    ],
    'office_pc' => [
      'title' => 'Офисный ПК',
      'cat' => 'auto',
      'base' => 750,
      'scale' => 1.28,
      'max' => 200,
      'effect' => 'auto_cps',
      'per' => 1.1, // 1.1 к/с за шт.
      'softcap' => 25,
      'softcap_factor' => 0.5, // после 25 шт. — 0.55 к/с
      'desc' => '+1.1 кликов/сек за шт. (после 25 — +0.55)'
    ],
    'server_rack' => [
      'title' => 'Серверная стойка',
      'cat' => 'auto',
      'base' => 5000,
      'scale' => 1.33,
      'max' => 100,
      'effect' => 'auto_cps',
      'per' => 6.0, // 6 к/с за шт.
      'softcap' => 15,
      'softcap_factor' => 0.5, // после 15 шт. — 3 к/с
      'desc' => '+6 кликов/сек за шт. (после 15 — +3)'
    ],
  ];
}

function econ_next_cost(string $code, int $lvl): int {
  $defs = econ_defs();
  if (!isset($defs[$code])) return PHP_INT_MAX;
  $base = (float)$defs[$code]['base'];
  $scale = (float)$defs[$code]['scale'];
  return (int)ceil($base * pow($scale, $lvl));
}

function econ_levels(PDO $pdo, int $uid): array {
  $levels = [];
  try {
    $stmt = $pdo->prepare("SELECT code,lvl FROM user_upgrades WHERE user_id=?");
    $stmt->execute([$uid]);
    foreach ($stmt->fetchAll() as $r) { $levels[$r['code']] = (int)$r['lvl']; }
  } catch (Throwable $e) {
    // таблицы может не быть — вернём пусто
  }
  return $levels;
}

function econ_effects_from_levels(array $levels): array {
  $defs = econ_defs();
  $base_click = 1.0;
  $add_click  = 0.0;
  $mult_click = 1.0;
  $auto_cps   = 0.0;

  // Сила клика (с софт-капом)
  $lvl = (int)($levels['click_power'] ?? 0);
  if ($lvl > 0) {
    $sc = (int)$defs['click_power']['softcap'];
    $f  = (float)$defs['click_power']['softcap_factor'];
    if ($lvl <= $sc) $add_click += $lvl * 1.0;
    else $add_click += $sc * 1.0 + ($lvl - $sc) * (1.0 * $f);
  }

  // Множитель клика
  $lvl = (int)($levels['click_mult'] ?? 0);
  if ($lvl > 0) {
    $per = (float)$defs['click_mult']['per'];
    $mult_click *= pow(1.0 + $per, $lvl);
  }

  // Авто‑тиеры
  foreach (['autoclicker','office_pc','server_rack'] as $code) {
    $lvl = (int)($levels[$code] ?? 0);
    if ($lvl <= 0) continue;
    $def = $defs[$code];
    $per = (float)$def['per'];
    $sc  = (int)$def['softcap'];
    $f   = (float)$def['softcap_factor'];
    if ($lvl <= $sc) $auto_cps += $lvl * $per;
    else $auto_cps += $sc * $per + ($lvl - $sc) * ($per * $f);
  }

  $click_value = ($base_click + $add_click) * $mult_click;

  return [
    'click_value' => $click_value,
    'auto_cps'    => $auto_cps,
    'mult_click'  => $mult_click,
    'add_click'   => $add_click,
  ];
}

function econ_upgrades_sorted(PDO $pdo, int $uid): array {
  $defs   = econ_defs();
  $levels = econ_levels($pdo, $uid);
  $list = [];
  foreach ($defs as $code => $def) {
    $lvl = (int)($levels[$code] ?? 0);
    $max = (int)$def['max'];
    $is_max = $lvl >= $max;
    $next_cost = $is_max ? null : econ_next_cost($code, $lvl);
    $list[] = [
      'code'      => $code,
      'title'     => $def['title'],
      'cat'       => $def['cat'],
      'lvl'       => $lvl,
      'max'       => $max,
      'next_cost' => $next_cost,
      'desc'      => $def['desc'],
    ];
  }
  usort($list, function($a,$b){
    if ($a['next_cost']===null && $b['next_cost']===null) return $a['title']<=>$b['title'];
    if ($a['next_cost']===null) return 1;
    if ($b['next_cost']===null) return -1;
    if ($a['next_cost']==$b['next_cost']) return $a['title']<=>$b['title'];
    return $a['next_cost'] <=> $b['next_cost'];
  });
  return $list;
}

function econ_buy(PDO $pdo, int $uid, string $code): array {
  $defs = econ_defs();
  if (!isset($defs[$code])) return ['ok'=>false,'error'=>'no such upgrade'];
  $max = (int)$defs[$code]['max'];

  $pdo->beginTransaction();
  try {
    // lock user
    $u = $pdo->prepare("SELECT id,balance FROM users WHERE id=? FOR UPDATE");
    $u->execute([$uid]);
    $user = $u->fetch();
    if (!$user) throw new Exception('user?');
    $bal = (float)$user['balance'];

    // current level
    $sel = $pdo->prepare("SELECT lvl FROM user_upgrades WHERE user_id=? AND code=? FOR UPDATE");
    $sel->execute([$uid, $code]);
    $cur = $sel->fetchColumn();
    $lvl = (int)($cur ?: 0);
    if ($lvl >= $max) throw new Exception('MAX');

    $cost = econ_next_cost($code, $lvl);
    if ($bal < $cost) throw new Exception('NOFUNDS');

    // pay
    $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id=?")->execute([$cost,$uid]);

    // level up
    if ($cur===false) {
      $pdo->prepare("INSERT INTO user_upgrades (user_id,code,lvl) VALUES (?,?,1)")->execute([$uid,$code]);
    } else {
      $pdo->prepare("UPDATE user_upgrades SET lvl=lvl+1 WHERE user_id=? AND code=?")->execute([$uid,$code]);
    }

    // new balance
    $nb = $pdo->prepare("SELECT balance FROM users WHERE id=?");
    $nb->execute([$uid]);
    $new_balance = (float)$nb->fetchColumn();

    $pdo->commit();
    return ['ok'=>true,'spent'=>$cost,'new_level'=>$lvl+1,'new_balance'=>$new_balance];
  } catch (Throwable $e) {
    $pdo->rollBack();
    return ['ok'=>false,'error'=>$e->getMessage()];
  }
}
