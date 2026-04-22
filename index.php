<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memuat - BayarSekolah</title>
    
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
<!-- Konfigurasi Tailwind Custom Warna & Animasi -->
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
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 1s ease-out forwards',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    
    <style>
        /* CSS murni untuk loading bar - lebih handal dari Tailwind animation */
        .loading-bar-container {
            width: 256px;
            height: 8px;
            background-color: rgba(74, 222, 128, 0.2);
            border-radius: 9999px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .loading-bar-fill {
            height: 100%;
            background-color: #398c58;
            border-radius: 9999px;
            width: 0%;
            transition: width 0.05s linear; /* Transisi halus setiap update */
            position: relative;
            overflow: hidden;
        }
        
        .loading-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255,255,255,0.3), 
                transparent);
            animation: shimmer 1.2s infinite;
        }
        
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        
        /* Efek glow untuk logo */
        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(57, 140, 88, 0.4);
            }
            50% {
                box-shadow: 0 0 0 15px rgba(57, 140, 88, 0);
            }
        }
        
        .logo-glow {
            animation: pulseGlow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="font-poppins h-screen w-full bg-brand-light flex flex-col items-center justify-center overflow-hidden relative">

    <!-- Elemen dekoratif latar belakang (Glow) -->
    <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-green-300/30 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-brand-primary/10 rounded-full blur-3xl"></div>

    <div class="relative z-10 flex flex-col items-center animate-fade-in">
        <!-- Animasi Lottie -->
        <div class="mb-0 mt-[-40px]">
            <lottie-player src="assets/animation/loading_login.json" background="transparent" speed="1.2" style="width: 280px; height: 280px;" loop autoplay></lottie-player>
        </div>

        <!-- Logo -->
        <div class="flex items-center gap-4 mb-6 mt-[-30px]">
            <div class="w-16 h-16 bg-brand-primary text-white rounded-2xl flex items-center justify-center shadow-lg logo-glow">
                <i class="fa-solid fa-leaf text-3xl"></i>
            </div>
            <h1 class="text-4xl font-bold text-[#1a4a2d] tracking-tight">Bayar<span class="text-brand-primary">Sekolah</span></h1>
        </div>

        <p class="text-brand-dark/70 font-medium mb-8">Menyiapkan lingkungan belajar Anda...</p>

        <!-- Loading Bar - Menggunakan container terpisah untuk kontrol penuh -->
        <div class="loading-bar-container">
            <div class="loading-bar-fill" id="loadingBarFill"></div>
        </div>
        
        <!-- Persentase teks -->
        <div class="mt-3 text-sm text-brand-dark/60 font-bold tracking-widest" id="loadingText">
            0%
        </div>
    </div>

    <!-- Script yang DIPERBAIKI: Memastikan mulai dari 0% dan berjalan ke 100% -->
    <script>
        (function() {
            // Ambil elemen
            const loadingBarFill = document.getElementById('loadingBarFill');
            const loadingText = document.getElementById('loadingText');
            
            // Reset ke 0% terlebih dahulu (memastikan)
            loadingBarFill.style.width = '0%';
            loadingText.innerText = '0%';
            
            let progress = 0;
            const totalWaktu = 2500; // 2.5 detik total loading
            const stepInterval = 20; // Update setiap 20ms untuk animasi halus
            const totalSteps = totalWaktu / stepInterval; // 125 step
            const incrementPerStep = 100 / totalSteps; // 0.8% per step
            
            let animationId = null;
            let startTime = null;
            
            // Fungsi untuk update progress menggunakan requestAnimationFrame (lebih akurat)
            function updateProgress(currentTime) {
                if (!startTime) {
                    startTime = currentTime;
                }
                
                const elapsed = currentTime - startTime;
                let newProgress = (elapsed / totalWaktu) * 100;
                
                // Batasi progress maksimal 100%
                if (newProgress >= 100) {
                    newProgress = 100;
                    loadingBarFill.style.width = '100%';
                    loadingText.innerText = '100%';
                    
                    // Hentikan animasi
                    if (animationId) {
                        cancelAnimationFrame(animationId);
                    }
                    
                    // Beri jeda 300ms lalu pindah halaman
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 300);
                    
                    return;
                }
                
                // Update tampilan dengan presisi tinggi
                loadingBarFill.style.width = newProgress + '%';
                loadingText.innerText = Math.floor(newProgress) + '%';
                
                // Lanjutkan animasi
                animationId = requestAnimationFrame(updateProgress);
            }
            
            // Mulai animasi dengan requestAnimationFrame untuk akurasi tinggi
            animationId = requestAnimationFrame(updateProgress);
            
            // Backup: Fallback menggunakan setInterval jika requestAnimationFrame tidak stabil
            // (tapi biasanya tidak diperlukan, hanya untuk jaga-jaga)
            let fallbackProgress = 0;
            const fallbackInterval = setInterval(() => {
                // Cek apakah progress sudah mencapai 100% melalui requestAnimationFrame
                const currentWidth = parseFloat(loadingBarFill.style.width) || 0;
                if (currentWidth >= 99.5) {
                    clearInterval(fallbackInterval);
                    return;
                }
                
                // Jika progress macet karena sesuatu, gunakan fallback
                fallbackProgress += 1.2;
                if (fallbackProgress >= 100) {
                    fallbackProgress = 100;
                    clearInterval(fallbackInterval);
                    
                    // Pastikan tampilan final
                    loadingBarFill.style.width = '100%';
                    loadingText.innerText = '100%';
                    
                    if (animationId) {
                        cancelAnimationFrame(animationId);
                    }
                    
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 300);
                } else if (fallbackProgress > parseFloat(loadingBarFill.style.width || 0)) {
                    // Update hanya jika fallback lebih maju
                    loadingBarFill.style.width = fallbackProgress + '%';
                    loadingText.innerText = Math.floor(fallbackProgress) + '%';
                }
            }, 25);
            
            // Cleanup fallback jika requestAnimationFrame selesai duluan
            setTimeout(() => {
                if (parseFloat(loadingBarFill.style.width || 0) >= 99.5) {
                    clearInterval(fallbackInterval);
                }
            }, 2400);
        })();
    </script>

</body>
</html>