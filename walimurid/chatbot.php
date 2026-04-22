<?php
/**
 * CHATBOT AI WIDGET - Portal Wali Murid
 * Desain Premium, Kompak & Responsif
 */

if (!isset($chatbot_data_loaded)) {
    $cb_id_wali = $_SESSION['id_user'] ?? 0;
    $cb_nama_wali = $_SESSION['nama_lengkap'] ?? 'Bapak/Ibu';
    $cb_nama_sekolah = $_SESSION['nama_sekolah'] ?? 'Sekolah';

    $cb_q_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_walimurid = '$cb_id_wali' LIMIT 1");
    $cb_siswa = mysqli_fetch_assoc($cb_q_siswa);

    $cb_tagihan_belum = [];
    $cb_tagihan_lunas = [];
    $cb_total_tunggakan = 0;
    $cb_total_lunas_amount = 0;
    $cb_banding_menunggu = 0;
    $cb_banding_ditolak = 0;

    if ($cb_siswa) {
        $cb_id_siswa = $cb_siswa['id_siswa'];

        $cb_q_bl = mysqli_query($koneksi, "SELECT * FROM tagihan WHERE id_siswa = '$cb_id_siswa' AND status = 'Belum Lunas' ORDER BY tahun DESC, bulan ASC");
        while ($row = mysqli_fetch_assoc($cb_q_bl)) {
            $row['is_jatuh_tempo'] = isJatuhTempo($row['bulan'], $row['tahun']);
            if ($row['is_jatuh_tempo']) $cb_total_tunggakan += $row['nominal'];
            $cb_tagihan_belum[] = $row;
        }

        $cb_q_lunas = mysqli_query($koneksi, "SELECT * FROM tagihan WHERE id_siswa = '$cb_id_siswa' AND status = 'Lunas' ORDER BY tahun DESC, bulan ASC LIMIT 5");
        while ($row = mysqli_fetch_assoc($cb_q_lunas)) {
            $cb_total_lunas_amount += $row['nominal'];
            $cb_tagihan_lunas[] = $row;
        }

        $cb_q_banding = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM banding WHERE id_walimurid = '$cb_id_wali' AND status_banding IN ('Menunggu', 'Diproses')");
        $cb_banding_menunggu = mysqli_fetch_assoc($cb_q_banding)['total'] ?? 0;

        $cb_q_ditolak = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM banding WHERE id_walimurid = '$cb_id_wali' AND status_banding = 'Ditolak'");
        $cb_banding_ditolak = mysqli_fetch_assoc($cb_q_ditolak)['total'] ?? 0;
    }
    $chatbot_data_loaded = true;
}

$cb_nama_depan = explode(' ', trim($cb_nama_wali))[0];
?>

