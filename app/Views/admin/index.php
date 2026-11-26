<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Customer Portal</title><style>:root{--bg:#0A1120;--surface:#121B2E;--border:#223252;--text:#E7EEF7;--muted:#8A99B5}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:14px system-ui}.wrap{max-width:960px;margin:auto;padding:24px}.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:22px;margin:14px 0}.row{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}input{display:block;width:100%;max-width:420px;padding:11px;margin:8px 0;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text)}button{padding:10px 14px;margin:4px;border:0;border-radius:8px;background:#16C4B0;color:#07111f;font-weight:700;cursor:pointer}.muted{color:var(--muted)}pre{white-space:pre-wrap;overflow-wrap:anywhere}.logo-preview{width:120px;height:120px;object-fit:contain;background:#0A1120;border:1px solid var(--border);border-radius:12px;padding:10px}.logo-empty[hidden],.logo-preview[hidden]{display:none!important}.logo-empty{width:120px;height:120px;display:grid;place-items:center;background:#0A1120;border:1px dashed var(--border);border-radius:12px;color:var(--muted)}</style></head><body><main class="wrap"><h1>Customer Portal — Admin</h1><section class="card" id="login"><h2>Login Admin</h2><form id="loginForm"><input id="username" placeholder="Username" required><input id="password" type="password" placeholder="Password" required><button>Masuk</button></form><p id="loginMsg"></p></section><div id="panel" hidden><div class="row"><section class="card"><h2>Pengaturan</h2><input id="appName" placeholder="Nama aplikasi"><input id="appUrl" placeholder="URL aplikasi"><input id="genieacsUrl" placeholder="URL GenieACS"><button id="save">Simpan</button><p id="msg"></p></section><section class="card"><h2>Ubah akun admin</h2><input id="newUsername" placeholder="Username baru"><input id="currentPassword" type="password" placeholder="Password saat ini"><input id="newPassword" type="password" placeholder="Password baru"><button id="credentials">Simpan akun</button><p id="credentialMsg"></p></section></div><section class="card"><h2>Logo Aplikasi</h2>
<p class="muted">Kelola logo yang ditampilkan pada aplikasi Pesona Wifi.</p>
<div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin:14px 0">
<img id="logoPreview" class="logo-preview" alt="Logo aplikasi" hidden>
<div id="logoEmpty" class="logo-empty">Belum ada logo</div>
<div>
<input id="logoFile" type="file" accept="image/png,image/jpeg,image/gif,image/webp">
<p class="muted">PNG, JPG, GIF, atau WebP. Maksimal 2 MB.</p>
<button id="uploadLogo" type="button">Unggah Logo</button>
<button id="deleteLogo" type="button" style="background:#ef4444;color:white" disabled>Hapus Logo</button>
</div></div>
<p id="logoMsg" class="muted"></p></section>
<section class="card"><h2>WhatsApp gateway</h2><button id="wa">Status gateway</button><pre id="waResult"></pre></section><section class="card"><h2>Pelanggan</h2><button id="customers">Tampilkan</button><pre id="customerResult"></pre></section><section class="card"><h2>Audit log</h2><button id="logs">Tampilkan</button><pre id="logsResult"></pre></section><button id="logout">Keluar</button></div></main><script>const $=x=>document.getElementById(x);
let csrfHash=null;
async function getCsrfHash(){
    if(!csrfHash){
        let r=await fetch('/api/config');
        let d=await r.json();
        if(!r.ok || !d.csrfHash) throw Error('Gagal mengambil token keamanan.');
        csrfHash=d.csrfHash;
    }
    return csrfHash;
}
async function api(u,o={}){
    let method=(o.method||'GET').toUpperCase();
    let isForm=o.body instanceof FormData;
    let headers={
        ...(isForm?{}:{'Content-Type':'application/json'}),
        ...(o.headers||{})
    };
    if(['POST','PUT','PATCH','DELETE'].includes(method)){
        headers['X-CSRF-TOKEN']=await getCsrfHash();
    }
    let r=await fetch(u,{...o,headers});
    let d={};
    try{d=await r.json()}catch(e){}
    if(!r.ok || d.success===false)throw Error(d.message||'Gagal');
    return d;
}async function init(){await api('/api/admin/status');$('login').hidden=true;$('panel').hidden=false;let d=await api('/api/admin/settings');$('appName').value=d.appName||'';$('appUrl').value=d.appUrl||'';$('genieacsUrl').value=d.genieacsUrl||'';setLogo(d.logoUrl||'')}init().catch(()=>{});

function setLogo(url){
    const img=$('logoPreview');
    const empty=$('logoEmpty');
    if(url){
        img.src=url+(url.includes('?')?'&':'?')+'v='+Date.now();
        img.hidden=false;
        empty.hidden=true;
        $('deleteLogo').disabled=false;
    }else{
        img.removeAttribute('src');
        img.hidden=true;
        empty.hidden=false;
        $('deleteLogo').disabled=true;
    }
}

$('uploadLogo').onclick=async()=>{
    const file=$('logoFile').files[0];
    if(!file){
        $('logoMsg').textContent='Pilih file logo terlebih dahulu.';
        return;
    }
    if(file.size>2*1024*1024){
        $('logoMsg').textContent='Ukuran logo maksimal 2 MB.';
        return;
    }

    const form=new FormData();
    form.append('logo',file);
    $('uploadLogo').disabled=true;
    $('logoMsg').textContent='Mengunggah logo...';

    try{
        const d=await api('/api/admin/logo',{
            method:'POST',
            body:form
        });
        setLogo(d.logoUrl);
        $('logoFile').value='';
        $('logoMsg').textContent='Logo berhasil diperbarui.';
    }catch(e){
        $('logoMsg').textContent=e.message;
    }finally{
        $('uploadLogo').disabled=false;
    }
};

$('deleteLogo').onclick=async()=>{
    if(!confirm('Yakin ingin menghapus logo aplikasi?'))return;
    $('deleteLogo').disabled=true;
    $('logoMsg').textContent='Menghapus logo...';

    try{
        await api('/api/admin/logo',{method:'DELETE'});
        setLogo('');
        $('logoMsg').textContent='Logo berhasil dihapus.';
    }catch(e){
        $('logoMsg').textContent=e.message;
        $('deleteLogo').disabled=false;
    }
};

$('loginForm').onsubmit=async e=>{e.preventDefault();try{await api('/api/admin/auth/login',{method:'POST',body:JSON.stringify({username:$('username').value,password:$('password').value})});await init()}catch(e){$('loginMsg').textContent=e.message}};$('save').onclick=async()=>{try{await api('/api/admin/settings',{method:'PUT',body:JSON.stringify({appName:$('appName').value,appUrl:$('appUrl').value,genieacsUrl:$('genieacsUrl').value})});$('msg').textContent='Tersimpan'}catch(e){$('msg').textContent=e.message}};$('credentials').onclick=async()=>{try{await api('/api/admin/credentials',{method:'PUT',body:JSON.stringify({username:$('newUsername').value,currentPassword:$('currentPassword').value,newPassword:$('newPassword').value})});$('credentialMsg').textContent='Akun diperbarui'}catch(e){$('credentialMsg').textContent=e.message}};$('wa').onclick=async()=>{$('waResult').textContent=JSON.stringify(await api('/api/admin/whatsapp/status'),null,2)};$('customers').onclick=async()=>{$('customerResult').textContent=JSON.stringify(await api('/api/admin/customers'),null,2)};$('logs').onclick=async()=>{$('logsResult').textContent=JSON.stringify(await api('/api/admin/logs'),null,2)};$('logout').onclick=async()=>{await api('/api/admin/auth/logout',{method:'POST'});location.reload()};</script></body></html>