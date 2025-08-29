<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_admin(); require_csrf();
$in=json_decode(file_get_contents('php://input'),true)??[]; $uid=(int)($in['user_id']??0); $code=trim($in['code']??'');
if($uid<=0 || $code==='') json_response(['ok'=>false,'error'=>'user_id/code?']);
$pdo=db();
$pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, code) VALUES (?,?)")->execute([$uid,$code]);
json_response(['ok'=>true]);
