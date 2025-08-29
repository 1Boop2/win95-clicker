<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_admin(); require_csrf();
$pdo=db();
$q=$pdo->query("SELECT u.id,u.username,u.password_hash AS password,u.is_admin,u.created_at, s.total_clicks,s.balance,s.best_cps FROM users u LEFT JOIN stats s ON s.user_id=u.id ORDER BY u.id ASC");
$rows=$q->fetchAll();
json_response(['ok'=>true,'users'=>$rows]);
