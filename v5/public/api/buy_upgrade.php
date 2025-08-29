<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../inc/econ.php';

$uid = require_login(); require_csrf();
$in = json_decode(file_get_contents('php://input'), true) ?? [];
$code = (string)($in['code'] ?? '');
if ($code==='') json_response(['ok'=>false,'error'=>'code?']);

$pdo = db();
$res = econ_buy($pdo, $uid, $code);
if (!$res['ok']) {
  $err = $res['error'] ?? 'ERR';
  $msg = $err==='NOFUNDS' ? 'Недостаточно кликов' : ($err==='MAX' ? 'Достигнут максимум' : 'Ошибка');
  json_response(['ok'=>false,'error'=>$msg]);
}

$upgrades = econ_upgrades_sorted($pdo, $uid);
$levels   = econ_levels($pdo, $uid);
$effects  = econ_effects_from_levels($levels);

json_response([
  'ok'          => true,
  'spent'       => $res['spent'],
  'new_balance' => $res['new_balance'],
  'upgrades'    => $upgrades,
  'effects'     => $effects
]);
