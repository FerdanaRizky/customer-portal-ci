<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login Pelanggan</title><style>:root{--bg:#0A1120;--surface:#121B2E;--border:#223252;--teal:#16C4B0;--blue:#3B82F6;--text:#E7EEF7;--muted:#8A99B5}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;background:radial-gradient(ellipse at 15% 0,#0F3D3A,transparent 55%),var(--bg);color:var(--text);font:15px system-ui}.card{width:min(100%,410px);background:var(--surface);border:1px solid #1B2740;border-radius:20px;padding:34px}.brand{text-align:center;margin-bottom:25px;font-size:27px;font-weight:800}p{color:var(--muted);line-height:1.6}label{display:block;margin:18px 0 8px;color:var(--muted);font-size:12px;font-weight:700}input{width:100%;padding:14px;border:1px solid var(--border);border-radius:10px;background:var(--bg);color:var(--text);font-size:16px}button{width:100%;margin-top:18px;border:0;border-radius:10px;padding:14px;background:linear-gradient(135deg,var(--teal),var(--blue));font-weight:800;cursor:pointer}.otp-hint{margin:8px 0 0;font-size:12px;line-height:1.5}.msg{min-height:22px;color:#f87171;margin-top:10px}</style></head><body><main class="card"><div class="brand" id="brand">Pesona Wifi</div><h2>Login Pelanggan</h2><p>Masukkan nomor WhatsApp untuk menerima kode OTP.</p><form id="f"><section id="phoneStep"><label>Nomor WhatsApp</label><input id="phone" type="tel" placeholder="08xxxxxxxxxx" required></section><section id="otpStep" hidden><label>Kode OTP</label><input id="otp" inputmode="numeric" maxlength="6" autocomplete="one-time-code"><p class="otp-hint">Jika kode OTP salah 3 kali, kode tidak dapat digunakan lagi. Silakan minta OTP baru.</p></section><button id="submit">Minta OTP</button><div id="otpTimer" class="msg" style="color:var(--muted)" hidden></div><button type="button" id="resendOtp" hidden>Kirim Ulang Kode OTP</button><div class="msg" id="msg"></div></form></main><script>
const $=x=>document.getElementById(x);
let csrfHash=null;
async function readApiResponse(response){
    const body=await response.text();
    let data;
    try {
        data=JSON.parse(body);
    } catch (_) {
        console.error('API returned non-JSON response', response.status, body.slice(0,500));
        if(response.status===404) throw Error('Nomor WhatsApp atau data yang Anda masukkan tidak ditemukan. Harap periksa kembali nomor WhatsApp yang terdaftar.');
        if(response.status===403) throw Error('Permintaan ditolak oleh sistem keamanan. Muat ulang halaman, lalu coba lagi. Jika masih terjadi, hubungi administrator.');
        if(response.status>=500) throw Error('Terjadi gangguan pada sistem/server (HTTP '+response.status+'). Silakan coba beberapa saat lagi atau hubungi administrator.');
        if(response.status===400 || response.status===422) throw Error('Nomor WhatsApp atau data yang Anda masukkan tidak valid. Periksa kembali data Anda.');
        throw Error('Terjadi kesalahan saat memproses permintaan (HTTP '+response.status+'). Silakan coba lagi.');
    }
    if(!response.ok && (!data || typeof data!=='object')) {
        throw Error('Permintaan gagal (HTTP '+response.status+').');
    }
    return data;
}
async function getCsrfHash(){
    if(!csrfHash){
        const response=await fetch('/api/config',{headers:{'Accept':'application/json'}});
        const data=await readApiResponse(response);
        if(!response.ok || !data.csrfHash) throw Error('Gagal mengambil token keamanan. Muat ulang halaman.');
        csrfHash=data.csrfHash;
    }
    return csrfHash;
}
let stage=0, otpExpiryTimer=null, otpSeconds=0;
function startOtpCountdown(seconds){
    if(otpExpiryTimer) clearInterval(otpExpiryTimer);
    otpSeconds=seconds;
    $('otpTimer').hidden=false;
    $('resendOtp').hidden=true;
    const tick=()=>{
        if(otpSeconds<=0){
            clearInterval(otpExpiryTimer);
            otpExpiryTimer=null;
            $('otpTimer').textContent='Waktu OTP sudah habis. Silakan kirim ulang kode OTP.';
            $('resendOtp').hidden=false;
            return;
        }
        const min=Math.floor(otpSeconds/60), sec=otpSeconds%60;
        $('otpTimer').textContent='Kode OTP berlaku '+min+':'+String(sec).padStart(2,'0')+'.';
        otpSeconds--;
    };
    tick();
    otpExpiryTimer=setInterval(tick,1000);
}
async function sendOtpAgain(){
    $('resendOtp').disabled=true;
    $('msg').textContent='';
    try{
        const response=await fetch('/api/auth/request-otp',{
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json',
                'X-CSRF-TOKEN':await getCsrfHash()
            },
            body:JSON.stringify({phone:$('phone').value})
        });
        const data=await readApiResponse(response);
        if(!response.ok || !data.success) throw Error(data.message||'Gagal mengirim ulang OTP.');
        $('otp').value='';
        startOtpCountdown(Number(data.expiresIn)||300);
        $('msg').textContent='Kode OTP baru berhasil dikirim ke WhatsApp.';
    }catch(error){
        $('msg').textContent=error.message||'Terjadi kesalahan saat mengirim ulang OTP.';
    }finally{
        $('resendOtp').disabled=false;
    }
}
$('resendOtp').onclick=sendOtpAgain;
$('f').onsubmit=async e=>{
    e.preventDefault();
    $('submit').disabled=true;
    try{
        const response=await fetch(stage?'/api/auth/verify-otp':'/api/auth/request-otp',{
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'Accept':'application/json',
                'X-CSRF-TOKEN':await getCsrfHash()
            },
            body:JSON.stringify({phone:$('phone').value,otp:$('otp').value})
        });
        const data=await readApiResponse(response);
        if(!response.ok || !data.success){
            if(data.code==='OTP_EXPIRED'){
                if(otpExpiryTimer) clearInterval(otpExpiryTimer);
                $('otpTimer').hidden=false;
                $('otpTimer').textContent='Kode OTP sudah kedaluwarsa.';
                $('resendOtp').hidden=false;
            }
            throw Error(data.message||'Permintaan gagal. Periksa data Anda.');
        }
        if(!stage){
            stage=1;
            $('phoneStep').hidden=true;
            $('otpStep').hidden=false;
            $('submit').textContent='Verifikasi OTP';
            startOtpCountdown(Number(data.expiresIn)||300);
            $('msg').textContent=data.message||'OTP dikirim ke WhatsApp.';
        }else{
            if(otpExpiryTimer) clearInterval(otpExpiryTimer);
            location.href=data.redirect||'/dashboard';
        }
    }catch(error){
        const message=error instanceof TypeError
            ? 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda, lalu coba lagi.'
            : (error.message||'Terjadi kesalahan pada sistem. Silakan coba lagi.');
        $('msg').textContent=message;
    }finally{
        $('submit').disabled=false;
    }
};
fetch('/api/config',{headers:{'Accept':'application/json'}})
    .then(readApiResponse)
    .then(data=>$('brand').textContent=data.appName||'Pesona Wifi')
    .catch(error=>console.error('Portal config:',error));
</script></body></html>