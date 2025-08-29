<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
$uid=require_login(); require_csrf();
$in=json_decode(file_get_contents('php://input'),true)??[]; $which=$in['which']??'cps'; $allowed=['cps','total','balance']; if(!in_array($which,$allowed,true)) $which='cps';
$map=['cps'=>['field'=>'best_cps','fmt'=>fn($v)=>number_format((float)$v,2,'.','')],'total'=>['field'=>'total_clicks','fmt'=>fn($v)=>(string)(int)$v],'balance'=>['field'=>'balance','fmt'=>fn($v)=>(string)(int)$v]];
$f=$map[$which]['field']; $fmt=$map[$which]['fmt']; $pdo=db();
$q=$pdo->query("SELECT u.username, s.$f AS val FROM stats s INNER JOIN users u ON u.id=s.user_id ORDER BY s.$f DESC LIMIT 20");
$rows=[]; foreach($q as $r){ $rows[]=['username'=>$r['username'],'val'=>$fmt($r['val'])]; } json_response(['ok'=>true,'rows'=>$rows]);
