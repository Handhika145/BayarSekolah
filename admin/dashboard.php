<?php
session_start();
require '../config/koneksi.php';

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$nama_admin = $_SESSION['nama_lengkap'];
$id_sekolah = $_SESSION['id_sekolah'];
// --- NOTIFIKASI BANDING ---
$q_notif_banding = mysqli_query($koneksi, "SELECT COUNT(b.id_banding) as total_baru FROM banding b JOIN tagihan t ON b.id_tagihan = t.id_tagihan JOIN siswa s ON t.id_siswa = s.id_siswa WHERE s.id_sekolah = '$id_sekolah' AND b.status_banding = 'Menunggu'");
$notif_banding = mysqli_fetch_assoc($q_notif_banding)['total_baru'];
 // KUNCI UTAMA ISOLASI DATA SAAS

// 1. Ambil Total Siswa (Khusus sekolah admin yang sedang login)
$q_siswa = mysqli_query($koneksi, "SELECT COUNT(id_siswa) as total FROM siswa WHERE id_sekolah = '$id_sekolah'");
$total_siswa = mysqli_fetch_assoc($q_siswa)['total'];

// 2. Ambil Total Penerimaan SPP (Status Lunas, khusus sekolah ini)
$q_spp = mysqli_query($koneksi, "
    SELECT SUM(t.nominal) as total 
    FROM tagihan t 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    WHERE s.id_sekolah = '$id_sekolah' AND t.jenis_tagihan='SPP' AND t.status='Lunas'
");
$total_spp = mysqli_fetch_assoc($q_spp)['total'] ?? 0;

// 3. Ambil Total Retribusi Sekolah (Status Lunas, khusus sekolah ini)
$q_retribusi = mysqli_query($koneksi, "
    SELECT SUM(t.nominal) as total 
    FROM tagihan t 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    WHERE s.id_sekolah = '$id_sekolah' AND t.jenis_tagihan='Retribusi' AND t.status='Lunas'
");
$total_retribusi = mysqli_fetch_assoc($q_retribusi)['total'] ?? 0;

// 4. Ambil Total Tagihan Tertunggak (Khusus sekolah ini)
$q_tunggak = mysqli_query($koneksi, "
    SELECT COUNT(t.id_tagihan) as total 
    FROM tagihan t 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    WHERE s.id_sekolah = '$id_sekolah' AND t.status='Belum Lunas'
");
$total_tunggak = mysqli_fetch_assoc($q_tunggak)['total'];

// 5. Data untuk Grafik Donat (Status SPP Khusus sekolah ini)
$q_lunas = mysqli_query($koneksi, "
    SELECT COUNT(t.id_tagihan) as total 
    FROM tagihan t 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    WHERE s.id_sekolah = '$id_sekolah' AND t.status='Lunas' AND t.jenis_tagihan='SPP'
");
$jml_lunas = mysqli_fetch_assoc($q_lunas)['total'];

$q_belum = mysqli_query($koneksi, "
    SELECT COUNT(t.id_tagihan) as total 
    FROM tagihan t 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    WHERE s.id_sekolah = '$id_sekolah' AND t.status='Belum Lunas' AND t.jenis_tagihan='SPP'
");
$jml_belum = mysqli_fetch_assoc($q_belum)['total'];

// Untuk status 'Menunggu', JOIN sampai ke tabel siswa agar tidak tercampur sekolah lain
$q_menunggu = mysqli_query($koneksi, "
    SELECT COUNT(b.id_banding) as total 
    FROM banding b 
    JOIN tagihan t ON b.id_tagihan = t.id_tagihan 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    WHERE s.id_sekolah = '$id_sekolah' 
    AND (b.status_banding='Menunggu' OR b.status_banding='Diproses')
");
$jml_menunggu = mysqli_fetch_assoc($q_menunggu)['total'];

// 6. Data untuk Grafik Garis (Tren Penerimaan per Bulan Khusus sekolah ini)
$tahun_ini = date('Y');
$pendapatan_bulanan = array_fill(1, 12, 0);


$q_tren = mysqli_query($koneksi, "
    SELECT MONTH(p.tgl_bayar) as bulan, SUM(t.nominal) as total 
    FROM pembayaran p 
    JOIN tagihan t ON p.id_tagihan = t.id_tagihan 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    WHERE s.id_sekolah = '$id_sekolah' AND YEAR(p.tgl_bayar) = '$tahun_ini' 
    GROUP BY MONTH(p.tgl_bayar)
");

while ($row = mysqli_fetch_assoc($q_tren)) {
    $pendapatan_bulanan[$row['bulan']] = (int)$row['total'];
}
$data_grafik_garis = json_encode(array_values($pendapatan_bulanan));


// 7. Data Tabel Transaksi (Tagihan terbaru khusus sekolah ini)
$q_transaksi = mysqli_query($koneksi, "
    SELECT t.*, s.nisn, s.nama_siswa, s.kelas 
    FROM tagihan t
    JOIN siswa s ON t.id_siswa = s.id_siswa
    WHERE s.id_sekolah = '$id_sekolah'
    ORDER BY t.id_tagihan DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Pembayaran Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    </style>
</head>
<body class="bg-[#f8fafc] flex font-sans min-h-screen text-gray-700">

    <!-- SIDEBAR -->
    <aside class="w-[260px] bg-[#1C2434] text-gray-400 flex flex-col fixed h-full z-10">
        <div class="h-16 flex items-center px-5 border-b border-white/[0.06]">
            <div class="bg-white/10 p-2 rounded-lg mr-3">
                <i class="fa-solid fa-graduation-cap text-white text-base"></i>
            </div>
            <div class="overflow-hidden">
                <h2 class="text-[13px] font-semibold text-white leading-tight truncate w-40" title="<?= $_SESSION['nama_sekolah']; ?>">
                    <?= $_SESSION['nama_sekolah']; ?>
                </h2>
                <p class="text-[10px] text-[#10B981] mt-0.5 font-bold">SaaS Panel</p>
            </div>
        </div>
        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center px-3 py-2.5 bg-[#10B981] text-white shadow-sm rounded-lg text-[13px] font-medium ">
                <i class="fa-solid fa-border-all w-7 text-[13px]"></i> Dashboard
            </a>
            <a href="data_siswa.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-regular fa-user w-7 text-[13px]"></i> Data Siswa & Wali
            </a>
            <a href="data_tagihan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-regular fa-credit-card w-7 text-[13px]"></i> Tagihan
            </a>
            <a href="pembayaran.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-solid fa-money-bill-transfer w-7 text-[13px]"></i> Pembayaran
            </a>
            <a href="m_rincian.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-solid fa-list-check w-7 text-[13px]"></i> Master Rincian Biaya
            </a>
            <a href="laporan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-solid fa-file-invoice-dollar w-7 text-[13px]"></i> Laporan Keuangan
            </a>
            <a href="banding.php" class="flex items-center justify-between px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <div class="flex items-center">
                    <i class="fa-solid fa-scale-balanced w-7 text-[13px]"></i> Data Banding
                </div>
                <?php if(isset($notif_banding) && $notif_banding > 0): ?>
                    <span class="bg-red-500/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_banding; ?></span>
                <?php endif; ?>
            </a>
            <a href="terms.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-solid fa-file-contract w-7 text-[13px]"></i> Terms of Service
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 ml-[260px] flex flex-col min-w-0">
        
        <!-- HEADER -->
        <header class="h-14 bg-white flex items-center justify-between px-8 border-b border-gray-100 sticky top-0 z-10">
            <h1 class="text-sm font-semibold text-gray-800">Dashboard</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center bg-[#f0fdf4] text-[#166534] px-4 py-1.5 rounded-full text-xs font-semibold mr-2 border border-green-100"> Admin: <?= $nama_admin; ?> </div>
                <a href="../auth/logout.php" class="flex items-center text-xs text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout
                </a>
            </div>
        </header>

        <!-- DASHBOARD CONTENT -->
        <main class="flex-1 p-6 overflow-y-auto">
            
            <!-- STATISTIC CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-4">
                        <div class="bg-blue-50 p-3 rounded-lg"><i class="fa-solid fa-users text-blue-500 text-lg w-5 text-center"></i></div>
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Total Siswa</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-0.5"><?= number_format($total_siswa, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-4">
                        <div class="bg-emerald-50 p-3 rounded-lg"><i class="fa-solid fa-money-bill-wave text-emerald-500 text-lg w-5 text-center"></i></div>
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Penerimaan SPP</p>
                            <h3 class="text-xl font-bold text-gray-800 mt-0.5">Rp <?= number_format($total_spp, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-4">
                        <div class="bg-purple-50 p-3 rounded-lg"><i class="fa-solid fa-circle-dollar-to-slot text-purple-500 text-lg w-5 text-center"></i></div>
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Total Retribusi</p>
                            <h3 class="text-xl font-bold text-gray-800 mt-0.5">Rp <?= number_format($total_retribusi, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-4">
                        <div class="bg-rose-50 p-3 rounded-lg"><i class="fa-regular fa-credit-card text-rose-500 text-lg w-5 text-center"></i></div>
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Tagihan Tertunggak</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-0.5"><?= number_format($total_tunggak, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHARTS AREA -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <!-- Line Chart -->
                <div class="bg-white p-6 rounded-xl border border-gray-100 lg:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-semibold text-gray-800">Tren Penerimaan (Tahun <?= $tahun_ini; ?>)</h3>
                    </div>
                    <div class="relative h-72 w-full">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>

                <!-- Doughnut Chart -->
                <div class="bg-white p-6 rounded-xl border border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4 text-center">Status Pembayaran SPP</h3>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="doughnutChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- TABLE AREA -->
            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-5">Transaksi Terakhir</h3>
                
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="text-[11px] text-gray-400 uppercase tracking-wider font-medium border-b border-gray-100">
                                <th class="py-3 px-4">No</th>
                                <th class="py-3 px-4">NISN</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-4">Kelas</th>
                                <th class="py-3 px-4">Jenis</th>
                                <th class="py-3 px-4">Jumlah</th>
                                <th class="py-3 px-4">Bulan/Tahun</th>
                                <th class="py-3 px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-600">
                            <?php
$no = 1;
if (mysqli_num_rows($q_transaksi) > 0):
    while ($row = mysqli_fetch_assoc($q_transaksi)):
?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-4"><?= $no++; ?></td>
                                <td class="py-3.5 px-4 text-gray-400 font-mono text-xs"><?= $row['nisn']; ?></td>
                                <td class="py-3.5 px-4 font-medium text-gray-800"><?= $row['nama_siswa']; ?></td>
                                <td class="py-3.5 px-4"><?= $row['kelas']; ?></td>
                                <td class="py-3.5 px-4"><?= $row['jenis_tagihan']; ?></td>
                                <td class="py-3.5 px-4 font-medium">Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></td>
                                <td class="py-3.5 px-4"><?= $row['bulan'] . ' ' . $row['tahun']; ?></td>
                                <td class="py-3.5 px-4">
                                    <?php if ($row['status'] == 'Lunas'): ?>
                                        <span class="inline-flex items-center whitespace-nowrap bg-emerald-50 text-emerald-600 py-1 px-2.5 rounded-md text-xs font-medium">Lunas</span>
                                    <?php
        else: ?>
                                        <span class="inline-flex items-center whitespace-nowrap bg-amber-50 text-amber-600 py-1 px-2.5 rounded-md text-xs font-medium">Menunggu</span>
                                    <?php
        endif; ?>
                                </td>
                            </tr>
                            <?php
    endwhile;
else:
?>
                            <tr><td colspan="8" class="text-center py-6 text-gray-400">Belum ada data transaksi.</td></tr>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- SCRIPT CHART.JS -->
    <script>
        // Data dari PHP
        const dataTrenPenerimaan = <?= $data_grafik_garis; ?>;
        const dataLunas = <?= $jml_lunas ?: 0; ?>;
        const dataBelum = <?= $jml_belum ?: 0; ?>;
        const dataMenunggu = <?= $jml_menunggu ?: 0; ?>;

        // Line Chart
        const ctxLine = document.getElementById('lineChart').getContext('2d');
        let gradientLine = ctxLine.createLinearGradient(0, 0, 0, 300);
        gradientLine.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
        gradientLine.addColorStop(1, 'rgba(16, 185, 129, 0)');

        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Penerimaan',
                    data: dataTrenPenerimaan,
                    borderColor: '#10B981',
                    backgroundColor: gradientLine,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10B981',
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
            }
        });

        // Doughnut Chart
        const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: ['Lunas', 'Menunggu', 'Tertunggak'],
                datasets: [{
                    data: [dataLunas, dataMenunggu, dataBelum],
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                    borderWidth: 2, borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '70%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 12, family: "'Inter', sans-serif" } } } }
            }
        });
    </script>
</body>
</html>
