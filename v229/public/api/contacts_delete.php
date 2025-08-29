<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
$uid=require_login(); require_csrf(); $in=json_decode(file_get_contents('php://input'),true)??[];
$id=(int)($in['id']??0); if($id<=0) json_response(['ok'=>false,'error':'Нет id']);
$pdo=db(); $pdo->prepare("DELETE FROM contacts WHERE id=? AND user_id=?")->execute([$id,$uid]);
json_response(['ok'=>true]);
