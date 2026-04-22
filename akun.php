<?php
require 'config/koneksi.php';

// Enkripsi password
$pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
$pass_wali = password_hash('wali123', PASSWORD_DEFAULT);

// Masukkan ke database
mysqli_query($koneksi, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('adminTU', '$pass_admin', 'Budi Bendahara', 'admin')");
mysqli_query($koneksi, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('walimurid1', '$pass_wali', 'Andi Orang Tua', 'walimurid')");

echo "Akun dummy berhasil dibuat! Silakan hapus file ini demi keamanan.";
?>