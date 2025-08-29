<?php
declare(strict_types=1);

/**
 * Экономика v3.3 + совместимость со старой схемой user_upgrades(upgrade_code,level)
 * и баланс/списание через stats (а не users).
 * НЕ использует фронтовые данные — всё считается на бэке.
 */

function econ_defs(): array {
  return [
    'click_power' => [
      'title' => 'Сила клика','cat'=>'click','base'=>10,'scale'=>1.18,'max'=>200,
      'effect'=>'add_click','softcap'=>50,'softcap_factor'=>0.5,
      'desc'=>'+1 за клик за уровень (после 50 — +0.5)'
    ],
    'click_mult' => [
      'title'=>'Оптимизатор DOS','cat'=>'multi','base'=>500,'scale'=>1.33,'max'=>20,
      'effect'=>'mul_click','per'=>0.05,
      'desc'=>'+5% к силе клика (до 20 ур.)'
    ],
    'autoclicker' => [
      'title'=>'Автокликер','cat'=>'auto','base'=>50,'scale'=>1.22,'max'=>500,
      'effect'=>'auto_cps','per'=>0.2,'softcap'=>50,'softcap_factor'=>0.5,
      'desc'=>'+0.2 кликов/сек за шт. (после 50 — +0.1)'
    ],
    'office_pc' => [
      'title'=>'Офисный ПК','cat'=>'auto','base'=>750,'scale'=>1.28,'max'=>200,
      'effect'=>'auto_cps','per'=>1.1,'softcap'=>25,'softcap_factor'=>0.5,
      'desc'=>'+1.1 кликов/сек за шт. (после 25 — +0.55)'
    ],
    'server_rack' => [
      'title'=>'Серверная стойка','cat'=>'auto','base'=>5000,'scale'=>1.33,'max'=>100,
      'effect'=>'auto_cps','per'=>6.0,'softcap'=>15,'softcap_factor'=>0.5,
      'desc'=>'+6 кликов/сек за шт. (после 15 — +3)'
    ],
  ];
}

function econ_next_cost(string $code, int $lvl): int {
  $defs = econ_defs();
  if (!isset($defs[$code])) return PHP_INT_MAX;
  return (int)ceil((float)$defs[$code]['base'] * pow((float)$defs[$code]['scale'], $lvl));
}

/** Автодетект колонок в user_upgrades */
function econ_upg_schema(PDO $pdo): array {
  static $cache = null;
  if ($cache !== null) return $cache;
  try {
    $st = $pdo->query("SHOW COLUMNS FROM `user_upgrades` LIKE 'code'");
    $st2= $pdo->query("SHOW COLUMNS FROM `user_upgrades` LIKE 'lvl'");
    if ($st && $st->fetch() && $st2 && $st2->fetch()) {
      return $cache = ['code'=>'code','lvl'=>'lvl'];
    }
  } catch (Throwable $e) {}
  try {
    $st = $pdo->query("SHOW COLUMNS FROM `user_upgrades` LIKE 'upgrade_code'");
    $st2= $pdo->query("SHOW COLUMNS FROM `user_upgrades` LIKE 'level'");
    if ($st && $st->fetch() && $st2 && $st2->fetch()) {
      return $cache = ['code'=>'upgrade_code','lvl'=>'level'];
    }
  } catch (Throwable $e) {}
  // по умолчанию — новая схема
  return $cache = ['code'=>'code','lvl'=>'lvl'];
}

function econ_levels(PDO $pdo, int $uid): array {
  $levels = [];
  try {
    $sch = econ_upg_schema($pdo);
    $q = $pdo->prepare("SELECT {$sch['code']} AS code, {$sch['lvl']} AS lvl FROM `user_upgrades` WHERE user_id=?");
    $q->execute([$uid]);
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
      $levels[$r['code']] = (int)$r['lvl'];
    }
  } catch (Throwable $e) {}
  return $levels;
}

