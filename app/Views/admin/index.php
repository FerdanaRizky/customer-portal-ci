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
#newWaQrImage{max-width:320px}
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
   <h2>Daftar Device WhatsApp</h2>
   <p class="muted">Daftar disinkronkan langsung dari GOWA. Pilih device yang akan digunakan Portal untuk mengirim pesan.</p>
   <div class="field">
    <label for="waDeviceSelect">Device aktif untuk Customer Portal</label>
    <select id="waDeviceSelect" style="display:block;width:100%;padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:9px;color:var(--text)">
     <option value="">Memuat daftar device...</option>
    </select>
   </div>
   <div class="actions">
    <button type="button" id="refreshWaDevices" class="secondary">Muat Ulang Daftar</button>
    <button type="button" id="saveWaDevice">Gunakan Device Ini</button>
   </div>
   <p class="message" id="waDevicesMsg"></p>
   <div id="waDevicesList" class="muted"></div>
   <div id="waPortalDevices" style="margin-top:12px"></div>
   <p class="muted">Memilih device hanya mengubah device pengiriman Portal. Device GOWA tidak dihapus, tidak logout, dan tidak dipasangkan ulang. Device <strong>billing</strong> tetap dibiarkan aktif untuk layanan lain.</p>
  </div>

  <div class="card" id="waPairingCard">
   <h2>Tambah Device WhatsApp Baru</h2>
   <p class="muted">Buat sesi WhatsApp khusus Customer Portal. Device baru akan memiliki ID dengan awalan <strong>portal-</strong> dan tidak mengubah device billing.</p>
   <div class="actions">
    <button type="button" id="createWaDevice">Buat Device &amp; Tampilkan QR</button>
   </div>
   <div class="field" id="waPairPhoneField" style="margin-top:16px;max-width:460px" hidden>
    <label for="waPairPhone">Nomor WhatsApp</label>
    <input id="waPairPhone" type="tel" inputmode="tel" autocomplete="tel" placeholder="Contoh: 6281234567890" style="display:block;width:100%;max-width:100%;box-sizing:border-box">
    <small style="display:block;margin-top:8px;line-height:1.5">Masukkan nomor WhatsApp aktif. Nomor 08 akan otomatis diubah menjadi 62.</small>
    <div class="actions"><button type="button" id="requestWaPairCode">Lanjutkan dengan nomor</button></div>
   </div>
   <div id="waPairCodePanel" hidden style="margin-top:16px;padding:16px;background:var(--surface2);border:1px solid var(--border);border-radius:12px">
    <strong id="waPairCodeDevice"></strong>
    <p class="muted">Masukkan kode berikut di WhatsApp pada ponsel yang menggunakan nomor tersebut.</p>
    <div id="waPairCodeValue" style="font-size:28px;font-weight:800;letter-spacing:4px;text-align:center;padding:18px;background:var(--bg);border-radius:10px;user-select:all"></div>
    <p class="muted">WhatsApp → Perangkat tertaut → Tautkan perangkat → Tautkan dengan nomor telepon.</p>
    <button type="button" class="secondary" id="copyWaPairCode">Salin Kode</button>
    <p style="margin:16px 0 0"><button type="button" id="backToWaQr" style="padding:0;border:0;background:none;color:var(--accent);text-decoration:underline;cursor:pointer">Kembali ke QR Code</button></p>
   </div>
   <p class="message" id="newWaDeviceMsg">Belum ada proses pairing.</p>
   <div id="newWaQrPanel" hidden style="margin-top:16px;padding:16px;background:var(--surface2);border:1px solid var(--border);border-radius:12px">
    <strong id="newWaDeviceId"></strong>
    <p class="muted">Pindai QR ini melalui WhatsApp di ponsel: Perangkat tertaut → Tautkan perangkat.</p>
    <img id="newWaQrImage" alt="QR WhatsApp untuk device baru" style="display:block;width:min(100%,320px);height:auto;margin:12px auto;background:#fff;padding:10px;border-radius:10px">
    <p class="muted" id="newWaQrCountdown"></p>
    <button type="button" class="secondary" id="refreshNewWaQr">Muat Ulang QR</button>
    <p style="margin:16px 0 0;text-align:center"><button type="button" id="showPhonePairing" style="padding:0;border:0;background:none;color:var(--accent);text-decoration:underline;cursor:pointer">Login dengan nomor telepon ›</button></p>
   </div>
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
    <div class="field"><label>Device aktif untuk Portal</label><input id="gowaDeviceId" type="text" readonly><small>Pilih device melalui tab WhatsApp.</small></div>
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
  <h2>Riwayat Pengiriman OTP WhatsApp</h2>
  <p class="muted">Menampilkan maksimal 100 percobaan OTP terbaru. Kode OTP tidak pernah disimpan di riwayat.</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:14px 0">
   <div>
    <label for="otpLogFrom">Dari tanggal</label>
    <input type="date" id="otpLogFrom">
   </div>
   <div>
    <label for="otpLogTo">Sampai tanggal</label>
    <input type="date" id="otpLogTo">
   </div>
   <div>
    <label for="otpLogPhone">Nomor WhatsApp</label>
    <input type="text" id="otpLogPhone" inputmode="numeric" placeholder="Cari nomor WhatsApp">
   </div>
   <div>
    <label for="otpLogStatus">Status pengiriman</label>
    <select id="otpLogStatus">
     <option value="">Semua status</option>
     <option value="success">Berhasil</option>
     <option value="failed">Gagal</option>
    </select>
   </div>
  </div>
  <button class="secondary" id="otpDeliveryLogs">Terapkan Filter</button>
  <button class="secondary" id="otpLogReset">Reset Filter</button>
  <pre id="otpDeliveryLogsResult"></pre>
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
 await loadWaDevices();
}


