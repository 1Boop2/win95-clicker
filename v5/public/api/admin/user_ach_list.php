<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../inc/lib.php';
require_admin(); require_csrf();
$in=json_decode(file_get_contents('php://input'),true)??[]; $uid=(int)($in['user_id']??0); if($uid<=0) json_response(['ok'=>false,'error'=>'user_id?']);
$defs=ach_definitions(); $pdo=db();
$times=$pdo->prepare("SELECT code, unlocked_at FROM user_achievements WHERE user_id=?"); $times->execute([$uid]);
$map=[]; foreach($times as $r){ $map[$r['code']]=$r['unlocked_at']; }
$list=[]; foreach($defs as $d){ $code=$d['code']; $list[]=['code'=>$code,'name'=>$d['name'],'type'=>$d['type']??'stat','unlocked_at'=>$map[$code]??null]; }
json_response(['ok'=>true,'list'=>$list]);
