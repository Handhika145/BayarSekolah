<?php
require 'config/koneksi.php';

// Query statistik dari database
$q_sekolah = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM sekolah");
$total_sekolah = mysqli_fetch_assoc($q_sekolah)['total'] ?? 0;

$q_siswa = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM users WHERE role = 'walimurid'");
$total_siswa = mysqli_fetch_assoc($q_siswa)['total'] ?? 0;

$q_transaksi = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pembayaran");
$total_transaksi = mysqli_fetch_assoc($q_transaksi)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPP Digital — Platform Manajemen Pembayaran Sekolah Modern</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<meta name="description"
        content="SPP Digital adalah platform SaaS multi-tenant untuk mengelola pembayaran SPP & retribusi sekolah secara digital. Daftar gratis dan tinggalkan cara manual sekarang.">
    <meta name="keywords"
        content="SPP Digital, pembayaran sekolah, manajemen SPP, pembayaran online sekolah, retribusi sekolah, SaaS sekolah">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        inter: ['Inter', 'sans-serif'],
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        navy: {
                            900: '#0b1120',
                            800: '#0f172a',
                            700: '#1e293b',
                            600: '#334155',
                        },
                        accent: {
                            emerald: '#10b981',
                            'emerald-light': '#34d399',
                            'emerald-dark': '#059669',
                            blue: '#3b82f6',
                            'blue-dark': '#2563eb',
                        }
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        fadeInLeft: {
                            '0%': { opacity: '0', transform: 'translateX(-30px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                        fadeInRight: {
                            '0%': { opacity: '0', transform: 'translateX(30px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-12px)' },
                        },
                        shimmer: {
                            '0%': { backgroundPosition: '-200% 0' },
                            '100%': { backgroundPosition: '200% 0' },
                        },
                        countUp: {
                            '0%': { opacity: '0', transform: 'scale(0.5)' },
                            '100%': { opacity: '1', transform: 'scale(1)' },
                        }
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.7s ease-out forwards',
                        'fade-in-left': 'fadeInLeft 0.7s ease-out forwards',
                        'fade-in-right': 'fadeInRight 0.7s ease-out forwards',
                        'float': 'float 6s ease-in-out infinite',
                        'shimmer': 'shimmer 2.5s ease-in-out infinite',
                        'count-up': 'countUp 0.5s ease-out forwards',
                    }
                }
            }
        }
    </script>

    <!-- Inline theme init to prevent flash -->
    <script>
        (function() {
            const saved = localStorage.getItem('spp-theme');
            if (saved === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <style>
        /* ===== CSS CUSTOM PROPERTIES ===== */
        :root {
            /* Light mode colors */
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-card: rgba(255, 255, 255, 0.7);
            --bg-card-border: rgba(0, 0, 0, 0.06);
            --bg-navbar-scrolled: rgba(255, 255, 255, 0.92);
            --bg-mobile-menu: rgba(248, 250, 252, 0.97);
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-muted: #64748b;
            --text-faint: #94a3b8;
            --border-color: rgba(0, 0, 0, 0.08);
            --scrollbar-track: #f1f5f9;
            --scrollbar-thumb: #cbd5e1;
            --grid-dot: #1e293b;
            --orb-opacity: 0.08;
            --glass-bg: rgba(255, 255, 255, 0.6);
            --glass-border: rgba(0, 0, 0, 0.06);
            --step-number-bg: #f8fafc;
            --footer-bg: #f8fafc;
            --navbar-shadow: 0 4px 30px rgba(0, 0, 0, 0.08);
        }

        .dark {
            --bg-primary: #0b1120;
            --bg-secondary: #0f172a;
            --bg-card: rgba(15, 23, 42, 0.6);
            --bg-card-border: rgba(255, 255, 255, 0.08);
            --bg-navbar-scrolled: rgba(11, 17, 32, 0.95);
            --bg-mobile-menu: rgba(15, 23, 42, 0.97);
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --text-faint: #64748b;
            --border-color: rgba(255, 255, 255, 0.08);
            --scrollbar-track: #0b1120;
            --scrollbar-thumb: #334155;
            --grid-dot: #ffffff;
            --orb-opacity: 0.15;
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-border: rgba(255, 255, 255, 0.08);
            --step-number-bg: #0b1120;
            --footer-bg: #0b1120;
            --navbar-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }

        /* ===== BODY ===== */
        body {
            background-color: var(--bg-primary);
            color: var(--text-muted);
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        /* Scroll-triggered animation base states */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.7s ease-out, transform 0.7s ease-out;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-left {
            opacity: 0;
            transform: translateX(-40px);
            transition: opacity 0.7s ease-out, transform 0.7s ease-out;
        }

        .reveal-left.active {
            opacity: 1;
            transform: translateX(0);
        }

        .reveal-right {
            opacity: 0;
            transform: translateX(40px);
            transition: opacity 0.7s ease-out, transform 0.7s ease-out;
        }

        .reveal-right.active {
            opacity: 1;
            transform: translateX(0);
        }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Glass card effect */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            transition: background 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
        }

        /* Light mode: enhanced card shadow */
        html:not(.dark) .glass-card {
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        /* Button shimmer */
        .btn-shimmer {
            background-size: 200% 100%;
            background-image: linear-gradient(90deg, #10b981 0%, #059669 25%, #10b981 50%, #059669 75%, #10b981 100%);
        }

        .btn-shimmer:hover {
            animation: shimmer 2s ease-in-out infinite;
        }

        /* Glow effects */
        .glow-emerald {
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.15), 0 0 60px rgba(16, 185, 129, 0.05);
        }

        .glow-blue {
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.15), 0 0 60px rgba(59, 130, 246, 0.05);
        }

        /* Step connector line */
        .step-connector {
            position: relative;
        }

        .step-connector::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -50%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #10b981, #3b82f6);
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .step-connector::after {
                display: none;
            }
        }

        /* Navbar scroll effect */
        .navbar-scrolled {
            background: var(--bg-navbar-scrolled) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: var(--navbar-shadow);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #10b981;
        }

        /* Orb decorations */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: var(--orb-opacity);
            pointer-events: none;
            transition: opacity 0.4s ease;
        }

        /* ===== THEME TOGGLE BUTTON ===== */
        .theme-toggle {
            position: relative;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .theme-toggle:hover {
            border-color: rgba(16, 185, 129, 0.4);
            transform: scale(1.05);
        }

        .theme-toggle:active {
            transform: scale(0.95);
        }

        .theme-toggle .icon-sun,
        .theme-toggle .icon-moon {
            position: absolute;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            font-size: 16px;
        }

        /* Dark mode: show sun, hide moon */
        .dark .theme-toggle .icon-sun {
            opacity: 1;
            transform: rotate(0deg) scale(1);
            color: #fbbf24;
        }

        .dark .theme-toggle .icon-moon {
            opacity: 0;
            transform: rotate(-90deg) scale(0.5);
        }

        /* Light mode: show moon, hide sun */
        html:not(.dark) .theme-toggle .icon-sun {
            opacity: 0;
            transform: rotate(90deg) scale(0.5);
        }

        html:not(.dark) .theme-toggle .icon-moon {
            opacity: 1;
            transform: rotate(0deg) scale(1);
            color: #6366f1;
        }

        /* ===== THEMED TEXT CLASSES ===== */
        .t-heading {
            color: var(--text-primary);
            transition: color 0.4s ease;
        }

        .t-body {
            color: var(--text-muted);
            transition: color 0.4s ease;
        }

        .t-muted {
            color: var(--text-faint);
            transition: color 0.4s ease;
        }

        .t-subtle {
            color: var(--text-secondary);
            transition: color 0.4s ease;
        }

        /* ===== LIGHT MODE SPECIFIC OVERRIDES ===== */
        /* Navbar links */
        html:not(.dark) .nav-link {
            color: #475569 !important;
        }
        html:not(.dark) .nav-link:hover {
            color: #059669 !important;
        }

        /* Nav login button */
        html:not(.dark) .nav-btn-outline {
            color: #475569 !important;
            border-color: rgba(0,0,0,0.15) !important;
        }
        html:not(.dark) .nav-btn-outline:hover {
            color: #059669 !important;
            border-color: rgba(16,185,129,0.4) !important;
        }

        /* Hero section light overrides */
        html:not(.dark) .hero-badge-ring {
            background: rgba(16,185,129,0.08) !important;
            border-color: rgba(16,185,129,0.15) !important;
        }

        html:not(.dark) .hero-subtitle {
            color: #475569 !important;
        }
        html:not(.dark) .hero-subtitle strong {
            color: #1e293b !important;
        }

        /* Ghost button in hero */
        html:not(.dark) .hero-ghost-btn {
            color: #475569 !important;
            background: rgba(0,0,0,0.04) !important;
            border-color: rgba(0,0,0,0.12) !important;
        }
        html:not(.dark) .hero-ghost-btn:hover {
            background: rgba(0,0,0,0.08) !important;
            border-color: rgba(0,0,0,0.2) !important;
            color: #1e293b !important;
        }

        /* Social proof */
        html:not(.dark) .social-proof-avatar {
            border-color: var(--bg-primary) !important;
        }

        /* Hero image container */
        html:not(.dark) .hero-img-wrap {
            border-color: rgba(0,0,0,0.08) !important;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15) !important;
        }

        /* Stats bar */
        html:not(.dark) .stats-bar {
            background: rgba(255,255,255,0.85) !important;
            border: 1px solid rgba(0,0,0,0.06) !important;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08) !important;
        }

        /* Feature cards light */
        html:not(.dark) .feature-card {
            background: rgba(255,255,255,0.8) !important;
            border: 1px solid rgba(0,0,0,0.06) !important;
        }
        html:not(.dark) .feature-card:hover {
            box-shadow: 0 12px 40px rgba(0,0,0,0.1) !important;
        }
        html:not(.dark) .feature-card .card-desc {
            color: #64748b !important;
        }
        html:not(.dark) .feature-card .card-desc strong {
            color: #334155 !important;
        }
        html:not(.dark) .feature-card .card-footer-line {
            border-color: rgba(0,0,0,0.06) !important;
        }

        /* Cara kerja section bg */
        html:not(.dark) .section-cara-kerja-bg {
            background: linear-gradient(180deg, var(--bg-primary) 0%, #eef2ff 50%, var(--bg-primary) 100%) !important;
        }

        /* Step number circle */
        html:not(.dark) .step-number-bg {
            background: var(--bg-primary) !important;
        }

        /* Mobile menu light */
        html:not(.dark) #mobile-menu {
            background: var(--bg-mobile-menu) !important;
            border-color: rgba(0,0,0,0.06) !important;
        }
        html:not(.dark) #mobile-menu a {
            color: #475569 !important;
        }
        html:not(.dark) #mobile-menu a:hover {
            color: #059669 !important;
        }

        /* Mobile masuk button light */
        html:not(.dark) .mobile-btn-outline {
            color: #475569 !important;
            border-color: rgba(0,0,0,0.12) !important;
        }

        /* Footer light */
        html:not(.dark) footer {
            background: #f1f5f9 !important;
            border-color: rgba(0,0,0,0.06) !important;
        }
        html:not(.dark) .footer-icon-btn {
            background: rgba(0,0,0,0.04) !important;
            color: #94a3b8 !important;
        }
        html:not(.dark) .footer-icon-btn:hover {
            background: rgba(16,185,129,0.1) !important;
        }
        html:not(.dark) .footer-bottom-bar {
            border-color: rgba(0,0,0,0.06) !important;
        }

        /* Mobile menu button icon light */
        html:not(.dark) #mobile-menu-btn {
            color: #475569 !important;
        }

        /* Logo text light */
        html:not(.dark) .logo-text {
            color: #0f172a !important;
        }

        /* Scroll indicator light */
        html:not(.dark) .scroll-indicator-text {
            color: #94a3b8 !important;
        }
        html:not(.dark) .scroll-indicator-icon {
            color: rgba(16,185,129,0.5) !important;
        }

        /* CTA banner stays vibrant in both modes — no changes needed */
    </style>