async function loadWaDevices(){
 const select=$('waDeviceSelect');
 const msg=$('waDevicesMsg');
 select.replaceChildren(new Option('Memuat daftar device...', ''));
 msg.textContent='Mengambil daftar dari GOWA...';
 try{
  const data=await api('/api/admin/whatsapp/devices');
  const devices=Array.isArray(data.devices)?data.devices:[];
  const selected=data.selectedDeviceId||'';
  // Portal hanya mengizinkan satu sesi WhatsApp yang sudah login.
  // Device billing dan device non-Portal tidak memengaruhi aturan ini.
  const hasLoggedInPortalSession=!!data.hasLoggedInPortalSession;
  $('waPairingCard').hidden=hasLoggedInPortalSession;
  if(hasLoggedInPortalSession){
   stopNewWaPairing();
   $('newWaQrPanel').hidden=true;
   $('newWaDeviceMsg').textContent='Sesi WhatsApp Customer Portal sudah aktif. Menu pairing tersedia kembali setelah sesi Portal di-logout atau dihapus dari GOWA.';
  }
  select.replaceChildren();
  if(!devices.length){
   select.add(new Option('Tidak ada device ditemukan',''));
  }
  devices.forEach(device=>{
   const state=String(device.state||'unknown');
   const label=(device.displayName||device.id)+' ('+device.id+') — '+state;
   select.add(new Option(label,device.id));
  });
  select.value=selected;
  if(select.value!==selected && selected){
   select.add(new Option(selected+' (tidak ditemukan di GOWA)',selected));
   select.value=selected;
  }
  $('waDevicesList').textContent=(data.totalGowaDevices??devices.length)+' device ditemukan di GOWA. '
   +(data.hiddenDeviceIds?.length||0)+' device dilepas dari daftar Portal.';
  const portalBox=$('waPortalDevices');
  portalBox.replaceChildren();
  const portalDevices=devices.filter(device=>String(device.id||'').startsWith('portal-'));
  if(portalDevices.length){
   const heading=document.createElement('strong');
   heading.textContent='Device milik Customer Portal';
   portalBox.appendChild(heading);
   portalDevices.forEach(device=>{
    const row=document.createElement('div');
    row.style.cssText='display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 0;border-top:1px solid var(--border)';
    const info=document.createElement('span');
    info.textContent=(device.displayName||device.id)+' — '+device.state;
    row.appendChild(info);
    if(device.id!==selected){
     const hide=document.createElement('button');
     hide.type='button';
     hide.className='secondary';
     hide.textContent='Hapus Device';
     hide.addEventListener('click',()=>hideWaDevice(device.id));
     row.appendChild(hide);
    }else{
     const active=document.createElement('span');
     active.className='muted';
     active.textContent='Sedang digunakan';
     row.appendChild(active);
    }
    portalBox.appendChild(row);
   });
  }
  const hiddenDevices=Array.isArray(data.hiddenDevices)?data.hiddenDevices:[];
  if(hiddenDevices.length){
   const hiddenHeading=document.createElement('strong');
   hiddenHeading.textContent='Device yang dilepas dari Portal';
   hiddenHeading.style.display='block';
   hiddenHeading.style.marginTop='14px';
   portalBox.appendChild(hiddenHeading);
   hiddenDevices.forEach(device=>{
    const row=document.createElement('div');
    row.style.cssText='display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 0;border-top:1px solid var(--border)';
    const info=document.createElement('span');
    info.textContent=(device.displayName||device.id)+' — '+device.state;
    row.appendChild(info);
    const restore=document.createElement('button');
    restore.type='button';
    restore.className='secondary';
    restore.textContent='Pulihkan ke Portal';
    restore.addEventListener('click',()=>restoreWaDevice(device.id));
    row.appendChild(restore);
    portalBox.appendChild(row);
   });
  }
  msg.textContent='Daftar berhasil disinkronkan.';
 }catch(e){
  select.replaceChildren(new Option('Gagal memuat daftar device',''));
  $('waDevicesList').textContent='';
  msg.textContent=e.message;
 }
}

