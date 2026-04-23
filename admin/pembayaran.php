<?php
session_start();
require '../config/koneksi.php';

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$id_sekolah = $_SESSION['id_sekolah'];
$nama_admin = $_SESSION['nama_lengkap'];
$id_user = $_SESSION['id_user'];
$pesan = '';

// --- NOTIFIKASI BANDING ---
$q_notif_banding = mysqli_query($koneksi, "SELECT COUNT(b.id_banding) as total_baru FROM banding b JOIN tagihan t ON b.id_tagihan = t.id_tagihan JOIN siswa s ON t.id_siswa = s.id_siswa WHERE s.id_sekolah = '$id_sekolah' AND b.status_banding = 'Menunggu'");
$notif_banding = mysqli_fetch_assoc($q_notif_banding)['total_baru'];

// --- LOGIKA PROSES BAYAR ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['proses_bayar'])) {
    $id_tagihan = $_POST['id_tagihan'];
    $tgl_bayar = date('Y-m-d H:i:s');
    
    // Update status tagihan jadi Lunas
    $update = mysqli_query($koneksi, "UPDATE tagihan SET status='Lunas' WHERE id_tagihan='$id_tagihan'");
    if ($update) {
        // Catat ke tabel pembayaran
        $insert = mysqli_query($koneksi, "INSERT INTO pembayaran (id_tagihan, tgl_bayar, id_admin) VALUES ('$id_tagihan', '$tgl_bayar', '$id_user')");
        if ($insert) {
            $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm font-bold'>Pembayaran berhasil diproses dan dicatat!</div>";
        }
    }
}

