<?php
session_start();
require '../config/koneksi.php';

// Proteksi tingkat tinggi: Hanya Super Admin yang boleh masuk!
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'superadmin') {
    header("Location: ../login.php");
    exit;
}

$nama_superadmin = $_SESSION['nama_lengkap'];
$pesan = '';

// --- LOGIKA UBAH STATUS SEKOLAH ---
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_sekolah = (int)$_GET['id'];
    $aksi = $_GET['aksi'];
    
    if ($aksi == 'setujui') {
        $update = mysqli_query($koneksi, "UPDATE sekolah SET status='Aktif' WHERE id_sekolah='$id_sekolah'");
        if ($update) $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm font-medium'>Sekolah berhasil diaktifkan! Mereka sekarang bisa login.</div>";
    } elseif ($aksi == 'blokir') {
        $update = mysqli_query($koneksi, "UPDATE sekolah SET status='Nonaktif' WHERE id_sekolah='$id_sekolah'");
        if ($update) $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm font-medium'>Akses sekolah berhasil diblokir/dinonaktifkan.</div>";
    }
}

// --- AMBIL DATA STATISTIK GLOBAL ---
// 1. Total Sekolah
$q_total_sekolah = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM sekolah");
$total_sekolah = mysqli_fetch_assoc($q_total_sekolah)['total'];

// 2. Sekolah Pending (Menunggu Verifikasi)
$q_pending = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM sekolah WHERE status='Pending'");
$total_pending = mysqli_fetch_assoc($q_pending)['total'];

// 3. Sekolah Aktif
$q_aktif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM sekolah WHERE status='Aktif'");
$total_aktif = mysqli_fetch_assoc($q_aktif)['total'];

// 4. Total Perputaran Uang (Semua transaksi Lunas dari semua sekolah)
$q_revenue = mysqli_query($koneksi, "SELECT SUM(nominal) as total FROM tagihan WHERE status='Lunas'");
$total_revenue = mysqli_fetch_assoc($q_revenue)['total'] ?? 0;

