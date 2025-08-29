<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
$uid = require_login(); require_csrf();
$in = json_decode(file_get_contents('php://input'), true) ?? [];
$key = trim($in['key'] ?? 'contacts');
if ($key==='') { json_response(['ok'=>false,'error'=>'key?']); }
$pdo = db();
$stmt = $pdo->prepare("SELECT content FROM pages WHERE `key`=?");
$stmt->execute([$key]);
$content = $stmt->fetchColumn();
if ($content===false) {
  $content = "Имя: \nTelegram: \nEmail: \nWebsite: \n\nЗдесь вы можете указать любые контакты. (Редактируется в Admin.exe)";
}
json_response(['ok'=>true,'content'=>$content]);
