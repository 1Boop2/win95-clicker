<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../inc/lib.php';  // ensure_stats()
require_once __DIR__ . '/../../inc/econ.php';

$uid = require_login();
require_csrf();
$pdo = db();

ensure_stats($uid);

$upgrades = econ_upgrades_sorted($pdo, $uid);
$levels   = econ_levels($pdo, $uid);
$effects  = econ_effects_from_levels($levels);

$stmt = $pdo->prepare("SELECT balance FROM `stats` WHERE user_id=?");
$stmt->execute([$uid]);
$bal = (float)$stmt->fetchColumn();

json_response([
  'ok'=>true,
  'balance'=>$bal,
  'effects'=>$effects,
  'upgrades'=>$upgrades
]);
