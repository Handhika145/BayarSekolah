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
    $ct_notif_tag = 0;
    while($row = mysqli_fetch_assoc($q_tagihan_notif)) {
        if(isJatuhTempo($row['bulan'], $row['tahun'])) {
            if($ct_notif_tag < 3) {
                $notifikasi_wali[] = ['ikon' => 'fa-file-invoice', 'warna' => 'text-yellow-500', 'bg' => 'bg-yellow-50', 'judul' => 'Tagihan Belum Lunas', 'pesan' => 'Ada tagihan '.$row['jenis_tagihan'].' '. $row['bulan'].' '.$row['tahun'].' sebesar Rp ' . number_format($row['nominal'],0,',','.'), 'waktu' => 'Menunggu Pembayaran', 'link' => 'read_notif.php?tipe=tagihan&id='.$row['id_tagihan']];
            }
            $total_notif_baru++;
            $ct_notif_tag++;
        }
    }
}
// --- NOTIFIKASI WALIMURID (END) ---

$nama_sekolah = $_SESSION['nama_sekolah'];
$pesan = '';

// --- LOGIKA UPLOAD BUKTI PEMBAYARAN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bayar'])) {
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
            // Buat nama file unik agar tidak bentrok
            $nama_baru = time() . '_' . uniqid() . '.' . $ext_file;
            $path_tujuan = '../uploads/';
            
            // Buat folder uploads jika belum ada
            if (!is_dir($path_tujuan)) {
                mkdir($path_tujuan, 0777, true);
            }

            if (move_uploaded_file($tmp_file, $path_tujuan . $nama_baru)) {
                // Simpan ke tabel banding sebagai antrean persetujuan Admin
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

// --- AMBIL DATA TAGIHAN ---
// 1. Cari dulu ID siswa dari wali murid ini
$q_siswa = mysqli_query($koneksi, "SELECT id_siswa, nama_siswa, kelas FROM siswa WHERE id_walimurid = '$id_walimurid' LIMIT 1");
$siswa = mysqli_fetch_assoc($q_siswa);

$tagihanList = [];
if ($siswa) {
    $id_siswa = $siswa['id_siswa'];
    
    // Ambil tagihan sekaligus cek apakah tagihan tersebut sedang di-banding/menunggu verifikasi
    $q_tagihan = mysqli_query($koneksi, "
        SELECT t.*, 
               (SELECT status_banding FROM banding WHERE id_tagihan = t.id_tagihan ORDER BY tgl_pengajuan DESC LIMIT 1) as status_pengajuan
        FROM tagihan t
        WHERE t.id_siswa = '$id_siswa'
        ORDER BY t.tahun DESC, t.bulan ASC
    ");
    
    while ($row = mysqli_fetch_assoc($q_tagihan)) {
        $row['is_jatuh_tempo'] = isJatuhTempo($row['bulan'], $row['tahun']);
        $tagihanList[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan & Pembayaran - Portal Orang Tua</title>
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
                <a href="tagihan.php" class="flex items-center px-4 py-3 bg-[#10b981] hover:text-white rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-credit-card w-6"></i>
                    <span>Tagihan & Bayar</span>
                </a>
                <a href="rincian_biaya.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-list-check w-6"></i>
                    <span>Rincian Biaya</span>
                </a>
                <!-- Menu Baru: Riwayat Pengajuan -->
                <a href="form_banding.php" class="flex items-center px-4 py-3 hover:bg-gray-800 text-white rounded-lg font-medium shadow-md transition-colors">
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
            <h1 class="text-xl font-bold text-gray-800 uppercase tracking-wide">Rincian Tagihan</h1>
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

            <?php if(!$siswa): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg shadow-sm text-center">
                    <i class="fa-solid fa-triangle-exclamation text-4xl text-red-400 mb-3 block"></i>
                    <h3 class="text-lg font-bold text-red-800">Akun Belum Ditautkan</h3>
                    <p class="text-red-600 mt-1">Akun Anda belum dihubungkan dengan data siswa manapun. Silakan hubungi bagian Tata Usaha sekolah Anda.</p>
                </div>
            <?php else: ?>

            <div class="mb-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Data Siswa</h2>
                    <p class="text-sm text-gray-500 mt-1">Tagihan di bawah ini adalah untuk ananda:</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-xl text-green-600"><?= $siswa['nama_siswa']; ?></p>
                    <p class="text-sm font-medium text-gray-500 border border-gray-200 bg-gray-50 px-2 py-0.5 rounded inline-block mt-1">Kelas <?= $siswa['kelas']; ?></p>
                </div>
            </div>

            <!-- TABEL TAGIHAN -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm font-semibold border-b border-gray-200">
                                <th class="py-4 px-6">Jenis Tagihan</th>
                                <th class="py-4 px-6">Periode</th>
                                <th class="py-4 px-6">Nominal</th>
                                <th class="py-4 px-6">Status Pembayaran</th>
                                <th class="py-4 px-6 text-center">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                            <?php 
                            if(count($tagihanList) > 0):
                                foreach($tagihanList as $tagihan): 
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-6 font-bold text-gray-800"><?= $tagihan['jenis_tagihan']; ?></td>
                                <td class="py-4 px-6"><?= $tagihan['bulan']; ?> <?= $tagihan['tahun']; ?></td>
                                <td class="py-4 px-6 font-bold text-gray-800">Rp <?= number_format($tagihan['nominal'], 0, ',', '.'); ?></td>
                                <td class="py-4 px-6">
                                    <?php 
                                    // LOGIKA STATUS
                                    if ($tagihan['status'] == 'Lunas') {
                                        echo '<span class="bg-green-100 text-green-700 py-1.5 px-3 rounded-md text-xs font-bold"><i class="fa-solid fa-check-circle mr-1"></i> Lunas</span>';
                                    } elseif ($tagihan['status_pengajuan'] == 'Menunggu' || $tagihan['status_pengajuan'] == 'Diproses') {
                                        echo '<span class="bg-yellow-100 text-yellow-700 py-1.5 px-3 rounded-md text-xs font-bold animate-pulse"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Sedang Diverifikasi</span>';
                                    } else {
                                        if(!$tagihan['is_jatuh_tempo']) {
                                            echo '<span class="bg-gray-100 text-gray-500 py-1.5 px-3 rounded-md text-xs font-bold border border-gray-200"><i class="fa-solid fa-calendar-clock mr-1"></i> Belum Jatuh Tempo</span>';
                                        } else {
                                            echo '<span class="bg-red-100 text-red-700 py-1.5 px-3 rounded-md text-xs font-bold"><i class="fa-solid fa-xmark-circle mr-1"></i> Belum Lunas</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($tagihan['status'] == 'Belum Lunas' && ($tagihan['status_pengajuan'] != 'Menunggu' && $tagihan['status_pengajuan'] != 'Diproses')): ?>
                                        <button onclick="openModalBayar('<?= $tagihan['id_tagihan'] ?>', '<?= $tagihan['jenis_tagihan'] ?> - <?= $tagihan['bulan'] ?> <?= $tagihan['tahun'] ?>', '<?= number_format($tagihan['nominal'], 0, ',', '.') ?>')" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-xs font-bold transition shadow-sm">
                                            <i class="fa-solid fa-upload mr-1"></i> Bayar & Upload
                                        </button>
                                    <?php elseif($tagihan['status'] == 'Lunas'): ?>
                                        <span class="text-green-500 font-bold"><i class="fa-solid fa-check"></i> Selesai</span>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic text-xs">Menunggu Konfirmasi...</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                            <tr><td colspan="5" class="text-center py-10 text-gray-500"><i class="fa-solid fa-receipt text-4xl block mb-3 text-gray-300"></i> Belum ada tagihan yang diterbitkan sekolah.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- ================= MODAL UPLOAD BUKTI PEMBAYARAN ================= -->
    <div id="modalBayar" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden flex items-center justify-center z-50 transition-opacity p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300" id="modalBayarContent">
            <div class="bg-blue-600 p-5 flex justify-between items-center text-white">
                <h3 class="font-bold text-lg"><i class="fa-solid fa-file-invoice-dollar mr-2"></i> Konfirmasi Pembayaran</h3>
                <button onclick="closeModal('modalBayar')" class="text-blue-200 hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="id_tagihan" id="input_id_tagihan">
                
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg mb-5">
                    <p class="text-xs text-blue-600 font-bold uppercase tracking-wide mb-1">Membayar Tagihan:</p>
                    <p class="text-gray-800 font-bold" id="text_detail_tagihan">SPP - Juli 2024</p>
                    <p class="text-xl text-blue-700 font-black mt-1" id="text_nominal">Rp 0</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Pesan Tambahan (Opsional)</label>
                        <input type="text" name="pesan_banding" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition" placeholder="Contoh: Sudah ditransfer dari bank BCA a.n Budi">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Upload Bukti Transfer / Kwitansi <span class="text-red-500">*</span></label>
                        <input type="file" name="bukti_transfer" required accept=".jpg, .jpeg, .png, .pdf" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-[11px] text-gray-500 mt-1">Format: JPG, PNG, atau PDF. Maksimal ukuran 2MB.</p>
                    </div>
                </div>

                <div class="mt-8 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('modalBayar')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition text-sm">Batal</button>
                    <button type="submit" name="bayar" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-md transition text-sm flex items-center">
                        <i class="fa-solid fa-paper-plane mr-2"></i> Kirim Bukti
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT -->
    <script>
        function openModalBayar(idTagihan, detailTagihan, nominal) {
            document.getElementById('input_id_tagihan').value = idTagihan;
            document.getElementById('text_detail_tagihan').innerText = detailTagihan;
            document.getElementById('text_nominal').innerText = 'Rp ' + nominal;
            
            const modal = document.getElementById('modalBayar');
            const content = document.getElementById('modalBayarContent');
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