function econ_effects_from_levels(array $levels): array {
  $defs = econ_defs();
  $base_click = 1.0; $add_click = 0.0; $mult_click = 1.0; $auto_cps = 0.0;

  // Сила клика (софт-кап)
  $lvl = (int)($levels['click_power'] ?? 0);
  if ($lvl > 0) {
    $sc = (int)$defs['click_power']['softcap'];
    $f  = (float)$defs['click_power']['softcap_factor'];
    $add_click += ($lvl <= $sc) ? $lvl*1.0 : $sc*1.0 + ($lvl-$sc)*(1.0*$f);
  }

  // Множитель
  $lvl = (int)($levels['click_mult'] ?? 0);
  if ($lvl > 0) $mult_click *= pow(1.0 + (float)$defs['click_mult']['per'], $lvl);

  // Автотиры
  foreach (['autoclicker','office_pc','server_rack'] as $code) {
    $lvl = (int)($levels[$code] ?? 0); if ($lvl<=0) continue;
    $def = $defs[$code]; $per=(float)$def['per']; $sc=(int)$def['softcap']; $f=(float)$def['softcap_factor'];
    $auto_cps += ($lvl <= $sc) ? $lvl*$per : $sc*$per + ($lvl-$sc)*($per*$f);
  }

  return [
    'click_value' => ($base_click + $add_click) * $mult_click,
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
      'code'=>$code,'title'=>$def['title'],'cat'=>$def['cat'],
      'lvl'=>$lvl,'max'=>$max,'next_cost'=>$next_cost,'desc'=>$def['desc'],
    ];
  }
  usort($list, function($a,$b){
    if ($a['next_cost']===null && $b['next_cost']===null) return $a['title']<=>$b['title'];
    if ($a['next_cost']===null) return 1;
    if ($b['next_cost']===null) return -1;
    if ($a['next_cost']===$b['next_cost']) return $a['title']<=>$b['title'];
    return $a['next_cost'] <=> $b['next_cost'];
  });
  return $list;
}

/**
 * Покупка апгрейда:
 * • Баланс читаем/списываем из stats (а не users).
 * • Старая/новая схема user_upgrades поддерживается автоматически.
 */
function econ_buy(PDO $pdo, int $uid, string $code): array {
  $defs = econ_defs();
  if (!isset($defs[$code])) return ['ok'=>false,'error'=>'NO_UPGRADE'];

  $sch = econ_upg_schema($pdo);
  $max = (int)$defs[$code]['max'];

  $pdo->beginTransaction();
  try {
    // ensure stats row + lock
    $st = $pdo->prepare("SELECT balance FROM `stats` WHERE user_id=? FOR UPDATE");
    $st->execute([$uid]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      $ins = $pdo->prepare("INSERT IGNORE INTO `stats` (user_id,total_clicks,balance,best_cps,last_update_ts,auto_carry)
                            VALUES (?,?,?,?,?,?)");
      $ins->execute([$uid,0,0,0,microtime(true),0]);
      $st->execute([$uid]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
    }
    $bal = (float)($row['balance'] ?? 0);

    // текущий уровень
    $sel = $pdo->prepare("SELECT {$sch['lvl']} AS lvl FROM `user_upgrades` WHERE user_id=? AND {$sch['code']}=? FOR UPDATE");
    $sel->execute([$uid,$code]);
    $cur = $sel->fetchColumn();
    $lvl = (int)($cur ?: 0);
    if ($lvl >= $max) throw new Exception('MAX');

    $cost = econ_next_cost($code, $lvl);
    if ($bal < $cost) throw new Exception('NOFUNDS');

    // списание
    $pdo->prepare("UPDATE `stats` SET balance = balance - ? WHERE user_id=?")->execute([$cost,$uid]);

    // прокачка (update→insert)
    $upd = $pdo->prepare("UPDATE `user_upgrades` SET {$sch['lvl']} = {$sch['lvl']} + 1 WHERE user_id=? AND {$sch['code']}=?");
    $upd->execute([$uid,$code]);
    if ($upd->rowCount() === 0) {
      $ins2 = $pdo->prepare("INSERT INTO `user_upgrades` (user_id, {$sch['code']}, {$sch['lvl']}) VALUES (?,?,1)");
      $ins2->execute([$uid,$code]);
    }

    // новый баланс
    $nb = $pdo->prepare("SELECT balance FROM `stats` WHERE user_id=?");
    $nb->execute([$uid]);
    $new_balance = (float)$nb->fetchColumn();

    $pdo->commit();
    return ['ok'=>true,'spent'=>$cost,'new_level'=>$lvl+1,'new_balance'=>$new_balance];
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $msg = $e->getMessage();
    if ($msg==='NOFUNDS' || $msg==='MAX') return ['ok'=>false,'error'=>$msg];
    return ['ok'=>false,'error'=>'ERR'];
  }
}
