<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $username=trim($_POST['username']??''); $password=$_POST['password']??'';
  if ($username===''||$password==='') { $error='Введите логин и пароль'; }
  else {
    $pdo=db();
    $s=$pdo->prepare("SELECT id, password_hash FROM users WHERE username=?");
    $s->execute([$username]);
    $row=$s->fetch();
    if ($row && $password===$row['password_hash']) {
      $_SESSION['uid']=(int)$row['id']; csrf_token(); header('Location: /'); exit;
    } else { $error='Неверный логин или пароль'; }
  }
}
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><title>Вход — Win95 Clicker</title>
<link rel="stylesheet" href="/assets/win95.css">
</head><body class="desktop-body">
<div class="window centered" style="width: 420px;">
  <div class="titlebar">
    <span>Вход</span>
    <div class="controls"><span class="btn disabled">_</span><span class="btn disabled">□</span><span class="btn"><a href="/register.php">✕</a></span></div>
  </div>
  <div class="window-content">
    <?php if ($error): ?><div class="alert error"><?=h($error)?></div><?php endif; ?>
    <form method="post">
      <label>Логин<br><input class="input" type="text" name="username" required></label><br><br>
      <label>Пароль<br><input class="input" type="password" name="password" required></label><br><br>
      <button class="btn-95" type="submit">Войти</button>
      <a class="link" href="/register.php">Создать аккаунт</a>
    </form>
  </div>
</div>
</body></html>
