PATCH v3.4 — Admin scroll + защита от «ручного кликера» (лимит 50 кликов/сек)

Что входит
----------
inc/rate_limit.php            — хелпер для лимита кликов/сек
db/patch_rate_limit_v3_4.sql  — ALTER TABLE users (3 новых поля для RL)
public/assets/style_admin_scroll.css — стили для скролла в админке

A) Лимит кликов/сек (50)
------------------------
1) Импортируйте SQL:
   db/patch_rate_limit_v3_4.sql

2) Подключите хелпер в inc/lib.php (рядом с остальными include):
   require_once __DIR__ . '/rate_limit.php';

3) В public/api/click.php — СРАЗУ после проверки логина/CSRF и до начисления клика:
   <?php
   // ...
   $uid = require_login(); require_csrf();
   $pdo = db();

   // ограничитель 50 кликов/сек на пользователя
   $rl = rl_allow_click($pdo, $uid);
   if (!$rl['ok']) {
     $retry = (int)($rl['retry_after'] ?? 1);
     json_response(['ok'=>false,'error'=>'rate_limit','retry_after'=>$retry]);
   }
   // ... дальше ваша логика одного клика ...
   ?>

   По умолчанию лимит 50/сек и короткая «пауза» 1 сек. Можно переопределить в config.php:
     define('CLICK_RATE_LIMIT', 50);
     define('CLICK_RATE_COOLDOWN', 1);

4) (Опционально) На фронте в app.js обработайте ответ 'rate_limit':
   if (j.error === 'rate_limit') {
     // например: визуальный "бип" и игнор клика на 200–300 мс
     return;
   }

B) Скролл в админке
-------------------
1) Подключите стили (добавьте в public/index.php ссылку на CSS):
   <link rel="stylesheet" href="/assets/style_admin_scroll.css">

2) Оберните таблицу пользователей в контейнер со скроллом.
   Было (пример):
     <div class="panel"><table id="admin_users" class="admin-table">...</table></div>
   Стало:
     <div class="panel">
       <div class="scroll-pane-95">
         <table id="admin_users" class="admin-table"> ... </table>
       </div>
     </div>

   — Получите вертикальный скролл, липкую шапку и аккуратный вид «под Win95».

Примечания
----------
• RL хранит три счётчика в таблице users и работает в транзакции (SELECT ... FOR UPDATE).  
• Блокировка на 1 секунду выставляется при попытке превысить лимит в текущую секунду.  
• Это касается только ручных кликов через /api/click.php. Автоклик/начисления по таймеру не трогаются (их обсчитывайте в другом коде, не вызывая rate limit).

Если нужно, сделаю пагинацию для админки (page/per_page), но для начала скролла обычно хватает.
