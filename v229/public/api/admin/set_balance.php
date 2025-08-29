<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_admin(); require_csrf();
$in=json_decode(file_get_contents('php://input'),true)??[];
$uid=(int)($in['user_id']??0); $bal=(int)($in['balance']??0);
if($uid<=0) json_response(['ok'=>false,'error'=>'user_id?']);
$pdo=db();
$pdo->prepare("INSERT IGNORE INTO stats (user_id,total_clicks,balance,best_cps,last_update_ts,auto_carry) VALUES (?,?,?,?,?,?)")->execute([$uid,0,0,0,microtime(true),0]);
$pdo->prepare("UPDATE stats SET balance=? WHERE user_id=?")->execute([$bal,$uid]);
json_response(['ok'=>true]);