</head>

<body class="font-inter antialiased overflow-x-hidden">

    <!-- ==================== NAVBAR ==================== -->
    <nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <a href="#" class="flex items-center gap-3 group">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-blue-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-emerald-500/25 transition-shadow duration-300">
                        <i class="fa-solid fa-graduation-cap text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-bold logo-text t-heading tracking-tight">SPP<span
                            class="gradient-text">Digital</span></span>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#fitur"
                        class="nav-link text-sm font-medium text-gray-400 hover:text-emerald-400 transition-colors duration-200">Fitur</a>
                    <a href="#cara-kerja"
                        class="nav-link text-sm font-medium text-gray-400 hover:text-emerald-400 transition-colors duration-200">Cara
                        Kerja</a>
                    <a href="#harga"
                        class="nav-link text-sm font-medium text-gray-400 hover:text-emerald-400 transition-colors duration-200">Harga</a>
                </div>

                <!-- Auth Buttons + Theme Toggle -->
                <div class="hidden md:flex items-center gap-3">
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="theme-toggle" aria-label="Toggle dark/light mode" title="Ganti tema gelap/terang">
                        <i class="fa-solid fa-sun icon-sun"></i>
                        <i class="fa-solid fa-moon icon-moon"></i>
                    </button>

                    <a href="login.php" id="btn-login-nav"
                        class="nav-btn-outline px-5 py-2.5 text-sm font-semibold text-gray-300 border border-gray-600/50 rounded-xl hover:border-emerald-500/50 hover:text-emerald-400 transition-all duration-200">
                        <i class="fa-solid fa-right-to-bracket mr-2"></i>Masuk
                    </a>
                    <a href="register.php" id="btn-register-nav"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-emerald-500 rounded-xl hover:from-emerald-500 hover:to-emerald-400 shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 transition-all duration-300">
                        <i class="fa-solid fa-school mr-2"></i>Daftarkan Sekolah
                    </a>
                </div>

                <!-- Mobile Menu Toggle -->
                <div class="flex md:hidden items-center gap-2">
                    <button id="theme-toggle-mobile" class="theme-toggle" aria-label="Toggle dark/light mode" title="Ganti tema gelap/terang">
                        <i class="fa-solid fa-sun icon-sun"></i>
                        <i class="fa-solid fa-moon icon-moon"></i>
                    </button>
                    <button id="mobile-menu-btn" class="text-gray-300 hover:text-emerald-400 transition-colors p-2">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobile-menu" class="hidden md:hidden bg-navy-800/95 backdrop-blur-xl border-t border-white/5">
            <div class="px-4 py-6 space-y-4">
                <a href="#fitur"
                    class="block text-sm font-medium text-gray-400 hover:text-emerald-400 transition-colors py-2">Fitur</a>
                <a href="#cara-kerja"
                    class="block text-sm font-medium text-gray-400 hover:text-emerald-400 transition-colors py-2">Cara
                    Kerja</a>
                <a href="#harga"
                    class="block text-sm font-medium text-gray-400 hover:text-emerald-400 transition-colors py-2">Harga</a>
                <hr class="border-white/10">
                <a href="login.php"
                    class="mobile-btn-outline block text-center px-5 py-3 text-sm font-semibold text-gray-300 border border-gray-600/50 rounded-xl hover:border-emerald-500/50 hover:text-emerald-400 transition-all">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i>Masuk
                </a>
                <a href="register.php"
                    class="block text-center px-5 py-3 text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-emerald-500 rounded-xl hover:from-emerald-500 hover:to-emerald-400 shadow-lg shadow-emerald-500/20 transition-all">
                    <i class="fa-solid fa-school mr-2"></i>Daftarkan Sekolah
                </a>
            </div>
        </div>
    </nav>

    <!-- ==================== HERO SECTION ==================== -->
    <section id="hero" class="relative min-h-screen flex items-center pt-20 overflow-hidden">
        <!-- Background Orbs -->
        <div class="orb w-[500px] h-[500px] bg-emerald-500 top-10 -left-40"></div>
        <div class="orb w-[400px] h-[400px] bg-blue-500 bottom-20 right-0"></div>
        <div class="orb w-[300px] h-[300px] bg-purple-500 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></div>

        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 opacity-[0.03]"
            style="background-image: radial-gradient(circle, var(--grid-dot) 1px, transparent 1px); background-size: 40px 40px;">
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Left: Copy -->
                <div class="text-center lg:text-left">
                    <!-- Badge -->
                    <div
                        class="hero-badge-ring inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 mb-8 animate-fade-in-up">
                        <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                        <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Platform #1
                            Manajemen SPP</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold t-heading leading-tight mb-6 animate-fade-in-up"
                        style="animation-delay: 0.15s;">
                        Tinggalkan Cara Manual,<br>
                        <span class="gradient-text">Kelola SPP Sekolah</span><br>
                        dalam Hitungan Detik
                    </h1>

                    <p class="hero-subtitle text-lg text-gray-400 leading-relaxed mb-10 max-w-xl mx-auto lg:mx-0 animate-fade-in-up"
                        style="animation-delay: 0.3s;">
                        SPP Digital membantu sekolah mengelola seluruh pembayaran SPP & retribusi secara digital. Dari
                        penagihan otomatis hingga verifikasi pembayaran — semua dalam satu platform terpadu. <strong
                            class="text-gray-200">Multi-tenant, multi-akses, tanpa ribet.</strong>
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start animate-fade-in-up"
                        style="animation-delay: 0.45s;">
                        <a href="register.php" id="btn-hero-daftar"
                            class="group inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-bold text-white btn-shimmer rounded-2xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300">
                            <i class="fa-solid fa-rocket"></i>
                            Mulai Gratis
                            <i
                                class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform duration-200"></i>
                        </a>
                        <a href="https://wa.me/6281234567890" id="btn-hero-kontak"
                            class="hero-ghost-btn group inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-semibold text-gray-300 bg-white/5 border border-white/10 rounded-2xl hover:bg-white/10 hover:border-white/20 hover:text-white transition-all duration-300">
                            <i class="fa-brands fa-whatsapp text-lg text-green-400"></i>
                            Hubungi Kami
                        </a>
                    </div>

                    <!-- Social proof -->
                    <div class="mt-10 flex items-center gap-6 justify-center lg:justify-start animate-fade-in-up"
                        style="animation-delay: 0.6s;">
                        <div class="flex -space-x-3">
                            <div
                                class="social-proof-avatar w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 border-2 border-navy-900 flex items-center justify-center text-white text-xs font-bold">
                                S</div>
                            <div
                                class="social-proof-avatar w-9 h-9 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 border-2 border-navy-900 flex items-center justify-center text-white text-xs font-bold">
                                M</div>
                            <div
                                class="social-proof-avatar w-9 h-9 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 border-2 border-navy-900 flex items-center justify-center text-white text-xs font-bold">
                                A</div>
                            <div
                                class="social-proof-avatar w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 border-2 border-navy-900 flex items-center justify-center text-white text-xs font-bold">
                                +</div>
                        </div>
                        <div class="text-left">
                            <div class="flex items-center gap-1 mb-0.5">
                                <i class="fa-solid fa-star text-amber-400 text-xs"></i>
                                <i class="fa-solid fa-star text-amber-400 text-xs"></i>
                                <i class="fa-solid fa-star text-amber-400 text-xs"></i>
                                <i class="fa-solid fa-star text-amber-400 text-xs"></i>
                                <i class="fa-solid fa-star text-amber-400 text-xs"></i>
                            </div>
                            <p class="text-xs t-muted">Dipercaya <strong class="t-subtle">500+
                                    sekolah</strong> di seluruh Indonesia</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Hero Image -->
                <div class="relative animate-fade-in-right" style="animation-delay: 0.3s;">
                    <div class="relative z-10 animate-float">
                        <div
                            class="hero-img-wrap rounded-2xl overflow-hidden shadow-2xl shadow-emerald-500/10 border border-white/10 glow-emerald">
                            <img src="assets/img/hero-dashboard-real.png"
                                alt="Dashboard SPP Digital - Platform Manajemen Pembayaran Sekolah"
                                class="w-full h-auto" loading="eager">
                        </div>
                    </div>
                    <!-- Decorative blobs behind image -->
                    <div class="absolute -top-8 -right-8 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-8 -left-8 w-48 h-48 bg-blue-500/10 rounded-full blur-3xl"></div>
                </div>
            </div>
        </div>

        <!-- Scroll indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 flex flex-col items-center gap-2 animate-bounce">
            <span class="scroll-indicator-text text-xs text-gray-500 font-medium tracking-wider uppercase">Scroll</span>
            <i class="scroll-indicator-icon fa-solid fa-chevron-down text-emerald-500/60 text-sm"></i>
        </div>
    </section>

    <!-- ==================== STATS BAR ==================== -->
    <section class="relative z-10 -mt-4">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="stats-bar glass-card rounded-2xl p-8 glow-emerald reveal">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                    <div>
                        <div class="text-3xl sm:text-4xl font-extrabold t-heading mb-1"><span class="counter" data-target="<?= $total_sekolah ?>">0</span></div>
                        <p class="text-xs sm:text-sm t-muted">Sekolah Terdaftar</p>
                    </div>
                    <div>
                        <div class="text-3xl sm:text-4xl font-extrabold t-heading mb-1"><span class="counter" data-target="<?= $total_siswa ?>">0</span></div>
                        <p class="text-xs sm:text-sm t-muted">Wali Murid Aktif</p>
                    </div>
                    <div>
                        <div class="text-3xl sm:text-4xl font-extrabold t-heading mb-1"><span class="counter" data-target="<?= $total_transaksi ?>">0</span></div>
                        <p class="text-xs sm:text-sm t-muted">Transaksi Terlaksana</p>
                    </div>
                    <div>
                        <div class="text-3xl sm:text-4xl font-extrabold t-heading mb-1">24<span
                                class="gradient-text">/7</span></div>
                        <p class="text-xs sm:text-sm t-muted">Dukungan Teknis</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== FITUR UTAMA ==================== -->
    <section id="fitur" class="relative py-24 lg:py-32">
        <!-- Background orb -->
        <div class="orb w-[500px] h-[500px] bg-blue-600 -right-60 top-0"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-16 reveal">
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/20 mb-6">
                    <i class="fa-solid fa-sparkles text-blue-400 text-xs"></i>
                    <span class="text-xs font-semibold text-blue-400 uppercase tracking-wider">Fitur Unggulan</span>
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold t-heading mb-6">
                    Mengapa Sekolah Memilih<br><span class="gradient-text">SPP Digital?</span>
                </h2>
                <p class="text-lg t-body leading-relaxed">
                    Kami merancang setiap fitur agar proses pembayaran SPP menjadi transparan, efisien, dan minim
                    kesalahan. Tidak perlu lagi menumpuk kertas kwitansi.
                </p>
            </div>

            <!-- Feature Cards -->
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Card 1: Multi-Akses -->
                <div class="group feature-card glass-card rounded-2xl p-8 hover:border-emerald-500/30 transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-emerald-500/5 reveal"
                    style="transition-delay: 0.1s;">
                    <div
                        class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-emerald-500/5 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-users-gear text-2xl text-emerald-400"></i>
                    </div>
                    <h3 class="text-xl font-bold t-heading mb-3">Multi-Akses Terintegrasi</h3>
                    <p class="card-desc text-gray-400 leading-relaxed text-sm">
                        Tiga level akses dalam satu platform: <strong class="text-gray-300">Super Admin</strong> untuk
                        pengawasan penuh, <strong class="text-gray-300">Admin Sekolah</strong> untuk operasional harian,
                        dan <strong class="text-gray-300">Wali Murid</strong> untuk memantau tagihan anak.
                    </p>
                    <div class="mt-6 pt-6 border-t card-footer-line border-white/5">
                        <div class="flex items-center gap-2 text-xs t-muted">
                            <i class="fa-solid fa-check-circle text-emerald-500"></i>
                            <span>Role-based access control</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Portal Orang Tua -->
                <div class="group feature-card glass-card rounded-2xl p-8 hover:border-blue-500/30 transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-blue-500/5 reveal"
                    style="transition-delay: 0.2s;">
                    <div
                        class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500/20 to-blue-500/5 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-mobile-screen-button text-2xl text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-bold t-heading mb-3">Portal Orang Tua</h3>
                    <p class="card-desc text-gray-400 leading-relaxed text-sm">
                        Wali murid mendapat akses portal khusus untuk melihat detail tagihan SPP dan langsung <strong
                            class="text-gray-300">mengunggah bukti transfer</strong> cukup dari genggaman smartphone
                        mereka. Praktis & transparan.
                    </p>
                    <div class="mt-6 pt-6 border-t card-footer-line border-white/5">
                        <div class="flex items-center gap-2 text-xs t-muted">
                            <i class="fa-solid fa-check-circle text-blue-500"></i>
                            <span>Upload bukti bayar via mobile</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Laporan Otomatis -->
                <div class="group feature-card glass-card rounded-2xl p-8 hover:border-purple-500/30 transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-purple-500/5 reveal"
                    style="transition-delay: 0.3s;">
                    <div
                        class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500/20 to-purple-500/5 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-chart-line text-2xl text-purple-400"></i>
                    </div>
                    <h3 class="text-xl font-bold t-heading mb-3">Verifikasi & Laporan Otomatis</h3>
                    <p class="card-desc text-gray-400 leading-relaxed text-sm">
                        Verifikasi pembayaran hanya dengan <strong class="text-gray-300">1 klik</strong>, dan tagihan
                        otomatis berubah status menjadi lunas. Semua data tersimpan rapi untuk kebutuhan <strong
                            class="text-gray-300">laporan keuangan bulanan</strong>.
                    </p>
                    <div class="mt-6 pt-6 border-t card-footer-line border-white/5">
                        <div class="flex items-center gap-2 text-xs t-muted">
                            <i class="fa-solid fa-check-circle text-purple-500"></i>
                            <span>Rekap otomatis setiap bulan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== CARA KERJA ==================== -->
    <section id="cara-kerja" class="relative py-24 lg:py-32 overflow-hidden">
        <!-- Background -->
        <div class="section-cara-kerja-bg absolute inset-0 bg-gradient-to-b from-navy-900 via-navy-800/50 to-navy-900"></div>
        <div class="orb w-[400px] h-[400px] bg-emerald-500 -left-40 top-1/2 -translate-y-1/2"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center max-w-3xl mx-auto mb-20 reveal">
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 mb-6">
                    <i class="fa-solid fa-route text-emerald-400 text-xs"></i>
                    <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Langkah Mudah</span>
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold t-heading mb-6">
                    Beralih ke Digital<br><span class="gradient-text">Semudah 1-2-3</span>
                </h2>
                <p class="text-lg t-body leading-relaxed">
                    Tidak perlu keahlian teknis. Tim kami siap mendampingi Anda dari awal pendaftaran hingga platform
                    berjalan lancar.
                </p>
            </div>

            <!-- Steps -->
            <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                <!-- Step 1 -->
                <div class="relative text-center step-connector reveal" style="transition-delay: 0.1s;">
                    <div class="relative inline-flex mb-8">
                        <div
                            class="w-20 h-20 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                            <i class="fa-solid fa-building-columns text-white text-2xl"></i>
                        </div>
                        <div
                            class="step-number-bg absolute -top-2 -right-2 w-8 h-8 rounded-full bg-navy-900 border-2 border-emerald-500 flex items-center justify-center">
                            <span class="text-xs font-bold text-emerald-400">1</span>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold t-heading mb-3">Daftarkan Sekolah</h3>
                    <p class="t-body text-sm leading-relaxed">
                        Isi formulir pendaftaran sekolah Anda. Setelah terverifikasi, Anda langsung mendapat akses
                        sebagai Admin Sekolah.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="relative text-center step-connector reveal" style="transition-delay: 0.2s;">
                    <div class="relative inline-flex mb-8">
                        <div
                            class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                            <i class="fa-solid fa-database text-white text-2xl"></i>
                        </div>
                        <div
                            class="step-number-bg absolute -top-2 -right-2 w-8 h-8 rounded-full bg-navy-900 border-2 border-blue-500 flex items-center justify-center">
                            <span class="text-xs font-bold text-blue-400">2</span>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold t-heading mb-3">Input Data & Buat Akun Wali</h3>
                    <p class="t-body text-sm leading-relaxed">
                        Masukkan data siswa, buat tagihan, dan generate akun untuk setiap wali murid. Semuanya sudah
                        disiapkan secara otomatis.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="relative text-center reveal" style="transition-delay: 0.3s;">
                    <div class="relative inline-flex mb-8">
                        <div
                            class="w-20 h-20 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/20">
                            <i class="fa-solid fa-circle-check text-white text-2xl"></i>
                        </div>
                        <div
                            class="step-number-bg absolute -top-2 -right-2 w-8 h-8 rounded-full bg-navy-900 border-2 border-purple-500 flex items-center justify-center">
                            <span class="text-xs font-bold text-purple-400">3</span>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold t-heading mb-3">Nikmati Pembayaran Otomatis</h3>
                    <p class="t-body text-sm leading-relaxed">
                        Wali murid menerima tagihan, mengunggah bukti transfer, dan Anda tinggal verifikasi 1 klik.
                        Laporan tersusun rapi secara otomatis.
                    </p>
                </div>
            </div>
        </div>
    </section>



    <!-- ==================== CTA BOTTOM BANNER ==================== -->
    <section class="relative py-24 lg:py-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative rounded-3xl overflow-hidden reveal">
                <!-- Gradient Background -->
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-600 via-emerald-700 to-blue-700"></div>
                <!-- Pattern overlay -->
                <div class="absolute inset-0 opacity-10"
                    style="background-image: radial-gradient(circle, #ffffff 1px, transparent 1px); background-size: 30px 30px;">
                </div>
                <!-- Glow orbs -->
                <div class="absolute -top-20 -left-20 w-64 h-64 bg-emerald-400/30 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-blue-400/30 rounded-full blur-3xl"></div>

                <div class="relative z-10 text-center py-16 lg:py-20 px-8">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 border border-white/20 mb-8">
                        <i class="fa-solid fa-bolt text-amber-300 text-xs"></i>
                        <span class="text-xs font-semibold text-white/90 uppercase tracking-wider">Gratis — Tanpa Kartu
                            Kredit</span>
                    </div>

                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white mb-6 leading-tight">
                        Siap Mengubah Cara Sekolah<br>Mengelola Pembayaran?
                    </h2>
                    <p class="text-lg text-emerald-100/80 max-w-2xl mx-auto mb-10 leading-relaxed">
                        Bergabung bersama ratusan sekolah yang telah beralih ke sistem digital. Daftarkan sekolah Anda
                        sekarang dan rasakan kemudahannya dalam 5 menit pertama.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="register.php" id="btn-cta-daftar"
                            class="group inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-bold text-emerald-700 bg-white rounded-2xl shadow-xl shadow-black/10 hover:bg-emerald-50 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300">
                            <i class="fa-solid fa-school"></i>
                            Daftarkan Sekolah Sekarang
                            <i
                                class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform duration-200"></i>
                        </a>
                        <a href="login.php" id="btn-cta-login"
                            class="inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-semibold text-white border-2 border-white/30 rounded-2xl hover:bg-white/10 hover:border-white/50 transition-all duration-300">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            Masuk ke Akun Saya
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== FOOTER ==================== -->
    <footer class="relative border-t border-white/5" style="background: var(--footer-bg); transition: background 0.4s ease;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid md:grid-cols-4 gap-12">
                <!-- Brand -->
                <div class="md:col-span-2">
                    <a href="#" class="flex items-center gap-3 mb-6 group">
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-blue-500 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fa-solid fa-graduation-cap text-white text-lg"></i>
                        </div>
                        <span class="text-xl font-bold t-heading tracking-tight">SPP<span
                                class="gradient-text">Digital</span></span>
                    </a>
                    <p class="t-muted text-sm leading-relaxed max-w-sm mb-6">
                        Platform manajemen pembayaran sekolah digital berbasis cloud. Solusi modern untuk pengelolaan
                        SPP & retribusi yang transparan dan efisien.
                    </p>
                    <div class="flex items-center gap-4">
                        <a href="#"
                            class="footer-icon-btn w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-500 hover:bg-emerald-500/10 hover:text-emerald-400 transition-all duration-200"
                            aria-label="Instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="#"
                            class="footer-icon-btn w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-500 hover:bg-blue-500/10 hover:text-blue-400 transition-all duration-200"
                            aria-label="Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="#"
                            class="footer-icon-btn w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-500 hover:bg-emerald-500/10 hover:text-emerald-400 transition-all duration-200"
                            aria-label="WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                        <a href="#"
                            class="footer-icon-btn w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-500 hover:bg-sky-500/10 hover:text-sky-400 transition-all duration-200"
                            aria-label="YouTube">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                    </div>
                </div>

                <!-- Navigasi -->
                <div>
                    <h4 class="text-sm font-bold t-heading uppercase tracking-wider mb-6">Navigasi</h4>
                    <ul class="space-y-3">
                        <li><a href="#fitur"
                                class="text-sm t-muted hover:text-emerald-400 transition-colors">Fitur</a></li>
                        <li><a href="#cara-kerja"
                                class="text-sm t-muted hover:text-emerald-400 transition-colors">Cara Kerja</a>
                        </li>
                        <li><a href="#harga"
                                class="text-sm t-muted hover:text-emerald-400 transition-colors">Harga</a></li>
                        <li><a href="login.php"
                                class="text-sm t-muted hover:text-emerald-400 transition-colors">Login</a></li>
                        <li><a href="register.php"
                                class="text-sm t-muted hover:text-emerald-400 transition-colors">Daftar
                                Sekolah</a></li>
                    </ul>
                </div>

                <!-- Kontak -->
                <div>
                    <h4 class="text-sm font-bold t-heading uppercase tracking-wider mb-6">Kontak</h4>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-sm t-muted">
                            <i class="fa-solid fa-envelope text-xs text-emerald-500"></i>
                            <a href="mailto:info@sppdigital.id"
                                class="hover:text-emerald-400 transition-colors">info@sppdigital.id</a>
                        </li>
                        <li class="flex items-center gap-3 text-sm t-muted">
                            <i class="fa-brands fa-whatsapp text-xs text-emerald-500"></i>
                            <a href="https://wa.me/6281234567890" class="hover:text-emerald-400 transition-colors">+62
                                812-3456-7890</a>
                        </li>
                        <li class="flex items-start gap-3 text-sm t-muted">
                            <i class="fa-solid fa-location-dot text-xs text-emerald-500 mt-1"></i>
                            <span>Indonesia</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div
                class="footer-bottom-bar mt-16 pt-8 border-t border-white/5 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-xs t-muted">&copy; 2026 SPP Digital. Hak cipta dilindungi undang-undang.</p>
                <div class="flex items-center gap-6">
                    <a href="#" class="text-xs t-muted hover:text-emerald-400 transition-colors">Kebijakan
                        Privasi</a>
                    <a href="#" class="text-xs t-muted hover:text-emerald-400 transition-colors">Syarat &
                        Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ==================== JAVASCRIPT ==================== -->
    <script>
        // ===== Theme Toggle =====
        const themeToggle = document.getElementById('theme-toggle');
        const themeToggleMobile = document.getElementById('theme-toggle-mobile');
        const html = document.documentElement;

        function toggleTheme() {
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('spp-theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('spp-theme', 'dark');
            }
        }

        themeToggle.addEventListener('click', toggleTheme);
        themeToggleMobile.addEventListener('click', toggleTheme);

        // ===== Mobile Menu Toggle =====
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuIcon = mobileMenuBtn.querySelector('i');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            if (mobileMenu.classList.contains('hidden')) {
                mobileMenuIcon.classList.replace('fa-xmark', 'fa-bars');
            } else {
                mobileMenuIcon.classList.replace('fa-bars', 'fa-xmark');
            }
        });

        // Close mobile menu on link click
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
                mobileMenuIcon.classList.replace('fa-xmark', 'fa-bars');
            });
        });

        // ===== Navbar Scroll Effect =====
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });

        // ===== Scroll Reveal Animation =====
        const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Apply transition delay from inline style if present
                    const delay = entry.target.style.transitionDelay;
                    if (delay) {
                        entry.target.style.transitionDelay = delay;
                    }
                    entry.target.classList.add('active');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15,
            rootMargin: '0px 0px -50px 0px'
        });

        revealElements.forEach(el => revealObserver.observe(el));

        // ===== Animated Counters =====
        const counters = document.querySelectorAll('.counter');
        const counterSpeed = 60; // smaller is faster

        const animateCounter = (counter) => {
            const target = +counter.getAttribute('data-target');
            const increment = Math.max(1, Math.ceil(target / counterSpeed));
            let current = 0;
            
            const updateCounter = () => {
                current += increment;
                if (current >= target) {
                    counter.innerText = target.toLocaleString('id-ID');
                } else {
                    counter.innerText = current.toLocaleString('id-ID');
                    requestAnimationFrame(updateCounter);
                }
            };
            updateCounter();
        };

        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(c => counterObserver.observe(c));

        // ===== Active nav link highlighting =====
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('nav a[href^="#"]');

        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                if (window.scrollY >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });
            navLinks.forEach(link => {
                link.classList.remove('text-emerald-400');
                if (html.classList.contains('dark')) {
                    link.classList.add('text-gray-400');
                }
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.remove('text-gray-400');
                    link.classList.add('text-emerald-400');
                }
            });
        });
    </script>

</body>

</html>