// --- AMBIL DATA TABEL SEKOLAH ---
$q_sekolah = mysqli_query($koneksi, "SELECT * FROM sekolah ORDER BY tgl_mendaftar DESC");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - SPP Digital SaaS</title>
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
        .custom-scrollbar::-webkit-scrollbar { height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-100 flex font-sans min-h-screen text-gray-800">

    <!-- SIDEBAR (Tema Gelap Khusus Super Admin) -->
    <aside class="w-64 bg-[#0f172a] text-gray-300 flex flex-col fixed h-full z-20 shadow-2xl">
        <div class="h-20 flex items-center px-6 border-b border-gray-800">
            <div class="bg-gradient-to-tr from-indigo-500 to-purple-500 p-2 rounded-lg mr-3 shadow-lg">
                <i class="fa-solid fa-bolt text-white text-xl"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-white tracking-widest uppercase">SaaS Center</h2>
                <p class="text-[10px] text-indigo-400 font-semibold tracking-wider">Super Admin</p>
            </div>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center px-4 py-3 bg-indigo-600 text-white rounded-lg font-medium shadow-md transition-all">
                <i class="fa-solid fa-building-columns w-6"></i> Manajemen Mitra
            </a>
            <a href="laporan.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-all">
                <i class="fa-solid fa-chart-line w-6"></i> Laporan Global
            </a>
            <a href="terms.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-all">
                <i class="fa-solid fa-file-contract w-6"></i> Terms of Service
            </a>
            <a href="#" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-all opacity-50 cursor-not-allowed" title="Segera Hadir">
                <i class="fa-solid fa-gear w-6"></i> Pengaturan
            </a>
        </nav>
        
        <div class="p-4 border-t border-gray-800">
            <a href="../auth/logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white border border-red-500 rounded-lg transition-all text-sm font-bold">
                <i class="fa-solid fa-power-off"></i> Keluar Sistem
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 ml-64 flex flex-col min-w-0 bg-slate-50/50">
        
        <!-- TOP NAVBAR -->
        <header class="h-20 bg-white flex items-center justify-between px-8 shadow-sm border-b border-gray-200 z-10 sticky top-0">
            <h1 class="text-xl font-bold text-gray-800 tracking-tight">Platform Command Center</h1>
            
            <div class="flex items-center space-x-4">
                <div class="flex items-center bg-indigo-50 px-4 py-2 rounded-full border border-indigo-100">
                    <span class="relative flex h-3 w-3 mr-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                    </span>
                    <span class="text-sm font-bold text-indigo-900"><?= $nama_superadmin; ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            
            <?= $pesan; ?>

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Ringkasan Eksekutif</h2>
                <p class="text-gray-500 mt-1">Pantau perkembangan startup SPP Digital Anda secara real-time.</p>
            </div>

            <!-- STATISTIC CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-blue-500">
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Mitra</p>
                    <h3 class="text-3xl font-black text-gray-800 mt-2"><?= $total_sekolah; ?> <span class="text-sm font-normal text-gray-500">Sekolah</span></h3>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-yellow-400">
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Perlu Verifikasi</p>
                    <h3 class="text-3xl font-black text-yellow-600 mt-2"><?= $total_pending; ?> <span class="text-sm font-normal text-gray-500">Antrean</span></h3>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-green-500">
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Mitra Aktif</p>
                    <h3 class="text-3xl font-black text-green-600 mt-2"><?= $total_aktif; ?> <span class="text-sm font-normal text-gray-500">Sekolah</span></h3>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-indigo-500">
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Transaksi Platform</p>
                    <h3 class="text-2xl font-black text-indigo-600 mt-2">Rp <?= number_format($total_revenue, 0, ',', '.'); ?></h3>
                </div>
            </div>

            <!-- TABEL MANAJEMEN SEKOLAH -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Daftar Klien (Sekolah)</h3>
                        <p class="text-sm text-gray-500 mt-1">Verifikasi atau cabut akses sekolah pengguna layanan.</p>
                    </div>
                </div>
                
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="bg-white text-gray-500 text-xs uppercase tracking-wider font-bold border-b border-gray-200">
                                <th class="py-4 px-6">ID</th>
                                <th class="py-4 px-6">Identitas Sekolah</th>
                                <th class="py-4 px-6">Kontak & Alamat</th>
                                <th class="py-4 px-6">Tgl Daftar</th>
                                <th class="py-4 px-6">Status</th>
                                <th class="py-4 px-6 text-center">Aksi (Kendali)</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                            <?php 
                            if(mysqli_num_rows($q_sekolah) > 0):
                                while($row = mysqli_fetch_assoc($q_sekolah)): 
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-6 font-semibold text-gray-500">#<?= $row['id_sekolah']; ?></td>
                                <td class="py-4 px-6">
                                    <p class="font-bold text-gray-900 text-base"><?= $row['nama_sekolah']; ?></p>
                                    <p class="text-xs text-indigo-600 font-medium mt-0.5"><i class="fa-regular fa-envelope mr-1"></i> <?= $row['email_sekolah']; ?></p>
                                </td>
                                <td class="py-4 px-6">
                                    <p class="text-gray-800"><i class="fa-solid fa-phone text-gray-400 mr-1.5 text-xs"></i> <?= $row['no_telp'] ?: '-'; ?></p>
                                    <p class="text-xs text-gray-500 mt-1 truncate max-w-[200px]"><i class="fa-solid fa-location-dot text-gray-400 mr-1.5"></i> <?= $row['alamat'] ?: '-'; ?></p>
                                </td>
                                <td class="py-4 px-6 font-medium text-gray-600">
                                    <?= date('d M Y', strtotime($row['tgl_mendaftar'])); ?>
                                </td>
                                <td class="py-4 px-6">
                                    <?php 
                                        if($row['status'] == 'Aktif') echo '<span class="bg-green-100 text-green-700 py-1.5 px-3 rounded-md text-xs font-bold border border-green-200"><i class="fa-solid fa-check mr-1"></i> Aktif</span>';
                                        elseif($row['status'] == 'Pending') echo '<span class="bg-yellow-100 text-yellow-700 py-1.5 px-3 rounded-md text-xs font-bold border border-yellow-200 animate-pulse"><i class="fa-solid fa-hourglass-half mr-1"></i> Menunggu</span>';
                                        else echo '<span class="bg-red-100 text-red-700 py-1.5 px-3 rounded-md text-xs font-bold border border-red-200"><i class="fa-solid fa-ban mr-1"></i> Diblokir</span>';
                                    ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <?php if($row['status'] == 'Pending' || $row['status'] == 'Nonaktif' || $row['status'] == 'Diblokir'): ?>
                                            <a href="?aksi=setujui&id=<?= $row['id_sekolah'] ?>" onclick="return confirm('Aktifkan akses sekolah ini?');" class="bg-indigo-600 hover:bg-indigo-700 text-white py-1.5 px-3 rounded text-xs font-bold transition shadow-sm" title="Setujui/Aktifkan">
                                                Approve
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if($row['status'] == 'Aktif' || $row['status'] == 'Pending'): ?>
                                            <a href="?aksi=blokir&id=<?= $row['id_sekolah'] ?>" onclick="return confirm('Blokir sekolah ini? Mereka tidak akan bisa login lagi!');" class="bg-white border border-gray-300 text-red-600 hover:bg-red-50 hover:border-red-300 py-1.5 px-3 rounded text-xs font-bold transition shadow-sm" title="Tolak/Blokir">
                                                Blokir
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                            <tr><td colspan="6" class="text-center py-12 text-gray-500"><i class="fa-solid fa-box-open text-4xl block mb-3 text-gray-300"></i> Belum ada sekolah yang mendaftar.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

</body>
</html>
