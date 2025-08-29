PATCH v3.2 — Contacts as Notepad (admin-editable text)

1) SQL (create table if needed):
   - Import db/pages.sql into your database.

2) Back-end API (copy files):
   - Copy public/api/page_get.php
   - Copy public/api/admin/page_set.php

3) Front-end JS (edit public/assets/app.js):
   - Replace the function openContacts with:
       function openContacts(){ show('win-contacts'); loadContactsPage(); }
   - Remove old functions: loadContacts(), addContact(), delContact().
   - Add new functions anywhere before the "return {...}" in App IIFE:
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
   - Ensure these four functions are exported by the returned object if you export functions explicitly.

4) HTML (edit public/index.php): replace Contacts window markup with:
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

5) Clear browser cache (hard refresh) so the new JS stops calling /api/contacts_list.php.

6) Reminder: fix MySQL reserved word bug in inc/lib.php (if you haven't yet).
   Replace ach_db_defs() with:
   function ach_db_defs(): array {
     $pdo = db();
     try {
       $rows = $pdo->query("SELECT code,name,description,icon,type,field,gte FROM ach_defs")->fetchAll();
       foreach ($rows as &$r) { if (isset($r['description'])) { $r['desc'] = $r['description']; unset($r['description']); } }
       return $rows ?: [];
     } catch (Throwable $e) { return []; }
   }