let newWaPairDeviceId='';
let newWaPairMode='qr';
let newWaPairTimer=null;
let newWaQrTimer=null;
let newWaQrSeconds=0;

function stopNewWaPairing(){
 if(newWaPairTimer!==null){clearInterval(newWaPairTimer);newWaPairTimer=null;}
 if(newWaQrTimer!==null){clearInterval(newWaQrTimer);newWaQrTimer=null;}
}

async function requestNewWaQr(){
 if(!newWaPairDeviceId)return;
 $('newWaDeviceMsg').textContent='Meminta QR dari GOWA...';
 $('refreshNewWaQr').disabled=true;
 try{
  const d=await api('/api/admin/whatsapp/qr',{
   method:'POST',
   body:JSON.stringify({deviceId:newWaPairDeviceId})
  });
  $('newWaQrImage').src=d.qrImage;
  newWaQrSeconds=Number(d.qrDuration)||20;
  $('newWaQrCountdown').textContent='QR berlaku sekitar '+newWaQrSeconds+' detik.';
  if(newWaQrTimer!==null)clearInterval(newWaQrTimer);
  newWaQrTimer=setInterval(()=>{
   newWaQrSeconds=Math.max(0,newWaQrSeconds-1);
   $('newWaQrCountdown').textContent=newWaQrSeconds>0
    ? 'QR berlaku sekitar '+newWaQrSeconds+' detik.'
    : 'Masa QR mungkin habis. Klik Muat Ulang QR jika belum berhasil dipindai.';
   if(newWaQrSeconds===0){clearInterval(newWaQrTimer);newWaQrTimer=null;}
  },1000);
  $('newWaDeviceMsg').textContent='QR siap. Pindai menggunakan WhatsApp di ponsel.';
 }catch(e){
  $('newWaDeviceMsg').textContent=e.message;
 }finally{
  $('refreshNewWaQr').disabled=false;
 }
}

async function checkNewWaPairing(){
 if(!newWaPairDeviceId)return;
 try{
  const d=await api('/api/admin/whatsapp/devices');
  const device=(d.devices||[]).find(x=>x.id===newWaPairDeviceId);
  if(device && String(device.state).toLowerCase()==='logged_in'){
   stopNewWaPairing();
   $('newWaDeviceMsg').textContent='Berhasil! Device '+newWaPairDeviceId+' sudah login. QR pairing telah ditutup dan tidak dapat digunakan kembali selama sesi ini aktif.';
   $('newWaQrImage').removeAttribute('src');
   $('newWaQrImage').hidden=true;
   $('refreshNewWaQr').hidden=true;
   $('newWaQrCountdown').textContent='Pairing selesai. Untuk memasangkan ulang, hapus sesi device ini terlebih dahulu.';
   $('waPairCodePanel').hidden=true;
   await loadWaDevices();
   return;
  }
  if(!device){
   $('newWaDeviceMsg').textContent='Menunggu device muncul di GOWA...';
  }else{
   $('newWaDeviceMsg').textContent=newWaPairMode==='phone'
    ? 'Menunggu konfirmasi kode pairing di WhatsApp. Status device: '+device.state
    : 'Menunggu pemindaian QR. Status device: '+device.state;
  }
 }catch(e){
  $('newWaDeviceMsg').textContent='Pemeriksaan pairing: '+e.message;
 }
}

