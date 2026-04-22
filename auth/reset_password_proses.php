<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require '../config/koneksi.php';

// Set MySQL timezone agar konsisten dengan PHP
mysqli_query($koneksi, "SET time_zone = '+07:00'");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = mysqli_real_escape_string($koneksi, $_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($token) || empty($password) || empty($confirm_password)) {
        $_SESSION['reset_error'] = "Semua field harus diisi.";
        header("Location: ../reset_password.php?token=" . urlencode($token));
        exit;
    }
    
    // Validasi password match
    if ($password !== $confirm_password) {
        $_SESSION['reset_error'] = "Konfirmasi kata sandi tidak cocok.";
        header("Location: ../reset_password.php?token=" . urlencode($token));
        exit;
    }
    
    // Validasi panjang password
    if (strlen($password) < 6) {
        $_SESSION['reset_error'] = "Kata sandi minimal 6 karakter.";
        header("Location: ../reset_password.php?token=" . urlencode($token));
        exit;
    }
    
    // Cek token valid dan belum expired (1 jam) - gunakan MySQL TIMESTAMPDIFF agar timezone konsisten
    $query = mysqli_query($koneksi, "SELECT * FROM password_resets WHERE token='$token' AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) <= 60 LIMIT 1");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $reset_data = mysqli_fetch_assoc($query);
        $email = $reset_data['email'];
        
        // Hash password baru
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password di tabel users
        $update = mysqli_query($koneksi, "UPDATE users SET password='$password_hashed' WHERE email='$email'");
        
        if ($update && mysqli_affected_rows($koneksi) > 0) {
            // Hapus semua token untuk email ini
            mysqli_query($koneksi, "DELETE FROM password_resets WHERE email='$email'");
            
            $_SESSION['reset_success'] = "Kata sandi berhasil direset! Silakan login dengan kata sandi baru Anda.";
            header("Location: ../reset_password.php?success=1");
            exit;
        } else {
            $_SESSION['reset_error'] = "Gagal memperbarui kata sandi. Silakan coba lagi.";
            header("Location: ../reset_password.php?token=" . urlencode($token));
            exit;
        }
    } else {
        $_SESSION['reset_error'] = "Link reset tidak valid atau sudah kedaluwarsa. Silakan request ulang.";
        header("Location: ../reset_password.php?token=" . urlencode($token));
        exit;
    }
}

// Jika bukan POST, redirect
header("Location: ../forgot_password.php");
exit;
?>
