<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Pelanggan</title>
<style>
:root{--bg:#0A1120;--surface:#121B2E;--border:#223252;--text:#E7EEF7;--muted:#8A99B5}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:14px system-ui}
.wrap{max-width:960px;margin:auto;padding:24px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:22px;margin:14px 0}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px}
.muted{color:var(--muted)}
button,input{padding:11px;border:1px solid var(--border);border-radius:8px;background:#16223A;color:var(--text);margin:5px}
input{max-width:260px;width:100%}
.msg{color:#16C4B0}
.wifi-item{border:1px solid var(--border);border-radius:12px;padding:15px;margin:12px 0}
.wifi-item h3{margin-top:0}
.status{font-size:12px;color:var(--muted)}
@media(max-width:600px){.wrap{padding:12px}.card{padding:16px}.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body>
<main class="wrap">

<header style="display:flex;justify-content:space-between;align-items:center;gap:10px">
<h1>Pesona Wifi</h1>
<button id="logout">Keluar</button>
</header>

<section class="card" data-config-section="deviceInfo">
<h2>Informasi Perangkat</h2>
<div class="grid" id="deviceInfoGrid">
<div data-config-field="phone"><span class="muted" data-config-label="phone">WhatsApp</span><h3 id="phone">-</h3></div>
<div data-config-field="product"><span class="muted" data-config-label="product">Product Class</span><h3 id="product">-</h3></div>
<div data-config-field="serial"><span class="muted" data-config-label="serial">Serial Number</span><h3 id="serial">-</h3></div>
<div data-config-field="lastInform"><span class="muted" data-config-label="lastInform">Last Inform</span><h3 id="last">-</h3></div>
<div data-config-field="manufacturer"><span class="muted" data-config-label="manufacturer">Manufacturer</span><h3 id="manufacturer">-</h3></div>
<div data-config-field="accessType"><span class="muted" data-config-label="accessType">Jenis Koneksi</span><h3 id="accessType">-</h3></div>
<div data-config-field="connected"><span class="muted" data-config-label="connected">Perangkat Terhubung</span><h3 id="connected">-</h3></div>
<div data-config-field="rxPower"><span class="muted" data-config-label="rxPower">Optical RX Power</span><h3 id="rxPower">-</h3></div>
</div>
<p id="msg" class="msg"></p>
<button id="refresh" data-config-feature="refresh">Refresh</button>
<button id="reboot" data-config-feature="reboot">Restart Modem</button>
</section>

<section class="card" data-config-section="wifi">
<h2>Pengaturan WiFi</h2>
<div id="wifi"><p class="muted">Memuat informasi WiFi...</p></div>
</section>

</main>

<script>
const $=x=>document.getElementById(x);

let csrfHash=null;

async function getCsrfHash(){
    if(!csrfHash){
        const r=await fetch('/api/config');
        const d=await r.json();
        if(!r.ok || !d.csrfHash) throw Error('Gagal mengambil token keamanan.');
        csrfHash=d.csrfHash;
    }
    return csrfHash;
}

async function api(u,o={}){
    const method=(o.method||'GET').toUpperCase();
    const headers={
        'Content-Type':'application/json',
        ...(o.headers||{})
    };

    if(['POST','PUT','PATCH','DELETE'].includes(method)){
        headers['X-CSRF-TOKEN']=await getCsrfHash();
    }

    const r=await fetch(u,{
        ...o,
        headers
    });

    let d={};
    try{d=await r.json()}catch(e){}

    if(r.status===401){
        location.href='/';
        throw Error('Sesi berakhir. Silakan login kembali.');
    }

    if(!r.ok || d.success===false){
        throw Error(d.message||'Request gagal');
    }

    return d;
}

function esc(s){
    return String(s??'').replace(/[&<>"']/g,c=>({
        '&':'&amp;',
        '<':'&lt;',
        '>':'&gt;',
        '"':'&quot;',
        "'":'&#39;'
    }[c]));
}

function formatDate(value){
    if(!value)return '-';
    const d=new Date(value);
    return isNaN(d.getTime())?value:d.toLocaleString('id-ID');
}

let portalDisplayConfig=null;
let portalSsidIndexes=[];

function applyPortalDisplayConfig(config){
    portalDisplayConfig=config||{};

    const sections=portalDisplayConfig.sections||{};
    const fields=portalDisplayConfig.fields||{};
    const features=portalDisplayConfig.features||{};

    document.querySelectorAll('[data-config-section]').forEach(section=>{
        const key=section.dataset.configSection;
        section.hidden=sections[key]===false;
    });

    const grid=$('deviceInfoGrid');

    Object.entries(fields).forEach(([key,setting])=>{
        const item=grid.querySelector('[data-config-field="'+key+'"]');
        if(!item)return;

        item.hidden=setting.visible===false;

        const label=item.querySelector('[data-config-label]');
        if(label && setting.label){
            label.textContent=setting.label;
        }
    });

    // Susun ulang elemen informasi berdasarkan angka urutan dari Admin.
    [...grid.querySelectorAll('[data-config-field]')]
        .sort((a,b)=>{
            const orderA=Number(fields[a.dataset.configField]?.order)||99;
            const orderB=Number(fields[b.dataset.configField]?.order)||99;
            return orderA-orderB;
        })
        .forEach(item=>grid.appendChild(item));

    document.querySelectorAll('[data-config-feature]').forEach(button=>{
        const key=button.dataset.configFeature;
        button.hidden=features[key]===false;
    });
}

function renderWifi(list){
    if(!Array.isArray(list)){
        $('wifi').innerHTML='<p class="muted">Informasi WiFi tidak tersedia.</p>';
        return;
    }

    // Tampilkan hanya indeks SSID yang dipilih oleh administrator.
    const selectedIndexes=new Set(
        portalSsidIndexes.map(Number).filter(i=>i>=1&&i<=8)
    );

    list=list.filter(x=>selectedIndexes.has(Number(x.index)));

    if(list.length===0){
        $('wifi').innerHTML='<p class="muted">Tidak ada WiFi yang dipilih untuk ditampilkan oleh administrator.</p>';
        return;
    }

    if(!Array.isArray(list)||list.length===0){
        $('wifi').innerHTML='<p class="muted">Informasi WiFi tidak tersedia.</p>';
        return;
    }

    const features=portalDisplayConfig?.features||{};

    $('wifi').innerHTML=list.map(x=>{
        const i=Number(x.index);
        const ssid=esc(x.ssid||'');
        const enabled=x.enabled===true;

        const ssidControl=features.updateSsid===false?'':`
            <div>
                <input id="s${i}" placeholder="Nama WiFi baru" value="${ssid}">
                <button onclick="change(${i},'SSID')">Ubah SSID</button>
            </div>`;

        const passwordControl=features.updatePassword===false?'':`
            <div>
                <input id="p${i}" type="password" placeholder="Password baru">
                <button onclick="change(${i},'KeyPassphrase')">Ubah Password</button>
            </div>`;

        return `
        <div class="wifi-item">
            <h3>WiFi ${i}</h3>
            <p>Nama WiFi: <b>${ssid||'-'}</b></p>
            <p>Status: <b>${enabled?'Aktif':'Nonaktif'}</b></p>
            <p>Perangkat terhubung: <b>${Number(x.connectedDevices)||0}</b></p>
            <p>Keamanan: <b>${esc(x.security||'-')}</b></p>
            ${ssidControl}
            ${passwordControl}
        </div>`;
    }).join('');
}

async function load(){
    $('msg').textContent='Memuat data perangkat...';

    try{
        const configResult=await api('/api/customer/portal-config');
        applyPortalDisplayConfig(configResult.config||{});
        portalSsidIndexes=Array.isArray(configResult.ssidIndexes)
            ?configResult.ssidIndexes:[ ];

        const me=await api('/api/customer/me');
        $('phone').textContent=me.customer.phone||'-';

        const result=await api('/api/customer/device');
        const d=result.device||{};

        $('product').textContent=d.model||'-';
        $('serial').textContent=d.serialNumber||'-';
        $('manufacturer').textContent=d.manufacturer||'-';
        $('last').textContent=formatDate(d.lastInform);
        $('accessType').textContent=d.connection?.accessType||'-';
        $('connected').textContent=Number(d.connectedDevices??0);

        const rx=d.connection?.opticalRxPowerRaw;
        $('rxPower').textContent=rx===null||rx===undefined?'-':String(rx);

        renderWifi(d.wifi);

        $('msg').textContent='Data perangkat berhasil diperbarui.';
    }catch(e){
        $('msg').textContent=e.message;
    }
}

async function change(i,k){
    const field=k==='SSID'?'s':'p';
    const value=document.getElementById(field+i)?.value||'';

    if(!value.trim()){
        $('msg').textContent=k==='SSID'?'Nama WiFi tidak boleh kosong.':'Password tidak boleh kosong.';
        return;
    }

    try{
        const endpoint=k==='SSID'?'ssid':'password';

        await api('/api/customer/wifi/'+endpoint,{
            method:'POST',
            body:JSON.stringify({index:i,value:value})
        });

        $('msg').textContent='Perintah perubahan WiFi berhasil dikirim ke GenieACS. Perubahan perangkat mungkin memerlukan waktu.';
    }catch(e){
        $('msg').textContent=e.message;
    }
}

$('refresh').onclick=load;

$('logout').onclick=async()=>{
    try{
        await api('/api/auth/logout',{method:'POST'});
    }finally{
        location.href='/';
    }
};

$('reboot').onclick=async()=>{
    if(!confirm('Restart modem sekarang?'))return;

    try{
        await api('/api/customer/device/reboot',{method:'POST'});
        $('msg').textContent='Perintah restart berhasil dikirim.';
    }catch(e){
        $('msg').textContent=e.message;
    }
};

load();
</script>
</body>
</html>
