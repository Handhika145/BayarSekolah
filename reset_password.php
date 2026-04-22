<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require 'config/koneksi.php';

// Set MySQL timezone agar konsisten dengan PHP
mysqli_query($koneksi, "SET time_zone = '+07:00'");

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'superadmin') header("Location: superadmin/dashboard.php");
    elseif ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    else header("Location: walimurid/dashboard.php");
    exit;
}

$pesan = '';
$pesan_type = '';
$token_valid = false;
$token = '';

// Ambil pesan dari session
if (isset($_SESSION['reset_error'])) {
    $pesan = $_SESSION['reset_error'];
    $pesan_type = 'error';
    unset($_SESSION['reset_error']);
}
if (isset($_SESSION['reset_success'])) {
    $pesan = $_SESSION['reset_success'];
    $pesan_type = 'success';
    unset($_SESSION['reset_success']);
}

// Cek token dari URL
if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($koneksi, $_GET['token']);
    
    // Validasi token: ada di database dan belum expired (max 1 jam)
    // Gunakan MySQL TIMESTAMPDIFF agar timezone konsisten
    $query = mysqli_query($koneksi, "SELECT * FROM password_resets WHERE token='$token' AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) <= 60 LIMIT 1");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $token_valid = true;
    } else {
        // Debug: cek apakah token ada tapi expired
        $check = mysqli_query($koneksi, "SELECT *, TIMESTAMPDIFF(MINUTE, created_at, NOW()) as age_minutes FROM password_resets WHERE token='$token' LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $row = mysqli_fetch_assoc($check);
            $pesan = "Link reset sudah kedaluwarsa (umur: " . $row['age_minutes'] . " menit). Silakan request ulang.";
        } else {
            $pesan = "Link reset tidak valid. Token tidak ditemukan di database. Silakan request ulang.";
        }
        $pesan_type = 'error';
    }
} else if (empty($pesan)) {
    $pesan = "Link reset tidak valid. Silakan request ulang dari halaman Lupa Password.";
    $pesan_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Reset Password - BayarSekolah</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { poppins: ['Poppins', 'sans-serif'] },
                    colors: {
                        'brand-light': '#d8f5df',
                        'brand-dark': '#1b633c',
                        'brand-primary': '#398c58',
                        'brand-primary-hover': '#2b7a4b',
                    },
                    animation: {
                        'shake': 'shake 0.5s ease-in-out',
                        'float': 'float 3s ease-in-out infinite',
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
        
        .hero-image { transition: none; transform: none !important; filter: none; -webkit-filter: none; }
        .hero-content {
            background: rgba(216, 245, 223, 0.9);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 2rem;
            width: 90%;
            max-width: 500px;
        }
        .gradient-border { position: relative; background: white; border-radius: 1rem; }
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
        .bubble-1 { width: 80px; height: 80px; left: 10%; animation-duration: 8s; }
        .bubble-2 { width: 120px; height: 120px; right: 15%; animation-duration: 12s; animation-delay: 2s; }
        .bubble-3 { width: 60px; height: 60px; left: 20%; bottom: 0; animation-duration: 6s; animation-delay: 4s; }
        
        input:hover { transform: translateY(-1px); transition: all 0.2s ease; }
        
        .btn-loading { position: relative; pointer-events: none; opacity: 0.7; }
        .btn-loading .btn-text { opacity: 0.7; }
        .btn-loading::after {
            content: ''; position: absolute; width: 20px; height: 20px;
            top: 50%; left: 50%; margin-left: -10px; margin-top: -10px;
            border: 2px solid white; border-top-color: transparent;
            border-radius: 50%; animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Password strength indicator */
        .strength-bar { height: 4px; border-radius: 2px; transition: all 0.3s ease; }
        
        @keyframes successPulse {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-icon { animation: successPulse 0.6s ease-out; }
        
        @media (max-width: 768px) {
            .main-container { flex-direction: column; }
            .left-side, .right-side { height: 50vh; }
            body, .main-container { overflow-y: auto; }
            .hero-content { padding: 1rem; margin: 1rem; width: auto; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <!-- Sisi Kiri -->
    <div class="left-side w-full md:w-1/2 relative bg-brand-light flex flex-col items-center justify-start overflow-hidden" data-aos="fade-right" data-aos-duration="800">
        <img src="assets/img/herosectionlogin.png" 
             alt="Ilustrasi Background" 
             class="hero-image absolute inset-0 w-full h-full object-cover object-center z-0 pointer-events-none">
        
        <div class="relative z-10 flex flex-col items-center px-8 text-center hero-content">
            <div class="flex items-center gap-3 mb-4 group">
                <i class="fa-solid fa-leaf text-4xl text-brand-primary-hover group-hover:rotate-12 transition-transform duration-300"></i>
                <h1 class="text-3xl font-bold text-brand-primary-hover tracking-tight group-hover:scale-105 transition-transform duration-300">BayarSekolah</h1>
            </div>
            <p class="text-[#1a4a2d] font-medium text-sm md:text-base">
                Pembayaran Sekolah Mudah & Aman.<br>
                Buat kata sandi baru Anda.
            </p>
        </div>
    </div>

    <!-- Sisi Kanan -->
    <div class="right-side w-full md:w-1/2 bg-brand-dark flex items-center justify-center p-6 md:p-8 relative overflow-hidden" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
        <div class="absolute inset-0 opacity-30 bg-[radial-gradient(circle_at_top_left,transparent_20%,#155230_21%,#155230_100%)] animate-pulse"></div>
        
        <div class="bubble bubble-1"></div>
        <div class="bubble bubble-2"></div>
        <div class="bubble bubble-3"></div>

        <div class="bg-white/95 backdrop-blur-sm p-6 md:p-8 rounded-2xl shadow-2xl w-full max-w-md z-10 gradient-border transition-all duration-300" data-aos="fade-in-up" data-aos-duration="600">
            
            <?php if ($token_valid): ?>
            <!-- FORM RESET PASSWORD -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-brand-light rounded-full mb-4">
                    <i class="fa-solid fa-key text-3xl text-brand-primary animate-pulse"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Reset Kata Sandi</h2>
                <p class="text-sm text-gray-500">Buat kata sandi baru untuk akun Anda. Pastikan menggunakan kata sandi yang kuat.</p>
            </div>
            
            <?php if ($pesan && $pesan_type == 'error'): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm text-center animate-shake">
                    ❌ <?= $pesan; ?>
                </div>
            <?php endif; ?>

            <form action="auth/reset_password_proses.php" method="POST" id="resetForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">
                
                <!-- Password Baru -->
                <div class="mb-4 group">
                    <label for="password" class="block text-sm text-gray-600 mb-2">
                        <i class="fa-solid fa-lock mr-2 text-brand-primary"></i>
                        Kata Sandi Baru
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required minlength="6"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/30 transition-all duration-300 group-hover:shadow-md pr-10"
                               placeholder="Minimal 6 karakter">
                        <i class="fa-regular fa-eye-slash absolute right-4 top-3 text-gray-400 cursor-pointer hover:text-brand-primary transition-colors duration-300 z-10" id="togglePassword1"></i>
                    </div>
                    <!-- Password Strength -->
                    <div class="mt-2 flex gap-1">
                        <div class="strength-bar flex-1 bg-gray-200" id="str1"></div>
                        <div class="strength-bar flex-1 bg-gray-200" id="str2"></div>
                        <div class="strength-bar flex-1 bg-gray-200" id="str3"></div>
                        <div class="strength-bar flex-1 bg-gray-200" id="str4"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1" id="strengthText">Masukkan kata sandi</p>
                </div>
                
                <!-- Konfirmasi Password -->
                <div class="mb-5 group">
                    <label for="confirm_password" class="block text-sm text-gray-600 mb-2">
                        <i class="fa-solid fa-lock mr-2 text-brand-primary"></i>
                        Konfirmasi Kata Sandi
                    </label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/30 transition-all duration-300 group-hover:shadow-md pr-10"
                               placeholder="Ulangi kata sandi baru">
                        <i class="fa-regular fa-eye-slash absolute right-4 top-3 text-gray-400 cursor-pointer hover:text-brand-primary transition-colors duration-300 z-10" id="togglePassword2"></i>
                    </div>
                    <p class="text-xs mt-1 hidden" id="matchText"></p>
                </div>

                <!-- Tombol -->
                <button type="submit" 
                        class="w-full bg-brand-primary hover:bg-brand-primary-hover text-white font-semibold py-2.5 rounded-lg transition-all duration-300 mb-3 transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-xl"
                        id="submitBtn">
                    <span class="btn-text">
                        <i class="fa-solid fa-shield-halved mr-2"></i>
                        Reset Kata Sandi
                    </span>
                </button>
            </form>

            <?php elseif ($pesan_type == 'success'): ?>
            <!-- PESAN SUKSES -->
            <div class="text-center py-4">
                <div class="success-icon inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-5">
                    <i class="fa-solid fa-circle-check text-5xl text-green-500"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-3">Berhasil!</h2>
                <p class="text-sm text-gray-500 mb-6"><?= $pesan; ?></p>
                <a href="login.php" 
                   class="inline-block w-full bg-brand-primary hover:bg-brand-primary-hover text-white font-semibold py-2.5 rounded-lg transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg text-center">
                    <i class="fa-solid fa-arrow-right-to-bracket mr-2"></i>
                    Login Sekarang
                </a>
            </div>

            <?php else: ?>
            <!-- PESAN ERROR / TOKEN INVALID -->
            <div class="text-center py-4">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-5">
                    <i class="fa-solid fa-circle-xmark text-5xl text-red-400"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-3">Link Tidak Valid</h2>
                <p class="text-sm text-gray-500 mb-6"><?= $pesan; ?></p>
                <div class="space-y-3">
                    <a href="forgot_password.php" 
                       class="inline-block w-full bg-brand-primary hover:bg-brand-primary-hover text-white font-semibold py-2.5 rounded-lg transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg text-center">
                        <i class="fa-solid fa-paper-plane mr-2"></i>
                        Kirim Ulang Link
                    </a>
                    <a href="login.php" 
                       class="block text-brand-primary hover:text-brand-primary-hover transition-colors duration-300 text-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Login
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Link Kembali (hanya untuk form) -->
            <?php if ($token_valid): ?>
            <div class="text-center mt-4">
                <a href="login.php" class="text-brand-primary hover:text-brand-primary-hover transition-colors duration-300 text-sm flex items-center justify-center gap-2 group">
                    <i class="fa-solid fa-arrow-left text-xs group-hover:-translate-x-1 transition-transform duration-300"></i>
                    <span class="group-hover:underline">Kembali ke Halaman Login</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
    AOS.init({ once: true, mirror: false, duration: 800 });
    
    // Toggle Password Visibility
    function setupToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        if (toggle && input) {
            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
                this.classList.toggle('fa-eye');
                this.style.transform = 'scale(1.2)';
                setTimeout(() => { this.style.transform = 'scale(1)'; }, 200);
            });
        }
    }
    setupToggle('togglePassword1', 'password');
    setupToggle('togglePassword2', 'confirm_password');
    
    // Password Strength Meter
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            let score = 0;
            
            if (val.length >= 6) score++;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val) || /[^A-Za-z0-9]/.test(val)) score++;
            
            const colors = ['#ef4444', '#f59e0b', '#3b82f6', '#22c55e'];
            const texts = ['Lemah', 'Cukup', 'Baik', 'Kuat'];
            const textColors = ['text-red-500', 'text-yellow-500', 'text-blue-500', 'text-green-500'];
            
            for (let i = 1; i <= 4; i++) {
                const bar = document.getElementById('str' + i);
                bar.style.backgroundColor = i <= score ? colors[score - 1] : '#e5e7eb';
            }
            
            const strengthText = document.getElementById('strengthText');
            if (val.length === 0) {
                strengthText.textContent = 'Masukkan kata sandi';
                strengthText.className = 'text-xs text-gray-400 mt-1';
            } else {
                strengthText.textContent = 'Kekuatan: ' + texts[score - 1] || 'Sangat Lemah';
                strengthText.className = 'text-xs mt-1 ' + (textColors[score - 1] || 'text-red-500');
            }
            
            checkMatch();
        });
    }
    
    // Password Match Check
    if (confirmInput) {
        confirmInput.addEventListener('input', checkMatch);
    }
    
    function checkMatch() {
        const pass = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        const matchText = document.getElementById('matchText');
        
        if (!pass || !confirm || !matchText) return;
        
        if (confirm.value.length === 0) {
            matchText.classList.add('hidden');
            return;
        }
        
        matchText.classList.remove('hidden');
        
        if (pass.value === confirm.value) {
            matchText.textContent = '✅ Kata sandi cocok';
            matchText.className = 'text-xs mt-1 text-green-500';
            confirm.classList.remove('border-red-300');
            confirm.classList.add('border-green-300');
        } else {
            matchText.textContent = '❌ Kata sandi tidak cocok';
            matchText.className = 'text-xs mt-1 text-red-500';
            confirm.classList.remove('border-green-300');
            confirm.classList.add('border-red-300');
        }
    }
    
    // Form submit validation
    const resetForm = document.getElementById('resetForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (pass !== confirm) {
                e.preventDefault();
                alert('Konfirmasi kata sandi tidak cocok!');
                return;
            }
            
            if (pass.length < 6) {
                e.preventDefault();
                alert('Kata sandi minimal 6 karakter!');
                return;
            }
            
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        });
    }
    
    // Input hover effects
    const inputs = document.querySelectorAll('input[type="password"]');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.parentElement.classList.add('ring-2', 'ring-brand-primary/20', 'rounded-lg');
        });
        input.addEventListener('blur', function() {
            this.parentElement.parentElement.classList.remove('ring-2', 'ring-brand-primary/20', 'rounded-lg');
        });
    });
    
    if (window.innerWidth >= 768) document.body.style.overflow = 'hidden';
    window.addEventListener('resize', function() {
        document.body.style.overflow = window.innerWidth >= 768 ? 'hidden' : 'auto';
    });
</script>
</body>
</html>
