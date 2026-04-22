<?php
session_start();
// Panggil file koneksi database (pastikan path-nya benar)
require 'config/koneksi.php';

// Jika sudah login, arahkan ke dashboard masing-masing
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'superadmin')
        header("Location: superadmin/dashboard.php");
    elseif ($_SESSION['role'] == 'admin')
        header("Location: admin/dashboard.php");
    else
        header("Location: walimurid/dashboard.php");
    exit;
}

$pesan = '';
$login_success = false;
$redirect_url = '';

// Logika pemrosesan form login (DIPERBAHARUI dengan sistem dari kode ke-2)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // --- GATE 1: CEK SUPER ADMIN ---
    $q_super = mysqli_query($koneksi, "SELECT * FROM super_admin WHERE username='$username'");

    if (mysqli_num_rows($q_super) > 0) {
        $data_super = mysqli_fetch_assoc($q_super);

        // Verifikasi password Super Admin
        if (password_verify($password, $data_super['password'])) {
            $_SESSION['id_user'] = $data_super['id_super'];
            $_SESSION['nama_lengkap'] = $data_super['nama_lengkap'];
            $_SESSION['role'] = 'superadmin';

            $login_success = true;
            $redirect_url = 'superadmin/dashboard.php';
            $pesan = '<div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm text-center animate-pulse">✅ Login Super Admin Berhasil! Mengalihkan ke dashboard...</div>';
        } else {
            $pesan = '<div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm text-center animate-shake">❌ Password salah!</div>';
        }
    }
    // --- GATE 2: CEK ADMIN SEKOLAH & WALI MURID ---
    else {
        // Cari user beserta status sekolahnya
        $q_user = mysqli_query($koneksi, "
            SELECT u.*, s.status AS status_sekolah, s.nama_sekolah 
            FROM users u 
            JOIN sekolah s ON u.id_sekolah = s.id_sekolah 
            WHERE u.username='$username'
        ");

        if (mysqli_num_rows($q_user) > 0) {
            $data_user = mysqli_fetch_assoc($q_user);

            // Verifikasi password
            if (password_verify($password, $data_user['password'])) {

                // --- GATE 3: CEK STATUS VERIFIKASI SEKOLAH ---
                if ($data_user['status_sekolah'] == 'Pending') {
                    $pesan = '<div class="mb-4 p-3 bg-yellow-100 text-yellow-800 rounded-lg text-sm text-center animate-shake">⚠️ Akun sekolah <b>' . htmlspecialchars($data_user['nama_sekolah']) . '</b> masih menunggu verifikasi.</div>';
                } elseif ($data_user['status_sekolah'] == 'Nonaktif' || $data_user['status_sekolah'] == 'Diblokir') {
                    $pesan = '<div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm text-center animate-shake">❌ Akun sekolah Anda telah dinonaktifkan!</div>';
                } else {
                    // Jika lolos semua, set Session
                    $_SESSION['id_user'] = $data_user['id_user'];
                    $_SESSION['id_sekolah'] = $data_user['id_sekolah'];
                    $_SESSION['nama_sekolah'] = $data_user['nama_sekolah']; // <--- TAMBAHKAN BARIS INI
                    $_SESSION['nama_lengkap'] = $data_user['nama_lengkap'];
                    $_SESSION['role'] = $data_user['role'];

                    $login_success = true;
                    if ($data_user['role'] == 'admin') {
                        $redirect_url = 'admin/dashboard.php';
                    } else {
                        $redirect_url = 'walimurid/dashboard.php';
                    }
                    $pesan = '<div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm text-center animate-pulse">✅ Login Berhasil! Mengalihkan ke dashboard...</div>';
                }
            } else {
                $pesan = '<div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm text-center animate-shake">❌ Password salah!</div>';
            }
        } else {
            $pesan = '<div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm text-center animate-shake">❌ Username tidak terdaftar!</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Login - BayarSekolah</title>

    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome untuk Ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lottie Player -->
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

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
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Konfigurasi Tailwind Custom Warna & Font -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        'brand-light': '#d8f5df',
                        'brand-dark': '#1b633c',
                        'brand-primary': '#398c58',
                        'brand-primary-hover': '#2b7a4b',
                        'social-btn': '#52a563',
                        'social-btn-hover': '#3d824b',
                    },
                    animation: {
                        'shake': 'shake 0.5s ease-in-out',
                        'float': 'float 3s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite',
                        'slide-in-left': 'slideInLeft 0.6s ease-out',
                        'slide-in-right': 'slideInRight 0.6s ease-out',
                        'fade-in-up': 'fadeInUp 0.8s ease-out',
                    },
                    keyframes: {
                        shake: {
                            '0%, 100%': { transform: 'translateX(0)' },
                            '25%': { transform: 'translateX(-5px)' },
                            '75%': { transform: 'translateX(5px)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        glow: {
                            '0%, 100%': { boxShadow: '0 0 5px rgba(57, 140, 88, 0.5)' },
                            '50%': { boxShadow: '0 0 20px rgba(57, 140, 88, 0.8)' },
                        },
                        slideInLeft: {
                            '0%': { transform: 'translateX(-100px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' },
                        },
                        slideInRight: {
                            '0%': { transform: 'translateX(100px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' },
                        },
                        fadeInUp: {
                            '0%': { transform: 'translateY(30px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                    },
                }
            }
        }
    </script>

    <style>
        /* Reset margin & padding untuk menghilangkan scroll */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            overflow: hidden;
            height: 100vh;
            width: 100vw;
            font-family: 'Poppins', sans-serif;
        }

        /* Container utama full viewport tanpa scroll */
        .main-container {
            display: flex;
            flex-direction: row;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* Sisi kiri dan kanan full height */
        .left-side,
        .right-side {
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Custom scrollbar yang lebih halus */
        .left-side::-webkit-scrollbar,
        .right-side::-webkit-scrollbar {
            width: 4px;
        }

        .left-side::-webkit-scrollbar-track,
        .right-side::-webkit-scrollbar-track {
            background: transparent;
        }

        .left-side::-webkit-scrollbar-thumb,
        .right-side::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }

        /* Efek partikel floating */
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: floatParticle linear infinite;
        }

        @keyframes floatParticle {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 0.5;
            }

            90% {
                opacity: 0.5;
            }

            100% {
                transform: translateY(-20vh) rotate(360deg);
                opacity: 0;
            }
        }

        /* Efek loading pada tombol */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading .btn-text {
            opacity: 0.7;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Efek hover pada input */
        input:hover {
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }

        /* Efek gradient border pada kotak login */
        .gradient-border {
            position: relative;
            background: white;
            border-radius: 1rem;
        }

        .gradient-border::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #398c58, #d8f5df, #398c58);
            border-radius: 1rem;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gradient-border:hover::before {
            opacity: 1;
        }

        /* Bubble floating */
        .bubble {
            position: absolute;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.2));
            border-radius: 50%;
            pointer-events: none;
            animation: bubbleFloat linear infinite;
        }

        @keyframes bubbleFloat {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }

            10% {
                opacity: 0.5;
            }

            90% {
                opacity: 0.5;
            }

            100% {
                transform: translateY(-20vh) scale(1);
                opacity: 0;
            }
        }

        .bubble-1 {
            width: 80px;
            height: 80px;
            left: 10%;
            animation-duration: 8s;
            animation-delay: 0s;
        }

        .bubble-2 {
            width: 120px;
            height: 120px;
            right: 15%;
            animation-duration: 12s;
            animation-delay: 2s;
        }

        .bubble-3 {
            width: 60px;
            height: 60px;
            left: 20%;
            bottom: 0;
            animation-duration: 6s;
            animation-delay: 4s;
        }

        /* Gambar hero section */
        .hero-image {
            transition: transform 0.3s ease;
            filter: none;
            -webkit-filter: none;
        }

        /* Teks hero section di atas dengan background semi transparan */
        .hero-content {
            background: rgba(216, 245, 223, 0.93);
            backdrop-filter: blur(8px);
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 1rem;
            width: 90%;
            max-width: 480px;
        }

        /* ===== ANIMASI HERO SECTION ===== */

        /* Daun beterbangan */
        .floating-leaf {
            position: absolute;
            pointer-events: none;
            z-index: 5;
            opacity: 0;
        }

        .floating-leaf svg {
            filter: drop-shadow(1px 2px 2px rgba(0, 0, 0, 0.15));
        }

        @keyframes leafFloat1 {
            0% {
                transform: translate(-60px, -40px) rotate(0deg) scale(0.7);
                opacity: 0;
            }

            8% {
                opacity: 0.85;
            }

            50% {
                transform: translate(calc(50vw * 0.4), calc(50vh * 0.5)) rotate(180deg) scale(1);
                opacity: 0.7;
            }

            85% {
                opacity: 0.5;
            }

            100% {
                transform: translate(calc(50vw * 0.8), calc(100vh * 0.7)) rotate(360deg) scale(0.5);
                opacity: 0;
            }
        }

        @keyframes leafFloat2 {
            0% {
                transform: translate(50vw, -30px) rotate(0deg) scale(0.6);
                opacity: 0;
            }

            10% {
                opacity: 0.8;
            }

            40% {
                transform: translate(calc(50vw * 0.3), calc(50vh * 0.3)) rotate(-120deg) scale(0.9);
            }

            70% {
                opacity: 0.6;
            }

            100% {
                transform: translate(-40px, calc(100vh * 0.8)) rotate(-360deg) scale(0.4);
                opacity: 0;
            }
        }

        @keyframes leafFloat3 {
            0% {
                transform: translate(calc(50vw * 0.5), -50px) rotate(45deg) scale(0.8);
                opacity: 0;
            }

            12% {
                opacity: 0.75;
            }

            55% {
                transform: translate(calc(50vw * 0.7), calc(50vh * 0.4)) rotate(200deg) scale(1.1);
            }

            100% {
                transform: translate(calc(50vw * 0.2), calc(100vh * 0.9)) rotate(400deg) scale(0.3);
                opacity: 0;
            }
        }

        @keyframes leafFloat4 {
            0% {
                transform: translate(calc(50vw * 0.8), -20px) rotate(-30deg) scale(0.5);
                opacity: 0;
            }

            15% {
                opacity: 0.7;
            }

            45% {
                transform: translate(calc(50vw * 0.4), calc(50vh * 0.6)) rotate(150deg) scale(0.85);
            }

            100% {
                transform: translate(-20px, calc(100vh * 0.6)) rotate(330deg) scale(0.35);
                opacity: 0;
            }
        }

        @keyframes leafFloat5 {
            0% {
                transform: translate(calc(50vw * 0.2), -60px) rotate(60deg) scale(0.9);
                opacity: 0;
            }

            10% {
                opacity: 0.8;
            }

            60% {
                transform: translate(calc(50vw * 0.6), calc(50vh * 0.5)) rotate(-160deg) scale(1);
            }

            100% {
                transform: translate(calc(50vw * 0.9), calc(100vh * 0.85)) rotate(-350deg) scale(0.4);
                opacity: 0;
            }
        }

        .leaf-1 {
            animation: leafFloat1 11s ease-in-out infinite;
            animation-delay: 0s;
        }

        .leaf-2 {
            animation: leafFloat2 14s ease-in-out infinite;
            animation-delay: 2.5s;
        }

        .leaf-3 {
            animation: leafFloat3 9s ease-in-out infinite;
            animation-delay: 5s;
        }

        .leaf-4 {
            animation: leafFloat4 13s ease-in-out infinite;
            animation-delay: 1s;
        }

        .leaf-5 {
            animation: leafFloat5 16s ease-in-out infinite;
            animation-delay: 7s;
        }

        .leaf-6 {
            animation: leafFloat1 12s ease-in-out infinite;
            animation-delay: 4s;
        }

        .leaf-7 {
            animation: leafFloat3 10s ease-in-out infinite;
            animation-delay: 8s;
        }

        .leaf-8 {
            animation: leafFloat2 15s ease-in-out infinite;
            animation-delay: 3s;
        }

        /* Awan bergerak */
        .cloud {
            position: absolute;
            z-index: 4;
            pointer-events: none;
            opacity: 0;
        }

        @keyframes cloudDrift1 {
            0% {
                transform: translateX(-120%);
                opacity: 0;
            }

            5% {
                opacity: 0.4;
            }

            90% {
                opacity: 0.35;
            }

            100% {
                transform: translateX(120%);
                opacity: 0;
            }
        }

        @keyframes cloudDrift2 {
            0% {
                transform: translateX(120%);
                opacity: 0;
            }

            5% {
                opacity: 0.3;
            }

            90% {
                opacity: 0.25;
            }

            100% {
                transform: translateX(-120%);
                opacity: 0;
            }
        }

        .cloud-1 {
            top: 5%;
            animation: cloudDrift1 25s linear infinite;
            animation-delay: 0s;
        }

        .cloud-2 {
            top: 12%;
            animation: cloudDrift2 35s linear infinite;
            animation-delay: 8s;
        }

        .cloud-3 {
            top: 8%;
            animation: cloudDrift1 30s linear infinite;
            animation-delay: 15s;
        }

        /* Shimmer / cahaya berkilau yang menyapu */
        .shimmer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 3;
            pointer-events: none;
            overflow: hidden;
        }

        .shimmer-overlay::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.08),
                    rgba(255, 255, 255, 0.15),
                    rgba(255, 255, 255, 0.08),
                    transparent);
            animation: shimmerSweep 8s ease-in-out infinite;
            animation-delay: 3s;
        }

        @keyframes shimmerSweep {
            0% {
                left: -100%;
            }

            100% {
                left: 200%;
            }
        }

        /* Efek kunang-kunang / sparkle */
        .sparkle {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.9), rgba(180, 240, 200, 0.6));
            pointer-events: none;
            z-index: 6;
            box-shadow: 0 0 6px 2px rgba(180, 240, 200, 0.4);
        }

        @keyframes sparkleFloat {

            0%,
            100% {
                opacity: 0;
                transform: translateY(0) scale(0.5);
            }

            20% {
                opacity: 1;
                transform: translateY(-15px) scale(1);
            }

            50% {
                opacity: 0.7;
                transform: translateY(-30px) scale(0.8);
            }

            80% {
                opacity: 0.3;
                transform: translateY(-10px) scale(0.6);
            }
        }

        .sparkle-1 {
            left: 15%;
            top: 60%;
            animation: sparkleFloat 4s ease-in-out infinite;
            animation-delay: 0s;
        }

        .sparkle-2 {
            left: 35%;
            top: 55%;
            animation: sparkleFloat 5s ease-in-out infinite;
            animation-delay: 1.5s;
        }

        .sparkle-3 {
            left: 55%;
            top: 65%;
            animation: sparkleFloat 3.5s ease-in-out infinite;
            animation-delay: 3s;
        }

        .sparkle-4 {
            left: 75%;
            top: 50%;
            animation: sparkleFloat 6s ease-in-out infinite;
            animation-delay: 2s;
        }

        .sparkle-5 {
            left: 25%;
            top: 45%;
            animation: sparkleFloat 4.5s ease-in-out infinite;
            animation-delay: 4s;
        }

        .sparkle-6 {
            left: 65%;
            top: 70%;
            animation: sparkleFloat 5.5s ease-in-out infinite;
            animation-delay: 0.5s;
        }

        .sparkle-7 {
            left: 45%;
            top: 40%;
            animation: sparkleFloat 3s ease-in-out infinite;
            animation-delay: 5s;
        }

        .sparkle-8 {
            left: 85%;
            top: 45%;
            animation: sparkleFloat 4s ease-in-out infinite;
            animation-delay: 2.5s;
        }

        /* Efek angin/goyang halus pada gambar utama (karakter + pohon) */
        @keyframes windSway {
            0% {
                transform: scale(1.02) rotate(0deg) translateX(0);
            }

            15% {
                transform: scale(1.02) rotate(0.3deg) translateX(2px);
            }

            30% {
                transform: scale(1.025) rotate(-0.2deg) translateX(-1px);
            }

            45% {
                transform: scale(1.02) rotate(0.15deg) translateX(1.5px);
            }

            60% {
                transform: scale(1.022) rotate(-0.25deg) translateX(-2px);
            }

            75% {
                transform: scale(1.02) rotate(0.2deg) translateX(1px);
            }

            90% {
                transform: scale(1.025) rotate(-0.1deg) translateX(-0.5px);
            }

            100% {
                transform: scale(1.02) rotate(0deg) translateX(0);
            }
        }

        .hero-breathe {
            transform-origin: bottom center;
            animation: windSway 8s ease-in-out infinite;
        }

        /* Bayangan cahaya bergerak di bawah karakter */
        .ground-glow {
            position: absolute;
            bottom: 18%;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 8px;
            background: radial-gradient(ellipse, rgba(57, 140, 88, 0.2) 0%, transparent 70%);
            z-index: 2;
            pointer-events: none;
            animation: groundGlowPulse 4s ease-in-out infinite;
        }

        @keyframes groundGlowPulse {

            0%,
            100% {
                opacity: 0.5;
                transform: translateX(-50%) scaleX(1);
            }

            50% {
                opacity: 0.8;
                transform: translateX(-50%) scaleX(1.1);
            }
        }

        /* Burung kecil terbang */
        .bird {
            position: absolute;
            z-index: 5;
            pointer-events: none;
            font-size: 10px;
            color: #2d6a3e;
            opacity: 0;
        }

        @keyframes birdFly1 {
            0% {
                transform: translate(-30px, 0) scaleX(1);
                opacity: 0;
            }

            5% {
                opacity: 0.6;
            }

            50% {
                transform: translate(calc(50vw * 0.5), -30px) scaleX(1);
                opacity: 0.5;
            }

            90% {
                opacity: 0.3;
            }

            100% {
                transform: translate(calc(50vw), -15px) scaleX(1);
                opacity: 0;
            }
        }

        @keyframes birdFly2 {
            0% {
                transform: translate(50vw, 0) scaleX(-1);
                opacity: 0;
            }

            5% {
                opacity: 0.5;
            }

            50% {
                transform: translate(calc(50vw * 0.3), 20px) scaleX(-1);
                opacity: 0.4;
            }

            90% {
                opacity: 0.2;
            }

            100% {
                transform: translate(-30px, 10px) scaleX(-1);
                opacity: 0;
            }
        }

        .bird-1 {
            top: 15%;
            animation: birdFly1 18s linear infinite;
            animation-delay: 2s;
        }

        .bird-2 {
            top: 20%;
            animation: birdFly2 22s linear infinite;
            animation-delay: 10s;
        }

        .bird-3 {
            top: 10%;
            animation: birdFly1 15s linear infinite;
            animation-delay: 6s;
        }

        /* Loading Overlay untuk transisi halus */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #d8f5df 0%, #1b633c 100%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #398c58;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 20px;
        }

        .loading-text {
            color: white;
            font-size: 18px;
            font-weight: 500;
            margin-top: 20px;
        }

        .loading-progress {
            width: 200px;
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 15px;
        }

        .loading-progress-bar {
            height: 100%;
            background: white;
            width: 0%;
            transition: width 0.1s linear;
        }

        /* Responsif untuk mobile */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }

            .left-side,
            .right-side {
                height: 50vh;
            }

            .left-side {
                min-height: 50vh;
            }

            .right-side {
                min-height: 50vh;
            }

            body {
                overflow-y: auto;
            }

            .main-container {
                overflow-y: auto;
            }

            .hero-content {
                padding: 1rem;
                margin: 1rem;
                width: auto;
            }
        }

        /* Untuk layar sangat kecil (landscape) */
        @media (max-height: 600px) and (orientation: landscape) {

            .left-side,
            .right-side {
                overflow-y: auto;
            }

            .hero-content {
                padding: 0.75rem;
                margin-top: 1rem;
            }
        }
    </style>
