<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/lib.php';
seed_upgrades();

$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $username=trim($_POST['username']??''); $password=$_POST['password']??''; $password2=$_POST['password2']??'';
  if ($username===''||$password==='') { $error='Введите логин и пароль'; }
  elseif ($password!==$password2) { $error='Пароли не совпадают'; }
  else {
    try {
      $pdo=db();
      // ВНИМАНИЕ: сохраняется БЕЗ хеширования по запросу пользователя
      $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?,?)")->execute([$username, $password]);
      $_SESSION['uid']=(int)$pdo->lastInsertId(); ensure_stats((int)$_SESSION['uid']); csrf_token();
      header('Location: /'); exit;
    } catch (Throwable $e) { $error='Логин занят или ошибка БД'; }
  }
}
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><title>Регистрация — Win95 Clicker</title>
<link rel="stylesheet" href="/assets/win95.css">
</head><body class="desktop-body">
<div class="window centered" style="width: 420px;">
  <div class="titlebar">
    <span>Регистрация</span>
    <div class="controls"><span class="btn disabled">_</span><span class="btn disabled">□</span><span class="btn"><a href="/login.php">✕</a></span></div>
  </div>
  <div class="window-content">
    <?php if ($error): ?><div class="alert error"><?=h($error)?></div><?php endif; ?>
    <form method="post">
      <label>Логин<br><input class="input" type="text" name="username" required></label><br><br>
      <label>Пароль<br><input class="input" type="password" name="password" required></label><br><br>
      <label>Повтор пароля<br><input class="input" type="password" name="password2" required></label><br><br>
      <button class="btn-95" type="submit">Создать</button>
      <a class="link" href="/login.php">У меня уже есть аккаунт</a>
    </form>
  </div>
</div>
</body></html>
