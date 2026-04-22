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
    $ct_notif = 0;
    while($row = mysqli_fetch_assoc($q_tagihan_notif)) {
        if(isJatuhTempo($row['bulan'], $row['tahun'])) {
            if($ct_notif < 3) {
                $notifikasi_wali[] = ['ikon' => 'fa-file-invoice', 'warna' => 'text-yellow-500', 'bg' => 'bg-yellow-50', 'judul' => 'Tagihan Belum Lunas', 'pesan' => 'Ada tagihan '.$row['jenis_tagihan'].' '. $row['bulan'].' '.$row['tahun'].' sebesar Rp ' . number_format($row['nominal'],0,',','.'), 'waktu' => 'Menunggu Pembayaran', 'link' => 'read_notif.php?tipe=tagihan&id='.$row['id_tagihan']];
            }
            $total_notif_baru++;
            $ct_notif++;
        }
    }
}
// --- NOTIFIKASI WALIMURID (END) ---

$nama_sekolah = $_SESSION['nama_sekolah'];
$pesan = '';

// --- LOGIKA UPLOAD BUKTI PEMBAYARAN BANDING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajukan_banding'])) {
    $id_tagihan = $_POST['id_tagihan'];
    $pesan_banding = mysqli_real_escape_string($koneksi, $_POST['pesan_banding']);
    
    // Proses File Upload
    $nama_file = $_FILES['bukti_transfer']['name'];
    $tmp_file = $_FILES['bukti_transfer']['tmp_name'];
    $ukuran_file = $_FILES['bukti_transfer']['size'];
    $ext_diizinkan = ['jpg', 'jpeg', 'png', 'pdf'];
    $ext_file = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

    if (in_array($ext_file, $ext_diizinkan)) {
        if ($ukuran_file <= 2000000) { // Maksimal 2MB
            $nama_baru = time() . '_' . uniqid() . '.' . $ext_file;
            $path_tujuan = '../uploads/';
            
            // Buat folder uploads jika belum ada
            if (!is_dir($path_tujuan)) {
                mkdir($path_tujuan, 0777, true);
            }

            if (move_uploaded_file($tmp_file, $path_tujuan . $nama_baru)) {
                $query_upload = "INSERT INTO banding (id_walimurid, id_tagihan, pesan_banding, bukti_transfer, status_banding) 
                                 VALUES ('$id_walimurid', '$id_tagihan', '$pesan_banding', '$nama_baru', 'Menunggu')";
                
                if (mysqli_query($koneksi, $query_upload)) {
                    $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm font-medium'>Berhasil! Bukti pembayaran telah dikirim dan menunggu verifikasi Admin.</div>";
                } else {
                    $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm'>Gagal menyimpan ke database.</div>";
                }
            } else {
                $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm'>Gagal mengunggah file ke server.</div>";
            }
        } else {
            $pesan = "<div class='bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 mb-6 rounded shadow-sm'>Ukuran file terlalu besar! Maksimal 2MB.</div>";
        }
    } else {
        $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm'>Format file tidak diizinkan! Hanya JPG, PNG, atau PDF.</div>";
    }
}