// 1. Ambil data tagihan Menunggu Pembayaran
$q_menunggu = mysqli_query($koneksi, "
    SELECT t.*, s.nama_siswa, s.kelas, s.sub_kelas 
    FROM tagihan t 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    WHERE s.id_sekolah = '$id_sekolah' AND t.status = 'Belum Lunas'
    ORDER BY t.tahun ASC, s.nama_siswa ASC
");

// 2. Ambil data Riwayat Pembayaran Berhasil
$q_riwayat = mysqli_query($koneksi, "
    SELECT p.*, t.jenis_tagihan, t.bulan, t.tahun, t.nominal, s.nama_siswa, u.nama_lengkap as nama_petugas
    FROM pembayaran p
    JOIN tagihan t ON p.id_tagihan = t.id_tagihan
    JOIN siswa s ON t.id_siswa = s.id_siswa
    JOIN users u ON p.id_admin = u.id_user
    WHERE s.id_sekolah = '$id_sekolah'
    ORDER BY p.tgl_bayar DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pembayaran Siswa - Sistem Pembayaran Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
    </style>
</head>
<body class="bg-[#f8fafc] flex font-sans min-h-screen text-gray-700">

    <!-- SIDEBAR -->
    <aside class="w-[260px] bg-[#1C2434] text-gray-400 flex flex-col fixed h-full z-20">
        <div class="h-16 flex items-center px-5 border-b border-white/[0.06]">
            <div class="bg-white/10 p-2 rounded-lg mr-3"><i class="fa-solid fa-graduation-cap text-white text-base"></i></div>
            <div class="overflow-hidden">
                <h2 class="text-[13px] font-semibold text-white leading-tight truncate w-40"><?= $_SESSION['nama_sekolah']; ?></h2>
                <p class="text-[10px] text-[#10B981] mt-0.5 font-bold">SaaS Panel</p>
            </div>
        </div>
        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-border-all w-7 text-[13px]"></i> Dashboard</a>
            <a href="data_siswa.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-regular fa-user w-7 text-[13px]"></i> Data Siswa & Wali</a>
            <a href="data_tagihan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-regular fa-credit-card w-7 text-[13px]"></i> Tagihan</a>
            <a href="pembayaran.php" class="flex items-center px-3 py-2.5 bg-[#10B981] text-white shadow-sm rounded-lg text-[13px] font-medium "><i class="fa-solid fa-money-bill-transfer w-7 text-[13px]"></i> Pembayaran</a>
            <a href="m_rincian.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-list-check w-7 text-[13px]"></i> Master Rincian Biaya</a>
            <a href="laporan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-file-invoice-dollar w-7 text-[13px]"></i> Laporan Keuangan</a>
            <a href="banding.php" class="flex items-center justify-between px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <div class="flex items-center"><i class="fa-solid fa-scale-balanced w-7 text-[13px]"></i> Data Banding</div>
                <?php if(isset($notif_banding) && $notif_banding > 0): ?><span class="bg-red-500/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_banding; ?></span><?php endif; ?>
            </a>
            <a href="terms.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-file-contract w-7 text-[13px]"></i> Terms of Service</a>
        </nav>
    </aside>

    <!-- wRAPPER MAIN CONTENT -->
    <div class="flex-1 ml-[260px] flex flex-col min-w-0">
        <header class="h-14 bg-white flex items-center justify-between px-8 border-b border-gray-100 sticky top-0 z-10">
            <h1 class="text-sm font-semibold text-gray-800">Input Pembayaran Siswa</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center bg-[#f0fdf4] text-[#166534] px-4 py-1.5 rounded-full text-xs font-semibold mr-2 border border-green-100"> Admin: <?= $nama_admin; ?> </div>
                <a href="../auth/logout.php" class="flex items-center text-xs text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout</a>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">
            <?= $pesan; ?>

            <!-- BAGIAN 1: TAGIHAN MENUNGGU -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                <div class="mb-5">
                    <h3 class="text-lg font-bold text-gray-800">Tagihan Menunggu Pembayaran</h3>
                    <p class="text-xs text-gray-400 mt-1">Cari tagihan siswa yang ingin melunasi pembayaran, lalu klik "Proses Bayar".</p>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="text-[11px] text-gray-800 uppercase tracking-wider font-semibold border-b border-gray-100">
                                <th class="py-3 px-4 w-12">No</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-4">Kelas</th>
                                <th class="py-3 px-4">Rincian Tagihan</th>
                                <th class="py-3 px-4 text-left">Nominal</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-600 font-medium">
                            <?php $no = 1; if (mysqli_num_rows($q_menunggu) > 0): while ($m = mysqli_fetch_assoc($q_menunggu)): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-4"><?= $no++; ?></td>
                                <td class="py-3.5 px-4 font-bold text-gray-800"><?= $m['nama_siswa']; ?></td>
                                <td class="py-3.5 px-4 text-gray-500 text-xs"><?= $m['kelas']; ?> <?= $m['sub_kelas']; ?></td>
                                <td class="py-3.5 px-4 text-gray-500 text-xs"><?= $m['jenis_tagihan']; ?> <?= $m['bulan'] ?></td>
                                <td class="py-3.5 px-4 font-bold text-red-500">Rp <?= number_format($m['nominal'], 0, ',', '.'); ?></td>
                                <td class="py-3.5 px-4 flex justify-center">
                                    <form method="POST" action="" onsubmit="return confirm('Proses pembayaran tagihan ini?');">
                                        <input type="hidden" name="id_tagihan" value="<?= $m['id_tagihan'] ?>">
                                        <button type="submit" name="proses_bayar" class="bg-[#10B981] hover:bg-[#059669] text-white px-4 py-2 rounded-md text-xs font-bold transition">Proses Bayar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="6" class="text-center py-6 text-gray-400">Tidak ada tagihan yang menunggu pembayaran.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- BAGIAN 2: RIWAYAT BERHASIL -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="mb-5">
                    <h3 class="text-lg font-bold text-gray-800">Riwayat Pembayaran Berhasil</h3>
                    <p class="text-xs text-gray-400 mt-1">Daftar transaksi yang sudah selesai. Anda bisa mencetak ulang struk di sini.</p>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="text-[11px] text-gray-800 uppercase tracking-wider font-semibold border-b border-gray-100">
                                <th class="py-3 px-4 w-12">No</th>
                                <th class="py-3 px-4">Tgl Bayar</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-4 text-left">Tagihan</th>
                                <th class="py-3 px-4 text-left">Nominal</th>
                                <th class="py-3 px-4">Petugas</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-600 font-medium">
                            <?php $no = 1; if (mysqli_num_rows($q_riwayat) > 0): while ($r = mysqli_fetch_assoc($q_riwayat)): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-4"><?= $no++; ?></td>
                                <td class="py-3.5 px-4 text-gray-500 text-xs"><?= date('d M Y H:i', strtotime($r['tgl_bayar'])) ?></td>
                                <td class="py-3.5 px-4 font-bold text-gray-800"><?= $r['nama_siswa'] ?></td>
                                <td class="py-3.5 px-4 text-gray-500 text-xs"><?= $r['jenis_tagihan'] ?> <?= $r['bulan'] ?></td>
                                <td class="py-3.5 px-4 font-bold text-[#10B981]">Rp <?= number_format($r['nominal'], 0, ',', '.'); ?></td>
                                <td class="py-3.5 px-4 text-gray-500 text-xs"><?= $r['nama_petugas'] ?></td>
                                <td class="py-3.5 px-4 flex justify-center">
                                    <a href="cetak_struk.php?id=<?= $r['id_pembayaran'] ?>" target="_blank" class="bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 font-bold px-4 py-1.5 rounded-md text-xs transition">Cetak</a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="7" class="text-center py-6 text-gray-400">Belum ada riwayat pembayaran.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>
</html>
