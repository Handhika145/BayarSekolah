<?php
session_start();
require '../config/koneksi.php';

// Proteksi halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'walimurid') {
    header('Location: ../login.php');
    exit();
}

$id_walimurid = $_SESSION['id_user'];

// Ambil data wali murid
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_walimurid' AND role = 'walimurid'");
$currentUser = mysqli_fetch_assoc($query_user);

if (!$currentUser) {
    die("Data user tidak ditemukan. Silakan hubungi administrator.");
}

$foto_profil_url = (!empty($currentUser['foto_profil']) && file_exists('../uploads/profil/' . $currentUser['foto_profil'])) 
    ? '../uploads/profil/' . $currentUser['foto_profil'] 
    : 'default';

// --- NOTIFIKASI WALIMURID (START) ---
$notifikasi_wali = [];
$total_notif_baru = 0;

$q_siswa_notif = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_walimurid = '$id_walimurid' LIMIT 1");
$siswa_notif_data = mysqli_fetch_assoc($q_siswa_notif);

if ($siswa_notif_data) {
    $id_siswa_notif = $siswa_notif_data['id_siswa'];

    // 1. Cek Banding (Selesai / Ditolak)
    $q_banding_notif = mysqli_query($koneksi, "SELECT b.*, t.jenis_tagihan, t.bulan, t.tahun FROM banding b JOIN tagihan t ON b.id_tagihan = t.id_tagihan WHERE b.id_walimurid = '$id_walimurid' AND b.status_banding IN ('Selesai', 'Ditolak') AND b.is_read_walimurid = 0 ORDER BY b.id_banding DESC LIMIT 3");
    while($row = mysqli_fetch_assoc($q_banding_notif)) {
        if ($row['status_banding'] == 'Selesai') {
            $notifikasi_wali[] = ['ikon' => 'fa-circle-check', 'warna' => 'text-green-500', 'bg' => 'bg-green-50', 'judul' => 'Pembayaran Terverifikasi!', 'pesan' => 'Bukti bayar '.$row['jenis_tagihan'].' '. $row['bulan'].' '.$row['tahun'].' disetujui.', 'waktu' => date('d M Y', strtotime($row['tgl_pengajuan'])), 'link' => 'read_notif.php?tipe=banding&id='.$row['id_banding']];
        } else {
            $notifikasi_wali[] = ['ikon' => 'fa-circle-xmark', 'warna' => 'text-red-500', 'bg' => 'bg-red-50', 'judul' => 'Verifikasi Ditolak', 'pesan' => 'Bukti bayar '.$row['jenis_tagihan'].' '. $row['bulan'].' '.$row['tahun'].' ditolak. Lengkapi ulang.', 'waktu' => date('d M Y', strtotime($row['tgl_pengajuan'])), 'link' => 'read_notif.php?tipe=banding&id='.$row['id_banding']];
        }
        $total_notif_baru++;
    }

    // 2. Tagihan Baru / Belum Lunas
    $q_tagihan_notif = mysqli_query($koneksi, "SELECT * FROM tagihan WHERE id_siswa = '$id_siswa_notif' AND status = 'Belum Lunas' AND is_read_walimurid = 0 ORDER BY id_tagihan DESC");
    $ct_notif_rincian = 0;
    while($row = mysqli_fetch_assoc($q_tagihan_notif)) {
        if(isJatuhTempo($row['bulan'], $row['tahun'])) {
            if($ct_notif_rincian < 3) {
                $notifikasi_wali[] = ['ikon' => 'fa-file-invoice', 'warna' => 'text-yellow-500', 'bg' => 'bg-yellow-50', 'judul' => 'Tagihan Belum Lunas', 'pesan' => 'Ada tagihan '.$row['jenis_tagihan'].' '. $row['bulan'].' '.$row['tahun'].' sebesar Rp ' . number_format($row['nominal'],0,',','.'), 'waktu' => 'Menunggu Pembayaran', 'link' => 'read_notif.php?tipe=tagihan&id='.$row['id_tagihan']];
            }
            $total_notif_baru++;
            $ct_notif_rincian++;
        }
    }
}
// --- NOTIFIKASI WALIMURID (END) ---

$nama_sekolah = $_SESSION['nama_sekolah'];

