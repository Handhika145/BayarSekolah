<?php
session_start();
require_once '../config/koneksi.php';

// Cek login dan role
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
    while ($row = mysqli_fetch_assoc($q_banding_notif)) {
        if ($row['status_banding'] == 'Selesai') {
            $notifikasi_wali[] = ['ikon' => 'fa-circle-check', 'warna' => 'text-green-500', 'bg' => 'bg-green-50', 'judul' => 'Pembayaran Terverifikasi!', 'pesan' => 'Bukti bayar ' . $row['jenis_tagihan'] . ' ' . $row['bulan'] . ' ' . $row['tahun'] . ' disetujui.', 'waktu' => date('d M Y', strtotime($row['tgl_pengajuan'])), 'link' => 'read_notif.php?tipe=banding&id=' . $row['id_banding']];
        } else {
            $notifikasi_wali[] = ['ikon' => 'fa-circle-xmark', 'warna' => 'text-red-500', 'bg' => 'bg-red-50', 'judul' => 'Verifikasi Ditolak', 'pesan' => 'Bukti bayar ' . $row['jenis_tagihan'] . ' ' . $row['bulan'] . ' ' . $row['tahun'] . ' ditolak. Lengkapi ulang.', 'waktu' => date('d M Y', strtotime($row['tgl_pengajuan'])), 'link' => 'read_notif.php?tipe=banding&id=' . $row['id_banding']];
        }
        $total_notif_baru++;
    }

    // 2. Tagihan Baru / Belum Lunas
    $q_tagihan_notif = mysqli_query($koneksi, "SELECT * FROM tagihan WHERE id_siswa = '$id_siswa_notif' AND status = 'Belum Lunas' AND is_read_walimurid = 0 ORDER BY id_tagihan DESC");
    $ct_notif_tagihan = 0;
    while ($row = mysqli_fetch_assoc($q_tagihan_notif)) {
        if(isJatuhTempo($row['bulan'], $row['tahun'])) {
            if($ct_notif_tagihan < 3) {
                $notifikasi_wali[] = ['ikon' => 'fa-file-invoice', 'warna' => 'text-yellow-500', 'bg' => 'bg-yellow-50', 'judul' => 'Tagihan Belum Lunas', 'pesan' => 'Ada tagihan ' . $row['jenis_tagihan'] . ' ' . $row['bulan'] . ' ' . $row['tahun'] . ' sebesar Rp ' . number_format($row['nominal'], 0, ',', '.'), 'waktu' => 'Menunggu Pembayaran', 'link' => 'read_notif.php?tipe=tagihan&id=' . $row['id_tagihan']];
            }
            $total_notif_baru++;
            $ct_notif_tagihan++;
        }
    }
}
// --- NOTIFIKASI WALIMURID (END) ---


// Ambil data SATU siswa saja yang terhubung dengan akun ini
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_walimurid = '$id_walimurid' LIMIT 1");
$siswa = mysqli_fetch_assoc($query_siswa);

$tagihanList = [];
$belumLunas = [];
$totalTunggakan = 0;
$totalLunas = 0;

// Jika data anak ditemukan, ambil tagihannya
if ($siswa) {
    $id_siswa = $siswa['id_siswa'];

    $query_tagihan = mysqli_query($koneksi, "
        SELECT t.*, s.nama_siswa, s.kelas, s.nisn
        FROM tagihan t
        JOIN siswa s ON t.id_siswa = s.id_siswa
        WHERE s.id_siswa = '$id_siswa'
        ORDER BY t.tahun DESC, t.bulan ASC
    ");

    while ($row = mysqli_fetch_assoc($query_tagihan)) {
        $row['is_jatuh_tempo'] = isJatuhTempo($row['bulan'], $row['tahun']);
        $tagihanList[] = $row;
        if ($row['status'] == 'Lunas') {
            $totalLunas += $row['nominal'];
        } elseif ($row['status'] == 'Belum Lunas' && $row['is_jatuh_tempo']) {
            $totalTunggakan += $row['nominal'];
        }
    }
}

// Fungsi untuk keamanan tampilan data
function escape($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Wali Murid - SPP Sekolah</title>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
        }

        .transition-smooth {
            transition: all 0.2s ease;
        }

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -12px rgba(0, 0, 0, 0.15);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }

        .content-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .content-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .content-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
    </style>
</head>

