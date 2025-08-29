<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/lib.php';
$uid=current_user_id(); if(!$uid){ header('Location: /login.php'); exit; }
ensure_stats($uid); seed_upgrades();
$pdo=db(); $s=$pdo->prepare("SELECT username, is_admin FROM users WHERE id=?"); $s->execute([$uid]); $row=$s->fetch(); $username=$row['username']??'user'; $is_admin=(int)($row['is_admin']??0);
$csrf=csrf_token();
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><title>Win95 Clicker</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?=h($csrf)?>">
<link rel="stylesheet" href="/assets/win95.css">
<link rel="stylesheet" href="/assets/style_admin_scroll.css">
<script defer src="/assets/app.js"></script>
</head><body class="desktop-body">
  <div class="taskbar">
    <div class="start-btn">Пуск</div>
    <div class="task-title">Win95 Clicker</div>
    <div class="task-right"><a class="link" href="/logout.php">Выйти (<?=h($username)?>)</a></div>
  </div>
  <div class="desktop">
    <div class="icon" onclick="App.openGame()"><div class="icon-img">🖱️</div><div class="icon-label">Clicker.exe</div></div>
    <div class="icon" onclick="App.openLeader()"><div class="icon-img">📊</div><div class="icon-label">Leaders.lnk</div></div>
    <div class="icon" onclick="App.openContacts()"><div class="icon-img">📇</div><div class="icon-label">Мои контакты</div></div>
    <div class="icon" onclick="App.openAch()"><div class="icon-img">🏅</div><div class="icon-label">Achievements.lnk</div></div>
    <?php if ($is_admin): ?>
      <div class="icon" onclick="App.openAdmin()"><div class="icon-img">🛠️</div><div class="icon-label">Admin.exe</div></div>
    <?php endif; ?>
    <div class="icon" onclick="App.openAbout()"><div class="icon-img">💾</div><div class="icon-label">README.txt</div></div>
  </div>

  <!-- Game Window -->
  <div id="win-game" class="window hidden" style="width: 760px; top: 80px; left: 100px;">
    <div class="titlebar draggable" onmousedown="App.dragStart(event,'win-game')">
      <span>Clicker.exe — Окно</span>
      <div class="controls"><span class="btn" onclick="App.minimize('win-game')">_</span><span class="btn" onclick="App.maximize('win-game')">□</span><span class="btn" onclick="App.close('win-game')">✕</span></div>
    </div>
    <div class="window-content game-grid">
      <div class="panel">
        <div class="stat"><b>Баланс:</b> <span id="balance">0</span></div>
        <div class="stat"><b>Всего кликов:</b> <span id="total">0</span></div>
        <div class="stat"><b>Лучший CPS:</b> <span id="best_cps">0</span></div>
        <div class="stat"><b>CPC:</b> <span id="cpc">1</span> | <b>Авто CPS:</b> <span id="auto_cps">0</span></div>
        <div class="button-wrap"><button id="clickBtn" class="btn-95 big" onclick="App.click()">КЛИК</button></div>
        <div class="hint">Нажимай по кнопке — получай клики. Покупай улучшения справа.</div>
        <div class="stat small"><b>Текущий CPS:</b> <span id="cps">0</span> | <b>Ачивки:</b> <span id="ach_small">0/0</span></div>
      </div>
      <div class="panel">
        <div class="panel-title">Улучшения</div>
        <div id="upgrades" class="upgrades"></div>
      </div>
    </div>
  </div>

  <!-- Leaderboard Window -->
  <div id="win-leader" class="window hidden" style="width: 640px; top: 120px; left: 160px;">
    <div class="titlebar draggable" onmousedown="App.dragStart(event,'win-leader')">
      <span>Leaders.lnk — Окно</span>
      <div class="controls"><span class="btn" onclick="App.minimize('win-leader')">_</span><span class="btn" onclick="App.maximize('win-leader')">□</span><span class="btn" onclick="App.close('win-leader')">✕</span></div>
    </div>
    <div class="window-content">
      <div class="tabs">
        <button class="btn-95" onclick="App.loadLeaderboard('cps')">CPS</button>
        <button class="btn-95" onclick="App.loadLeaderboard('total')">Всего кликов</button>
        <button class="btn-95" onclick="App.loadLeaderboard('balance')">Баланс</button>
      </div>
      <div id="leaderboard"></div>
    </div>
  </div>

  <!-- Contacts Window -->
   <div id="win-contacts" class="window hidden" style="width: 700px; top: 200px; left: 220px;">
     <div class="titlebar draggable" onmousedown="App.dragStart(event,'win-contacts')">
       <span>Notepad — Contacts.txt</span>
       <div class="controls"><span class="btn" onclick="App.minimize('win-contacts')">_</span><span class="btn" onclick="App.maximize('win-contacts')">□</span><span class="btn" onclick="App.close('win-contacts')">✕</span></div>
     </div>
     <div class="window-content">
       <div class="panel-title">Contacts.txt</div>
       <pre id="contacts_text" class="notepad" style="min-height:240px; max-height:360px;"></pre>
       <?php if ($is_admin): ?>
       <div class="panel" style="margin-top:8px;">
         <div class="panel-title">Редактировать (только админ)</div>
         <textarea id="contacts_edit" class="input" style="height:160px; font-family: monospace; white-space: pre;"></textarea>
         <div style="margin-top:8px; display:flex; gap:8px;">
           <button class="btn-95" onclick="App.adminLoadContactsPage()">Загрузить в редактор</button>
           <button class="btn-95" onclick="App.adminSaveContactsPage()">Сохранить</button>
         </div>
       </div>
       <?php endif; ?>
     </div>
   </div>
   <!-- /Contacts Window -->

  <!-- Achievements Window -->
  <div id="win-ach" class="window hidden" style="width: 700px; top: 240px; left: 260px;">
    <div class="titlebar draggable" onmousedown="App.dragStart(event,'win-ach')">
      <span>Achievements.lnk — Окно</span>
      <div class="controls"><span class="btn" onclick="App.minimize('win-ach')">_</span><span class="btn" onclick="App.maximize('win-ach')">□</span><span class="btn" onclick="App.close('win-ach')">✕</span></div>
    </div>
    <div class="window-content">
      <div class="panel-title">Достижения <span id="ach_summary"></span></div>
      <div id="ach_grid" class="ach-grid"></div>
    </div>
  </div>

  <!-- Admin Window -->
  <?php if ($is_admin): ?>
  <div id="win-admin" class="window hidden" style="width: 900px; top: 100px; left: 80px;">
    <div class="titlebar draggable" onmousedown="App.dragStart(event,'win-admin')">
      <span>Admin.exe — Окно</span>
      <div class="controls"><span class="btn" onclick="App.minimize('win-admin')">_</span><span class="btn" onclick="App.maximize('win-admin')">□</span><span class="btn" onclick="App.close('win-admin')">✕</span></div>
    </div>
    <div class="window-content">
      <div class="tabs">
        <button class="btn-95" onclick="App.adminLoadUsers()">Пользователи</button>
        <button class="btn-95" onclick="App.adminLoadAchDefs()">Ачивки</button>
        <button class="btn-95" onclick="App.adminOpenCreateAch()">Создать ачивку</button>
      </div>
      <div id="admin_body"></div>
      <div class="panel" style="margin-top:12px;">
        <div class="panel-title">Сброс прогресса</div>
        <label><input type="checkbox" id="r_bal" checked> Баланс и счётчики</label><br>
        <label><input type="checkbox" id="r_upg" checked> Улучшения</label><br>
        <label><input type="checkbox" id="r_ach"> Ачивки</label><br>
        <button class="btn-95" onclick="App.adminResetAll()">Сбросить выбранное</button>
      </div>

    </div>
  </div>
  <?php endif; ?>

  <!-- About -->
  <div id="win-about" class="window hidden" style="width: 520px; top: 160px; left: 200px;">
    <div class="titlebar draggable" onmousedown="App.dragStart(event,'win-about')">
      <span>README.txt — Блокнот</span>
      <div class="controls"><span class="btn" onclick="App.minimize('win-about')">_</span><span class="btn" onclick="App.maximize('win-about')">□</span><span class="btn" onclick="App.close('win-about')">✕</span></div>
    </div>
    <div class="window-content">
<pre class="notepad">Win95 Clicker — v228
  • Я заебался, всем пока
  • Обнов не будет
  • Фиксов тоже
</pre>
    </div>
  </div>
</body></html>