// Ambil tahun pelajaran secara dinamis berdasarkan bulan saat ini
$bulan_sekarang = date('n'); // 1-12
$tahun_sekarang = date('Y');
if ($bulan_sekarang >= 7) {
    $tahun_pelajaran = $tahun_sekarang . '/' . ($tahun_sekarang + 1);
} else {
    $tahun_pelajaran = ($tahun_sekarang - 1) . '/' . $tahun_sekarang;
}

// Ambil Master Biaya
$id_sekolah_wali = $siswa_notif_data['id_sekolah'] ?? 0;
$kelas_wali = $siswa_notif_data['kelas'] ?? '';
$q_master = mysqli_query($koneksi, "SELECT * FROM master_biaya WHERE id_sekolah = '$id_sekolah_wali' AND (kelas = 'Semua' OR kelas = '$kelas_wali') ORDER BY id_biaya ASC");
$biaya_lainnya = [];
$spp_ganjil = 0;
$spp_genap = 0;
while($mb = mysqli_fetch_assoc($q_master)) {
    if($mb['nama_biaya'] == 'SPP Semester Ganjil') {
        $spp_ganjil = $mb['nominal'];
    } elseif($mb['nama_biaya'] == 'SPP Semester Genap') {
        $spp_genap = $mb['nominal'];
    } else {
        $biaya_lainnya[] = $mb;
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Biaya - Portal Orang Tua</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
        <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Poppins', system-ui, -apple-system, sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
    </style>
</head>
<body class="bg-[#f4f7f6] flex font-sans min-h-screen text-gray-800">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#1e293b] text-gray-300 flex flex-col fixed h-full z-20 shadow-xl">
        <div class="h-20 flex items-center px-6 border-b border-gray-700">
            <div class="bg-white p-1.5 rounded-full mr-3 shrink-0">
                <i class="fa-solid fa-graduation-cap text-green-600 text-xl"></i>
            </div>
            <div class="overflow-hidden">
                <h2 class="text-sm font-bold text-white leading-tight truncate w-40" title="<?= htmlspecialchars($nama_sekolah); ?>">
                    <?= htmlspecialchars($nama_sekolah); ?>
                </h2>
                <h2 class="text-[10px] font-bold text-green-400 uppercase tracking-widest mt-0.5">Portal Wali Murid</h2>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto py-6 custom-scrollbar">
            <nav class="px-4 space-y-1">
                <a href="dashboard.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-border-all w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="tagihan.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-credit-card w-6"></i>
                    <span>Tagihan & Bayar</span>
                </a>
                <a href="rincian_biaya.php" class="flex items-center px-4 py-3 bg-[#10b981] text-white rounded-lg font-medium shadow-md transition-colors">
                    <i class="fa-solid fa-list-check w-6"></i>
                    <span>Rincian Biaya</span>
                </a>
                <a href="form_banding.php" class="flex items-center px-4 py-3 hover:bg-gray-800 text-white rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-clock-rotate-left w-6"></i>
                    <span>Riwayat Pengajuan</span>
                </a>
                <a href="profil.php"
                    class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-smooth">
                    <i class="fa-solid fa-user-gear w-6"></i>
                    <span>Profil Saya</span>
                </a>
            </nav>
        </div>
        
        <div class="p-4 border-t border-gray-700">
            <div class="flex items-center gap-3 mb-4 px-2">
                <?php if($foto_profil_url == 'default'): ?>
                    <div class="h-10 w-10 rounded-full bg-gray-700 flex items-center justify-center border border-gray-600">
                        <i class="fa-solid fa-user text-gray-300"></i>
                    </div>
                <?php else: ?>
                    <img src="<?= $foto_profil_url ?>" alt="Profil" class="h-10 w-10 rounded-full object-cover border border-gray-600">
                <?php endif; ?>
                <div class="flex-1 overflow-hidden">
                    <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($currentUser['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-green-400">Wali Murid</p>
                </div>
            </div>
            <a href="../auth/logout.php" class="flex items-center justify-center gap-2 w-full py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors text-sm font-bold shadow-md">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 ml-64 flex flex-col min-w-0">
        
        <!-- HEADER -->
        <header class="h-20 bg-white flex items-center justify-between px-8 shadow-sm z-10 sticky top-0">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold text-gray-800 uppercase tracking-wide">Rincian Estimasi Biaya</h1>
                <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full border border-blue-200">
                    TA. <?php echo $tahun_pelajaran; ?>
                </span>
            </div>
            
            <div class="flex items-center space-x-6 relative">
                <!-- NOTIFICATION ICON -->
                <div class="relative group cursor-pointer">
                    <button type="button" class="relative p-2 text-gray-500 hover:text-green-600 transition-colors focus:outline-none" onclick="toggleNotif()">
                        <i class="fa-regular fa-bell text-xl"></i>
                        <?php if($total_notif_baru > 0): ?>
                            <span class="absolute top-1 right-2 inline-flex items-center justify-center w-4 h-4 text-[9px] font-bold text-white bg-red-500 rounded-full border-2 border-white"><?= $total_notif_baru > 9 ? '9+' : $total_notif_baru; ?></span>
                        <?php endif; ?>
                    </button>

                    <!-- DROPDOWN NOTIFIKASI -->
                    <div id="dropdownNotif" class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-100 hidden z-50 transform origin-top-right transition-all">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-xl">
                            <h4 class="font-bold text-gray-800"><i class="fa-solid fa-bell text-green-500 mr-2"></i> Notifikasi</h4>
                            <span class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full font-bold"><?= $total_notif_baru; ?> Baru</span>
                        </div>
                        <div class="max-h-80 overflow-y-auto content-scrollbar py-2">
                            <?php if(empty($notifikasi_wali)): ?>
                                <div class="px-4 py-8 text-center">
                                    <i class="fa-regular fa-bell-slash text-3xl text-gray-300 mb-2"></i>
                                    <p class="text-xs text-gray-500 mt-2">Belum ada notifikasi.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($notifikasi_wali as $notif): ?>
                                    <a href="<?= $notif['link']; ?>" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-50 last:border-0 transition-colors">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 <?= $notif['bg']; ?> p-2 rounded-lg">
                                                <i class="fa-solid <?= $notif['ikon']; ?> <?= $notif['warna']; ?>"></i>
                                            </div>
                                            <div class="ml-3 w-0 flex-1">
                                                <p class="text-sm font-bold text-gray-800 leading-tight"><?= $notif['judul']; ?></p>
                                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2"><?= $notif['pesan']; ?></p>
                                                <p class="text-[10px] text-gray-400 mt-1 font-medium"><i class="fa-regular fa-clock mr-1"></i> <?= $notif['waktu']; ?></p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <a href="profil.php" class="flex items-center bg-green-50 px-4 py-2 rounded-full border border-green-100 transition hover:bg-green-100">
                    <?php if($foto_profil_url == 'default'): ?>
                        <div class="h-6 w-6 rounded-full bg-green-200 flex items-center justify-center mr-2">
                            <i class="fa-regular fa-user text-green-700 text-xs"></i>
                        </div>
                    <?php else: ?>
                        <img src="<?= $foto_profil_url ?>" alt="Profil" class="h-6 w-6 rounded-full object-cover mr-2 border border-green-300">
                    <?php endif; ?>
                    <span class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </div>
        </header>

        <div class="p-8">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Daftar Rincian Biaya Sekolah</h2>
                <p class="text-gray-500 mt-1">Berikut adalah estimasi biaya pendaftaran dan administrasi SPP selama dua semester untuk panduan persiapan pembayaran.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- KIRI: SPP 2 SEMESTER -->
                <div class="space-y-6">
                    
                    <!-- Semester Ganjil -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden relative">
                        <div class="absolute top-0 left-0 w-2 h-full bg-blue-500"></div>
                        <div class="p-6 pl-8">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                                    <i class="fa-solid fa-calendar-days text-blue-500 mr-2"></i> SPP Semester Ganjil
                                </h3>
                                <span class="text-xs font-semibold bg-gray-100 text-gray-500 px-2 py-1 rounded">Juli - Desember</span>
                            </div>
                            <div class="space-y-3">
                                <?php 
                                $bulan_ganjil = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                foreach($bulan_ganjil as $bg):
                                ?>
                                <div class="flex justify-between items-center border-b border-gray-50 pb-2 last:border-0 last:pb-0">
                                    <span class="text-gray-600 font-medium">Bulan <?= $bg; ?></span>
                                    <?php if($spp_ganjil > 0): ?>
                                        <span class="text-gray-800 font-bold bg-blue-50 text-blue-700 px-2 rounded">Rp <?= number_format($spp_ganjil,0,',','.'); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-800 font-bold bg-green-50 text-green-600 px-2 rounded">- Menyesuaikan Tagihan -</span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Semester Genap -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden relative">
                        <div class="absolute top-0 left-0 w-2 h-full bg-indigo-500"></div>
                        <div class="p-6 pl-8">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                                    <i class="fa-solid fa-calendar-check text-indigo-500 mr-2"></i> SPP Semester Genap
                                </h3>
                                <span class="text-xs font-semibold bg-gray-100 text-gray-500 px-2 py-1 rounded">Januari - Juni</span>
                            </div>
                            <div class="space-y-3">
                                <?php 
                                $bulan_genap = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
                                foreach($bulan_genap as $bgen):
                                ?>
                                <div class="flex justify-between items-center border-b border-gray-50 pb-2 last:border-0 last:pb-0">
                                    <span class="text-gray-600 font-medium">Bulan <?= $bgen; ?></span>
                                    <?php if($spp_genap > 0): ?>
                                        <span class="text-gray-800 font-bold bg-indigo-50 text-indigo-700 px-2 rounded">Rp <?= number_format($spp_genap,0,',','.'); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-800 font-bold bg-green-50 text-green-600 px-2 rounded">- Menyesuaikan Tagihan -</span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- KANAN: BIAYA LAINNYA -->
                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden relative">
                        <div class="absolute top-0 left-0 w-2 h-full bg-[#10b981]"></div>
                        <div class="p-6 pl-8">
                            <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                                <i class="fa-solid fa-money-bills text-green-500 mr-2"></i> Rincian Pembayaran Lainnya
                            </h3>
                            
                            <ul class="space-y-4">
                                <?php if(count($biaya_lainnya) > 0): ?>
                                    <?php 
                                    $icons = ['fa-pen-to-square text-orange-500', 'fa-file-pen text-indigo-500', 'fa-id-card text-blue-600', 'fa-rotate text-purple-500', 'fa-book text-emerald-500', 'fa-money-bill-wave text-green-500'];
                                    $i = 0;
                                    foreach($biaya_lainnya as $bl): 
                                        $ikon_terpilih = $icons[$i % count($icons)];
                                        $i++;
                                    ?>
                                    <li class="flex flex-col sm:flex-row sm:justify-between sm:items-center bg-gray-50 p-3 rounded-lg border border-gray-100 gap-2">
                                        <div class="flex items-center">
                                            <div class="bg-white p-2 rounded shadow-sm mr-3">
                                                <i class="fa-solid <?= $ikon_terpilih; ?>"></i>
                                            </div>
                                            <span class="font-bold text-gray-700"><?= $bl['nama_biaya']; ?></span>
                                        </div>
                                        <span class="text-green-700 bg-green-100 px-3 py-1 rounded-full font-bold text-sm text-right whitespace-nowrap">Rp <?= number_format($bl['nominal'],0,',','.'); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="text-center py-6 text-gray-500">
                                        <i class="fa-solid fa-clipboard-list text-3xl mb-2 text-gray-300 block"></i>
                                        Belum ada rincian biaya yang dirilis.
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-xl flex items-start shadow-sm">
                        <i class="fa-solid fa-circle-info mt-1 mr-3 text-yellow-500"></i>
                        <div>
                            <p class="font-bold">Informasi Estimasi Biaya</p>
                            <p class="text-sm mt-1">Biaya aktual dapat berubah menyesuaikan kebijakan sekolah terbaru. Harga final yang harus Anda bayarkan adalah nominal yang tertera pada menu <a href="tagihan.php" class="underline font-bold text-yellow-900 hover:text-yellow-600">Tagihan & Bayar</a>.</p>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </main>

    <!-- SCRIPT NOTIFIKASI -->
    <script>
        function toggleNotif() {
            const dropdown = document.getElementById('dropdownNotif');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('dropdownNotif');
            if(!dropdown) return;
            const button = dropdown.previousElementSibling;
            
            if (button && !button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>

    <!-- CHATBOT WIDGET -->
    <?php include 'chatbot.php'; ?>

</body>
</html>
