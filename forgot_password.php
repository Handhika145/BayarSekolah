<?php
session_start();
require 'config/koneksi.php';

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'superadmin') header("Location: superadmin/dashboard.php");
    elseif ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    else header("Location: walimurid/dashboard.php");
    exit;
}

$pesan = '';
$pesan_type = '';

// Ambil pesan dari session jika ada (redirect dari proses)
if (isset($_SESSION['forgot_success'])) {
    $pesan = $_SESSION['forgot_success'];
    $pesan_type = 'success';
    unset($_SESSION['forgot_success']);
}
if (isset($_SESSION['forgot_error'])) {
    $pesan = $_SESSION['forgot_error'];
    $pesan_type = 'error';
    unset($_SESSION['forgot_error']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Lupa Password - BayarSekolah</title>
    
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS CDN -->
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
<!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Tailwind Custom Config (sama dengan login.php) -->
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            overflow: hidden;
            height: 100vh;
            width: 100vw;
            font-family: 'Poppins', sans-serif;
        }
        .main-container {
            display: flex;
            flex-direction: row;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }
        .left-side, .right-side {
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .left-side::-webkit-scrollbar, .right-side::-webkit-scrollbar { width: 4px; }
        .left-side::-webkit-scrollbar-track, .right-side::-webkit-scrollbar-track { background: transparent; }
        .left-side::-webkit-scrollbar-thumb, .right-side::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 10px; }
        
        .hero-image {
            transition: none;
            transform: none !important;
            filter: none;
            -webkit-filter: none;
        }
        .hero-content {
            background: rgba(216, 245, 223, 0.9);
            backdrop-filter: none;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 2rem;
            width: 90%;
            max-width: 500px;
        }
        .gradient-border {
            position: relative;
            background: white;
            border-radius: 1rem;
        }
        .gradient-border::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(45deg, #398c58, #d8f5df, #398c58);
            border-radius: 1rem;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .gradient-border:hover::before { opacity: 1; }
        
        /* Floating bubbles */
        .bubble {
            position: absolute;
            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.8), rgba(255,255,255,0.2));
            border-radius: 50%;
            pointer-events: none;
            animation: bubbleFloat linear infinite;
        }
        @keyframes bubbleFloat {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-20vh) scale(1); opacity: 0; }
        }
        .bubble-1 { width: 80px; height: 80px; left: 10%; animation-duration: 8s; animation-delay: 0s; }
        .bubble-2 { width: 120px; height: 120px; right: 15%; animation-duration: 12s; animation-delay: 2s; }
        .bubble-3 { width: 60px; height: 60px; left: 20%; bottom: 0; animation-duration: 6s; animation-delay: 4s; }
        
        input:hover {
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
        
        /* Efek loading pada tombol */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        .btn-loading .btn-text { opacity: 0.7; }
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px; height: 20px;
            top: 50%; left: 50%;
            margin-left: -10px; margin-top: -10px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Email sent animation */
        @keyframes emailSent {
            0% { transform: scale(0) rotate(-10deg); opacity: 0; }
            50% { transform: scale(1.1) rotate(5deg); opacity: 1; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        .email-sent-icon {
            animation: emailSent 0.8s ease-out;
        }
        
        @media (max-width: 768px) {
            .main-container { flex-direction: column; }
            .left-side, .right-side { height: 50vh; }
            .left-side { min-height: 50vh; }
            .right-side { min-height: 50vh; }
            body { overflow-y: auto; }
            .main-container { overflow-y: auto; }
            .hero-content { padding: 1rem; margin: 1rem; width: auto; }
        }
        @media (max-height: 600px) and (orientation: landscape) {
            .left-side, .right-side { overflow-y: auto; }
            .hero-content { padding: 0.75rem; margin-top: 1rem; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <!-- Sisi Kiri (Informasi & Ilustrasi) -->
    <div class="left-side w-full md:w-1/2 relative bg-brand-light flex flex-col items-center justify-start overflow-hidden" data-aos="fade-right" data-aos-duration="800">
        
        <!-- Gambar Ilustrasi -->
        <img src="assets/img/herosectionlogin.png" 
             alt="Ilustrasi Background" 
             class="hero-image absolute inset-0 w-full h-full object-cover object-center z-0 pointer-events-none">
        
        <!-- Konten Teks -->
        <div class="relative z-10 flex flex-col items-center px-8 text-center hero-content">
            <div class="flex items-center gap-3 mb-4 group">
                <i class="fa-solid fa-leaf text-4xl text-brand-primary-hover group-hover:rotate-12 transition-transform duration-300"></i>
                <h1 class="text-3xl font-bold text-brand-primary-hover tracking-tight group-hover:scale-105 transition-transform duration-300">BayarSekolah</h1>
            </div>
            
            <p class="text-[#1a4a2d] font-medium text-sm md:text-base">
                Pembayaran Sekolah Mudah & Aman.<br>
                Pulihkan akses akun Anda.
            </p>
        </div>
    </div>

    <!-- Sisi Kanan (Form Forgot Password) -->
    <div class="right-side w-full md:w-1/2 bg-brand-dark flex items-center justify-center p-6 md:p-8 relative overflow-hidden" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
        <!-- Elemen dekoratif -->
        <div class="absolute inset-0 opacity-30 bg-[radial-gradient(circle_at_top_left,transparent_20%,#155230_21%,#155230_100%)] animate-pulse"></div>
        
        <!-- Floating bubbles -->
        <div class="bubble bubble-1"></div>
        <div class="bubble bubble-2"></div>
        <div class="bubble bubble-3"></div>

        <!-- Kotak Form -->
        <div class="bg-white/95 backdrop-blur-sm p-6 md:p-8 rounded-2xl shadow-2xl w-full max-w-md z-10 gradient-border transition-all duration-300" data-aos="fade-in-up" data-aos-duration="600">
            
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-brand-light rounded-full mb-4">
                    <i class="fa-solid fa-envelope-open-text text-3xl text-brand-primary animate-pulse"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Lupa Kata Sandi?</h2>
                <p class="text-sm text-gray-500">Masukkan alamat email yang terdaftar pada akun Anda. Kami akan mengirimkan link untuk mereset kata sandi.</p>
            </div>
            
            <!-- Output Pesan -->
            <?php if ($pesan): ?>
                <?php if ($pesan_type == 'success'): ?>
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-center">
                        <div class="email-sent-icon inline-block mb-2">
                            <i class="fa-solid fa-paper-plane text-3xl text-green-500"></i>
                        </div>
                        <p class="text-green-700 text-sm font-medium"><?= $pesan; ?></p>
                    </div>
                <?php else: ?>
                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm text-center animate-shake">
                        ❌ <?= $pesan; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form action="auth/forgot_password_proses.php" method="POST" id="forgotForm">
                <!-- Input Email -->
                <div class="mb-5 group">
                    <label for="email" class="block text-sm text-gray-600 mb-2">
                        <i class="fa-regular fa-envelope mr-2 text-brand-primary"></i>
                        Alamat Email
                    </label>
                    <div class="relative">
                        <input type="email" id="email" name="email" required 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/30 transition-all duration-300 group-hover:shadow-md"
                               placeholder="contoh@email.com">
                        <div class="absolute right-3 top-3 text-gray-400">
                            <i class="fa-regular fa-envelope"></i>
                        </div>
                    </div>
                </div>

                <!-- Tombol Kirim -->
                <button type="submit" 
                        class="w-full bg-brand-primary hover:bg-brand-primary-hover text-white font-semibold py-2.5 rounded-lg transition-all duration-300 mb-3 transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-xl"
                        id="submitBtn">
                    <span class="btn-text">
                        <i class="fa-solid fa-paper-plane mr-2"></i>
                        Kirim Link Reset
                    </span>
                </button>
            </form>

            <!-- Link Kembali -->
            <div class="text-center mt-4">
                <a href="login.php" class="text-brand-primary hover:text-brand-primary-hover transition-colors duration-300 text-sm flex items-center justify-center gap-2 group">
                    <i class="fa-solid fa-arrow-left text-xs group-hover:-translate-x-1 transition-transform duration-300"></i>
                    <span class="group-hover:underline">Kembali ke Halaman Login</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Script AOS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
    // Inisialisasi AOS
    AOS.init({ once: true, mirror: false, duration: 800 });
    
    // Efek loading pada form submit
    const forgotForm = document.getElementById('forgotForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (forgotForm) {
        forgotForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            if (email) {
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;
            }
        });
    }
    
    // Efek hover pada input
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.parentElement.classList.add('ring-2', 'ring-brand-primary/20', 'rounded-lg');
        });
        input.addEventListener('blur', function() {
            this.parentElement.parentElement.classList.remove('ring-2', 'ring-brand-primary/20', 'rounded-lg');
        });
    });
    
    // Cegah scroll pada body untuk desktop
    if (window.innerWidth >= 768) {
        document.body.style.overflow = 'hidden';
    }
    window.addEventListener('resize', function() {
        document.body.style.overflow = window.innerWidth >= 768 ? 'hidden' : 'auto';
    });
</script>
</body>
</html>