<body class="bg-[#f4f7f6] flex font-sans min-h-screen text-gray-800">

    <aside class="w-64 bg-[#1e293b] text-gray-300 flex flex-col fixed h-full z-20">
        <div class="h-20 flex items-center px-6 border-b border-gray-700">
            <div class="bg-white p-1.5 rounded-full mr-3">
                <i class="fa-solid fa-graduation-cap text-green-600 text-xl"></i>
            </div>
            <div class="overflow-hidden">
                <h2 class="text-sm font-bold text-white leading-tight truncate w-40"
                    title="<?= $_SESSION['nama_sekolah']; ?>">
                    <?= $_SESSION['nama_sekolah']; ?>
                </h2>
                <h2 class="text-[10px] font-bold text-green-400 uppercase tracking-widest mt-0.5">Portal Wali Murid</h2>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto py-6 custom-scrollbar">
            <nav class="px-4 space-y-1">
                <a href="dashboard.php"
                    class="flex items-center px-4 py-3 bg-[#10b981] text-white rounded-lg font-medium shadow-md transition-smooth">
                    <i class="fa-solid fa-border-all w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="tagihan.php"
                    class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-smooth">
                    <i class="fa-regular fa-credit-card w-6"></i>
                    <span>Tagihan & Bayar</span>
                </a>
                <a href="rincian_biaya.php"
                    class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-smooth">
                    <i class="fa-solid fa-list-check w-6"></i>
                    <span>Rincian Biaya</span>
                </a>
                <a href="form_banding.php"
                    class="flex items-center px-4 py-3 hover:bg-gray-800 text-white rounded-lg font-medium shadow-md transition-colors">
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
                    <p class="text-sm font-semibold text-white truncate"><?= escape($currentUser['nama_lengkap']) ?></p>
                    <p class="text-xs text-green-400">Wali Murid</p>
                </div>
            </div>
            <a href="../auth/logout.php"
                class="flex items-center justify-center gap-2 w-full py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-smooth text-sm font-bold shadow-md">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 ml-64 flex flex-col min-w-0">

        <header class="h-20 bg-white flex items-center justify-between px-8 shadow-sm z-10 sticky top-0">
            <h1 class="text-xl font-bold text-gray-800 uppercase tracking-wide">Portal Orang Tua -
                <?= $_SESSION['nama_sekolah']; ?>
            </h1>
            <div class="flex items-center space-x-6 relative">
                <!-- NOTIFICATION ICON -->
                <div class="relative group cursor-pointer">
                    <button type="button"
                        class="relative p-2 text-gray-500 hover:text-green-600 transition-colors focus:outline-none"
                        onclick="toggleNotif()">
                        <i class="fa-regular fa-bell text-xl"></i>
                        <?php if ($total_notif_baru > 0): ?>
                            <span
                                class="absolute top-1 right-2 inline-flex items-center justify-center w-4 h-4 text-[9px] font-bold text-white bg-red-500 rounded-full border-2 border-white"><?= $total_notif_baru > 9 ? '9+' : $total_notif_baru; ?></span>
                        <?php endif; ?>
                    </button>

                    <!-- DROPDOWN NOTIFIKASI -->
                    <div id="dropdownNotif"
                        class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-100 hidden z-50 transform origin-top-right transition-all">
                        <div
                            class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-xl">
                            <h4 class="font-bold text-gray-800"><i class="fa-solid fa-bell text-green-500 mr-2"></i>
                                Notifikasi</h4>
                            <span
                                class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full font-bold"><?= $total_notif_baru; ?>
                                Baru</span>
                        </div>
                        <div class="max-h-80 overflow-y-auto content-scrollbar py-2">
                            <?php if (empty($notifikasi_wali)): ?>
                                <div class="px-4 py-8 text-center">
                                    <i class="fa-regular fa-bell-slash text-3xl text-gray-300 mb-2"></i>
                                    <p class="text-xs text-gray-500 mt-2">Belum ada notifikasi.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifikasi_wali as $notif): ?>
                                    <a href="<?= $notif['link']; ?>"
                                        class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-50 last:border-0 transition-colors">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 <?= $notif['bg']; ?> p-2 rounded-lg">
                                                <i class="fa-solid <?= $notif['ikon']; ?> <?= $notif['warna']; ?>"></i>
                                            </div>
                                            <div class="ml-3 w-0 flex-1">
                                                <p class="text-sm font-bold text-gray-800 leading-tight"><?= $notif['judul']; ?>
                                                </p>
                                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2"><?= $notif['pesan']; ?></p>
                                                <p class="text-[10px] text-gray-400 mt-1 font-medium"><i
                                                        class="fa-regular fa-clock mr-1"></i> <?= $notif['waktu']; ?></p>
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
                    <span class="text-sm font-semibold text-gray-700"><?= escape($currentUser['nama_lengkap']) ?></span>
                </a>
            </div>
        </header>

        <div class="p-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Selamat Datang, Bapak/Ibu
                    <?= escape($currentUser['nama_lengkap']) ?>! 👋
                </h2>
                <?php if ($siswa): ?>
                    <p class="text-gray-500 mt-2 text-lg">
                        Ringkasan administrasi untuk ananda <span
                            class="font-bold text-[#10b981]"><?= escape($siswa['nama_siswa']) ?></span> (Kelas
                        <?= escape($siswa['kelas']) ?>).
                    </p>
                <?php else: ?>
                    <div class="mt-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg shadow-sm">
                        <i class="fa-solid fa-triangle-exclamation mr-2"></i> Akun Anda belum ditautkan dengan data siswa
                        manapun. Silakan hubungi bagian Tata Usaha Sekolah.
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($siswa): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div
                        class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 card-hover transition-smooth relative overflow-hidden group">
                        <div class="absolute bottom-0 left-0 w-full h-1 bg-red-500 rounded-b-xl"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Tunggakan Tagihan</p>
                                <p class="text-3xl font-bold text-gray-800 mt-1 text-red-600">Rp
                                    <?= number_format($totalTunggakan, 0, ',', '.') ?>
                                </p>
                            </div>
                            <div class="bg-red-50 p-3 rounded-lg text-red-500">
                                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 card-hover transition-smooth relative overflow-hidden group">
                        <div class="absolute bottom-0 left-0 w-full h-1 bg-[#10b981] rounded-b-xl"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Pembayaran Lunas</p>
                                <p class="text-3xl font-bold text-gray-800 mt-1 text-green-600">Rp
                                    <?= number_format($totalLunas, 0, ',', '.') ?>
                                </p>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg text-green-500">
                                <i class="fa-solid fa-circle-check text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-chart-pie text-green-500"></i> Rasio Pembayaran
                        </h3>
                        <div class="flex justify-center mt-6">
                            <div class="w-64 h-64">
                                <canvas id="paymentChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fa-regular fa-clock text-yellow-500"></i> Tagihan Belum Lunas Mendatang
                        </h3>
                        <div class="space-y-3 max-h-[280px] overflow-y-auto pr-2 content-scrollbar">
                            <?php
                            if (count($tagihanList) > 0):
                                $belumLunas = array_filter($tagihanList, function ($t) {
                                    return $t['status'] == 'Belum Lunas';
                                });
                                if (count($belumLunas) > 0):
                                    foreach ($belumLunas as $tagihan):
                                        ?>
                                        <div
                                            class="border-b border-gray-100 pb-3 last:border-0 hover:bg-gray-50 p-2 rounded transition">
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <p class="font-bold text-gray-800 text-sm"><?= $tagihan['jenis_tagihan'] ?></p>
                                                    <p class="text-xs text-gray-500 mt-0.5">Periode: <?= $tagihan['bulan'] ?> <?= $tagihan['tahun'] ?></p>
                                                    <?php if(!$tagihan['is_jatuh_tempo']): ?>
                                                        <span class="inline-block mt-1 bg-gray-100 text-gray-500 text-[10px] px-2 py-0.5 rounded-full font-semibold border border-gray-200"><i class="fa-solid fa-lock text-gray-400 mr-1"></i> Belum Jatuh Tempo</span>
                                                    <?php else: ?>
                                                        <span class="inline-block mt-1 bg-red-100 text-red-600 text-[10px] px-2 py-0.5 rounded-full font-semibold border border-red-200"><i class="fa-solid fa-circle-exclamation mr-1"></i> Telat Dibayar</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-red-600 font-bold text-sm">Rp <?= number_format($tagihan['nominal'], 0, ',', '.') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    endforeach;
                                else:
                                    ?>
                                    <div class="text-center text-gray-400 py-10 flex flex-col items-center">
                                        <div class="bg-green-50 p-4 rounded-full mb-3">
                                            <i class="fa-solid fa-shield-check text-4xl text-green-500"></i>
                                        </div>
                                        <p class="font-bold text-gray-600 text-lg">Luar Biasa! 🎉</p>
                                        <p class="text-sm mt-1">Semua tagihan ananda sudah lunas terbayar.</p>
                                    </div>
                                    <?php
                                endif;
                            else:
                                ?>
                                <div class="text-center text-gray-400 py-10">
                                    <i class="fa-regular fa-folder-open text-4xl mb-3 block"></i>
                                    <p>Belum ada data tagihan yang terbit.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (count($belumLunas) > 0): ?>
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <a href="tagihan.php"
                                    class="bg-[#10b981] hover:bg-green-600 text-white w-full text-sm font-bold py-2.5 rounded-lg flex items-center justify-center transition shadow-sm">
                                    Buka Rincian Tagihan <i class="fa-solid fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    </div>

    <script>
        // Logika inisialisasi Chart.js
        <?php if ($siswa && ($totalLunas > 0 || $totalTunggakan > 0)): ?>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('paymentChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Sudah Lunas', 'Belum Lunas'],
                        datasets: [{
                            data: [<?= $totalLunas ?>, <?= $totalTunggakan ?>],
                            backgroundColor: ['#10b981', '#ef4444'],
                            hoverBackgroundColor: ['#059669', '#dc2626'],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            }
                        }
                    }
                });
            });
        <?php endif; ?>
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
        document.addEventListener('click', function (event) {
            const dropdown = document.getElementById('dropdownNotif');
            if (!dropdown) return;
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