<!-- ============ CHATBOT AI WIDGET ============ -->
<div id="chatbotWidget" style="position:fixed; bottom:20px; right:20px; z-index:9999; font-family:'Poppins',system-ui,sans-serif; pointer-events:none;">

    <!-- Chat Window Group (Window is INSIDE the relative wrapper for proper absolute positioning) -->
    <div style="position:relative; width:100%; height:100%; pointer-events:auto;">
        
        <!-- Floating Button -->
        <button id="chatToggleBtn" onclick="toggleChatbot()" 
            style="position:absolute; bottom:0; right:0; width:56px; height:56px; border-radius:50%; border:none; cursor:pointer; 
                   background:linear-gradient(135deg,#10b981 0%,#059669 100%); color:white; font-size:22px;
                   box-shadow:0 8px 24px rgba(16,185,129,0.35); transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);
                   display:flex; align-items:center; justify-content:center; z-index:2;
                   animation: floatBtn 3.5s ease-in-out infinite;">
            <i class="fa-solid fa-robot" id="chatIconOpen" style="font-size:24px;"></i>
            <i class="fa-solid fa-xmark" id="chatIconClose" style="display:none; font-size:22px;"></i>
            <span id="chatPulse" style="position:absolute;top:0;right:0;width:14px;height:14px;
                  background:#ef4444;border-radius:50%;border:2px solid white;
                  animation:chatPulseAnim 2s infinite;"></span>
        </button>

        <!-- Chat Window -->
        <div id="chatWindow" style="display:none; position:absolute; bottom:70px; right:0;
             width:360px; max-width:calc(100vw - 40px); height:520px; max-height:calc(100vh - 110px); 
             background:white; border-radius:20px;
             box-shadow:0 12px 40px rgba(0,0,0,0.15),0 0 0 1px rgba(0,0,0,0.05); overflow:hidden;
             transform:scale(0.9) translateY(20px); opacity:0; transform-origin:bottom right;
             transition:all 0.35s cubic-bezier(0.34,1.56,0.64,1); flex-direction:column; z-index:1;">

            <!-- Header -->
            <div style="background:#1e293b; padding:16px; display:flex; align-items:center; gap:12px; position:relative; overflow:hidden; flex-shrink:0;">
                <div style="position:absolute;top:-30px;right:-10px;width:100px;height:100px;
                     background:radial-gradient(circle,rgba(16,185,129,0.2) 0%,transparent 70%);border-radius:50%;"></div>
                
                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#10b981,#059669);
                            display:flex;align-items:center;justify-content:center;flex-shrink:0; position:relative;">
                    <i class="fa-solid fa-robot" style="color:white;font-size:18px;"></i>
                    <div style="position:absolute;bottom:-2px;right:-2px;width:10px;height:10px;background:#22c55e;
                         border-radius:50%;border:2px solid #1e293b;"></div>
                </div>
                <div style="flex:1;position:relative;z-index:1;">
                    <h4 style="margin:0;color:white;font-size:14px;font-weight:700;letter-spacing:-0.2px;">Asisten Digital</h4>
                    <p style="margin:2px 0 0;color:#94a3b8;font-size:10px;font-weight:500;">Online · Siap membantu SPP</p>
                </div>
                <button onclick="toggleChatbot()" style="background:transparent;border:none;color:#94a3b8;
                       cursor:pointer;width:28px;height:28px;border-radius:8px;display:flex;
                       align-items:center;justify-content:center;transition:all 0.2s;"
                       onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='white'"
                       onmouseout="this.style.background='transparent';this.style.color='#94a3b8'">
                    <i class="fa-solid fa-chevron-down" style="font-size:12px;"></i>
                </button>
            </div>

            <!-- Chat Body -->
            <div id="chatBody" style="flex:1; overflow-y:auto; padding:16px 16px 8px; background:#f8fafc; scroll-behavior:smooth;">
                <!-- Welcome -->
                <div class="chat-msg bot" style="display:flex;gap:8px;margin-bottom:16px;animation:chatMsgIn 0.4s ease;">
                    <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#10b981,#059669);
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                        <i class="fa-solid fa-robot" style="color:white;font-size:12px;"></i>
                    </div>
                    <div>
                        <div style="background:white;border-radius:4px 16px 16px 16px;padding:12px 14px;
                             max-width:260px;box-shadow:0 1px 4px rgba(0,0,0,0.04);border:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:13px;color:#334155;line-height:1.5;">
                                Halo <strong><?= htmlspecialchars($cb_nama_depan) ?></strong>! 👋<br>
                                Ada yang bisa saya bantu terkait <em>Administrasi SPP</em> hari ini?
                            </p>
                        </div>
                        <span style="font-size:9px;color:#94a3b8;margin-top:4px;display:block;padding-left:4px;">Asisten SPP · baru saja</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div id="chatQuickActions" style="padding:8px 16px 4px;background:white;border-top:1px solid #e2e8f0;flex-shrink:0;">
                <div style="display:flex; flex-wrap:wrap; gap:6px; padding-bottom:6px;">
                    <button onclick="sendQuick('Cek Tagihan')" class="cqb flex-shrink-0">💳 Tagihan</button>
                    <button onclick="sendQuick('Status Pembayaran')" class="cqb flex-shrink-0">📋 Status Bayar</button>
                    <button onclick="sendQuick('Cara Bayar SPP')" class="cqb flex-shrink-0">🔍 Cara Bayar</button>
                    <button onclick="sendQuick('Rincian Biaya')" class="cqb flex-shrink-0">📊 Detail Biaya</button>
                </div>
            </div>

            <!-- Input -->
            <div style="padding:10px 14px 14px;background:white;flex-shrink:0;">
                <form onsubmit="sendMessage(event)" style="display:flex;gap:8px;align-items:center;">
                    <input type="text" id="chatInput" placeholder="Ketik pertanyaan..." autocomplete="off"
                           style="flex:1;padding:10px 14px;border:1px solid #e2e8f0;border-radius:12px;
                                  font-size:13px;outline:none;transition:all 0.2s;background:#f8fafc;color:#1e293b;"
                           onfocus="this.style.borderColor='#10b981';this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)';this.style.background='white'"
                           onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none';this.style.background='#f8fafc'">
                    <button type="submit" id="chatSendBtn" style="width:38px;height:38px;border-radius:12px;border:none;cursor:pointer;
                            background:linear-gradient(135deg,#10b981,#059669);color:white;font-size:14px;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.2s;"
                            onmouseover="this.style.transform='scale(1.05)';this.style.boxShadow='0 4px 10px rgba(16,185,129,0.3)'"
                            onmouseout="this.style.transform='scale(1)';this.style.boxShadow='none'">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes floatBtn { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-4px);} }
    @keyframes chatPulseAnim { 0%,100%{opacity:1;transform:scale(1);} 50%{opacity:0.5;transform:scale(1.2);} }
    @keyframes chatMsgIn { from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:translateY(0);} }
    @keyframes typingBounce { 0%,80%,100%{transform:translateY(0);opacity:0.4;} 40%{transform:translateY(-4px);opacity:1;} }
    
    .cqb { background:white;border:1px solid #e2e8f0;color:#64748b;padding:6px 12px;border-radius:16px;
           font-size:11px;font-weight:600;cursor:pointer;transition:all 0.2s;white-space:nowrap; }
    .cqb:hover { background:#f0fdf4;color:#10b981;border-color:#10b981; }
    
    #chatBody::-webkit-scrollbar { width:4px; }
    #chatBody::-webkit-scrollbar-track { background:transparent; }
    #chatBody::-webkit-scrollbar-thumb { background:#cbd5e1;border-radius:4px; }
    
    .hide-scrollbar::-webkit-scrollbar { display:none; }
    .hide-scrollbar { -ms-overflow-style:none; scrollbar-width:none; }
    
    #chatToggleBtn:hover { transform:scale(1.05) translateY(-2px) !important;box-shadow:0 10px 25px rgba(16,185,129,0.4) !important;}
    
    .chat-link { color:#10b981;font-weight:600;text-decoration:none;border-bottom:1px solid transparent;transition:all 0.2s;}
    .chat-link:hover { border-bottom-color:#10b981;}
    .chat-tag { background:#f0fdf4;color:#16a34a;font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;border:1px solid #bbf7d0;}
    .chat-tag-red { background:#fef2f2;color:#dc2626;font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;border:1px solid #fecaca;}
    .chat-tag-yellow { background:#fffbeb;color:#d97706;font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;border:1px solid #fde68a;}
</style>

<script>
const D = {
    nama: <?= json_encode($cb_nama_depan) ?>,
    siswa: <?= $cb_siswa ? json_encode(['nama'=>$cb_siswa['nama_siswa'],'kelas'=>$cb_siswa['kelas']]) : 'null' ?>,
    tunggakan: <?= $cb_total_tunggakan ?>,
    banding: <?= $cb_banding_menunggu ?>
};

let chatOpen = false, firstOpen = true;

function toggleChatbot() {
    chatOpen = !chatOpen;
    const w = document.getElementById('chatWindow'), o = document.getElementById('chatIconOpen'),
          c = document.getElementById('chatIconClose'), p = document.getElementById('chatPulse');
    if (chatOpen) {
        w.style.display = 'flex';
        requestAnimationFrame(() => { w.style.transform = 'scale(1) translateY(0)'; w.style.opacity = '1'; });
        o.style.display = 'none'; c.style.display = 'block';
        if(p) p.style.display = 'none';
        
        setTimeout(() => { document.getElementById('chatInput').focus(); }, 300);
        
        if (firstOpen) {
            firstOpen = false;
            let introText = '';
            if (D.tunggakan > 0) {
                introText = `Saya mendeteksi ada tagihan jatuh tempo sebesar <strong>Rp ${new Intl.NumberFormat('id-ID').format(D.tunggakan)}</strong> untuk ananda ${D.siswa?.nama||''}.<br><br>Ingin saya pandu pembayarannya?`;
            } else if (D.banding > 0) {
                introText = `Ada <strong>${D.banding} pembayaran</strong> Anda yang sedang menunggu verifikasi admin.`;
            } else {
                return; // Default greeting is enough
            }
            setTimeout(() => { showTyping(); }, 500);
            setTimeout(() => { removeTyping(); botReply(introText); }, 1500);
        }
    } else {
        w.style.transform = 'scale(0.9) translateY(20px)'; w.style.opacity = '0';
        setTimeout(() => { w.style.display = 'none'; }, 350);
        o.style.display = 'block'; c.style.display = 'none';
    }
}

function sendQuick(text) {
    addUserBubble(text);
    setTimeout(() => { analyzeAndReply(text); }, 300);
}

function sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('chatInput');
    const text = input.value.trim();
    if (!text) return;
    addUserBubble(text);
    input.value = '';
    setTimeout(() => { analyzeAndReply(text); }, 300);
}

function analyzeAndReply(text) {
    showTyping();
    const t = text.toLowerCase();
    let reply = '';

    const checks = {
        tagihan: /tagihan|tunggakan|hutang|belum lunas|cek bayar/,
        bayar: /cara|bayar|transfer|upload|kirim/,
        status: /status|verifikasi|banding|disetujui|menunggu/,
        jadwal: /jadwal|kapan|jatuh tempo|batas|deadline/,
        profil: /siswa|anak|nama|kelas|data/,
        rincian: /rincian|detail|komponen|biaya|spp/
    };

    if (checks.tagihan.test(t)) {
        if (!D.siswa) {
            reply = `Oops, akun Bapak/Ibu belum ditautkan dengan data siswa. Mohon hubungi Tata Usaha sekolah terlebih dahulu ya. 🙏`;
        } else if (D.tunggakan > 0) {
            reply = `Saat ini terdapat <strong>Tunggakan Tagihan</strong> sebesar <span class="chat-tag-red">Rp ${new Intl.NumberFormat('id-ID').format(D.tunggakan)}</span>.<br><br>👉 Silakan ke menu <a href="tagihan.php" class="chat-link">Tagihan & Bayar</a> untuk proses pelunasan.`;
        } else {
            reply = `Luar Biasa! 🎉<br>Seluruh tagihan ananda ${D.siswa.nama} <strong>sudah Lunas</strong>. Tidak ada tunggakan saat ini.`;
        }
    } else if (checks.bayar.test(t)) {
        reply = `<strong>Cara Pembayaran SPP:</strong><br>1. Masuk ke halaman <a href="tagihan.php" class="chat-link">Tagihan & Bayar</a><br>2. Pilih tagihan & klik tombol biru <strong>Bayar & Upload</strong><br>3. Transfer sesuai nominal ke rekening sekolah<br>4. Unggah foto bukti transfer tersebut<br><br>Admin akan mengecek bukti dalam 1-2 hari kerja. 😊`;
    } else if (checks.status.test(t)) {
        if (D.banding > 0) {
            reply = `Saat ini ada <span class="chat-tag-yellow">${D.banding} pengajuan bayar</span> yang sedang menunggu verifikasi admin 😉<br><br>Biasanya butuh 1-2 hari kerja ya Bapak/Ibu. 👉 Cek progresnya di menu <a href="form_banding.php" class="chat-link">Riwayat Pengajuan</a>.`;
        } else {
            reply = `Belum ada pembayaran yang sedang menunggu verifikasi.<br><br>Jika baru saja transfer, jangan lupa upload buktinya di menu <a href="tagihan.php" class="chat-link">Tagihan</a> ya! 📝`;
        }
    } else if (checks.rincian.test(t)) {
        reply = `Bapak/Ibu bisa melihat rincian biaya komplit per semester (termasuk SPP dan biaya lainnya) langsung pada halaman <a href="rincian_biaya.php" class="chat-link">Rincian Biaya</a>. 📊`;
    } else if (/makasih|terima kasih|thanks/.test(t)) {
        reply = `Sama-sama Bapak/Ibu! 😊 Dengan senang hati saya membantu. Jangan sungkan menanyakan hal lain ya!`;
    } else if (/halo|hi|pagi|siang|sore|malam/.test(t)) {
        reply = `Halo Bapak/Ibu ${D.nama}! 👋<br>Ada keperluan administrasi SPP yang bisa saya obrolkan hari ini?`;
    } else {
        reply = `Hmm, sepertinya saya kurang mengerti konteks pertanyaannya 🤔<br><br>Saya bisa membantu mengecek <strong>Tagihan</strong>, menjelaskan <strong>Cara Bayar</strong>, atau menginfokan <strong>Status Verifikasi</strong>. Boleh diperjelas lagi pertanyaannya? 😊`;
    }

    setTimeout(() => {
        removeTyping();
        botReply(reply);
    }, 800 + Math.random() * 500);
}

function addUserBubble(text) {
    const body = document.getElementById('chatBody');
    const div = document.createElement('div');
    div.style.cssText = 'display:flex;justify-content:flex-end;margin-bottom:16px;animation:chatMsgIn 0.3s ease;';
    div.innerHTML = `<div>
        <div style="background:#10b981;border-radius:18px 4px 18px 18px;padding:10px 14px;max-width:240px;box-shadow:0 1px 2px rgba(0,0,0,0.05);">
            <p style="margin:0;font-size:13px;color:white;line-height:1.5;">${text.replace(/</g,"&lt;")}</p>
        </div>
    </div>`;
    body.appendChild(div);
    scroll();
}

function botReply(html) {
    const body = document.getElementById('chatBody');
    const div = document.createElement('div');
    div.style.cssText = 'display:flex;gap:8px;margin-bottom:16px;animation:chatMsgIn 0.4s ease;';
    div.innerHTML = `
        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#10b981,#059669);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
            <i class="fa-solid fa-robot" style="color:white;font-size:12px;"></i>
        </div>
        <div>
            <div style="background:white;border-radius:4px 16px 16px 16px;padding:12px 14px;
                 max-width:260px;box-shadow:0 1px 4px rgba(0,0,0,0.04);border:1px solid #e2e8f0;">
                <div style="margin:0;font-size:13px;color:#334155;line-height:1.6;">${html}</div>
            </div>
            <span style="font-size:9px;color:#94a3b8;margin-top:4px;display:block;padding-left:4px;">Asisten SPP · baru saja</span>
        </div>`;
    body.appendChild(div);
    scroll();
}

function showTyping() {
    const body = document.getElementById('chatBody');
    const div = document.createElement('div');
    div.id = 'typingIndicator';
    div.style.cssText = 'display:flex;gap:8px;margin-bottom:16px;animation:chatMsgIn 0.3s ease;';
    div.innerHTML = `
        <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#10b981,#059669);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
            <i class="fa-solid fa-robot" style="color:white;font-size:12px;"></i>
        </div>
        <div style="background:white;border-radius:4px 16px 16px 16px;padding:12px 16px;
             border:1px solid #e2e8f0;display:flex;gap:4px;align-items:center;">
            <span style="width:6px;height:6px;background:#10b981;border-radius:50%;animation:typingBounce 1s infinite;animation-delay:0s;"></span>
            <span style="width:6px;height:6px;background:#10b981;border-radius:50%;animation:typingBounce 1s infinite;animation-delay:0.15s;"></span>
            <span style="width:6px;height:6px;background:#10b981;border-radius:50%;animation:typingBounce 1s infinite;animation-delay:0.3s;"></span>
        </div>`;
    body.appendChild(div);
    scroll();
}

function removeTyping() { const t = document.getElementById('typingIndicator'); if(t) t.remove(); }
function scroll() { const b = document.getElementById('chatBody'); setTimeout(()=>b.scrollTop=b.scrollHeight, 50); }
</script>
