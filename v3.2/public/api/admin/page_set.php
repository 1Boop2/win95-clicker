<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_admin(); require_csrf();
$in = json_decode(file_get_contents('php://input'), true) ?? [];
$key = trim($in['key'] ?? '');
$content = (string)($in['content'] ?? '');
if ($key==='') { json_response(['ok'=>false,'error'=>'key?']); }
$pdo = db();
$pdo->prepare("INSERT INTO pages (`key`, content) VALUES (?,?) ON DUPLICATE KEY UPDATE content=VALUES(content), updated_at=NOW()")->execute([$key, $content]);
json_response(['ok'=>true]);
