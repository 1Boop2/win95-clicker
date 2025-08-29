<?php
declare(strict_types=1);
session_start();
const DB_HOST='localhost'; const DB_NAME='win95_clicker'; const DB_USER='win95_clicker'; const DB_PASS='1';
function db(): PDO {
  static $pdo=null; if($pdo===null){ $pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false
  ]); } return $pdo;
}
function csrf_token(): string { if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function require_csrf(): void { $t=$_SERVER['HTTP_X_CSRF_TOKEN']??($_POST['csrf_token']??''); if(!hash_equals($_SESSION['csrf_token']??'', $t)){ http_response_code(403); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'CSRF token invalid']); exit; } }
function current_user_id(): ?int { return $_SESSION['uid'] ?? null; }
function require_login(): int { $u=current_user_id(); if($u===null){ header('Location: /login.php'); exit; } return $u; }
function json_response(array $p): void { header('Content-Type: application/json; charset=utf-8'); echo json_encode($p, JSON_UNESCAPED_UNICODE); exit; }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
