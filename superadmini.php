<?php
require 'config/koneksi.php';

$username = "founder"; // Username untukmu
$password = password_hash("HnkDhk145663", PASSWORD_DEFAULT); // Password default: admin123
$nama = "CEO SPP Digital";

$query = "INSERT INTO super_admin (username, password, nama_lengkap) VALUES ('$username', '$password', '$nama')";

if (mysqli_query($koneksi, $query)) {
    echo "Akun Super Admin berhasil dibuat!<br>";
    echo "Silakan login menggunakan:<br>Username: <b>founder</b><br>Password: <b>HnkDhk145663</b><br><br>";
    echo "<a href='login.php'>Kembali ke Login</a>";
} else {
    echo "Gagal: " . mysqli_error($koneksi);
}
?>