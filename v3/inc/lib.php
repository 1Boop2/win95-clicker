<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

function ensure_stats(int $uid): void {
  $pdo=db();
  $pdo->prepare("INSERT IGNORE INTO stats (user_id, total_clicks, balance, best_cps, last_update_ts, auto_carry) VALUES (?,?,?,0,?,0)")
      ->execute([$uid,0,0,microtime(true)]);
}
function seed_upgrades(): void {
  $pdo=db();
  $rows=[
    ['m_mouse95','Мышь 95','Старая добрая мышь. +50% к клику за уровень.','manual',10,1.15,0.50,1.00],
    ['m_oil','Смазка колёсика','Колесо крутится — клики мутятся. +100% за уровень.','manual',100,1.18,1.00,1.00],
    ['m_driver','Драйвер‑пак 3.1','Ставим драйвера — клики летят. +150% за уровень.','manual',750,1.20,1.50,1.00],
    ['m_vga','Турбо‑VGA','Видеопамять ускоряет руку. +200% за уровень.','manual',5000,1.22,2.00,1.00],
    ['m_oc','Разгон Пентиума','Немного дымка — много кликов. +300% за уровень.','manual',20000,1.25,3.00,1.00],
    ['m_winmm','WinMM API','Системные звуки ускоряют руку. +400%/ур.','manual',80000,1.27,4.00,1.00],
    ['m_mouse_laser','Лазерная мышь','Лучи клика. +700%/ур.','manual',240000,1.28,7.00,1.00],
    ['m_usb','USB 1.1','Подключи — и полетели. +1000%/ур.','manual',500000,1.30,10.00,1.00],
    ['m_dx','DirectX 3','Графика помогает кликать. +1500%/ур.','manual',1500000,1.32,15.00,1.00],
    ['m_timewarp','Таймворп','Время на вашей стороне. +2500%/ур.','manual',6000000,1.35,25.00,1.00],
    ['a_cursor','Курсор‑бот','Автоклики по 0.1/сек за уровень.','auto',25,1.15,0.10,1.00],
    ['a_macro','MacroRecorder','Запишем клик — умножим. +1/сек за уровень.','auto',250,1.17,1.00,1.00],
    ['a_net','Сетевой кликер','LAN‑ферма даёт +10/сек.','auto',2500,1.20,10.00,1.00],
    ['a_corp','Корп‑бот','Офисные ПК помогают: +50/сек.','auto',10000,1.22,50.00,1.00],
    ['a_ai','ИИ‑кликер','Немного ИИ — +250/сек.','auto',50000,1.25,250.00,1.00],
    ['a_factory','Клик‑фабрика','+500/сек.','auto',120000,1.23,500.00,1.00],
    ['a_cluster','Кластер ПК','+2500/сек.','auto',600000,1.24,2500.00,1.00],
    ['a_datacenter','Дата‑центр','+10000/сек.','auto',2500000,1.25,10000.00,1.00],
    ['a_cloud','Облако кликов','+50000/сек.','auto',10000000,1.26,50000.00,1.00],
    ['a_quantum','Квант‑кликер','+200000/сек.','auto',60000000,1.28,200000.00,1.00],
    ['a_multiverse','Мульти‑вселенная','+1000000/сек.','auto',250000000,1.30,1000000.00,1.00],
  ];
  $stmt=$pdo->prepare("INSERT IGNORE INTO upgrades (code,name,description,type,base_cost,cost_growth,base_effect,effect_growth) VALUES (?,?,?,?,?,?,?,?)");
  foreach($rows as $r){ $stmt->execute($r); }
}
function get_upgrades(): array { $pdo=db(); $rows=$pdo->query("SELECT * FROM upgrades")->fetchAll(); $out=[]; foreach($rows as $r){ $out[$r['code']]=$r; } return $out; }
function get_user_upgrades(int $uid): array {
  $pdo=db(); $stmt=$pdo->prepare("SELECT upgrade_code, level FROM user_upgrades WHERE user_id=?"); $stmt->execute([$uid]);
  $out=[]; foreach($stmt as $row){ $out[$row['upgrade_code']]=(int)$row['level']; }
  $all=get_upgrades(); $ins=$pdo->prepare("INSERT IGNORE INTO user_upgrades (user_id, upgrade_code, level) VALUES (?,?,0)");
  foreach($all as $code=>$_){ if(!isset($out[$code])){ $ins->execute([$uid,$code]); $out[$code]=0; } }
  return $out;
}
function cost_for_level(array $up, int $level): int { return (int)ceil((float)$up['base_cost'] * pow((float)$up['cost_growth'], $level)); }
function compute_factors(int $uid): array {
  $levels=get_user_upgrades($uid); $all=get_upgrades(); $manual_mult=1.0; $auto_cps=0.0;
  foreach($levels as $code=>$lvl){ if($lvl<=0) continue; $up=$all[$code]; if($up['type']==='manual'){ $manual_mult*=pow(1.0+(float)$up['base_effect'],(int)$lvl); } else { $auto_cps+=(float)$up['base_effect']*(int)$lvl; } }
  return ['manual_mult'=>$manual_mult,'auto_cps'=>$auto_cps];
}
function apply_auto_income(int $uid): void {
  $pdo=db(); $pdo->beginTransaction();
  try{
    $row=$pdo->prepare("SELECT balance,total_clicks,last_update_ts,auto_carry FROM stats WHERE user_id=? FOR UPDATE"); $row->execute([$uid]); $s=$row->fetch();
    if(!$s){ $pdo->rollBack(); ensure_stats($uid); $pdo->beginTransaction(); $row->execute([$uid]); $s=$row->fetch(); }
    $now=microtime(true); $last=(float)$s['last_update_ts']; if($last<=0) $last=$now; $dt=max(0.0,$now-$last);
    $f=compute_factors($uid); $auto=$f['auto_cps']*$dt + (float)$s['auto_carry']; $add=(int)floor($auto); $carry=$auto-$add;
    if($add>0){ $pdo->prepare("UPDATE stats SET balance=balance+?, total_clicks=total_clicks+?, auto_carry=?, last_update_ts=? WHERE user_id=?")->execute([$add,$add,$carry,$now,$uid]); }
    else { $pdo->prepare("UPDATE stats SET auto_carry=?, last_update_ts=? WHERE user_id=?")->execute([$carry,$now,$uid]); }
    $pdo->commit();
  }catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function log_click_and_cps(int $uid, int $count): float {
  $pdo=db(); $now_ms=(int)floor(microtime(true)*1000); $bucket=$now_ms-($now_ms%100);
  $pdo->prepare("INSERT INTO clicks_log (user_id, bucket_ms, count) VALUES (?,?,?) ON DUPLICATE KEY UPDATE count=count+VALUES(count)")->execute([$uid,$bucket,$count]);
  $edge=$now_ms-3000; $stmt=$pdo->prepare("SELECT COALESCE(SUM(count),0) FROM clicks_log WHERE user_id=? AND bucket_ms>?"); $stmt->execute([$uid,$edge]); $sum=(int)$stmt->fetchColumn();
  $cps=$sum/3.0; $pdo->prepare("UPDATE stats SET best_cps = GREATEST(best_cps, ?) WHERE user_id=?")->execute([$cps,$uid]); return $cps;
}

/** ===== Achievements: dynamic + built-ins ===== */
function ach_builtin(): array {
  return [
    ['code'=>'ach_first_click','name'=>'Первый клик','desc'=>'Сделайте свой первый клик.','icon'=>'🐭','type'=>'stat','field'=>'total_clicks','gte'=>1],
    ['code'=>'ach_10','name'=>'Разгон','desc'=>'10 кликов.','icon'=>'🔟','type'=>'stat','field'=>'total_clicks','gte'=>10],
    ['code'=>'ach_100','name'=>'Сотня','desc'=>'100 кликов.','icon'=>'💯','type'=>'stat','field'=>'total_clicks','gte'=>100],
    ['code'=>'ach_1k','name'=>'Тысяча','desc'=>'1000 кликов.','icon'=>'🧮','type'=>'stat','field'=>'total_clicks','gte'=>1000],
    ['code'=>'ach_first_buy','name'=>'Первая покупка','desc'=>'Купите первое улучшение.','icon'=>'🛒','type'=>'stat','field'=>'levels_sum','gte'=>1],
    ['code'=>'ach_5lvls','name'=>'Апгрейдоман','desc'=>'Суммарно 5 уровней улучшений.','icon'=>'⚙️','type'=>'stat','field'=>'levels_sum','gte'=>5],
    ['code'=>'ach_10lvls','name'=>'Коллекционер','desc'=>'Суммарно 10 уровней улучшений.','icon'=>'🧰','type'=>'stat','field'=>'levels_sum','gte'=>10],
    ['code'=>'ach_auto10','name'=>'Моторчик','desc'=>'Достигните авто‑CPS = 10.','icon'=>'🤖','type'=>'stat','field'=>'auto_cps','gte'=>10],
    ['code'=>'ach_auto100','name'=>'Фермер кликов','desc'=>'Достигните авто‑CPS = 100.','icon'=>'🏭','type'=>'stat','field'=>'auto_cps','gte'=>100],
    ['code'=>'ach_cps5','name'=>'Спринтер','desc'=>'Лучший CPS ≥ 5.','icon'=>'⚡','type'=>'stat','field'=>'best_cps','gte'=>5],
    ['code'=>'ach_cps20','name'=>'Рекордсмен','desc'=>'Лучший CPS ≥ 20.','icon'=>'🏆','type'=>'stat','field'=>'best_cps','gte'=>20],
    ['code'=>'ach_million','name'=>'Миллионер','desc'=>'Баланс 1 000 000.','icon'=>'💰','type'=>'stat','field'=>'balance','gte'=>1000000],
    // Admin-only built-ins (невозможные без админки)
    ['code'=>'ach_admin_legend','name'=>'Легенда','desc'=>'Выдано вручную админом.','icon'=>'👑','type'=>'admin','field'=>null,'gte'=>null],
    ['code'=>'ach_admin_vip','name'=>'VIP','desc'=>'Выдано вручную админом.','icon'=>'💎','type'=>'admin','field'=>null,'gte'=>null],
  ];
}
function ach_db_defs(): array {
  $pdo=db(); $rows=$pdo->query("SELECT code,name,description AS desc,icon,type,field,gte FROM ach_defs")->fetchAll();
  return $rows ?: [];
}
function ach_definitions(): array {
  // merge by code: DB overrides built-in if same code
  $base = ach_builtin(); $db = ach_db_defs(); $map=[]; foreach($base as $d){ $map[$d['code']]=$d; }
  foreach($db as $d){ $map[$d['code']]=$d; }
  return array_values($map);
}
function get_user_ach_set(int $uid): array { $pdo=db(); $s=$pdo->prepare("SELECT code FROM user_achievements WHERE user_id=?"); $s->execute([$uid]); $set=[]; foreach($s as $r){ $set[$r['code']]=true; } return $set; }
function total_upgrade_levels(int $uid): int { $pdo=db(); $s=$pdo->prepare("SELECT COALESCE(SUM(level),0) FROM user_upgrades WHERE user_id=?"); $s->execute([$uid]); return (int)$s->fetchColumn(); }
function check_achievements(int $uid): array {
  $defs=ach_definitions(); $unlocked=get_user_ach_set($uid);
  $pdo=db(); $s=$pdo->prepare("SELECT total_clicks, balance, best_cps FROM stats WHERE user_id=?"); $s->execute([$uid]); $st=$s->fetch() ?: ['total_clicks'=>0,'balance'=>0,'best_cps'=>0];
  $f=compute_factors($uid); $levels_sum=total_upgrade_levels($uid);
  $vals=['total_clicks'=>(int)$st['total_clicks'],'balance'=>(int)$st['balance'],'best_cps'=>(float)$st['best_cps'],'auto_cps'=>(float)$f['auto_cps'],'levels_sum'=>$levels_sum];
  $ins=$pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, code) VALUES (?,?)"); $new=[];
  foreach($defs as $d){
    if(($d['type']??'stat')!=='stat') continue; // only stat-type auto-unlocks
    $c=$d['code']; if(!empty($unlocked[$c])) continue;
    $field=$d['field']; $gte=(float)($d['gte'] ?? 0); $v=(float)($vals[$field]??0);
    if($v >= $gte){ $ins->execute([$uid,$c]); $new[]=$c; }
  }
  return $new;
}
function get_state(int $uid): array {
  apply_auto_income($uid);
  $pdo=db(); $s=$pdo->prepare("SELECT total_clicks, balance, best_cps FROM stats WHERE user_id=?"); $s->execute([$uid]); $st=$s->fetch() ?: ['total_clicks'=>0,'balance'=>0,'best_cps'=>0];
  $f=compute_factors($uid); $levels=get_user_upgrades($uid); $all=get_upgrades(); $up=[];
  foreach($all as $code=>$u){ $lvl=$levels[$code]??0; $up[]=['code'=>$code,'name'=>$u['name'],'desc'=>$u['description'],'type'=>$u['type'],'level'=>$lvl,'cost'=>cost_for_level($u,$lvl),'effect'=>$u['base_effect']]; }
  $new_ach=check_achievements($uid); $ach_total=count(ach_definitions()); $ach_unlocked=count(get_user_ach_set($uid));
  return ['balance'=>(int)$st['balance'],'total_clicks'=>(int)$st['total_clicks'],'best_cps'=>round((float)$st['best_cps'],2),'manual_mult'=>round($f['manual_mult'],4),'auto_cps'=>round($f['auto_cps'],2),'upgrades'=>$up,'ach_total'=>$ach_total,'ach_unlocked'=>$ach_unlocked];
}
