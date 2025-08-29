<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../inc/lib.php';
require_admin(); require_csrf();
$defs=ach_definitions();
json_response(['ok'=>true,'defs'=>$defs]);
