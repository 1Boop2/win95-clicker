/* app.js */
const App=(()=>{
  const $=s=>document.querySelector(s), $$=s=>Array.from(document.querySelectorAll(s));
  const csrf=document.querySelector('meta[name="csrf-token"]').content;
  let state={balance:0,total_clicks:0,best_cps:0,manual_mult:1,auto_cps:0,cpc:1,ach_total:0,ach_unlocked:0};
  const ui={balance:$("#balance"),total:$("#total"),best_cps:$("#best_cps"),cps:$("#cps"),cpc:$("#cpc"),auto_cps:$("#auto_cps"),upgrades:$("#upgrades"),ach_small:$("#ach_small")};
  function setText(el,v){ if(el) el.textContent=v; }
  async function call(url,data){ const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:data?JSON.stringify(data):"{}",credentials:'same-origin'}); const j=await r.json(); if(!j.ok) throw new Error(j.error||"API error"); return j; }

  async function refresh(){
    try{
      const j=await call('/api/state.php'); state=j.state; state.cpc=Math.max(1,Math.floor(1*state.manual_mult));
      setText(ui.balance,state.balance); setText(ui.total,state.total_clicks); setText(ui.best_cps,state.best_cps.toFixed(2));
      setText(ui.cpc,state.cpc); setText(ui.auto_cps,state.auto_cps.toFixed(2)); setText(ui.ach_small,`${state.ach_unlocked}/${state.ach_total}`);
      renderUpgrades(j.state.upgrades);
    }catch(e){ console.error(e); }
  }
  function renderUpgrades(list){
    const frag=document.createDocumentFragment();
    list.forEach(u=>{
      const box=document.createElement('div'); const cost=u.cost;
      box.className='upgrade'; box.innerHTML=`<div class="up-head"><div><b>${u.name}</b> <span class="lvl">Lv.${u.level}</span></div><div class="type">${u.type==='manual'?'Клик':'Авто'}</div></div>
        <div class="up-desc">${u.desc}</div>
        <div class="up-foot"><div>Цена: <b>${cost}</b></div><button class="btn-95 buy" data-code="${u.code}" ${state.balance<cost?'disabled':''}>Купить</button></div>`;
      frag.appendChild(box);
    });
    ui.upgrades.innerHTML=''; ui.upgrades.appendChild(frag);
    ui.upgrades.querySelectorAll('.buy').forEach(btn=>btn.addEventListener('click', async e=>{ const code=e.currentTarget.dataset.code; try{ await call('/api/upgrade.php',{code}); await refresh(); }catch(err){ alert(err.message); } }));
  }
  async function click(){
    try{ const j=await call('/api/click.php',{count:1}); state.balance=j.balance; state.total_clicks=j.total_clicks;
      setText(ui.balance,state.balance); setText(ui.total,state.total_clicks); setText(ui.cps,j.cps.toFixed(2));
      if(Math.random()<0.25) refresh();
    } catch(e){ console.error(e); }
  }

  // Windows management
  function show(id){ const w=document.getElementById(id); if(!w) return; w.classList.remove('hidden'); focusWindow(w); }
  function close(id){ const w=document.getElementById(id); if(!w) return; w.classList.add('hidden'); }
  function minimize(id){ const w=document.getElementById(id); if(!w) return; w.classList.add('minimized'); }
  function maximize(id){ const w=document.getElementById(id); if(!w) return; w.classList.toggle('maximized'); focusWindow(w); }
  function focusWindow(w){ $$('.window').forEach(x=>x.style.zIndex=(x===w? 10:1)); }
  let drag=null;
  function dragStart(e,id){ const w=document.getElementById(id); focusWindow(w); const r=w.getBoundingClientRect(); drag={id,dx:e.clientX-r.left,dy:e.clientY-r.top}; document.addEventListener('mousemove',onMove); document.addEventListener('mouseup',onUp); }
  function onMove(e){ if(!drag) return; const w=document.getElementById(drag.id); w.style.left=(e.clientX-drag.dx)+'px'; w.style.top=(e.clientY-drag.dy)+'px'; }
  function onUp(){ document.removeEventListener('mousemove',onMove); document.removeEventListener('mouseup',onUp); drag=null; }

  // Leaderboard & Achievements & Contacts
  async function loadLeaderboard(which){
    try{ const j=await call('/api/leaderboard.php',{which}); const wrap=$("#leaderboard");
      let title="Таблица лидеров"; if(which==='cps') title="Топ по лучшему CPS"; if(which==='total') title="Топ по общему количеству кликов"; if(which==='balance') title="Топ по балансу";
      let html=`<div class="panel-title">${title}</div><table class="table-95"><tr><th>#</th><th>Игрок</th><th>Значение</th></tr>`;
      j.rows.forEach((r,i)=>{ html+=`<tr><td>${i+1}</td><td>${r.username}</td><td>${r.val}</td></tr>`; }); html+='</table>'; wrap.innerHTML=html;
    }catch(e){ alert(e.message); }
  }
  function openGame(){ show('win-game'); refresh(); }
  function openLeader(){ show('win-leader'); loadLeaderboard('cps'); }
  function openAbout(){ show('win-about'); }
  function openContacts(){ show('win-contacts'); loadContactsPage(); }
  async function loadContactsPage(){
    try{
      const j = await call('/api/page_get.php',{key:'contacts'});
      const pre = document.getElementById('contacts_text');
      if (pre) pre.textContent = j.content || '';
    }catch(e){ alert(e.message); }
  }
  async function adminLoadContactsPage(){
    try{
      const j = await call('/api/page_get.php',{key:'contacts'});
      const ta = document.getElementById('contacts_edit'); if (ta) ta.value = j.content || '';
    }catch(e){ alert(e.message); }
  }
  async function adminSaveContactsPage(){
    try{
      const ta = document.getElementById('contacts_edit'); const content = ta ? ta.value : '';
      await call('/api/admin/page_set.php',{key:'contacts', content});
      await loadContactsPage();
      alert('Сохранено');
    }catch(e){ alert(e.message); }
  }
  function openAch(){ show('win-ach'); loadAchievements(); }
  async function loadAchievements(){
    try{ const j=await call('/api/achievements.php'); const grid=$("#ach_grid"); const items=j.list; const unlocked=items.filter(x=>x.unlocked).length; const total=items.length;
      const sum=$("#ach_summary"); if(sum) sum.textContent=`(${unlocked}/${total})`;
      let html=''; items.forEach(a=>{ html+=`<div class="ach ${a.unlocked?'':'locked'}"><div class="ach-ico">${a.icon}</div><div class="ach-body"><div class="ach-title"><b>${a.name}</b></div><div class="ach-desc">${a.desc}</div><div class="ach-time">${a.unlocked?('Открыто: '+(a.unlocked_at||'')):'Ещё скрыто'}</div></div></div>`; });
      grid.innerHTML=html;
    }catch(e){ alert(e.message); }
  }

  // Admin
  function openAdmin(){ show('win-admin'); adminLoadUsers(); }
  async function adminLoadUsers(){
    try{ const j=await call('/api/admin/users_list.php'); const body=$("#admin_body");
      let html=`<div class="scroll-pane-95"><div class="panel-title">Пользователи</div>
        <table class="table-95"><tr><th>ID</th><th>Логин</th><th>Пароль</th><th>is_admin</th><th>Клики всего</th><th>Баланс</th><th>Лучший CPS</th><th>Действие</th></tr>`;
      j.users.forEach(u=>{
        html+=`<tr><td>${u.id}</td><td>${u.username}</td><td>${u.password}</td><td>${u.is_admin}</td>
          <td>${u.total_clicks}</td><td><input type="number" value="${u.balance}" id="bal_${u.id}" style="width:110px"></td><td>${u.best_cps}</td>
          <td><button class="btn-95" onclick="App.adminSetBalance(${u.id})">Сохранить баланс</button>
              <button class="btn-95" onclick="App.adminUserAch(${u.id})">Ачивки</button></td></tr>`;
      });
      html+='</table></div>'; body.innerHTML=html;
    }catch(e){ alert(e.message); }
  }
  async function adminSetBalance(uid){
    try{ const v=Number($("#bal_"+uid).value||0); await call('/api/admin/set_balance.php',{user_id:uid,balance:v}); alert('OK'); }catch(e){ alert(e.message); }
  }
  async function adminUserAch(uid){
    try{ const j=await call('/api/admin/user_ach_list.php',{user_id:uid}); const defs=await call('/api/admin/ach_defs_list.php');
      const body=$("#admin_body");
      let html=`<div class="panel-title">Ачивки пользователя #${uid}</div>`;
      html+=`<div style="margin:6px 0;">Выдать: <select id="grant_code">`;
      defs.defs.forEach(d=>{ html+=`<option value="${d.code}">${d.code} — ${d.name}</option>`; });
      html+=`</select> <button class="btn-95" onclick="App.adminGrantAch(${uid})">Выдать</button></div>`;
      html+=`<table class="table-95"><tr><th>Код</th><th>Название</th><th>Тип</th><th>Открыто</th><th></th></tr>`;
      j.list.forEach(a=>{ html+=`<tr><td>${a.code}</td><td>${a.name}</td><td>${a.type}</td><td>${a.unlocked_at||'-'}</td><td><button class="btn-95" onclick="App.adminRevokeAch(${uid},'${a.code}')">Забрать</button></td></tr>`; });
      html+='</table>'; body.innerHTML=html;
    }catch(e){ alert(e.message); }
  }
  async function adminGrantAch(uid){
    try{ const code=$("#grant_code").value; await call('/api/admin/ach_grant.php',{user_id:uid,code}); alert('Выдано'); await adminUserAch(uid); }catch(e){ alert(e.message); }
  }
  async function adminRevokeAch(uid,code){
    try{ await call('/api/admin/ach_revoke.php',{user_id:uid,code}); alert('Снято'); await adminUserAch(uid); }catch(e){ alert(e.message); }
  }
  async function adminLoadAchDefs(){
    try{ const j=await call('/api/admin/ach_defs_list.php'); const body=$("#admin_body");
      let html=`<div class="panel-title">Список ачивок (включая встроенные и кастомные)</div>
      <table class="table-95"><tr><th>Код</th><th>Название</th><th>Тип</th><th>Поле</th><th>Порог</th></tr>`;
      j.defs.forEach(d=>{ html+=`<tr><td>${d.code}</td><td>${d.name}</td><td>${d.type}</td><td>${d.field||''}</td><td>${d.gte??''}</td></tr>`; });
      html+='</table>'; body.innerHTML=html;
    }catch(e){ alert(e.message); }
  }
  function adminOpenCreateAch(){
    const body=$("#admin_body");
    body.innerHTML = `<div class="panel-title">Создать ачивку</div>
      <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr 1fr 1fr;">
        <input id="new_code" class="input" placeholder="code (uniq)">
        <input id="new_name" class="input" placeholder="name">
        <input id="new_icon" class="input" placeholder="icon (emoji)">
        <select id="new_type" class="input">
          <option value="admin">admin (выдаётся вручную)</option>
          <option value="stat">stat (по условию)</option>
        </select>
        <button class="btn-95" onclick="App.adminCreateAch()">Создать</button>
      </div>
      <div class="form-row" style="grid-template-columns: 1fr 1fr 2fr;">
        <input id="new_field" class="input" placeholder="field (для stat): total_clicks|balance|best_cps|auto_cps|levels_sum">
        <input id="new_gte" class="input" placeholder="gte (для stat)">
        <input id="new_desc" class="input" placeholder="description">
      </div>`;
  }
  async function adminCreateAch(){
    try{
      const code=$("#new_code").value.trim(), name=$("#new_name").value.trim(), icon=$("#new_icon").value.trim()||'🏅', type=$("#new_type").value;
      const field=$("#new_field").value.trim()||null; const gte=$("#new_gte").value.trim(); const desc=$("#new_desc").value.trim();
      await call('/api/admin/ach_create.php',{code,name,icon,type,field,desc,gte: gte===''?null:Number(gte)});
      alert('Ачивка создана'); adminLoadAchDefs();
    }catch(e){ alert(e.message); }
  }
  async function adminResetAll(){
    const data = {
      balances: document.getElementById('r_bal')?.checked ?? true,
      upgrades: document.getElementById('r_upg')?.checked ?? true,
      achievements: document.getElementById('r_ach')?.checked ?? false,
    };
    if(!confirm('Точно сбросить выбранные данные? Действие необратимо.')) return;
    try{
      const j = await call('/api/admin/reset_all.php', data);
      if(!j.ok){ alert(j.error||'Ошибка'); return; }
      alert('Сброшено');
      // можно сразу обновить экран, если открыт Clicker.exe
      if (typeof refresh === 'function') refresh();
    }catch(e){ alert(e.message); }
  }


  setInterval(()=>{ if (!document.getElementById('win-game').classList.contains('hidden')) refresh(); }, 4000);

  return {openGame,openLeader,openAbout,openContacts,openAch,click,dragStart,minimize,maximize,close,loadLeaderboard,adminLoadContactsPage, adminResetAll, adminSaveContactsPage, openAdmin, adminLoadUsers, adminSetBalance, adminUserAch, adminGrantAch, adminRevokeAch, adminLoadAchDefs, adminOpenCreateAch, adminCreateAch};
})();