async function createNewWaDevice(){
 if(newWaPairDeviceId){
  $('newWaDeviceMsg').textContent='Sesi device sudah dibuat. Gunakan pilihan metode pada panel ini; tidak perlu membuat device baru.';
  return;
 }
 if(!confirm('Buat satu sesi WhatsApp khusus Customer Portal dan tampilkan QR? Device billing tidak akan diubah.'))return;
 $('createWaDevice').disabled=true;
 $('newWaDeviceMsg').textContent='Membuat satu slot device baru di GOWA...';
 $('newWaQrPanel').hidden=true;
 $('waPairCodePanel').hidden=true;
 $('waPairPhoneField').hidden=true;
 newWaPairMode='qr';
 stopNewWaPairing();
 try{
  const created=await api('/api/admin/whatsapp/create',{
   method:'POST',
   body:JSON.stringify({})
  });
  newWaPairDeviceId=created.deviceId;
  $('newWaDeviceId').textContent='Device ID: '+newWaPairDeviceId;
  $('newWaDeviceMsg').textContent=created.reused
   ? 'Melanjutkan sesi device Portal yang sudah ada. Tidak membuat sesi GOWA baru.'
   : 'Satu sesi device Portal berhasil dibuat. QR dan pairing nomor akan menggunakan sesi yang sama.';
  $('newWaQrImage').hidden=false;
  $('refreshNewWaQr').hidden=false;
  $('newWaQrPanel').hidden=false;
  await requestNewWaQr();
  newWaPairTimer=setInterval(checkNewWaPairing,5000);
  await loadWaDevices();
 }catch(e){
  $('newWaDeviceMsg').textContent=e.message;
  newWaPairDeviceId='';
  await loadWaDevices();
 }finally{
  $('createWaDevice').disabled=false;
 }
}

async function showPhonePairing(){
 if(!newWaPairDeviceId){
  $('newWaDeviceMsg').textContent='Buat device terlebih dahulu dengan QR. Sesi yang sama akan digunakan untuk pairing nomor.';
  return;
 }
 $('newWaQrPanel').hidden=true;
 $('waPairCodePanel').hidden=true;
 $('waPairPhoneField').hidden=false;
 $('waPairPhone').focus();
 $('newWaDeviceMsg').textContent='Masukkan nomor WhatsApp untuk menggunakan device yang sama. Tidak ada sesi baru yang dibuat.';
}

async function requestWaPairCode(){
 const phone=$('waPairPhone').value.trim();
 if(!newWaPairDeviceId){
  $('newWaDeviceMsg').textContent='Device belum dibuat. Buat device terlebih dahulu melalui QR.';
  return;
 }
 if(!phone){
  $('newWaDeviceMsg').textContent='Masukkan nomor WhatsApp terlebih dahulu.';
  $('waPairPhone').focus();
  return;
 }
 $('requestWaPairCode').disabled=true;
 $('newWaDeviceMsg').textContent='Meminta kode pairing untuk device yang sama...';
 try{
  const result=await api('/api/admin/whatsapp/pair-code',{
   method:'POST',
   body:JSON.stringify({deviceId:newWaPairDeviceId,phone})
  });
  $('waPairCodeDevice').textContent='Device ID: '+newWaPairDeviceId;
  $('waPairCodeValue').textContent=result.pairCode;
  $('waPairCodePanel').hidden=false;
  $('waPairPhoneField').hidden=true;
  $('newWaDeviceMsg').textContent=result.message||'Kode pairing berhasil dibuat untuk sesi yang sama.';
  newWaPairMode='phone';
  if(newWaPairTimer===null)newWaPairTimer=setInterval(checkNewWaPairing,5000);
 }catch(e){
  $('newWaDeviceMsg').textContent=e.message;
 }finally{
  $('requestWaPairCode').disabled=false;
 }
}

async function backToWaQr(){
 if(!newWaPairDeviceId)return;
 $('waPairCodePanel').hidden=true;
 $('waPairPhoneField').hidden=true;
 $('newWaQrPanel').hidden=false;
 newWaPairMode='qr';
 await requestNewWaQr();
}

async function restoreWaDevice(deviceId){
 if(!confirm('Pulihkan '+deviceId+' ke daftar Customer Portal? Sesi GOWA tidak akan diubah.'))return;
 try{
  const result=await api('/api/admin/whatsapp/restore',{
   method:'POST',
   body:JSON.stringify({deviceId})
  });
  $('waDevicesMsg').textContent=result.message||'Device dipulihkan ke Portal.';
  await loadWaDevices();
 }catch(e){
  $('waDevicesMsg').textContent=e.message;
 }
}

