<?php
session_start();
require '../config/koneksi.php';

// Proteksi tingkat tinggi: Hanya Super Admin yang boleh masuk!
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'superadmin') {
    header("Location: ../login.php");
    exit;
}

$nama_superadmin = $_SESSION['nama_lengkap'];

// --- AMBIL DATA STATISTIK GLOBAL (HANYA SEKOLAH AKTIF) ---

// 1. Total Pendapatan Platform (Tagihan Lunas dari Sekolah Aktif)
$q_revenue = mysqli_query($koneksi, "
    SELECT SUM(t.nominal) as total 
    FROM tagihan t 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    JOIN sekolah sk ON s.id_sekolah = sk.id_sekolah 
    WHERE t.status='Lunas' AND sk.status='Aktif'
");
$total_revenue = mysqli_fetch_assoc($q_revenue)['total'] ?? 0;

// 2. Total Tunggakan Keseluruhan (Tagihan Belum Lunas dari Sekolah Aktif)
$q_tunggakan = mysqli_query($koneksi, "
    SELECT SUM(t.nominal) as total 
    FROM tagihan t 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    JOIN sekolah sk ON s.id_sekolah = sk.id_sekolah 
    WHERE t.status='Belum Lunas' AND sk.status='Aktif'
");
$total_tunggakan = mysqli_fetch_assoc($q_tunggakan)['total'] ?? 0;

// 3. Total Siswa dari Sekolah Aktif
$q_total_siswa = mysqli_query($koneksi, "
    SELECT COUNT(s.id_siswa) as total 
    FROM siswa s 
    JOIN sekolah sk ON s.id_sekolah = sk.id_sekolah 
    WHERE sk.status='Aktif'
");
$total_siswa = mysqli_fetch_assoc($q_total_siswa)['total'];

// 4. Total Sekolah Aktif
$q_aktif = mysqli_query($koneksi, "SELECT COUNT(id_sekolah) as total FROM sekolah WHERE status='Aktif'");
$total_aktif = mysqli_fetch_assoc($q_aktif)['total'];


// --- AMBIL DATA TABEL SEKOLAH ---
$q_laporan_sekolah = mysqli_query($koneksi, "
    SELECT 
        sk.id_sekolah,
        sk.nama_sekolah,
        sk.email_sekolah,
        sk.tgl_mendaftar,
        (SELECT COUNT(*) FROM siswa s WHERE s.id_sekolah = sk.id_sekolah) as total_siswa,
        (SELECT COALESCE(SUM(t.nominal), 0) FROM tagihan t JOIN siswa s ON t.id_siswa = s.id_siswa WHERE s.id_sekolah = sk.id_sekolah AND t.status = 'Lunas') as total_lunas,
        (SELECT COALESCE(SUM(t.nominal), 0) FROM tagihan t JOIN siswa s ON t.id_siswa = s.id_siswa WHERE s.id_sekolah = sk.id_sekolah AND t.status = 'Belum Lunas') as total_tunggakan
    FROM sekolah sk
    WHERE sk.status = 'Aktif'
    ORDER BY total_lunas DESC
");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Global Super Admin - SPP Digital SaaS</title>
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
        .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 8px;}
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
            <a href="dashboard.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-all">
                <i class="fa-solid fa-building-columns w-6"></i> Manajemen Mitra
            </a>
            <a href="laporan.php" class="flex items-center px-4 py-3 bg-indigo-600 text-white rounded-lg font-medium shadow-md transition-all">
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
            <h1 class="text-xl font-bold text-gray-800 tracking-tight">Laporan Keuangan Global</h1>
            
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
            
            <div class="mb-8 flex justify-between items-end">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Laporan Global Akurat</h2>
                    <p class="text-gray-500 mt-1">Total pencapaian dan rekapitulasi data keuangan dari <strong class="text-indigo-600">seluruh sekolah aktif</strong> di platform.</p>
                </div>
                <button onclick="window.print()" class="bg-white hover:bg-indigo-50 border border-indigo-200 text-indigo-600 py-2 px-4 rounded-lg font-bold shadow-sm transition flex items-center">
                    <i class="fa-solid fa-print mr-2"></i> Cetak PDF
                </button>
            </div>

            <!-- STATISTIC CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 print:grid-cols-4">
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-green-500">
                    <div class="flex justify-between items-start">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Peredaran Uang Masuk</p>
                        <i class="fa-solid fa-money-bill-wave text-green-200 text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-green-600 mt-3 truncate" title="Rp <?= number_format($total_revenue, 0, ',', '.'); ?>">Rp <?= number_format($total_revenue, 0, ',', '.'); ?></h3>
                    <p class="text-[10px] text-gray-400 mt-1">Total Tagihan Lunas</p>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-red-500">
                    <div class="flex justify-between items-start">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Potensi Piutang / Tunggakan</p>
                        <i class="fa-solid fa-triangle-exclamation text-red-200 text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-red-600 mt-3 truncate" title="Rp <?= number_format($total_tunggakan, 0, ',', '.'); ?>">Rp <?= number_format($total_tunggakan, 0, ',', '.'); ?></h3>
                    <p class="text-[10px] text-gray-400 mt-1">Total Tagihan Menunggu Dibayar</p>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-blue-500">
                    <div class="flex justify-between items-start">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Siswa Aktif</p>
                        <i class="fa-solid fa-users text-blue-200 text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-black text-gray-800 mt-2"><?= number_format($total_siswa, 0, ',', '.'); ?> <span class="text-sm font-normal text-gray-500">Pelajar</span></h3>
                    <p class="text-[10px] text-gray-400 mt-1">Dari Semua Sekolah</p>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-indigo-500">
                    <div class="flex justify-between items-start">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Sekolah Aktif</p>
                        <i class="fa-solid fa-school text-indigo-200 text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-black text-gray-800 mt-2"><?= $total_aktif; ?> <span class="text-sm font-normal text-gray-500">Mitra</span></h3>
                    <p class="text-[10px] text-gray-400 mt-1">Telah di-approve</p>
                </div>
            </div>

            <!-- TABEL LAPORAN GLOBAL SEKOLAH -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Rekapitulasi Kinerja per Sekolah</h3>
                        <p class="text-sm text-gray-500 mt-1">Data keuangan spesifik secara real-time dari tiap-tiap entitas mitra.</p>
                    </div>
                </div>
                
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="bg-white text-gray-500 text-xs uppercase tracking-wider font-bold border-b border-gray-200">
                                <th class="py-4 px-6 text-center w-16">Peringkat</th>
                                <th class="py-4 px-6">Identitas Sekolah</th>
                                <th class="py-4 px-6 text-center">Total Siswa Terdaftar</th>
                                <th class="py-4 px-6 text-right">Pemasukan (Lunas)</th>
                                <th class="py-4 px-6 text-right">Tunggakan (Belum Lunas)</th>
                                <th class="py-4 px-6 text-center">Performa Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($q_laporan_sekolah) > 0):
                                while($row = mysqli_fetch_assoc($q_laporan_sekolah)): 
                                    $lunas = $row['total_lunas'];
                                    $tunggakan = $row['total_tunggakan'];
                                    $total_tagihan = $lunas + $tunggakan;
                                    
                                    // Hitung persentase pembayaran
                                    $persentase = 0;
                                    if ($total_tagihan > 0) {
                                        $persentase = round(($lunas / $total_tagihan) * 100, 1);
                                    }
                            ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-6 text-center font-bold text-gray-400">#<?= $no++; ?></td>
                                <td class="py-4 px-6">
                                    <p class="font-bold text-gray-900 text-base"><?= $row['nama_sekolah']; ?></p>
                                    <p class="text-xs text-indigo-600 font-medium mt-0.5"><i class="fa-regular fa-envelope mr-1"></i> <?= $row['email_sekolah']; ?></p>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="bg-gray-100 text-gray-700 font-bold px-3 py-1 rounded-full text-xs">
                                        <i class="fa-solid fa-user-graduate mr-1 text-gray-400"></i> <?= $row['total_siswa']; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <p class="font-bold text-green-600">Rp <?= number_format($lunas, 0, ',', '.'); ?></p>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <p class="font-bold text-red-500">Rp <?= number_format($tunggakan, 0, ',', '.'); ?></p>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-center space-x-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-<?= $persentase >= 75 ? 'green' : ($persentase >= 50 ? 'yellow' : 'red'); ?>-500 h-2.5 rounded-full" style="width: <?= $persentase ?>%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-gray-600 w-8"><?= $persentase ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                            <tr><td colspan="6" class="text-center py-12 text-gray-500"><i class="fa-solid fa-file-invoice text-4xl block mb-3 text-gray-300"></i> Belum ada data transaksi dari sekolah aktif.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer for printing -->
            <div class="hidden print:block mt-8 text-center text-gray-500 text-sm">
                <p>Laporan ini digenerate secara otomatis oleh SPP Digital pada <?= date('d F Y H:i:s'); ?></p>
            </div>

        </main>
    </div>

    <style>
        @media print {
            aside, header, button, .print\:hidden {
                display: none !important;
            }
            .flex-1.ml-64 {
                margin-left: 0 !important;
                background-color: white !important;
            }
            main {
                padding: 0 !important;
                overflow: visible !important;
            }
            .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl, .shadow-2xl {
                box-shadow: none !important;
            }
            .bg-slate-50\/50, .bg-gray-100 {
                background-color: white !important;
            }
            .border-gray-200, .border-gray-100 {
                border-color: #e5e7eb !important;
            }
        }
    </style>
</body>
</html>

