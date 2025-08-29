<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
$uid=require_login(); require_csrf(); $pdo=db();
$s=$pdo->prepare("SELECT id,name,email,phone,note,DATE_FORMAT(created_at,'%Y-%m-%d %H:%i') AS created_at FROM contacts WHERE user_id=? ORDER BY id DESC");
$s->execute([$uid]); $rows=$s->fetchAll(); json_response(['ok'=>true,'contacts'=>$rows]);