</head>

<body>

    <!-- Loading Overlay untuk transisi setelah login sukses -->
    <div class="loading-overlay" id="loadingOverlay">
        <!-- Backdrop Glassmorphism -->
        <div class="absolute inset-0 bg-white/80 backdrop-blur-md z-0 transition-opacity duration-300"></div>

        <!-- Main Card -->
        <div class="relative z-10 flex flex-col items-center p-8 bg-white/95 rounded-[2rem] shadow-[0_20px_50px_rgba(57,140,88,0.2)] border border-white w-[90%] max-w-[400px] transform transition-transform duration-500 scale-95 opacity-0"
            id="loadingCard">
            <!-- Brand Logo -->
            <div class="flex items-center gap-3 mb-2 mt-2">
                <div
                    class="w-12 h-12 bg-brand-primary text-white rounded-xl flex items-center justify-center shadow-lg shadow-brand-primary/30">
                    <i class="fa-solid fa-leaf text-2xl animate-pulse"></i>
                </div>
                <h2 class="text-2xl font-bold text-brand-dark tracking-tight">Bayar<span
                        class="text-brand-primary">Sekolah</span></h2>
            </div>

            <!-- Lottie Animation -->
            <div class="my-4">
                <lottie-player src="assets/animation/loading_login.json" background="transparent" speed="1.2"
                    style="width: 220px; height: 220px;" loop autoplay></lottie-player>
            </div>

            <!-- Text & Progress -->
            <div class="text-lg font-bold text-brand-dark mb-1">Berhasil Masuk!</div>
            <div class="text-sm text-brand-dark/70 mb-6 font-medium text-center">Menyiapkan dashboard Anda...</div>

            <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden relative shadow-inner">
                <div class="h-full bg-brand-primary rounded-full relative overflow-hidden transition-all duration-[30ms]"
                    id="progressBar">
                    <!-- Shimmer inline style CSS handled via CSS later -->
                    <div
                        style="position: absolute; top:0; left:0; width:100%; height:100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent); animation: shimmer_prog 1.5s infinite;">
                    </div>
                </div>
            </div>
            <div class="mt-2 text-xs font-bold text-brand-primary tracking-widest text-center" id="progressText">0%
            </div>
        </div>

        <style>
            @keyframes shimmer_prog {
                0% {
                    transform: translateX(-100%);
                }

                100% {
                    transform: translateX(100%);
                }
            }

            .loading-overlay.active #loadingCard {
                transform: scale(1);
                opacity: 1;
            }
        </style>
    </div>

    <div class="main-container">
        <!-- Sisi Kiri (Informasi & Ilustrasi) -->
        <div class="left-side w-full md:w-1/2 relative bg-brand-light flex flex-col items-center justify-start overflow-hidden"
            data-aos="fade-right" data-aos-duration="800">

            <!-- Gambar Ilustrasi dengan efek nafas halus -->
            <img src="assets/img/herosectionlogin.png" alt="Ilustrasi Background"
                class="hero-image hero-breathe absolute inset-0 w-full h-full object-cover object-center z-0 pointer-events-none">

            <!-- Shimmer cahaya menyapu -->
            <div class="shimmer-overlay"></div>

            <!-- Awan bergerak -->
            <div class="cloud cloud-1">
                <svg width="120" height="50" viewBox="0 0 120 50" fill="none">
                    <ellipse cx="60" cy="30" rx="55" ry="18" fill="white" opacity="0.6" />
                    <ellipse cx="40" cy="22" rx="30" ry="16" fill="white" opacity="0.5" />
                    <ellipse cx="80" cy="25" rx="25" ry="14" fill="white" opacity="0.4" />
                </svg>
            </div>
            <div class="cloud cloud-2">
                <svg width="90" height="40" viewBox="0 0 90 40" fill="none">
                    <ellipse cx="45" cy="24" rx="40" ry="14" fill="white" opacity="0.5" />
                    <ellipse cx="30" cy="18" rx="22" ry="12" fill="white" opacity="0.4" />
                    <ellipse cx="60" cy="20" rx="20" ry="10" fill="white" opacity="0.35" />
                </svg>
            </div>
            <div class="cloud cloud-3">
                <svg width="100" height="45" viewBox="0 0 100 45" fill="none">
                    <ellipse cx="50" cy="28" rx="46" ry="15" fill="white" opacity="0.5" />
                    <ellipse cx="35" cy="20" rx="26" ry="13" fill="white" opacity="0.4" />
                    <ellipse cx="68" cy="22" rx="22" ry="11" fill="white" opacity="0.35" />
                </svg>
            </div>

            <!-- Daun-daun beterbangan (8 daun dengan trajectory berbeda) -->
            <div class="floating-leaf leaf-1">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="#2d8a4e">
                    <path
                        d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" />
                </svg>
            </div>
            <div class="floating-leaf leaf-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#3fa660">
                    <path
                        d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" />
                </svg>
            </div>
            <div class="floating-leaf leaf-3">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="#1b7a3a">
                    <path
                        d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" />
                </svg>
            </div>
            <div class="floating-leaf leaf-4">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="#4db870">
                    <path
                        d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" />
                </svg>
            </div>
            <div class="floating-leaf leaf-5">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="#2d8a4e">
                    <path
                        d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" />
                </svg>
            </div>
            <div class="floating-leaf leaf-6">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="#3fa660">
                    <path
                        d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" />
                </svg>
            </div>
            <div class="floating-leaf leaf-7">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="#1b7a3a">
                    <path
                        d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" />
                </svg>
            </div>
            <div class="floating-leaf leaf-8">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="#4db870">
                    <path
                        d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" />
                </svg>
            </div>

            <!-- Sparkle / kunang-kunang -->
            <div class="sparkle sparkle-1"></div>
            <div class="sparkle sparkle-2"></div>
            <div class="sparkle sparkle-3"></div>
            <div class="sparkle sparkle-4"></div>
            <div class="sparkle sparkle-5"></div>
            <div class="sparkle sparkle-6"></div>
            <div class="sparkle sparkle-7"></div>
            <div class="sparkle sparkle-8"></div>

            <!-- Burung kecil terbang -->
            <div class="bird bird-1">🐦</div>
            <div class="bird bird-2">🐦</div>
            <div class="bird bird-3">🕊️</div>

            <!-- Glow di bawah karakter -->
            <div class="ground-glow"></div>

            <!-- Konten Teks (di atas gambar dengan background solid) - POSISI DI ATAS -->
            <div class="relative z-10 flex flex-col items-center px-8 text-center hero-content">
                <div class="flex items-center gap-3 mb-2 group">
                    <i
                        class="fa-solid fa-leaf text-4xl text-brand-primary-hover group-hover:rotate-12 transition-transform duration-300"></i>
                    <h1
                        class="text-3xl font-bold text-brand-primary-hover tracking-tight group-hover:scale-105 transition-transform duration-300">
                        BayarSekolah</h1>
                </div>

                <p class="text-[#1a4a2d] font-medium text-sm md:text-base">
                    Pembayaran Sekolah Mudah & Aman.<br>
                    Selamat Datang Kembali!
                </p>


            </div>
        </div>

        <!-- Sisi Kanan (Form Login) -->
        <div class="right-side w-full md:w-1/2 bg-brand-dark flex items-center justify-center py-12 md:py-20 px-6 relative overflow-y-auto min-h-screen"
            data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
            <!-- Elemen dekoratif gelombang dengan animasi -->
            <div
                class="absolute inset-0 opacity-30 bg-[radial-gradient(circle_at_top_left,transparent_20%,#155230_21%,#155230_100%)] animate-pulse">
            </div>

            <!-- Floating bubbles -->
            <div class="bubble bubble-1"></div>
            <div class="bubble bubble-2"></div>
            <div class="bubble bubble-3"></div>

            <!-- Kotak Login (Landscape Mode - Lebar & Ringkas) -->
            <div class="bg-white/90 backdrop-blur-xl px-8 md:px-12 py-6 rounded-[2.5rem] shadow-[0_20px_60px_rgba(27,99,60,0.15)] w-full max-w-[500px] z-10 relative border border-white/50 overflow-hidden group/card my-8 md:my-10 shrink-0"
                data-aos="fade-in-up" data-aos-duration="600">

                <!-- Dekorasi Internal Kotak -->
                <div
                    class="absolute -top-10 -right-10 w-32 h-32 bg-brand-primary/10 rounded-full blur-2xl group-hover/card:bg-brand-primary/20 transition-colors duration-700">
                </div>
                <div
                    class="absolute bottom-0 -left-10 w-24 h-24 bg-green-400/10 rounded-full blur-xl group-hover/card:bg-green-400/20 transition-colors duration-700">
                </div>

                <!-- Header Form yang Meriah -->
                <div class="text-center mb-6 relative z-10">
                    <div class="relative w-16 h-16 mx-auto mb-3">
                        <!-- Animasi muter di belakang logo -->
                        <div
                            class="absolute inset-0 bg-gradient-to-tr from-brand-primary to-green-300 rounded-2xl animate-[spin_4s_linear_infinite] opacity-30 blur-sm">
                        </div>
                        <div
                            class="absolute inset-0 bg-white rounded-2xl shadow-lg border border-brand-light flex items-center justify-center transform group-hover/card:scale-110 transition-transform duration-500">
                            <i class="fa-solid fa-graduation-cap text-2xl text-brand-primary animate-bounce mt-1"></i>
                        </div>
                        <div
                            class="absolute -right-1 -bottom-1 bg-green-100 text-brand-dark text-[9px] font-bold px-1.5 py-0.5 rounded-full border border-green-200 shadow-sm animate-pulse">
                            Aman
                        </div>
                    </div>
                    <h2
                        class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-brand-dark to-brand-primary tracking-tight">
                        Selamat Datang!</h2>
                    <p class="text-[13px] font-medium text-gray-500 mt-1.5">Kelola administrasi dengan <span
                            class="text-brand-primary font-bold">Mudah & Aman</span>.</p>
                </div>

                <!-- Output Pesan PHP dengan efek -->
                <div class="relative z-10">
                    <?= $pesan; ?>
                </div>

                <form action="" method="POST" id="loginForm" class="relative z-10">
                    <!-- Input Username -->
                    <div class="mb-5 group/input">
                        <div class="relative flex items-center">
                            <div
                                class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-gray-400 group-focus-within/input:text-brand-primary transition-colors duration-300">
                                <i
                                    class="fa-solid fa-user-astronaut text-lg group-hover/input:scale-110 transition-transform"></i>
                            </div>
                            <input type="text" id="username" name="username" required
                                class="w-full pl-12 pr-4 py-4 bg-gray-50/80 border-2 border-transparent rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-brand-primary focus:shadow-[0_0_15px_rgba(57,140,88,0.2)] transition-all duration-300 group-hover/input:bg-white/80"
                                placeholder="Masukkan Email / Username">
                        </div>
                    </div>

                    <!-- Input Password -->
                    <div class="mb-6 group/input relative">
                        <div class="relative flex items-center">
                            <div
                                class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-gray-400 group-focus-within/input:text-brand-primary transition-colors duration-300">
                                <i
                                    class="fa-solid fa-shield-halved text-lg group-hover/input:scale-110 transition-transform"></i>
                            </div>
                            <input type="password" id="password" name="password" required
                                class="w-full pl-12 pr-12 py-4 bg-gray-50/80 border-2 border-transparent rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-brand-primary focus:shadow-[0_0_15px_rgba(57,140,88,0.2)] transition-all duration-300 group-hover/input:bg-white/80"
                                placeholder="Masukkan Kata Sandi">
                            <div
                                class="absolute inset-y-0 right-0 pr-5 flex items-center cursor-pointer text-gray-400 hover:text-brand-primary transition-colors duration-300">
                                <i class="fa-regular fa-eye-slash text-lg hover:scale-110 transition-transform"
                                    id="togglePassword"></i>
                            </div>
                        </div>
                        <div class="flex justify-end mt-3">
                            <a href="forgot_password.php"
                                class="text-xs font-bold text-brand-primary hover:text-brand-dark hover:underline underline-offset-2 transition-all duration-300 flex items-center gap-1"><i
                                    class="fa-solid fa-key"></i> Lupa kata sandi?</a>
                        </div>
                    </div>

                    <!-- Tombol Masuk yang Ramai -->
                    <button type="submit"
                        class="w-full relative overflow-hidden bg-gradient-to-r from-brand-primary via-[#4ade80] to-brand-dark bg-[length:200%_auto] hover:bg-right text-white font-bold py-4 rounded-2xl shadow-[0_10px_20px_rgba(57,140,88,0.3)] hover:shadow-[0_15px_30px_rgba(57,140,88,0.4)] transform hover:-translate-y-1 active:translate-y-0 transition-all duration-500 mb-6 group/btn"
                        id="submitBtn">
                        <span class="btn-text relative z-10 flex items-center justify-center gap-2 text-lg">
                            Masuk Sekarang <i
                                class="fa-solid fa-rocket group-hover/btn:translate-x-1 group-hover/btn:-translate-y-1 transition-transform duration-300"></i>
                        </span>
                        <!-- Efek kilau -->
                        <div
                            class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover/btn:translate-x-[250%] transition-transform duration-1000 ease-in-out z-0">
                        </div>
                    </button>

                    <!-- Dekorasi Divider yang menarik -->
                    <div class="flex items-center justify-center space-x-4 mb-6 relative z-10">
                        <div class="h-[2px] w-full bg-gradient-to-r from-transparent to-gray-200"></div>
                        <div class="bg-gray-100 rounded-full px-3 py-1 flex items-center shadow-inner">
                            <span
                                class="text-[10px] text-gray-500 font-extrabold uppercase tracking-widest flex gap-1 items-center"><i
                                    class="fa-solid fa-star text-yellow-500 animate-pulse"></i> Sekolah Baru</span>
                        </div>
                        <div class="h-[2px] w-full bg-gradient-to-l from-transparent to-gray-200"></div>
                    </div>

                    <!-- Tombol Daftar Sekolah -->
                    <a href="register.php"
                        class="relative flex items-center justify-center w-full py-4 px-4 bg-white border-2 border-brand-light rounded-2xl text-sm font-bold text-brand-dark hover:text-white hover:border-brand-primary hover:bg-brand-primary transition-all duration-500 gap-2 group/reg shadow-sm hover:shadow-lg overflow-hidden z-10">
                        <!-- Hover background fill -->
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-brand-primary to-brand-dark transform scale-x-0 group-hover/reg:scale-x-100 origin-left transition-transform duration-500 z-0 opacity-90">
                        </div>
                        <i
                            class="fa-solid fa-building-flag text-brand-primary group-hover/reg:text-white relative z-10 transition-colors duration-500 text-lg group-hover/reg:animate-pulse"></i>
                        <span class="relative z-10">Daftarkan Sekolah Anda Sekarang</span>
                    </a>
                </form>
            </div>
        </div>
    </div>

    <!-- Partikel Latar Belakang Dinamis -->
    <div id="particles-container" class="fixed inset-0 pointer-events-none z-0"></div>

    <!-- Script AOS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Cek apakah login sukses dari PHP
        const loginSuccess = <?= json_encode($login_success) ?>;
        const redirectUrl = <?= json_encode($redirect_url) ?>;

        // Inisialisasi AOS
        AOS.init({
            once: true,
            mirror: false,
            duration: 800
        });

        // Toggle Password
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function (e) {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
                this.classList.toggle('fa-eye');

                // Efek getar pada ikon
                this.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 200);
            });
        }

        // Fungsi untuk menampilkan loading overlay dengan animasi progress
        function showLoadingOverlay(callback) {
            const overlay = document.getElementById('loadingOverlay');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');

            overlay.classList.add('active');

            let progress = 0;
            // Buat animasi lebih sedikit lambat & realistis (2.5 detik total)
            const interval = setInterval(() => {
                // Randomize increment between 1 and 3 for more natural feel
                progress += Math.floor(Math.random() * 3) + 1;

                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    progressBar.style.width = '100%';
                    if (progressText) progressText.innerText = '100%';

                    // Jeda sejenak sebelum redirect
                    setTimeout(() => {
                        if (callback) callback();
                    }, 400);
                } else {
                    progressBar.style.width = progress + '%';
                    if (progressText) progressText.innerText = progress + '%';
                }
            }, 35);
        }

        // Jika login sukses, tampilkan loading overlay lalu redirect
        if (loginSuccess && redirectUrl) {
            // Nonaktifkan form
            const form = document.getElementById('loginForm');
            if (form) {
                form.style.opacity = '0.5';
                form.style.pointerEvents = 'none';
            }

            // Tampilkan loading overlay dengan animasi
            showLoadingOverlay(() => {
                window.location.href = redirectUrl;
            });
        }

        // Efek loading pada form submit
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');

        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;

                if (username && password) {
                    // Tambahkan class loading pada tombol
                    submitBtn.classList.add('btn-loading');
                    submitBtn.disabled = true;

                    // Biarkan form submit normal, PHP akan menangani login
                    // Jika sukses, script di atas akan menangani redirect dengan animasi
                }
            });
        }

        // Partikel latar belakang
        function createParticles() {
            const container = document.getElementById('particles-container');
            if (!container) return;

            const particleCount = 30;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');

                const size = Math.random() * 5 + 2;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDuration = Math.random() * 10 + 5 + 's';
                particle.style.animationDelay = Math.random() * 5 + 's';
                particle.style.opacity = Math.random() * 0.5;

                container.appendChild(particle);
            }
        }

        // Efek hover pada input
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function () {
                this.parentElement.parentElement.classList.add('ring-2', 'ring-brand-primary/20', 'rounded-lg');
            });

            input.addEventListener('blur', function () {
                this.parentElement.parentElement.classList.remove('ring-2', 'ring-brand-primary/20', 'rounded-lg');
            });
        });

        // Cegah scroll pada body untuk desktop
        if (window.innerWidth >= 768) {
            document.body.style.overflow = 'hidden';
        }

        // Inisialisasi semua efek
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();


        });

        // Handle resize untuk mengaktifkan/nonaktifkan overflow
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        });
    </script>
</body>

</html>