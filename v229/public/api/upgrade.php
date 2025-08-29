<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../inc/lib.php';
$uid=require_login(); require_csrf();
$in=json_decode(file_get_contents('php://input'),true)??[]; $code=$in['code']??''; if($code==='') json_response(['ok'=>false,'error'=>'Нет кода улучшения']);
$pdo=db(); $pdo->beginTransaction();
try{
  $s=$pdo->prepare("SELECT balance FROM stats WHERE user_id=? FOR UPDATE"); $s->execute([$uid]); $stats=$s->fetch(); if(!$stats) throw new Exception('Нет статистики');
  $u=$pdo->prepare("SELECT level FROM user_upgrades WHERE user_id=? AND upgrade_code=? FOR UPDATE"); $u->execute([$uid,$code]); $lvlRow=$u->fetch();
  if(!$lvlRow){ $pdo->prepare("INSERT IGNORE INTO user_upgrades (user_id, upgrade_code, level) VALUES (?,?,0)")->execute([$uid,$code]); $u->execute([$uid,$code]); $lvlRow=$u->fetch(); }
  $level=(int)$lvlRow['level'];
  $up=$pdo->prepare("SELECT * FROM upgrades WHERE code=?"); $up->execute([$code]); $upg=$up->fetch(); if(!$upg) throw new Exception('Неизвестное улучшение');
  $cost=(int)ceil((float)$upg['base_cost']*pow((float)$upg['cost_growth'],$level));
  if ((int)$stats['balance'] < $cost) { $pdo->rollBack(); json_response(['ok'=>false,'error'=>'Не хватает кликов']); }
  $pdo->prepare("UPDATE stats SET balance=balance-? WHERE user_id=?")->execute([$cost,$uid]);
  $pdo->prepare("UPDATE user_upgrades SET level=level+1 WHERE user_id=? AND upgrade_code=?")->execute([$uid,$code]);
  $pdo->commit(); $_new=check_achievements($uid);
  $st=$pdo->prepare("SELECT balance,total_clicks FROM stats WHERE user_id=?"); $st->execute([$uid]); $cur=$st->fetch();
  json_response(['ok'=>true,'balance'=>(int)$cur['balance'],'total_clicks'=>(int)$cur['total_clicks']]);
}catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); json_response(['ok'=>false,'error'=>$e->getMessage()]); }
