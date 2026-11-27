<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Customer Portal</title>
<style>
:root{
 --bg:#0A1120;--surface:#121B2E;--surface2:#17243A;
 --border:#223252;--text:#E7EEF7;--muted:#8A99B5;
 --accent:#16C4B0;--danger:#ef4444;--success:#22c55e;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:14px system-ui,sans-serif}
button,input{font:inherit}
.wrap{max-width:1120px;margin:auto;padding:24px}
header{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:22px}
h1{font-size:23px;margin:0}
h2{font-size:17px;margin:0 0 17px}
h3{font-size:15px;margin:0 0 10px}
.muted{color:var(--muted)}
.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:22px;margin:15px 0}
.row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.field{margin:13px 0}
label{display:block;color:var(--muted);font-size:13px;margin-bottom:7px}
input[type=text],input[type=url],input[type=password],input[type=number]{
 display:block;width:100%;padding:12px;background:var(--bg);
 border:1px solid var(--border);border-radius:9px;color:var(--text);outline:none
}
input:focus{border-color:var(--accent)}
button{border:0;border-radius:9px;padding:11px 16px;background:var(--accent);
 color:#07111f;font-weight:700;cursor:pointer}
button:disabled{opacity:.5;cursor:not-allowed}
button.secondary{background:var(--surface2);border:1px solid var(--border);color:var(--text)}
button.danger{background:var(--danger);color:white}
.actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:15px}
.tabs{display:flex;gap:8px;overflow-x:auto;padding:5px 0 12px}
.tab{background:var(--surface);border:1px solid var(--border);color:var(--muted);white-space:nowrap}
.tab.active{background:var(--accent);color:#07111f;border-color:var(--accent)}
.tab-panel[hidden],[hidden]{display:none!important}
.status{display:flex;align-items:center;gap:10px;padding:13px;
 background:var(--surface2);border:1px solid var(--border);border-radius:11px;margin:12px 0}
.dot{width:10px;height:10px;border-radius:50%;background:var(--muted);flex:none}
.dot.ok{background:var(--success)}.dot.bad{background:var(--danger)}
.logo-preview,.logo-empty{width:110px;height:110px;object-fit:contain;
 background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:9px}
.logo-empty{display:grid;place-items:center;color:var(--muted);border-style:dashed}
.logo-preview[hidden],.logo-empty[hidden]{display:none!important}
.checks{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}
.check{display:flex;align-items:center;gap:8px;padding:10px;
 background:var(--surface2);border:1px solid var(--border);border-radius:9px}
.check input{accent-color:var(--accent)}
pre{white-space:pre-wrap;overflow-wrap:anywhere;color:var(--muted);font-size:12px}
#login{max-width:420px;margin:70px auto}
#login input{margin:10px 0}
.message{min-height:20px;color:var(--muted);margin:12px 0 0}
small{color:var(--muted)}
@media(max-width:650px){
 .wrap{padding:14px}.row{grid-template-columns:1fr}
 .card{padding:17px}.checks{grid-template-columns:repeat(2,minmax(0,1fr))}
 header{align-items:flex-start}.actions button{flex:1}
 h1{font-size:19px}
}
</style>
</head>
<body>
<main class="wrap">

<section class="card" id="login">
 <h2>Login Admin</h2>
 <form id="loginForm">
  <div class="field"><label>Username</label><input id="username" type="text" required autocomplete="username"></div>
  <div class="field"><label>Password</label><input id="password" type="password" required autocomplete="current-password"></div>
  <button type="submit">Masuk</button>
 </form>
 <p class="message" id="loginMsg"></p>
</section>

<div id="panel" hidden>
 <header>
  <div><h1>Customer Portal</h1><small>Administrator Panel</small></div>
  <button class="secondary" id="logout">Keluar</button>
 </header>

 <nav class="tabs" aria-label="Menu Admin">
  <button class="tab active" data-tab="whatsapp">WhatsApp</button>
  <button class="tab" data-tab="settings">Pengaturan</button>
  <button class="tab" data-tab="account">Akun</button>
 </nav>

 <section class="tab-panel" id="tab-whatsapp">
  <div class="card">
   <h2>Status WhatsApp Gateway</h2>
   <div class="status"><span class="dot" id="waDot"></span><div>
    <strong id="waState">Belum diperiksa</strong><div class="muted" id="waMessage">Tekan periksa status untuk memulai.</div>
   </div></div>
   <div class="row">
    <div><label>Device ID</label><input id="waStatusDevice" type="text" readonly></div>
    <div><label>URL Gateway</label><input id="waStatusUrl" type="text" readonly></div>
   </div>
   <div class="actions"><button id="checkWa">Periksa Status</button></div>
   <p class="message" id="waMsg"></p>
  </div>

  <div class="card">
   <h2>Informasi Koneksi</h2>
   <p class="muted">Pengaturan URL, device ID, dan kredensial GOWA berada di tab Pengaturan.</p>
   <p class="muted">Device <strong>billing</strong> digunakan bersama layanan lain. Panel ini tidak menghapus device atau melakukan logout otomatis.</p>
   <p class="muted">Fitur QR dan sambungkan ulang akan ditambahkan setelah endpoint login GOWA diverifikasi.</p>
  </div>
 </section>

 <section class="tab-panel" id="tab-settings" hidden>
  <div class="card">
   <h2>Identitas Aplikasi</h2>
   <div class="row">
    <div class="field"><label>Nama aplikasi</label><input id="appName" type="text"></div>
    <div class="field"><label>URL aplikasi</label><input id="appUrl" type="url"></div>
   </div>
   <div class="field"><label>Logo aplikasi</label>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
     <img id="logoPreview" class="logo-preview" alt="Logo" hidden>
     <div id="logoEmpty" class="logo-empty">Belum ada logo</div>
     <div>
      <input id="logoFile" type="file" accept="image/png,image/jpeg,image/gif,image/webp">
      <small>PNG, JPG, GIF, WebP. Maksimal 2 MB.</small>
      <div class="actions">
       <button type="button" id="uploadLogo">Unggah Logo</button>
       <button type="button" class="danger" id="deleteLogo">Hapus Logo</button>
      </div>
     </div>
    </div>
    <p class="message" id="logoMsg"></p>
   </div>
  </div>

  <div class="card">
   <h2>GenieACS</h2>
   <div class="field"><label>URL GenieACS</label><input id="genieacsUrl" type="url" placeholder="https://acs.example.com:7557"></div>
   <div class="row">
    <div class="field"><label>Username API</label><input id="genieacsUsername" type="text" autocomplete="off" placeholder="Kosongkan jika tidak ingin mengubah"></div>
    <div class="field"><label>Password API</label><input id="genieacsPassword" type="password" autocomplete="new-password" placeholder="Kosongkan jika tidak ingin mengubah"></div>
   </div>
   <small id="genieacsSecretState"></small>
  </div>

  <div class="card">
   <h2>WhatsApp GOWA</h2>
   <div class="row">
    <div class="field"><label>URL GOWA</label><input id="gowaBaseUrl" type="url" placeholder="https://wa.example.com"></div>
    <div class="field"><label>Device ID</label><input id="gowaDeviceId" type="text"></div>
   </div>
   <div class="row">
    <div class="field"><label>Username GOWA</label><input id="gowaUsername" type="text" autocomplete="off" placeholder="Kosongkan jika tidak ingin mengubah"></div>
    <div class="field"><label>Password GOWA</label><input id="gowaPassword" type="password" autocomplete="new-password" placeholder="Kosongkan jika tidak ingin mengubah"></div>
   </div>
   <small id="gowaSecretState"></small>
  </div>

  <div class="card">
   <h2>Keamanan dan Jaringan</h2>
   <label class="check"><input id="forceHttps" type="checkbox"> Paksa HTTPS</label>
   <p class="muted">Pengaturan HTTPS tersimpan di database. Penerapan ke konfigurasi runtime masih memerlukan tahap integrasi tersendiri.</p>
  </div>

  <div class="card">
   <h2>Indeks SSID GenieACS</h2>
   <p class="muted">Pilih indeks SSID yang akan digunakan aplikasi.</p>
   <div class="checks" id="ssidChecks">
    <label class="check"><input type="checkbox" value="1"> SSID 1</label>
    <label class="check"><input type="checkbox" value="2"> SSID 2</label>
    <label class="check"><input type="checkbox" value="3"> SSID 3</label>
    <label class="check"><input type="checkbox" value="4"> SSID 4</label>
    <label class="check"><input type="checkbox" value="5"> SSID 5</label>
    <label class="check"><input type="checkbox" value="6"> SSID 6</label>
    <label class="check"><input type="checkbox" value="7"> SSID 7</label>
    <label class="check"><input type="checkbox" value="8"> SSID 8</label>
   </div>
  </div>

  <div class="card">
   <button id="saveSettings">Simpan Semua Pengaturan</button>
   <p class="message" id="settingsMsg"></p>
  </div>
 </section>

 <section class="tab-panel" id="tab-account" hidden>
  <div class="card">
   <h2>Akun Administrator</h2>
   <div class="field"><label>Username saat ini</label><input id="currentAdminUsername" type="text" readonly></div>
   <div class="field"><label>Username baru</label><input id="newUsername" type="text" autocomplete="username"></div>
   <div class="field"><label>Password saat ini (wajib)</label><input id="currentPassword" type="password" autocomplete="current-password"></div>
   <div class="field"><label>Password baru (opsional, minimal 8 karakter)</label><input id="newPassword" type="password" autocomplete="new-password"></div>
   <button id="saveCredentials">Ubah Akun</button>
   <p class="message" id="credentialMsg"></p>
  </div>
 </section>

 <section class="card">
  <h2>Data Pelanggan</h2>
  <button class="secondary" id="customers">Muat Pelanggan</button>
  <pre id="customerResult"></pre>
 </section>

 <section class="card">
  <h2>Audit Log</h2>
  <button class="secondary" id="logs">Muat Audit Log</button>
  <pre id="logsResult"></pre>
 </section>
</div>
</main>

<script>
const $ = id => document.getElementById(id);
let csrfHash = null;
let settingsCache = {};

async function getCsrfHash(){
 if(!csrfHash){
  const r=await fetch('/api/config');
  const d=await r.json();
  if(!r.ok || !d.csrfHash) throw Error('Gagal mengambil token keamanan.');
  csrfHash=d.csrfHash;
 }
 return csrfHash;
}

async function api(url, options={}){
 const method=(options.method||'GET').toUpperCase();
 const isForm=options.body instanceof FormData;
 const headers={
  ...(isForm?{}:{'Content-Type':'application/json'}),
  ...(options.headers||{})
 };
 if(['POST','PUT','PATCH','DELETE'].includes(method)){
  headers['X-CSRF-TOKEN']=await getCsrfHash();
 }
 const r=await fetch(url,{...options,headers});
 let d={};
 try{d=await r.json()}catch(e){}
 if(!r.ok || d.success===false) throw Error(d.message||'Permintaan gagal.');
 return d;
}

function setLogo(url){
 const img=$('logoPreview'), empty=$('logoEmpty');
 if(url){
  img.src=url+(url.includes('?')?'&':'?')+'v='+Date.now();
  img.hidden=false;empty.hidden=true;$('deleteLogo').disabled=false;
 }else{
  img.removeAttribute('src');img.hidden=true;empty.hidden=false;
  $('deleteLogo').disabled=true;
 }
}

function activateTab(name){
 document.querySelectorAll('.tab').forEach(b=>b.classList.toggle('active',b.dataset.tab===name));
 document.querySelectorAll('.tab-panel').forEach(p=>p.hidden=p.id!=='tab-'+name);
}

document.querySelectorAll('.tab').forEach(b=>b.addEventListener('click',()=>activateTab(b.dataset.tab)));

async function loadSettings(){
 const d=await api('/api/admin/settings');
 settingsCache=d;
 $('appName').value=d.appName||'';
 $('appUrl').value=d.appUrl||'';
 $('genieacsUrl').value=d.genieacsUrl||'';
 $('gowaBaseUrl').value=d.gowaBaseUrl||'';
 $('gowaDeviceId').value=d.gowaDeviceId||'';
 $('forceHttps').checked=!!d.forceHttps;
 $('genieacsSecretState').textContent=
  'Username: '+(d.genieacsUsernameConfigured?'tersimpan':'belum diatur')+
  ' · Password: '+(d.genieacsPasswordConfigured?'tersimpan':'belum diatur');
 $('gowaSecretState').textContent=
  'Username: '+(d.gowaUsernameConfigured?'tersimpan':'belum diatur')+
  ' · Password: '+(d.gowaPasswordConfigured?'tersimpan':'belum diatur');
 document.querySelectorAll('#ssidChecks input').forEach(c=>{
  c.checked=(d.ssidIndexes||[]).map(Number).includes(Number(c.value));
 });
 setLogo(d.logoUrl||'');
 $('waStatusDevice').value=d.gowaDeviceId||'';
 $('waStatusUrl').value=d.gowaBaseUrl||'';
}

async function init(){
 const d=await api('/api/admin/status');
 $('login').hidden=true;
 $('panel').hidden=false;
 $('currentAdminUsername').value=d.admin?.username||'';
 $('newUsername').value=d.admin?.username||'';
 await loadSettings();
}

let waRefreshInFlight=false;
let waRefreshTimer=null;

async function refreshWa(){
 if(waRefreshInFlight || document.hidden)return;
 waRefreshInFlight=true;
 $('checkWa').disabled=true;
 $('waMessage').textContent='Memeriksa koneksi...';
 $('waDot').className='dot';
 try{
  const d=await api('/api/admin/whatsapp/status');
  $('waStatusDevice').value=d.deviceId||'';
  $('waState').textContent=d.loggedIn?'Terhubung':(d.connected?'GOWA aktif':'Tidak terhubung');
  $('waMessage').textContent=d.message||'';
  $('waDot').className='dot '+(d.loggedIn?'ok':'bad');
  $('waMsg').textContent=d.configured?'Pemeriksaan selesai.':'Konfigurasi belum lengkap.';
 }catch(e){
  $('waState').textContent='Gagal memeriksa';
  $('waMessage').textContent=e.message;
  $('waDot').className='dot bad';
  $('waMsg').textContent=e.message;
 }finally{
  waRefreshInFlight=false;
  $('checkWa').disabled=false;
 }
}

function startAutoWaRefresh(){
 refreshWa();
 if(waRefreshTimer===null){
  waRefreshTimer=setInterval(refreshWa,15000);
 }
}

$('loginForm').onsubmit=async e=>{
 e.preventDefault();
 $('loginMsg').textContent='Memeriksa akun...';
 try{
  await api('/api/admin/auth/login',{method:'POST',body:JSON.stringify({
   username:$('username').value,password:$('password').value
  })});
  await init();
  startAutoWaRefresh();
 }catch(e){$('loginMsg').textContent=e.message;}
};

$('saveSettings').onclick=async()=>{
 const indexes=[...document.querySelectorAll('#ssidChecks input:checked')].map(x=>Number(x.value));
 const payload={
  appName:$('appName').value,
  appUrl:$('appUrl').value,
  genieacsUrl:$('genieacsUrl').value,
  gowaBaseUrl:$('gowaBaseUrl').value,
  gowaDeviceId:$('gowaDeviceId').value,
  forceHttps:$('forceHttps').checked,
  ssidIndexes:indexes
 };
 const secretInputs={
  genieacsUsername:'genieacsUsername',
  genieacsPassword:'genieacsPassword',
  gowaUsername:'gowaUsername',
  gowaPassword:'gowaPassword'
 };
 Object.entries(secretInputs).forEach(([id,key])=>{
  if($(id).value.trim()) payload[key]=$(id).value;
 });
 $('saveSettings').disabled=true;
 $('settingsMsg').textContent='Menyimpan pengaturan...';
 try{
  await api('/api/admin/settings',{method:'PUT',body:JSON.stringify(payload)});
  Object.keys(secretInputs).forEach(id=>$(id).value='');
  await loadSettings();
  $('settingsMsg').textContent='Pengaturan berhasil disimpan.';
 }catch(e){$('settingsMsg').textContent=e.message;}
 finally{$('saveSettings').disabled=false;}
};

$('uploadLogo').onclick=async()=>{
 const file=$('logoFile').files[0];
 if(!file){$('logoMsg').textContent='Pilih file logo terlebih dahulu.';return;}
 if(file.size>2*1024*1024){$('logoMsg').textContent='Ukuran maksimal 2 MB.';return;}
 const form=new FormData();form.append('logo',file);
 $('uploadLogo').disabled=true;
 try{
  const d=await api('/api/admin/logo',{method:'POST',body:form});
  setLogo(d.logoUrl);$('logoFile').value='';
  $('logoMsg').textContent='Logo berhasil diperbarui.';
 }catch(e){$('logoMsg').textContent=e.message;}
 finally{$('uploadLogo').disabled=false;}
};

$('deleteLogo').onclick=async()=>{
 if(!confirm('Yakin ingin menghapus logo aplikasi?'))return;
 try{
  await api('/api/admin/logo',{method:'DELETE'});
  setLogo('');$('logoMsg').textContent='Logo berhasil dihapus.';
 }catch(e){$('logoMsg').textContent=e.message;}
};

$('saveCredentials').onclick=async()=>{
 $('saveCredentials').disabled=true;
 try{
  await api('/api/admin/credentials',{method:'PUT',body:JSON.stringify({
   username:$('newUsername').value,
   currentPassword:$('currentPassword').value,
   newPassword:$('newPassword').value
  })});
  $('currentAdminUsername').value=$('newUsername').value;
  $('credentialMsg').textContent='Akun berhasil diperbarui.';
  $('currentPassword').value='';$('newPassword').value='';
 }catch(e){$('credentialMsg').textContent=e.message;}
 finally{$('saveCredentials').disabled=false;}
};

$('checkWa').onclick=refreshWa;
$('customers').onclick=async()=>{
 try{$('customerResult').textContent=JSON.stringify(await api('/api/admin/customers'),null,2);}
 catch(e){$('customerResult').textContent=e.message;}
};
$('logs').onclick=async()=>{
 try{$('logsResult').textContent=JSON.stringify(await api('/api/admin/logs'),null,2);}
 catch(e){$('logsResult').textContent=e.message;}
};
$('logout').onclick=async()=>{
 try{await api('/api/admin/auth/logout',{method:'POST'});location.reload();}
 catch(e){alert(e.message);}
};

init().then(startAutoWaRefresh).catch(()=>{});
</script>
</body>
</html>
