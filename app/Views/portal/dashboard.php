<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Pelanggan</title>
<style>
:root{
    --bg:#091120;
    --surface:#121d32;
    --surface2:#091426;
    --border:#1e304e;
    --text:#e7eef7;
    --muted:#8294b2;
    --teal:#13c5b4;
    --blue:#2799ed;
    --green:#19c96b;
    --red:#ff4d55;
}
*{box-sizing:border-box}
body{
    margin:0;
    background:radial-gradient(ellipse at 0 0,#0b2630 0,transparent 35%),
               radial-gradient(ellipse at 100% 0,#102847 0,transparent 30%),
               var(--bg);
    color:var(--text);
    font:14px system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}
button,input{font:inherit}
button{
    padding:10px 15px;
    border:1px solid var(--border);
    border-radius:9px;
    background:#14233c;
    color:var(--text);
    cursor:pointer;
    transition:.2s;
}
button:hover{border-color:var(--teal);color:var(--teal)}
button:disabled{opacity:.55;cursor:wait}
input{
    width:100%;
    min-width:0;
    padding:10px 12px;
    border:1px solid var(--border);
    border-radius:8px;
    background:#091426;
    color:var(--text);
    outline:none;
}
input:focus{border-color:var(--teal)}
.topbar{
    position:sticky;
    top:0;
    z-index:1000;
    width:100%;
    min-height:76px;
    padding:12px 28px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    background:rgba(17,28,48,.96);
    border-bottom:1px solid var(--border);
}
.brand img{display:block;max-width:145px;max-height:52px;object-fit:contain}
.brand-name{font-size:22px;font-weight:800;letter-spacing:.3px}
.top-actions{display:flex;align-items:center;gap:12px}
.phone{font-weight:700;font-size:14px}
.count-badge{
    width:40px;height:40px;border-radius:50%;
    display:grid;place-items:center;
    background:linear-gradient(135deg,#14c9b3,#318de9);
    color:#061326;font-weight:800;
}
.logout-btn{width:42px;height:40px;padding:0;font-size:18px}
.wrap{width:min(1120px,100%);margin:auto;padding:30px 24px 48px}
.page-heading{margin:0 0 24px}
.page-heading h1{font-size:22px;margin:0 0 4px}
.page-heading p{margin:0;color:var(--muted)}
.dashboard-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
    align-items:stretch;
}
.card{
    min-width:0;
    padding:22px;
    border:1px solid var(--border);
    border-radius:20px;
    background:rgba(18,29,50,.96);
}
.card-head{
    display:flex;align-items:center;justify-content:space-between;
    gap:12px;margin-bottom:18px;
}
.card-title{display:flex;align-items:center;gap:11px;font-size:15px;font-weight:750}
.card-icon{
    width:34px;height:34px;display:grid;place-items:center;
    color:var(--teal);background:#142541;
    border:1px solid #223b60;border-radius:9px;font-size:17px;
}
.head-actions{display:flex;align-items:center;gap:10px}
.icon-btn{width:36px;height:34px;padding:0;font-size:17px;color:#a3b4cf}
.refresh-icon{display:inline-block;transform-origin:center}
.refreshing .refresh-icon{animation:refresh-spin .8s linear infinite}
@keyframes refresh-spin{to{transform:rotate(360deg)}}
.online-pill,.metric-pill{
    display:inline-flex;align-items:center;gap:6px;
    padding:4px 10px;border:1px solid #147c50;
    border-radius:20px;color:var(--green);font-size:12px;font-weight:700;
    background:rgba(17,180,95,.09);
}
.online-dot{width:8px;height:8px;border-radius:50%;background:var(--green)}
.offline-pill{border-color:#9a3944;color:#ff747b;background:rgba(255,70,80,.08)}
.info-list{display:grid;gap:12px}
.info-row{
    min-height:46px;padding:11px 14px;
    display:flex;justify-content:space-between;align-items:center;gap:12px;
    border:1px solid var(--border);border-radius:11px;background:var(--surface2);
}
.info-row span{color:#91a5c7}
.info-row strong{text-align:right;overflow-wrap:anywhere}
.metrics-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:16px}
.metrics-grid:has(#temperatureMetric[hidden]){grid-template-columns:1fr}
.metric{
    min-width:0;padding:14px;
    border:1px solid var(--border);border-radius:14px;background:var(--surface2);
}
.metric-top{display:flex;justify-content:space-between;align-items:center;gap:6px}
.metric-label{font-size:11px;font-weight:800;letter-spacing:.4px;color:#91a5c7}
.metric-value{font-size:25px;font-weight:800;margin:10px 0 6px;overflow-wrap:anywhere}
.metric-value small{font-size:12px;color:var(--muted);font-weight:500}
.metric-track{height:5px;background:#1b2a45;border-radius:9px;overflow:hidden}
.metric-track span{display:block;height:100%;width:0;background:var(--green);border-radius:9px}
.metric-note{font-size:11px;color:var(--muted);margin-top:6px}
.reboot-btn{
    width:100%;margin-top:14px;padding:13px;
    color:var(--red);border-color:#71313f;background:rgba(255,65,75,.08);
    font-weight:750;
}
.reboot-btn:hover{background:rgba(255,65,75,.14);color:#ff747b;border-color:var(--red)}
.msg{
    display:inline-flex;
    align-items:center;
    gap:10px;
    min-height:48px;
    margin:12px 0 0;
    padding:10px 18px;
    border:1px solid var(--teal);
    border-radius:12px;
    background:rgba(19,197,180,.06);
    color:var(--text);
    font-size:14px;
    font-weight:650;
}
.msg{display:none!important}
.refresh-notice{
    display:none;
    align-items:center;
    gap:10px;
    position:fixed;
    left:50%;
    bottom:24px;
    transform:translateX(-50%);
    z-index:99999;
    width:max-content;
    max-width:calc(100vw - 32px);
    min-height:48px;
    padding:12px 20px;
    border:1px solid var(--teal);
    border-radius:12px;
    background:var(--surface, #142238);
    color:var(--text);
    font-size:14px;
    font-weight:650;
    box-shadow:0 8px 30px rgba(0,0,0,.35);
}
.refresh-notice.is-visible{display:flex!important}
.refresh-notice .notice-check{color:var(--teal);font-size:18px}
@media(max-width:600px){
    .refresh-notice{
        left:50%;
        bottom:12px;
        transform:translateX(-50%);
        max-width:calc(100vw - 24px);
    }
}

.wifi-list{display:grid;gap:14px}
.wifi-item{
    display:flex;align-items:center;justify-content:space-between;
    gap:12px;padding:15px 16px;
    border:1px solid var(--border);border-radius:14px;background:var(--surface2);
}
.wifi-name{font-size:15px;font-weight:750;overflow-wrap:anywhere}
.wifi-meta{font-size:12px;color:var(--muted);margin-top:4px}
.wifi-edit{
    flex:0 0 auto;min-width:66px;
    border-color:var(--teal);color:var(--teal);background:rgba(19,197,180,.06);
}
.wifi-form{
    display:grid;grid-template-columns:1fr;gap:8px;
    width:100%;margin-top:12px;
}
.wifi-form[hidden]{display:none}
.wifi-form button{justify-self:start}
.connected-card{margin-top:20px}
.connected-list{display:grid;gap:10px}
.connected-item{
    display:flex;justify-content:space-between;align-items:center;gap:12px;
    padding:14px 15px;border:1px solid var(--border);
    border-radius:11px;background:var(--surface2);
}
.connected-name{font-weight:700;overflow-wrap:anywhere}
.connected-meta{font-size:12px;color:var(--muted);margin-top:4px;overflow-wrap:anywhere}
.device-count{
    font-size:12px;color:var(--muted);
    border:1px solid var(--border);border-radius:7px;padding:4px 9px;
}
.empty-state{color:var(--muted);padding:14px 2px}
[hidden]{display:none!important}
@media(max-width:760px){
    .topbar{padding:10px 15px;min-height:66px}
    .brand img{max-width:118px;max-height:44px}
    .brand-name{font-size:18px}
    .phone{font-size:12px}
    .count-badge{width:34px;height:34px}
    .wrap{padding:22px 14px 36px}
    .dashboard-grid{grid-template-columns:1fr;gap:14px}
    .card{padding:17px;border-radius:16px}
    .page-heading h1{font-size:20px}
}
@media(max-width:390px){
    .topbar{gap:8px;padding:9px 10px}
    .top-actions{gap:7px}
    .phone{font-size:11px}
    .metrics-grid{gap:8px}
    .metric{padding:11px}
    .metric-value{font-size:21px}
}

.modal-backdrop{
    position:fixed;inset:0;z-index:2000;
    display:flex;align-items:center;justify-content:center;
    padding:20px;background:rgba(3,8,18,.78);
    backdrop-filter:blur(7px);
}
.modal-backdrop[hidden]{display:none}
.wifi-modal{
    width:min(610px,100%);padding:30px 32px;
    border:1px solid var(--border);border-radius:22px;
    background:#121d32;box-shadow:0 25px 80px rgba(0,0,0,.45);
}
.modal-head{display:flex;justify-content:space-between;gap:15px;align-items:center}
.modal-head h2{font-size:20px;margin:0}
.modal-close{background:transparent;border:0;font-size:23px;color:var(--muted);padding:3px 8px}
.modal-desc{color:var(--muted);line-height:1.5;margin:8px 0 26px}
.modal-field{
    display:grid;grid-template-columns:145px minmax(0,1fr) 90px;
    align-items:center;gap:12px;margin:16px 0;
}
.modal-field label{font-size:12px;font-weight:800;letter-spacing:.5px;color:var(--muted)}
.modal-field button{
    background:linear-gradient(110deg,#12c5b4,#3289ed);
    border:0;color:#071526;font-weight:800;
}
.modal-cancel{width:100%;margin-top:12px;padding:13px;color:var(--muted);background:transparent}
@media(max-width:600px){
    .wifi-modal{padding:22px 18px;border-radius:18px}
    .modal-field{grid-template-columns:1fr 76px;gap:8px}
    .modal-field label{grid-column:1/-1}
}

</style>
</head>
<body>
<header class="topbar">
    <div class="brand">
        <img id="appLogo" src="" alt="Logo Pesona WiFi" hidden>
        <span id="appName" class="brand-name">Pesona WiFi</span>
    </div>
    <div class="top-actions">
        <span class="phone" id="phone">-</span>
        <span class="count-badge" id="deviceCount" title="Perangkat terhubung">0</span>
        <button id="logout" class="logout-btn" title="Keluar" aria-label="Keluar">⇥</button>
    </div>
</header>

<main class="wrap">

<div class="page-heading">
    <h1>Dashboard Perangkat</h1>
    <p>Pantau status koneksi dan kelola pengaturan WiFi rumah Anda.</p>
</div>

<div class="dashboard-grid">
<section class="card" data-config-section="deviceInfo">
    <div class="card-head">
        <div class="card-title"><span class="card-icon">▣</span>Info Perangkat</div>
        <div class="head-actions">
            <span id="connectionStatus" class="online-pill"><span class="online-dot"></span>Memuat</span>
            <button id="refresh" class="icon-btn" data-config-feature="refresh" title="Refresh perangkat" aria-label="Refresh perangkat"><span class="refresh-icon">⟳</span></button>
        </div>
    </div>

    <div class="info-list" id="deviceInfoGrid">
        <div class="info-row" data-config-field="product"><span data-config-label="product">Product Class</span><strong id="product">-</strong></div>
        <div class="info-row" data-config-field="serial"><span data-config-label="serial">Serial Number</span><strong id="serial">-</strong></div>
        <div class="info-row" data-config-field="lastInform"><span data-config-label="lastInform">Last Update</span><strong id="last">-</strong></div>
        <div class="info-row" data-config-field="manufacturer"><span data-config-label="manufacturer">Manufacturer</span><strong id="manufacturer">-</strong></div>
        <div class="info-row" data-config-field="accessType"><span data-config-label="accessType">Jenis Koneksi</span><strong id="accessType">-</strong></div>
        <div class="info-row" data-config-field="connected"><span data-config-label="connected">Perangkat Terhubung</span><strong id="connected">-</strong></div>
    </div>

    <div class="metrics-grid">
        <div class="metric" id="rxMetricCard">
            <div class="metric-top"><span class="metric-label">REDAMAN</span><span id="rxStatus" class="metric-pill">-</span></div>
            <div class="metric-value" id="rxMetric">- <small>dBm</small></div>
            <div class="metric-track"><span id="rxBar"></span></div>
            <div class="metric-note" id="rxNote">Menunggu data</div>
        </div>
        <div class="metric" id="temperatureMetric" hidden>
            <div class="metric-top"><span class="metric-label">SUHU</span><span id="temperatureStatus" class="metric-pill">-</span></div>
            <div class="metric-value" id="temperatureValue">- <small>°C</small></div>
            <div class="metric-track"><span id="temperatureBar"></span></div>
            <div class="metric-note">Suhu perangkat</div>
        </div>
    </div>

    <p id="msg" class="msg" role="status" aria-live="polite"></p>
    <div id="refreshNotice" class="refresh-notice" role="status" aria-live="polite" hidden>
        <span class="notice-check">✓</span>
        <span id="refreshNoticeText"></span>
    </div>
    <button id="reboot" class="reboot-btn" data-config-feature="reboot">⏻ &nbsp;Restart Modem</button>
</section>

<section class="card" data-config-section="wifi">
    <div class="card-head">
        <div class="card-title"><span class="card-icon">⌁</span>Pengaturan WiFi</div>
        <button id="refreshWifi" class="icon-btn" title="Refresh WiFi" aria-label="Refresh WiFi"><span class="refresh-icon">⟳</span></button>
    </div>
    <div id="wifi" class="wifi-list"><p class="empty-state">Memuat informasi WiFi...</p></div>
</section>
</div>

<section class="card connected-card" id="connectedSection">
    <div class="card-head">
        <div class="card-title"><span class="card-icon">♧</span>Device yang Terhubung</div>
        <span class="device-count" id="connectedCount">0 device</span>
    </div>
    <div id="connectedDevices" class="connected-list">
        <p class="empty-state">Memuat daftar perangkat...</p>
    </div>
</section>

</main>

<div class="modal-backdrop" id="wifiModal" hidden>
    <div class="wifi-modal" role="dialog" aria-modal="true" aria-labelledby="wifiModalTitle">
        <div class="modal-head">
            <h2 id="wifiModalTitle">Ubah WiFi</h2>
            <button type="button" class="modal-close" onclick="closeWifiModal()" aria-label="Tutup">×</button>
        </div>
        <p class="modal-desc">Perubahan akan langsung dikirim ke perangkat Anda. Koneksi WiFi mungkin terputus sesaat.</p>

        <div class="modal-field" id="modalSsidField">
            <label for="modalSsid">SSID</label>
            <input id="modalSsid" type="text" placeholder="Nama WiFi baru">
            <button type="button" id="saveModalSsid">Ubah</button>
        </div>

        <div class="modal-field" id="modalPasswordField">
            <label for="modalPassword">PASSWORD</label>
            <input id="modalPassword" type="password" placeholder="8-63 karakter">
            <button type="button" id="saveModalPassword">Ubah</button>
        </div>

        <button type="button" class="modal-cancel" onclick="closeWifiModal()">Batal</button>
        <p class="msg" id="modalMsg" role="status" aria-live="polite"></p>
    </div>
</div>


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

    const phoneSetting=fields.phone||{};
    const phoneVisible=phoneSetting.visible!==false;
    $('phone').hidden=!phoneVisible;
    if(phoneSetting.label){$('phone').title=phoneSetting.label;}

    Object.entries(fields).forEach(([key,setting])=>{
        if(key==='rxPower'){
            if($('rxMetricCard')) $('rxMetricCard').hidden=setting.visible===false;
            return;
        }

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

let activeWifiIndex=null;

function renderWifi(list){
    if(!Array.isArray(list)){
        $('wifi').innerHTML='<p class="empty-state">Informasi WiFi tidak tersedia.</p>';
        return;
    }

    const selectedIndexes=new Set(
        portalSsidIndexes.map(Number).filter(i=>i>=1&&i<=8)
    );
    list=list.filter(x=>selectedIndexes.has(Number(x.index)));

    if(list.length===0){
        $('wifi').innerHTML='<p class="empty-state">Tidak ada WiFi yang dipilih oleh Admin.</p>';
        return;
    }

    $('wifi').innerHTML=list.map(x=>{
        const i=Number(x.index);
        const ssid=esc(x.ssid||'-');
        const enabled=x.enabled===true;

        return `
        <div class="wifi-item">
            <div style="min-width:0;flex:1">
                <div class="wifi-name">${ssid}</div>
                <div class="wifi-meta">SSID ${i} · ${enabled?'Aktif':'Nonaktif'} · ${Number(x.connectedDevices)||0} perangkat</div>
            </div>
            <button type="button" class="wifi-edit"
                onclick="openWifiModal(${i},'${ssid}')">Ubah</button>
        </div>`;
    }).join('');
}

function openWifiModal(index,ssid){
    activeWifiIndex=index;
    const features=portalDisplayConfig?.features||{};

    $('wifiModalTitle').textContent='Ubah WiFi — SSID '+index;
    $('modalSsid').value=ssid==='-'?'':ssid;
    $('modalPassword').value='';
    $('modalMsg').textContent='';

    $('modalSsidField').hidden=features.updateSsid===false;
    $('modalPasswordField').hidden=features.updatePassword===false;

    $('wifiModal').hidden=false;
    document.body.style.overflow='hidden';

    if(features.updateSsid!==false) $('modalSsid').focus();
    else $('modalPassword').focus();
}

function closeWifiModal(){
    $('wifiModal').hidden=true;
    document.body.style.overflow='';
    activeWifiIndex=null;
}

async function saveWifiValue(parameter){
    if(activeWifiIndex===null)return;

    const isSsid=parameter==='SSID';
    const input=$(isSsid?'modalSsid':'modalPassword');
    const button=$(isSsid?'saveModalSsid':'saveModalPassword');
    const value=input.value.trim();

    if(!value){
        $('modalMsg').textContent=isSsid?'Nama WiFi tidak boleh kosong.':'Password tidak boleh kosong.';
        return;
    }

    if(!isSsid&&(value.length<8||value.length>63)){
        $('modalMsg').textContent='Password harus 8-63 karakter.';
        return;
    }

    const original=button.textContent;
    button.disabled=true;
    button.textContent='Menyimpan...';
    $('modalMsg').textContent='Mengirim perubahan ke GenieACS...';

    try{
        await api('/api/customer/wifi/'+(isSsid?'ssid':'password'),{
            method:'POST',
            body:JSON.stringify({index:activeWifiIndex,value:value})
        });

        $('modalMsg').textContent='Perintah berhasil dikirim. Perangkat mungkin memerlukan waktu untuk menerapkan perubahan.';
        button.textContent='Terkirim ✓';
    }catch(e){
        $('modalMsg').textContent='Gagal: '+e.message;
        button.textContent=original;
    }finally{
        button.disabled=false;
    }
}

$('saveModalSsid').onclick=()=>saveWifiValue('SSID');
$('saveModalPassword').onclick=()=>saveWifiValue('KeyPassphrase');

$('wifiModal').addEventListener('click',e=>{
    if(e.target===$('wifiModal'))closeWifiModal();
});

document.addEventListener('keydown',e=>{
    if(e.key==='Escape'&&!$('wifiModal').hidden)closeWifiModal();
});

function renderConnectedDevices(list){
    const container=$('connectedDevices');
    if(!Array.isArray(list)||list.length===0){
        container.innerHTML='<p class="empty-state">Belum ada informasi perangkat terhubung dari GenieACS.</p>';
        $('connectedCount').textContent='0 device';
        return;
    }

    $('connectedCount').textContent=list.length+' device';
    container.innerHTML=list.map((item,index)=>`
        <div class="connected-item">
            <div style="min-width:0">
                <div class="connected-name">${esc(item.name||'Perangkat '+(index+1))}</div>
                <div class="connected-meta">${esc(item.ip||'IP tidak tersedia')} &nbsp; ${esc(item.mac||'MAC tidak tersedia')}</div>
            </div>
            <span class="device-count">${item.active?'Aktif':'Terdeteksi'}</span>
        </div>
    `).join('');
}

function updateConnectionStatus(lastInform){
    const el=$('connectionStatus');
    const time=lastInform?new Date(lastInform).getTime():NaN;
    const online=Number.isFinite(time)&&(Date.now()-time)<=15*60*1000;

    el.className=online?'online-pill':'online-pill offline-pill';
    el.innerHTML=online
        ?'<span class="online-dot"></span>Online'
        :'Offline';
}

function updateRxMetric(value){
    const number=Number(value);
    if(value===null||value===undefined||value===''||!Number.isFinite(number)){
        $('rxMetric').innerHTML='- <small>dBm</small>';
        $('rxStatus').textContent='Tidak tersedia';
        $('rxNote').textContent='Data redaman belum tersedia';
        $('rxBar').style.width='0%';
        return;
    }

    $('rxMetric').innerHTML=esc(number.toFixed(1))+' <small>dBm</small>';

    // Nilai RX lebih dekat ke 0 biasanya menunjukkan sinyal lebih kuat.
    const quality=number>=-25?'Baik':(number>=-27?'Perhatian':'Lemah');
    $('rxStatus').textContent=quality;
    $('rxNote').textContent=quality==='Baik'?'Stabil':(quality==='Perhatian'?'Perlu dipantau':'Periksa jalur optik');
    $('rxBar').style.width=Math.max(5,Math.min(100,((number+35)/20)*100))+'%';
}

function updateTemperatureMetric(value){
    const raw=String(value??'').trim();
    const number=Number(raw.replace(',', '.'));
    const valid=raw!=='' && raw.toUpperCase()!=='N/A' && Number.isFinite(number);

    $('temperatureMetric').hidden=!valid;

    if(!valid){
        $('temperatureValue').innerHTML='- <small>°C</small>';
        $('temperatureStatus').textContent='Tidak tersedia';
        $('temperatureBar').style.width='0%';
        return;
    }

    $('temperatureValue').innerHTML=esc(number.toFixed(0))+' <small>°C</small>';
    $('temperatureStatus').textContent='Terbaca';
    $('temperatureBar').style.width=Math.max(0,Math.min(100,number))+'%';
}

function updateDeviceInfo(d){
    $('product').textContent=d.model||'-';
    $('serial').textContent=d.serialNumber||'-';
    $('manufacturer').textContent=d.manufacturer||'-';
    $('last').textContent=d.lastInform?formatDate(d.lastInform):'-';
    $('accessType').textContent=d.connection?.accessType||'-';
    $('connected').textContent=Number(d.connectedDevices??0);
    $('deviceCount').textContent=Number(d.connectedDevices??0);
    $('connectedCount').textContent=Number((d.hosts||[]).length)+' device';

    updateConnectionStatus(d.lastInform);
    updateRxMetric(d.connection?.opticalRxPowerRaw);
    updateTemperatureMetric(d.connection?.temperature);
    renderConnectedDevices(d.hosts||[]);
}

async function getDeviceData(){
    const result=await api('/api/customer/device');
    return result.device||{};
}

let refreshNoticeTimer=null;

async function runRefresh(button, task, successMessage){
    if(button.disabled)return;

    button.disabled=true;
    button.classList.add('refreshing');

    clearTimeout(refreshNoticeTimer);
    $('refreshNotice').classList.remove('is-visible');
    $('refreshNoticeText').textContent='';

    try{
        await task();
        $('refreshNoticeText').textContent=successMessage;
    }catch(e){
        $('refreshNoticeText').textContent='Gagal memperbarui data: '+e.message;
    }finally{
        button.classList.remove('refreshing');
        button.disabled=false;

        $('refreshNotice').classList.add('is-visible');

        refreshNoticeTimer=setTimeout(()=>{
            $('refreshNotice').classList.remove('is-visible');
        },3500);
    }
}

async function refreshDeviceInfo(){
    await runRefresh(
        $('refresh'),
        async()=>{
            const d=await getDeviceData();
            updateDeviceInfo(d);
            renderWifi(d.wifi);
        },
        'Seluruh data dashboard berhasil diperbarui.'
    );
}

async function refreshWifiList(){
    await runRefresh(
        $('refreshWifi'),
        async()=>{
            const d=await getDeviceData();
            renderWifi(d.wifi);
            renderConnectedDevices(d.hosts||[]);
            $('connectedCount').textContent=(d.hosts||[]).length+' device';
        },
        'Data WiFi dan perangkat terhubung berhasil diperbarui.'
    );
}

async function load(){
    const refreshButtons=[$('refresh'),$('refreshWifi')].filter(Boolean);
    refreshButtons.forEach(button=>{
        button.disabled=true;
        button.classList.add('refreshing');
    });

    $('msg').textContent='Memuat data perangkat...';
    $('connectedDevices').innerHTML='<p class="empty-state">Memuat daftar perangkat...</p>';

    try{
        const appConfig=await api('/api/config');
        $('appName').textContent=appConfig.appName||'Pesona WiFi';

        if(appConfig.logoUrl){
            $('appLogo').src=appConfig.logoUrl;
            $('appLogo').hidden=false;
            $('appName').hidden=true;
        }else{
            $('appLogo').hidden=true;
            $('appName').hidden=false;
        }

        const configResult=await api('/api/customer/portal-config');
        applyPortalDisplayConfig(configResult.config||{});
        portalSsidIndexes=Array.isArray(configResult.ssidIndexes)
            ?configResult.ssidIndexes:[];

        const me=await api('/api/customer/me');
        $('phone').textContent=me.customer.phone||'-';

        const d=await getDeviceData();

        updateDeviceInfo(d);
        renderWifi(d.wifi);

        $('msg').textContent='✓  Data dashboard berhasil dimuat.';
    }catch(e){
        $('msg').textContent='Gagal memuat dashboard: '+e.message;
        $('connectedDevices').innerHTML='<p class="empty-state">Gagal mengambil daftar perangkat.</p>';
        $('wifi').innerHTML='<p class="empty-state">Gagal mengambil informasi WiFi.</p>';
    }finally{
        refreshButtons.forEach(button=>{
            button.classList.remove('refreshing');
            button.disabled=false;
        });
    }
}

async function change(i,k){
    const isSsid=k==='SSID';
    const field=isSsid?'s':'p';
    const value=document.getElementById(field+i)?.value||'';

    if(!value.trim()){
        $('msg').textContent=isSsid?'Nama WiFi tidak boleh kosong.':'Password tidak boleh kosong.';
        return;
    }

    const buttons=document.querySelectorAll('#wifiForm'+i+' button');
    buttons.forEach(button=>button.disabled=true);
    $('msg').textContent='Mengirim perubahan WiFi...';

    try{
        const endpoint=isSsid?'ssid':'password';
        await api('/api/customer/wifi/'+endpoint,{
            method:'POST',
            body:JSON.stringify({index:i,value:value})
        });
        $('msg').textContent='Perintah perubahan WiFi berhasil dikirim ke GenieACS. Perubahan perangkat mungkin memerlukan waktu.';
    }catch(e){
        $('msg').textContent='Gagal mengubah WiFi: '+e.message;
    }finally{
        buttons.forEach(button=>button.disabled=false);
    }
}

$('refresh').onclick=refreshDeviceInfo;
$('refreshWifi').onclick=refreshWifiList;

$('logout').onclick=async()=>{
    const button=$('logout');
    button.disabled=true;
    button.textContent='…';
    try{
        await api('/api/auth/logout',{method:'POST'});
        location.href='/';
    }catch(e){
        $('msg').textContent='Gagal keluar: '+e.message;
        button.disabled=false;
        button.textContent='⇥';
    }
};

$('reboot').onclick=async()=>{
    if(!confirm('Restart modem sekarang?'))return;

    const button=$('reboot');
    const original=button.textContent;
    button.disabled=true;
    button.textContent='Mengirim perintah...';
    $('msg').textContent='Sedang mengirim perintah restart modem...';

    try{
        await api('/api/customer/device/reboot',{method:'POST'});
        $('msg').textContent='Perintah restart berhasil dikirim. Modem mungkin membutuhkan waktu untuk tersambung kembali.';
        button.textContent='Perintah Terkirim';
    }catch(e){
        $('msg').textContent=e.message;
        button.textContent=original;
    }finally{
        button.disabled=false;
        if(button.textContent==='Perintah Terkirim'){
            setTimeout(()=>{button.textContent=original;},2000);
        }
    }
};

load();
</script>
</body>
</html>
