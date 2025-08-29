<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_admin(); require_csrf();
$in=json_decode(file_get_contents('php://input'),true)??[];
$code=trim($in['code']??''); $name=trim($in['name']??''); $icon=trim($in['icon']??'🏅'); $type=$in['type']??'admin';
$field=trim((string)($in['field']??'')); if($field==='') $field=null;
$desc=trim((string)($in['desc']??'')); $gte=$in['gte']; if($gte===null||$gte==='') $gte=null;
if($code===''||$name==='') json_response(['ok'=>false,'error'=>'code/name?']);
$allowed=['admin','stat']; if(!in_array($type,$allowed,true)) $type='admin';
if($type==='stat' && !$field) json_response(['ok'=>false,'error'=>'field required for stat']);

$pdo=db();
$pdo->prepare("INSERT INTO ach_defs (code,name,description,icon,type,field,gte) VALUES (?,?,?,?,?,?,?)")
    ->execute([$code,$name,$desc,$icon,$type,$field,$gte]);
json_response(['ok'=>true]);
