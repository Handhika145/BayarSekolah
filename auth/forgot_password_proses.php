<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require '../config/koneksi.php';
require '../config/mail_config.php';

// Set MySQL timezone agar konsisten dengan PHP
mysqli_query($koneksi, "SET time_zone = '+07:00'");

// Log function untuk debugging
function debug_log($msg) {
    file_put_contents(__DIR__ . '/forgot_debug.log', date('Y-m-d H:i:s') . " | " . $msg . "\n", FILE_APPEND);
}

debug_log("=== START REQUEST ===");
debug_log("Method: " . $_SERVER["REQUEST_METHOD"]);
debug_log("POST: " . json_encode($_POST));

// PHPMailer
require '../vendor/PHPMailer/src/Exception.php';
require '../vendor/PHPMailer/src/PHPMailer.php';
require '../vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email']));
    debug_log("Email: $email");

    if (empty($email)) {
        debug_log("ERROR: Email kosong");
        $_SESSION['forgot_error'] = "Silakan masukkan alamat email Anda.";
        header("Location: ../forgot_password.php");
        exit;
    }

    // Cek apakah email terdaftar di tabel users
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email' LIMIT 1");
    debug_log("Query result: " . ($query ? "OK, rows=" . mysqli_num_rows($query) : "FAIL: " . mysqli_error($koneksi)));

    if ($query && mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        debug_log("User found: " . $user['nama_lengkap']);

        // Generate token unik
        $token = bin2hex(random_bytes(32));
        debug_log("Token: " . substr($token, 0, 20) . "...");

        // Auto-create tabel password_resets jika belum ada
        mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(100) NOT NULL,
            `token` VARCHAR(64) NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_token` (`token`),
            INDEX `idx_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Hapus token lama untuk email ini (jika ada)
        $del = mysqli_query($koneksi, "DELETE FROM password_resets WHERE email='$email'");
        debug_log("DELETE: " . ($del ? "OK, affected=" . mysqli_affected_rows($koneksi) : "FAIL: " . mysqli_error($koneksi)));

        // Simpan token baru (gunakan MySQL NOW() agar timezone konsisten)
        $insert = mysqli_query($koneksi, "INSERT INTO password_resets (email, token, created_at) VALUES ('$email', '$token', NOW())");
        $insert_id = mysqli_insert_id($koneksi);
        debug_log("INSERT: " . ($insert ? "OK, id=" . $insert_id : "FAIL: " . mysqli_error($koneksi)));

        // Verifikasi token benar-benar tersimpan
        $verify = mysqli_query($koneksi, "SELECT id, token, created_at FROM password_resets WHERE token='$token' LIMIT 1");
        if ($verify && mysqli_num_rows($verify) > 0) {
            $vrow = mysqli_fetch_assoc($verify);
            debug_log("VERIFY: Token tersimpan OK, ID=" . $vrow['id'] . ", created_at=" . $vrow['created_at']);
        } else {
            debug_log("VERIFY: GAGAL - Token TIDAK ditemukan di database setelah INSERT!");
        }

        if ($insert) {
            // Buat link reset
            $reset_link = APP_URL . "/reset_password.php?token=" . $token;

            // Kirim email menggunakan PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Konfigurasi SMTP
                $mail->isSMTP();
                $mail->Host = MAIL_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = MAIL_USERNAME;
                $mail->Password = MAIL_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = MAIL_PORT;
                $mail->CharSet = 'UTF-8';

                // Pengirim & Penerima
                $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
                $mail->addAddress($email, $user['nama_lengkap']);

                // Konten Email
                $mail->isHTML(true);
                $mail->Subject = 'Reset Kata Sandi - BayarSekolah';
                $mail->Body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
                <body style="margin:0; padding:0; background-color:#f4f7f6; font-family: Arial, sans-serif;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7f6; padding: 40px 0;">
                        <tr>
                            <td align="center">
                                <table width="500" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                                    <!-- Header -->
                                    <tr>
                                        <td style="background: linear-gradient(135deg, #1b633c 0%, #398c58 100%); padding: 30px 40px; text-align:center;">
                                            <h1 style="color:#ffffff; margin:0; font-size:24px; letter-spacing:1px;">🌿 BayarSekolah</h1>
                                            <p style="color:#d8f5df; margin:8px 0 0 0; font-size:13px;">Sistem Pembayaran SPP Digital</p>
                                        </td>
                                    </tr>
                                    <!-- Body -->
                                    <tr>
                                        <td style="padding: 35px 40px;">
                                            <h2 style="color:#1b633c; margin:0 0 15px 0; font-size:20px;">Reset Kata Sandi</h2>
                                            <p style="color:#555; font-size:14px; line-height:1.6; margin:0 0 15px 0;">
                                                Halo <strong>' . htmlspecialchars($user['nama_lengkap']) . '</strong>,
                                            </p>
                                            <p style="color:#555; font-size:14px; line-height:1.6; margin:0 0 25px 0;">
                                                Kami menerima permintaan untuk mereset kata sandi akun Anda. Klik tombol di bawah ini untuk membuat kata sandi baru:
                                            </p>
                                            <!-- Button -->
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td align="center">
                                                        <a href="' . $reset_link . '" 
                                                           style="display:inline-block; background: linear-gradient(135deg, #398c58, #2b7a4b); color:#ffffff; text-decoration:none; padding:14px 40px; border-radius:8px; font-size:15px; font-weight:bold; letter-spacing:0.5px; box-shadow: 0 4px 15px rgba(57,140,88,0.3);">
                                                            🔑 Reset Kata Sandi
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                            <p style="color:#888; font-size:12px; line-height:1.6; margin:25px 0 0 0; padding-top:20px; border-top:1px solid #eee;">
                                                ⏰ Link ini hanya berlaku selama <strong>1 jam</strong>. Jika Anda tidak meminta reset kata sandi, abaikan email ini.
                                            </p>
                                            <p style="color:#aaa; font-size:11px; line-height:1.4; margin:15px 0 0 0; word-break:break-all;">
                                                Jika tombol tidak berfungsi, salin link berikut:<br>
                                                <a href="' . $reset_link . '" style="color:#398c58;">' . $reset_link . '</a>
                                            </p>
                                        </td>
                                    </tr>
                                    <!-- Footer -->
                                    <tr>
                                        <td style="background-color:#f8faf9; padding:20px 40px; text-align:center; border-top:1px solid #eee;">
                                            <p style="color:#999; font-size:11px; margin:0;">© ' . date('Y') . ' BayarSekolah - Sistem Pembayaran SPP Digital</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>';

                $mail->AltBody = "Halo " . $user['nama_lengkap'] . ",\n\nKlik link berikut untuk mereset kata sandi Anda:\n" . $reset_link . "\n\nLink berlaku selama 1 jam.\n\n- BayarSekolah";

                $mail->send();

                $_SESSION['forgot_success'] = "Link reset kata sandi telah dikirim ke email Anda. Silakan cek inbox (atau folder spam).";
            } catch (Exception $e) {
                $_SESSION['forgot_error'] = "Gagal mengirim email. Silakan coba lagi nanti. Error: " . $mail->ErrorInfo;
            }
        } else {
            $_SESSION['forgot_error'] = "Gagal menyimpan token: " . mysqli_error($koneksi);
        }
    } else {
        $_SESSION['forgot_error'] = "Email tidak terdaftar dalam sistem kami.";
    }

    header("Location: ../forgot_password.php");
    exit;
}

// Jika bukan POST, redirect ke halaman forgot password
header("Location: ../forgot_password.php");
exit;
?>
