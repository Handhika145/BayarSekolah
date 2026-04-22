<?php
session_start();
require '../config/koneksi.php';

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak");
}

if (!isset($_GET['id'])) {
    die("ID Pembayaran tidak ditemukan.");
}

$id_pembayaran = $_GET['id'];
$id_sekolah = $_SESSION['id_sekolah'];

// Ambil data pembayaran
$q = mysqli_query($koneksi, "
    SELECT p.id_pembayaran, p.tgl_bayar, t.id_tagihan, t.jenis_tagihan, t.bulan, t.tahun, t.nominal, 
           s.nama_siswa, s.nisn, s.kelas, u.nama_lengkap AS nama_petugas
    FROM pembayaran p
    JOIN tagihan t ON p.id_tagihan = t.id_tagihan
    JOIN siswa s ON t.id_siswa = s.id_siswa
    JOIN users u ON p.id_admin = u.id_user
    WHERE p.id_pembayaran = '$id_pembayaran' AND s.id_sekolah = '$id_sekolah'
");

if (mysqli_num_rows($q) == 0) {
    die("Data pembayaran tidak ditemukan atau Anda tidak memiliki akses ke data ini.");
}

$data = mysqli_fetch_assoc($q);
$nama_sekolah = $_SESSION['nama_sekolah'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran - <?= htmlspecialchars($data['nama_siswa']) ?></title>
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
        body {
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .struk-container {
            background-color: white;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 8px;
        }
        /* CSS khusus untuk print */
        @media print {
            body {
                background-color: white;
                padding: 0; margin: 0;
            }
            .struk-container {
                box-shadow: none;
                margin: 0; padding: 0;
                width: 100%; max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="struk-container">
        <!-- Header Struk -->
        <div class="text-center border-b-2 border-gray-800 pb-4 mb-4">
            <h1 class="text-xl font-bold uppercase tracking-wider text-gray-900"><?= htmlspecialchars($nama_sekolah) ?></h1>
            <p class="text-sm text-gray-500 mt-1">Bukti Pembayaran Resmi</p>
        </div>

        <!-- Info Transaksi -->
        <div class="text-sm text-gray-700 space-y-2 mb-6">
            <div class="flex justify-between">
                <span class="font-medium text-gray-500">No. Transaksi</span>
                <span class="font-bold">#TRX-<?= sprintf("%05d", $data['id_pembayaran']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium text-gray-500">Tanggal Bayar</span>
                <span class="font-medium"><?= date('d F Y', strtotime($data['tgl_bayar'])) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium text-gray-500">Petugas / Kasir</span>
                <span class="font-medium"><?= htmlspecialchars($data['nama_petugas']) ?></span>
            </div>
        </div>

        <div class="border-t border-dashed border-gray-300 my-4"></div>

        <!-- Info Siswa -->
        <div class="text-sm text-gray-700 space-y-2 mb-6 p-3 bg-gray-50 rounded-lg border border-gray-100">
            <div class="flex flex-col">
                <span class="text-xs text-gray-500 mb-0.5">Nama Siswa</span>
                <span class="font-bold text-gray-900 uppercase"><?= htmlspecialchars($data['nama_siswa']) ?></span>
            </div>
            <div class="flex justify-between">
                <div>
                    <span class="text-xs text-gray-500 block">NISN</span>
                    <span class="font-medium"><?= htmlspecialchars($data['nisn']) ?></span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-500 block">Kelas</span>
                    <span class="font-medium"><?= htmlspecialchars($data['kelas']) ?></span>
                </div>
            </div>
        </div>

        <div class="border-t border-dashed border-gray-300 my-4"></div>

        <!-- Rincian Pembayaran -->
        <div class="mb-6">
            <p class="text-xs text-gray-500 mb-2 uppercase font-bold tracking-wider">Rincian Pembayaran</p>
            <div class="flex justify-between items-start text-sm mt-1">
                <div class="pr-4">
                    <span class="font-bold text-gray-800 block"><?= htmlspecialchars($data['jenis_tagihan']) ?></span>
                    <?php if($data['bulan'] != '-'): ?>
                        <span class="text-gray-500 text-xs">Periode: <?= $data['bulan'] ?> <?= $data['tahun'] ?></span>
                    <?php else: ?>
                        <span class="text-gray-500 text-xs">Tahun: <?= $data['tahun'] ?></span>
                    <?php endif; ?>
                </div>
                <span class="font-bold text-gray-900 whitespace-nowrap">Rp <?= number_format($data['nominal'],0,',','.') ?></span>
            </div>
        </div>

        <div class="border-t-2 border-gray-800 my-4"></div>

        <!-- Total -->
        <div class="flex justify-between items-center mb-8">
            <span class="font-bold text-gray-700 uppercase">Total Lunas</span>
            <span class="text-2xl font-black text-gray-900">Rp <?= number_format($data['nominal'],0,',','.') ?></span>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-500 mt-8 mb-4">
            <p>Terima kasih atas pembayaran Anda.</p>
            <p class="mt-1">Struk ini adalah bukti pembayaran yang sah.</p>
            <p class="mt-4 italic text-[10px]">Dicetak otomatis oleh Sistem SPP Digital</p>
        </div>

        <!-- Print Button (Hidden in Print) -->
        <div class="text-center mt-6 no-print flex flex-col space-y-3">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg transition shadow-md w-full flex justify-center items-center">
                <i class="fa-solid fa-print mr-2"></i> Cetak Struk
            </button>
            <button onclick="window.close()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-6 rounded-lg transition w-full">
                Tutup Jendela
            </button>
        </div>
    </div>

</body>
</html>

