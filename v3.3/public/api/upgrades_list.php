<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../inc/econ.php';

$uid = require_login(); require_csrf();
$pdo = db();

$upgrades = econ_upgrades_sorted($pdo, $uid);
$levels = econ_levels($pdo, $uid);
$effects = econ_effects_from_levels($levels);

// вернем ещё баланс и краткую сводку
$bal = (float)$pdo->query("SELECT balance FROM users WHERE id={$uid}")->fetchColumn();

json_response([
  'ok'=>true,
  'balance'=>$bal,
  'effects'=>$effects,
  'upgrades'=>$upgrades
]);
