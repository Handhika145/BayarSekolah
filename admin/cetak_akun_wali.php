<?php
session_start();
require '../config/koneksi.php';

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$id_sekolah = $_SESSION['id_sekolah'];
$nama_sekolah = $_SESSION['nama_sekolah'];

// Ambil data Siswa beserta akun walinya dari database untuk sekolah yang bersangkutan
$q_siswa = mysqli_query($koneksi, "
    SELECT s.nama_siswa, s.nisn, s.kelas, u.nama_lengkap AS nama_wali, u.username AS username_wali, u.password_plain
    FROM siswa s 
    INNER JOIN users u ON s.id_walimurid = u.id_user 
    WHERE s.id_sekolah = '$id_sekolah'  AND u.role = 'walimurid'
    ORDER BY s.kelas ASC, s.nama_siswa ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Akun Wali Murid - <?= htmlspecialchars($nama_sekolah); ?></title>
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
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .print-break { page-break-inside: avoid; }
        }
        /* Custom border for printing to ensure it shows up well */
        .card-border { border: 2px dashed #cbd5e1; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 p-4 md:p-8">

    <div class="max-w-6xl mx-auto">
        <!-- Header Non-Print -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-8 no-print bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Cetak Kartu Akun Wali Murid</h1>
                <p class="text-sm text-gray-500 mt-1">Bagikan kartu ini kepada orang tua agar dapat login ke portal.</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <a href="data_siswa.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg font-semibold transition flex items-center">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                </a>
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-bold shadow-md transition flex items-center">
                    <i class="fa-solid fa-print mr-2"></i> Print Sekarang
                </button>
            </div>
        </div>

        <!-- Header Print (Hanya tampil saat print atau di layar) -->
        <div class="text-center mb-10 pb-6 border-b-2 border-gray-300">
            <h2 class="text-3xl font-bold uppercase tracking-wider text-gray-800">Daftar Akun Portal Wali Murid</h2>
            <h3 class="text-xl text-blue-700 font-bold mt-2"><?= htmlspecialchars($nama_sekolah); ?></h3>
            <p class="text-sm mt-1 text-gray-600 italic">Dokumen rahasia. Harap simpan dengan baik.</p>
        </div>

        <!-- Grid Kartu -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php 
            if(mysqli_num_rows($q_siswa) > 0):
                while($row = mysqli_fetch_assoc($q_siswa)): 
            ?>
            <div class="bg-white card-border rounded-xl p-5 print-break relative shadow-sm hover:shadow-md transition">
                <!-- Background Pattern -->
                <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none">
                    <i class="fa-solid fa-shield-halved text-8xl"></i>
                </div>
                
                <div class="border-b-2 border-blue-100 pb-3 mb-4 text-center relative z-10">
                    <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-2 text-blue-600">
                        <i class="fa-regular fa-id-card text-xl"></i>
                    </div>
                    <h4 class="font-bold text-sm text-gray-800 line-clamp-1 truncate px-2" title="<?= htmlspecialchars($row['nama_wali']) ?>">
                        <?= htmlspecialchars($row['nama_wali']); ?>
                    </h4>
                    <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mt-0.5">Wali dari <?= htmlspecialchars($row['nama_siswa']); ?></p>
                </div>
                
                <div class="space-y-2 mb-4 relative z-10">
                    <div class="flex items-center text-xs">
                        <div class="w-20 font-semibold text-gray-500">Siswa</div>
                        <div class="font-bold text-gray-800 truncate flex-1">: <?= htmlspecialchars($row['nama_siswa']); ?></div>
                    </div>
                    <div class="flex items-center text-xs">
                        <div class="w-20 font-semibold text-gray-500">Kelas</div>
                        <div class="font-bold text-gray-800 flex-1">: <?= htmlspecialchars($row['kelas']); ?></div>
                    </div>
                    <div class="flex items-center text-xs">
                        <div class="w-20 font-semibold text-gray-500">NISN</div>
                        <div class="font-bold text-gray-800 flex-1">: <?= htmlspecialchars($row['nisn']); ?></div>
                    </div>
                </div>

                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 relative z-10 text-center">
                    <p class="text-[10px] text-gray-500 font-bold uppercase mb-1">=== Informasi Login ===</p>
                    <div class="flex flex-col space-y-1 my-2">
                        <div class="flex">
                            <span class="text-xs text-gray-500 w-16 text-left">User</span>
                            <span class="font-mono font-bold text-blue-700 text-sm flex-1 text-left">: <?= htmlspecialchars($row['username_wali']); ?></span>
                        </div>
                        <div class="flex">
                            <span class="text-xs text-gray-500 w-16 text-left">Pass</span>
                            <?php $pass_show = !empty($row['password_plain']) ? htmlspecialchars($row['password_plain']) : '<i>[Sesuai Pembuatan]</i>'; ?>
                            <span class="font-mono text-xs text-gray-600 flex-1 text-left">: <?= $pass_show; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-[9px] text-center text-gray-400 font-medium relative z-10 leading-tight">
                    *Gunakan username di atas untuk login ke aplikasi SPP Digital.
                </div>
            </div>
            <?php 
                endwhile;
            else: 
            ?>
                <div class="col-span-full text-center py-16 bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                        <i class="fa-solid fa-users-slash text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700">Belum Ada Akun Wali Murid</h3>
                    <p class="text-gray-500 mt-1 max-w-md mx-auto">Anda belum menambahkan siswa yang tertaut dengan akun wali murid, atau data tidak ditemukan.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>

