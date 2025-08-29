PATCH v3.3 — Баланс улучшений, сортировка по цене ↑ и кнопка «Сбросить всё» в админке

Что внутри
----------
inc/econ.php                           — новая сбалансированная экономика (формулы, софт-кап)
public/api/upgrades_list.php           — отдаёт апгрейды отсортированные по цене (asc) + эффекты
public/api/buy_upgrade.php             — покупка апгрейда по новой экономике
public/api/admin/reset_all.php         — сброс прогресса (баланс/статы/апгрейды/ачивки)
db/patch_balance_v3_3.sql              — таблицы user_upgrades (+ pages, на случай отсутствия)

1) SQL
------
Импортируйте файл: db/patch_balance_v3_3.sql

2) Подключить экономику в PHP
-----------------------------
Откройте inc/lib.php и добавьте наверху рядом с другими include:
    require_once __DIR__ . '/econ.php';

Затем в функциях, где считается сила клика/авто‑клик (обычно get_state() / calc_stats()),
после того как у вас есть $uid и $pdo, вставьте:

    $levels = econ_levels($pdo, $uid);
    $eff    = econ_effects_from_levels($levels);
    // Подменяем вычисленные значения
    $state['click_value'] = $eff['click_value'];
    $state['auto_cps']    = $eff['auto_cps'];

(Если у вас другие имена полей в $state — подставьте свои.)

3) API на фронт
----------------
- Список апгрейдов теперь берите из /api/upgrades_list.php
  Возвращает JSON: { ok, balance, effects, upgrades: [{code,title,cat,lvl,max,next_cost,desc}, ...] }
  Незаполненные (MAX) идут в конце. Сортировка — по next_cost возрастанию.

- Покупка: POST /api/buy_upgrade.php { code }
  В ответе вернётся новый баланс, обновлённые upgrades и effects.

4) UI: сортировка и отображение
-------------------------------
В public/assets/app.js:
- замените вашу функцию загрузки апгрейдов на вызов /api/upgrades_list.php,
  и отрисовывайте список в том порядке, в котором пришёл (он уже отсортирован по цене).
- Покупку делайте через POST /api/buy_upgrade.php и после успеха перерисовывайте список.

Пример функций (если нужно):
    async function loadUpgrades(){
      const j = await call('/api/upgrades_list.php',{});
      const list = j.upgrades || [];
      // list уже отсортирован по возрастанию цены
      // ... тут рендер в DOM ...
    }
    async function buyUpgrade(code){
      const j = await call('/api/buy_upgrade.php',{code});
      if(!j.ok){ alert(j.error||'Ошибка'); return; }
      // обновить баланс/эффекты/список
      await loadUpgrades();
    }

5) Admin.exe — кнопка «Сбросить всё»
------------------------------------
В public/index.php внутри окна Admin.exe добавьте панель:

  <div class="panel" style="margin-top:8px;">
    <div class="panel-title">Сброс прогресса</div>
    <label><input type="checkbox" id="r_bal" checked> Баланс и счётчики</label><br>
    <label><input type="checkbox" id="r_upg" checked> Улучшения</label><br>
    <label><input type="checkbox" id="r_ach"> Ачивки</label><br>
    <button class="btn-95" onclick="App.adminResetAll()">Сбросить</button>
  </div>

И в public/assets/app.js добавьте:

  async function adminResetAll(){
    const data = {
      balances: document.getElementById('r_bal')?.checked ?? true,
      upgrades: document.getElementById('r_upg')?.checked ?? true,
      achievements: document.getElementById('r_ach')?.checked ?? false,
    };
    if(!confirm('Точно сбросить выбранные данные? Действие необратимо.')) return;
    const j = await call('/api/admin/reset_all.php', data);
    if(!j.ok){ alert(j.error||'Ошибка'); return; }
    alert('Сброшено');
    if (typeof refresh==='function') refresh();
  }

6) Пояснение по балансу
-----------------------
• Сила клика: базовый клик 1. Апгрейд «Сила клика» даёт +1 за уровень до 50 ур., далее — +0.5 за уровень (софт‑кап).  
• Множитель клика: «Оптимизатор DOS» даёт +5% мультипликативно, максимум 20 ур. (итого ~×2.65).  
• Автокликер: +0.2 к/с за шт., после 50 шт. — +0.1 к/с.  
• Офисный ПК: +1.1 к/с за шт., после 25 шт. — +0.55 к/с.  
• Серверная стойка: +6 к/с за шт., после 15 шт. — +3 к/с.

Цены растут по формуле: ceil(base * scale^level).  
Базовые цены/скейлы задраны умеренно (1.18–1.33), чтобы прогресс чувствовался, но не ломал экономику.

7) Советы
---------
• Если у вас уже был свой список апгрейдов — отключите старые эндпоинты и UI, или синхронизируйте с новой экономикой.  
• В админке теперь есть быстрый сброс: удобно после твиков баланса.  
• При желании легко добавить ещё уровни — просто поправьте econ_defs().
