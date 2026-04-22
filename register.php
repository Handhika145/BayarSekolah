<?php
session_start();
require 'config/koneksi.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    else header("Location: walimurid/dashboard.php");
    exit;
}

$pesan = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Data Sekolah
    $nama_sekolah  = mysqli_real_escape_string($koneksi, $_POST['nama_sekolah']);
    $email_sekolah = mysqli_real_escape_string($koneksi, $_POST['email_sekolah']);
    $no_telp       = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $alamat        = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    
    // Data Admin Sekolah
    $nama_admin = mysqli_real_escape_string($koneksi, $_POST['nama_admin']);
    $username   = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password   = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi_password'];

    // Validasi Password
    if ($password !== $konfirmasi) {
        $pesan = '<div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg text-sm border-l-4 border-red-500 font-medium">Konfirmasi password tidak cocok!</div>';
    } else {
        // Cek duplikasi Email Sekolah atau Username Admin
        $cek_email = mysqli_query($koneksi, "SELECT * FROM sekolah WHERE email_sekolah='$email_sekolah'");
        $cek_user  = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
        
        if (mysqli_num_rows($cek_email) > 0) {
            $pesan = '<div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg text-sm border-l-4 border-red-500 font-medium">Email sekolah sudah terdaftar!</div>';
        } else if (mysqli_num_rows($cek_user) > 0) {
            $pesan = '<div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg text-sm border-l-4 border-red-500 font-medium">Username admin sudah dipakai, silakan cari yang lain!</div>';
        } else {
            // Mulai Transaksi Database
            mysqli_begin_transaction($koneksi);
            try {
                // 1. Simpan Data Sekolah
                $q_sekolah = "INSERT INTO sekolah (nama_sekolah, email_sekolah, no_telp, alamat) VALUES ('$nama_sekolah', '$email_sekolah', '$no_telp', '$alamat')";
                mysqli_query($koneksi, $q_sekolah);
                
                // Ambil ID Sekolah yang baru saja di-generate
                $id_sekolah_baru = mysqli_insert_id($koneksi);
                
                // 2. Simpan Data Admin (Hubungkan dengan id_sekolah)
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                $q_admin = "INSERT INTO users (id_sekolah, username, password, nama_lengkap, role) VALUES ('$id_sekolah_baru', '$username', '$password_hashed', '$nama_admin', 'admin')";
                mysqli_query($koneksi, $q_admin);
                
                // Jika sukses semua, simpan permanen
                mysqli_commit($koneksi);
                
                $pesan = '<div class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg text-sm border-l-4 border-green-500 text-center font-bold">Pendaftaran Sekolah Berhasil! Mengalihkan ke login...</div>';
                header("refresh:2;url=login.php");
                
            } catch (Exception $e) {
                // Jika error, batalkan semua
                mysqli_rollback($koneksi);
                $pesan = '<div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg text-sm border-l-4 border-red-500 font-medium">Terjadi kesalahan: ' . $e->getMessage() . '</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Sekolah - Layanan SPP Digital</title>
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
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen py-10 px-4">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl border border-gray-100 overflow-hidden flex flex-col md:flex-row">
        
        <!-- Panel Info (Sisi Kiri) -->
        <div class="bg-[#1e293b] p-8 md:w-2/5 text-white flex flex-col justify-between">
            <div>
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-[#10b981] text-white mb-6 shadow-lg">
                    <i class="fa-solid fa-rocket text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold tracking-tight mb-4">Mulai Digitalisasi Sekolah Anda.</h2>
                <p class="text-slate-400 leading-relaxed text-sm mb-6">Bergabunglah dengan layanan SPP Digital kami. Kelola tagihan, pantau tunggakan, dan mudahkan wali murid dalam hitungan menit.</p>
                <ul class="space-y-3 text-sm text-slate-300">
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-[#10b981] mr-3"></i> Multi-pengguna & Aman</li>
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-[#10b981] mr-3"></i> Laporan Otomatis</li>
                    <li class="flex items-center"><i class="fa-solid fa-circle-check text-[#10b981] mr-3"></i> Portal Orang Tua Khusus</li>
                </ul>
            </div>
            <div class="mt-12 text-sm text-slate-500">
                &copy; <?= date('Y'); ?> SPP Digital SaaS.
            </div>
        </div>

        <!-- Form Register (Sisi Kanan) -->
        <div class="p-8 md:w-3/5">
            <div class="mb-6 border-b border-gray-100 pb-4">
                <h3 class="text-xl font-bold text-gray-800">Form Pendaftaran Institusi</h3>
                <p class="text-xs text-gray-500 mt-1">Isi data di bawah ini untuk mendaftarkan sekolah Anda.</p>
            </div>

            <?= $pesan; ?>

            <form action="" method="POST" class="space-y-6">
                <!-- Data Sekolah -->
                <div>
                    <h4 class="text-sm font-bold text-[#10b981] uppercase tracking-wider mb-3"><i class="fa-solid fa-building-columns mr-1"></i> Data Sekolah</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Resmi Sekolah <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_sekolah" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10b981] outline-none text-sm" placeholder="Cth: SMA Harapan Bangsa">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Email Sekolah <span class="text-red-500">*</span></label>
                            <input type="email" name="email_sekolah" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10b981] outline-none text-sm" placeholder="info@sekolah.sch.id">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Nomor Telepon</label>
                            <input type="text" name="no_telp" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10b981] outline-none text-sm" placeholder="021-XXXXXXX">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat Lengkap</label>
                            <textarea name="alamat" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10b981] outline-none text-sm" placeholder="Jln. Pendidikan No. 1..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Data Admin -->
                <div class="pt-4 border-t border-gray-100">
                    <h4 class="text-sm font-bold text-blue-600 uppercase tracking-wider mb-3"><i class="fa-solid fa-user-tie mr-1"></i> Data Administrator Pertama</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap PIC/Admin <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_admin" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Nama petugas TU/Bendahara">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Username Login <span class="text-red-500">*</span></label>
                            <input type="text" name="username" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Buat username tanpa spasi">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password" required minlength="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Minimal 6 karakter">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                            <input type="password" name="konfirmasi_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Ulangi password">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-[#1e293b] hover:bg-slate-800 text-white font-bold py-3 px-4 rounded-lg transition duration-200 shadow-md">
                    Daftarkan Sekolah Sekarang
                </button>
            </form>

            <p class="text-center text-sm text-gray-600 mt-6">
                Sudah terdaftar? 
                <a href="login.php" class="font-bold text-[#10b981] hover:text-green-700 transition">Masuk ke Sistem</a>
            </p>
        </div>

    </div>
</body>
</html>