async function hideWaDevice(deviceId){
 if(!confirm('Hapus '+deviceId+' beserta sesi WhatsApp-nya dari GOWA? Tindakan ini akan memutus sesi device tersebut. Device billing tidak akan terpengaruh.'))return;
 try{
  const result=await api('/api/admin/whatsapp/hide',{
   method:'POST',
   body:JSON.stringify({deviceId})
  });
  $('waDevicesMsg').textContent=result.message||'Device dan sesi WhatsApp berhasil dihapus dari GOWA.';
  await loadWaDevices();
 }catch(e){
  $('waDevicesMsg').textContent=e.message;
 }
}

async function saveWaDevice(){
 const deviceId=$('waDeviceSelect').value;
 if(!deviceId){$('waDevicesMsg').textContent='Pilih device terlebih dahulu.';return;}
 $('saveWaDevice').disabled=true;
 $('waDevicesMsg').textContent='Menyimpan pilihan device...';
 try{
  await api('/api/admin/whatsapp/select',{
   method:'POST',
   body:JSON.stringify({deviceId})
  });
  $('gowaDeviceId').value=deviceId;
  $('waStatusDevice').value=deviceId;
  $('waDevicesMsg').textContent='Device '+deviceId+' berhasil dipilih untuk Portal.';
  await refreshWa();
 }catch(e){
  $('waDevicesMsg').textContent=e.message;
 }finally{
  $('saveWaDevice').disabled=false;
 }
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
$('refreshWaDevices').onclick=loadWaDevices;
$('saveWaDevice').onclick=saveWaDevice;
$('createWaDevice').onclick=createNewWaDevice;
$('showPhonePairing').onclick=showPhonePairing;
$('requestWaPairCode').onclick=requestWaPairCode;
$('backToWaQr').onclick=backToWaQr;
$('copyWaPairCode').onclick=()=>GenUI.copy($('waPairCodeValue').textContent);
$('refreshNewWaQr').onclick=requestNewWaQr;
$('customers').onclick=async()=>{
 try{$('customerResult').textContent=JSON.stringify(await api('/api/admin/customers'),null,2);}
 catch(e){$('customerResult').textContent=e.message;}
};
$('logs').onclick=async()=>{
 try{$('logsResult').textContent=JSON.stringify(await api('/api/admin/logs'),null,2);}
 catch(e){$('logsResult').textContent=e.message;}
};
async function loadOtpDeliveryLogs(){
 try{
  const params=new URLSearchParams();
  const from=$('otpLogFrom').value;
  const to=$('otpLogTo').value;
  const phone=$('otpLogPhone').value.trim();
  const status=$('otpLogStatus').value;
  if(from) params.set('from',from);
  if(to) params.set('to',to);
  if(phone) params.set('phone',phone);
  if(status) params.set('status',status);
  const query=params.toString();
  const data=await api('/api/admin/otp-delivery-logs'+(query?'?'+query:''));
  const labels={sent:'Berhasil dikirim',failed:'Gagal dikirim',customer_not_found:'Nomor tidak ditemukan',system_error:'Gangguan sistem',rate_limited:'Dibatasi (tunggu 60 detik)'};
  $('otpDeliveryLogsResult').textContent=data.logs.length
   ? 'Ditemukan '+data.count+' riwayat (maksimal 100 terbaru).\n\n'+data.logs.map(item=>[
      '#'+item.id+' | '+(labels[item.status]||item.status),
      'Nomor: '+item.phone,
      'Device: '+(item.device_id||'-'),
      'Waktu: '+item.created_at,
      'Keterangan: '+(item.message||'-')
     ].join('\n')).join('\n\n')
   : 'Tidak ada riwayat OTP yang sesuai dengan filter.';
 }catch(e){$('otpDeliveryLogsResult').textContent=e.message;}
}
$('otpDeliveryLogs').onclick=loadOtpDeliveryLogs;
$('otpLogReset').onclick=()=>{
 $('otpLogFrom').value='';
 $('otpLogTo').value='';
 $('otpLogPhone').value='';
 $('otpLogStatus').value='';
 loadOtpDeliveryLogs();
};
$('logout').onclick=async()=>{
 try{await api('/api/admin/auth/logout',{method:'POST'});location.reload();}
 catch(e){alert(e.message);}
};

init().then(startAutoWaRefresh).catch(()=>{});
</script>
</body>
</html>
