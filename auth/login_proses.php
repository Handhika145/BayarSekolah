<?php
session_start();
require '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // Cari user berdasarkan username
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        // Verifikasi password yang di-hash
        if (password_verify($password, $data['password'])) {
            // Set Session
            $_SESSION['id_user'] = $data['id_user'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];

            // Arahkan sesuai role
            if ($data['role'] == 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../walimurid/dashboard.php");
            }
            exit;
        } else {
            $_SESSION['error'] = "Password yang Anda masukkan salah!";
            header("Location: ../login.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Username tidak terdaftar!";
        header("Location: ../login.php");
        exit;
    }
}
?>