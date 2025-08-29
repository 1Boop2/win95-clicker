<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
$uid=require_login(); require_csrf(); $in=json_decode(file_get_contents('php://input'),true)??[];
$name=trim($in['name']??''); $email=trim($in['email']??''); $phone=trim($in['phone']??''); $note=trim($in['note']??'');
if($name==='') json_response(['ok'=>false,'error'=>'Имя обязательно']);
$pdo=db(); $pdo->prepare("INSERT INTO contacts (user_id,name,email,phone,note) VALUES (?,?,?,?,?)")->execute([$uid,$name,$email,$phone,$note]);
json_response(['ok'=>true]);
