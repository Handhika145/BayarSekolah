<?php
// =============================================
// Konfigurasi SMTP Gmail untuk PHPMailer
// =============================================
// PENTING: Untuk menggunakan Gmail SMTP, Anda perlu:
// 1. Aktifkan 2-Step Verification di Google Account
// 2. Buat App Password di: https://myaccount.google.com/apppasswords
// 3. Gunakan App Password (bukan password akun biasa) di bawah ini

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'hankaseito145@gmail.com'); // Ganti dengan email Gmail Anda
define('MAIL_PASSWORD', 'xsbn gtrx aasx jvbp'); // Ganti dengan App Password Gmail
define('MAIL_FROM_NAME', 'BayarSekolah'); // Nama pengirim
define('APP_URL', 'http://localhost/sppsekolah'); // URL aplikasi
?>
