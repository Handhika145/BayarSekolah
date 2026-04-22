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

// Ambil notif banding untuk sidebar
$q_notif_banding = mysqli_query($koneksi, "SELECT COUNT(b.id_banding) as total_baru FROM banding b JOIN tagihan t ON b.id_tagihan = t.id_tagihan JOIN siswa s ON t.id_siswa = s.id_siswa WHERE s.id_sekolah = '$id_sekolah' AND b.status_banding = 'Menunggu'");
$notif_banding = mysqli_fetch_assoc($q_notif_banding)['total_baru'];

// Ambil Terms of Service dari superadmin
$q_terms = mysqli_query($koneksi, "SELECT setting_value FROM settings WHERE setting_key='terms_of_service'");
$terms_data = mysqli_fetch_assoc($q_terms);
$terms_content = $terms_data ? $terms_data['setting_value'] : 'Terms of Service belum diatur.';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Admin Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        
        .tos-content h1, .tos-content h2, .tos-content h3 { font-weight: 600; margin-bottom: 0.5rem; margin-top: 1rem; color: #1e293b; }
        .tos-content h1 { font-size: 1.25rem; }
        .tos-content h2 { font-size: 1.1rem; }
        .tos-content p { margin-bottom: 1rem; line-height: 1.7; color: #64748b; font-size: 0.875rem; }
        .tos-content ul, .tos-content ol { margin-bottom: 1rem; padding-left: 1.5rem; color: #64748b; font-size: 0.875rem; }
        .tos-content ul { list-style-type: disc; }
        .tos-content ol { list-style-type: decimal; }
        .tos-content a { color: #334155; text-decoration: underline; }
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
            <a href="dashboard.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-border-all w-7 text-[13px]"></i> Dashboard</a>
            <a href="data_siswa.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-regular fa-user w-7 text-[13px]"></i> Data Siswa & Wali</a>
            <a href="data_tagihan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-regular fa-credit-card w-7 text-[13px]"></i> Tagihan</a>
            <a href="pembayaran.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-money-bill-transfer w-7 text-[13px]"></i> Pembayaran</a>
            <a href="m_rincian.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-list-check w-7 text-[13px]"></i> Master Rincian Biaya</a>
            <a href="laporan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-file-invoice-dollar w-7 text-[13px]"></i> Laporan Keuangan</a>
            <a href="banding.php" class="flex items-center justify-between px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <div class="flex items-center"><i class="fa-solid fa-scale-balanced w-7 text-[13px]"></i> Data Banding</div>
                <?php if(isset($notif_banding) && $notif_banding > 0): ?><span class="bg-red-500/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_banding; ?></span><?php endif; ?>
            </a>
            <a href="terms.php" class="flex items-center px-3 py-2.5 bg-[#10B981] text-white shadow-sm rounded-lg text-[13px] font-medium "><i class="fa-solid fa-file-contract w-7 text-[13px]"></i> Terms of Service</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 ml-[260px] flex flex-col min-w-0">
        
        <!-- HEADER -->
        <header class="h-14 bg-white flex items-center justify-between px-8 border-b border-gray-100 sticky top-0 z-10">
            <h1 class="text-sm font-semibold text-gray-800">Terms of Service</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center bg-[#f0fdf4] text-[#166534] px-4 py-1.5 rounded-full text-xs font-semibold mr-2 border border-green-100"> Admin: <?= $nama_admin; ?> </div>
                <a href="../auth/logout.php" class="flex items-center text-xs text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout</a>
            </div>
        </header>

        <!-- CONTENT -->
        <main class="flex-1 p-6 overflow-y-auto">
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="p-8">
                    <p class="text-[11px] text-gray-400 mb-6 uppercase tracking-widest font-semibold border-b border-gray-50 pb-4">Syarat & Ketentuan Layanan SaaS SPP Digital</p>
                    
                    <div class="tos-content w-full prose max-w-none">
                        <?= $terms_content; ?>
                    </div>

                    <div class="mt-12 bg-gray-50 border border-gray-100 rounded-lg p-4 flex items-start">
                        <i class="fa-solid fa-circle-info text-gray-400 mt-0.5 mr-3"></i>
                        <div>
                            <p class="text-xs text-gray-500">Dengan menggunakan layanan SPP Digital, Anda selaku Admin Sekolah dianggap telah menyetujui seluruh ketentuan yang tercantum di atas secara sah.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
