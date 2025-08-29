<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../inc/lib.php';
$uid=require_login(); require_csrf();
json_response(['ok'=>true,'state'=>get_state($uid)]);