// --- AMBIL DATA RIWAYAT PENGAJUAN (BANDING) ---
$q_riwayat = mysqli_query($koneksi, "
    SELECT b.*, t.jenis_tagihan, t.bulan, t.tahun, t.nominal 
    FROM banding b
    JOIN tagihan t ON b.id_tagihan = t.id_tagihan
    WHERE b.id_walimurid = '$id_walimurid'
    ORDER BY b.tgl_pengajuan DESC
");

// --- AMBIL DATA TAGIHAN BELUM LUNAS UNTUK DROPDOWN MODAL ---
$q_siswa = mysqli_query($koneksi, "SELECT id_siswa FROM siswa WHERE id_walimurid = '$id_walimurid' LIMIT 1");
$siswa = mysqli_fetch_assoc($q_siswa);

$dropdown_tagihan = [];
if ($siswa) {
    $id_siswa = $siswa['id_siswa'];
    $q_tagihan_blm = mysqli_query($koneksi, "
        SELECT t.id_tagihan, t.jenis_tagihan, t.bulan, t.tahun, t.nominal 
        FROM tagihan t 
        WHERE t.id_siswa = '$id_siswa' AND t.status = 'Belum Lunas'
        AND t.id_tagihan NOT IN (
            SELECT id_tagihan FROM banding WHERE id_walimurid = '$id_walimurid' AND status_banding IN ('Menunggu', 'Diproses')
        )
        ORDER BY t.tahun DESC, t.bulan ASC
    ");
    while ($row_t = mysqli_fetch_assoc($q_tagihan_blm)) {
        $row_t['is_jatuh_tempo'] = isJatuhTempo($row_t['bulan'], $row_t['tahun']);
        $dropdown_tagihan[] = $row_t;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pengajuan - Portal Orang Tua</title>
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
                <h2 class="text-sm font-bold text-white leading-tight truncate w-40" title="<?= $nama_sekolah; ?>">
                    <?= $nama_sekolah; ?>
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
                <a href="rincian_biaya.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-list-check w-6"></i>
                    <span>Rincian Biaya</span>
                </a>
                <a href="form_banding.php" class="flex items-center px-4 py-3 bg-[#10b981] text-white rounded-lg font-medium shadow-md transition-colors">
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
            <h1 class="text-xl font-bold text-gray-800 uppercase tracking-wide">Riwayat Pengajuan & Pembayaran</h1>
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
            <?= $pesan; ?>
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-6 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Status Bukti Pembayaran</h2>
                    <p class="text-gray-500 mt-1">Pantau proses verifikasi bukti transfer yang sudah Anda unggah di sini.</p>
                </div>
                <button onclick="openModal('modalAjukanBanding')" class="bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-5 rounded-lg font-bold shadow-md transition flex items-center text-sm">
                    <i class="fa-solid fa-plus mr-2"></i> Ajukan Bukti Pembayaran
                </button>
            </div>

            <!-- TABEL RIWAYAT BANDING -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm font-semibold border-b border-gray-200">
                                <th class="py-4 px-6">Tgl. Upload</th>
                                <th class="py-4 px-6">Rincian Tagihan</th>
                                <th class="py-4 px-6">Pesan Anda</th>
                                <th class="py-4 px-6 text-center">Bukti Transfer</th>
                                <th class="py-4 px-6">Status Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                            <?php 
                            if(mysqli_num_rows($q_riwayat) > 0):
                                while($row = mysqli_fetch_assoc($q_riwayat)): 
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-6 font-medium text-gray-600">
                                    <?= date('d M Y', strtotime($row['tgl_pengajuan'])); ?><br>
                                    <span class="text-xs text-gray-400"><?= date('H:i', strtotime($row['tgl_pengajuan'])); ?> WIB</span>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="font-bold text-gray-800"><?= $row['jenis_tagihan']; ?> - <?= $row['bulan']; ?> <?= $row['tahun']; ?></p>
                                    <p class="font-bold text-green-600 mt-0.5">Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></p>
                                </td>
                                <td class="py-4 px-6 max-w-[200px]">
                                    <?php if(!empty($row['pesan_banding'])): ?>
                                        <p class="text-xs italic text-gray-500 bg-gray-50 p-2 rounded border border-gray-100">"<?= htmlspecialchars($row['pesan_banding']); ?>"</p>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic text-xs">- Tidak ada pesan -</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <button onclick="lihatBukti('../uploads/<?= $row['bukti_transfer']; ?>')" class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white py-1.5 px-3 rounded text-xs font-bold transition border border-blue-200 shadow-sm">
                                        <i class="fa-solid fa-image mr-1"></i> Lihat
                                    </button>
                                </td>
                                <td class="py-4 px-6">
                                    <?php 
                                        if($row['status_banding'] == 'Menunggu') {
                                            echo '<span class="bg-yellow-100 text-yellow-700 py-1.5 px-3 rounded-md text-xs font-bold border border-yellow-200 animate-pulse"><i class="fa-solid fa-clock mr-1"></i> Menunggu Antrean</span>';
                                        } else if($row['status_banding'] == 'Diproses') {
                                            echo '<span class="bg-blue-100 text-blue-700 py-1.5 px-3 rounded-md text-xs font-bold border border-blue-200"><i class="fa-solid fa-magnifying-glass mr-1"></i> Sedang Dicek</span>';
                                        } else if($row['status_banding'] == 'Selesai') {
                                            echo '<span class="bg-green-100 text-green-700 py-1.5 px-3 rounded-md text-xs font-bold border border-green-200"><i class="fa-solid fa-check-double mr-1"></i> Lunas & Disetujui</span>';
                                        } else {
                                            echo '<div class="flex flex-col items-start gap-1">';
                                            echo '<span class="bg-red-100 text-red-700 py-1.5 px-3 rounded-md text-xs font-bold border border-red-200"><i class="fa-solid fa-xmark mr-1"></i> Ditolak</span>';
                                            echo '<a href="tagihan.php" class="text-[10px] text-blue-600 hover:underline font-semibold mt-1"><i class="fa-solid fa-rotate-right mr-1"></i>Upload Ulang</a>';
                                            echo '</div>';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                            <tr><td colspan="5" class="text-center py-12 text-gray-500"><i class="fa-solid fa-file-invoice text-4xl block mb-3 text-gray-300"></i> Anda belum memiliki riwayat pengajuan bukti pembayaran.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-info text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-800 font-medium">Informasi Penting</p>
                        <p class="text-xs text-blue-600 mt-1">Jika status pengajuan Anda <strong>Ditolak</strong>, kemungkinan gambar bukti transfer tidak terbaca atau nominal tidak sesuai. Anda dapat melakukan pembayaran dan mengunggah ulang bukti melalui menu <a href="tagihan.php" class="font-bold underline hover:text-blue-800">Tagihan & Bayar</a>.</p>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- ================= MODAL LIHAT BUKTI ================= -->
    <div id="modalBukti" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex items-center justify-center z-50 transition-opacity p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col" id="modalBuktiContent">
            <div class="bg-[#1e293b] p-4 flex justify-between items-center text-white">
                <h3 class="font-bold"><i class="fa-solid fa-image mr-2"></i> Bukti Transfer Saya</h3>
                <button onclick="closeModal('modalBukti')" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <div class="p-4 bg-gray-200 flex justify-center items-center min-h-[300px]">
                <img id="imgBukti" src="" alt="Bukti Transfer" class="max-h-[70vh] object-contain rounded shadow-sm">
            </div>
        </div>
    </div>

    <!-- ================= MODAL AJUKAN BANDING BARU ================= -->
    <div id="modalAjukanBanding" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex items-center justify-center z-50 transition-opacity p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col" id="modalAjukanBandingContent">
            <div class="bg-blue-600 p-4 flex justify-between items-center text-white">
                <h3 class="font-bold"><i class="fa-solid fa-upload mr-2"></i> Upload Bukti Pembayaran Baru</h3>
                <button onclick="closeModal('modalAjukanBanding')" class="text-blue-200 hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data" class="p-6">
                <?php if(empty($dropdown_tagihan)): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4 text-sm text-yellow-800 rounded">
                        <p><i class="fa-solid fa-circle-info mr-1"></i> Tidak ada tagihan yang bisa diajukan banding (semua sudah lunas atau sedang diproses).</p>
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Pilih Tagihan <span class="text-red-500">*</span></label>
                        <select name="id_tagihan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white">
                            <option value="">-- Pilih Tagihan Belum Lunas --</option>
                            <?php foreach($dropdown_tagihan as $dt): ?>
                                <option value="<?= $dt['id_tagihan'] ?>">
                                    <?= $dt['jenis_tagihan'] ?> - <?= $dt['bulan'] ?> <?= $dt['tahun'] ?> (Rp <?= number_format($dt['nominal'],0,',','.') ?>) <?= !$dt['is_jatuh_tempo'] ? '[Belum Jatuh Tempo]' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Pesan Tambahan (Opsional)</label>
                        <input type="text" name="pesan_banding" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition" placeholder="Contoh: Sudah ditransfer dari bank BRI a.n Budi">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Upload Bukti Transfer / Kwitansi <span class="text-red-500">*</span></label>
                        <input type="file" name="bukti_transfer" required accept=".jpg, .jpeg, .png, .pdf" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-[11px] text-gray-500 mt-1">Format: JPG, PNG, atau PDF. Maksimal ukuran 2MB.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal('modalAjukanBanding')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition text-sm">Batal</button>
                        <button type="submit" name="ajukan_banding" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-md transition text-sm flex items-center">
                            <i class="fa-solid fa-paper-plane mr-2"></i> Ajukan Bukti
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- SCRIPT -->
    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            const content = document.getElementById(modalId + 'Content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            const content = document.getElementById(modalId + 'Content');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        function lihatBukti(urlGambar) {
            const imgEl = document.getElementById('imgBukti');
            imgEl.src = urlGambar;
            
            imgEl.onerror = function() {
                this.src = 'https://placehold.co/600x400/e2e8f0/475569?text=Gagal+Memuat+Gambar';
            };
            openModal('modalBukti');
        }
    </script>

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