<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'superadmin') {
    header("Location: ../login.php");
    exit;
}

$nama_superadmin = $_SESSION['nama_lengkap'];
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
    $terms_content = mysqli_real_escape_string($koneksi, $_POST['terms_content']);
    $update = mysqli_query($koneksi, "UPDATE settings SET setting_value='$terms_content' WHERE setting_key='terms_of_service'");
    if ($update) {
        $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm font-medium'>Terms of Service berhasil diperbarui!</div>";
    } else {
        $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm font-medium'>Gagal memperbarui Terms of Service.</div>";
    }
}

$q_terms = mysqli_query($koneksi, "SELECT setting_value FROM settings WHERE setting_key='terms_of_service'");
$terms_data = mysqli_fetch_assoc($q_terms);
$current_terms = $terms_data ? $terms_data['setting_value'] : '';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Super Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Poppins', system-ui, -apple-system, sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
    </style>
    <!-- Trumbowyg Editor -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
</head>
<body class="bg-gray-100 flex font-sans min-h-screen text-gray-800">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#0f172a] text-gray-300 flex flex-col fixed h-full z-20 shadow-2xl">
        <div class="h-20 flex items-center px-6 border-b border-gray-800">
            <div class="bg-gradient-to-tr from-indigo-500 to-purple-500 p-2 rounded-lg mr-3 shadow-lg">
                <i class="fa-solid fa-bolt text-white text-xl"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-white tracking-widest uppercase">SaaS Center</h2>
                <p class="text-[10px] text-indigo-400 font-semibold tracking-wider">Super Admin</p>
            </div>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-all">
                <i class="fa-solid fa-building-columns w-6"></i> Manajemen Mitra
            </a>
            <a href="laporan.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-all">
                <i class="fa-solid fa-chart-line w-6"></i> Laporan Global
            </a>
            <a href="terms.php" class="flex items-center px-4 py-3 bg-indigo-600 text-white rounded-lg font-medium shadow-md transition-all">
                <i class="fa-solid fa-file-contract w-6"></i> Terms of Service
            </a>
            <a href="#" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-all opacity-50 cursor-not-allowed" title="Segera Hadir">
                <i class="fa-solid fa-gear w-6"></i> Pengaturan
            </a>
        </nav>
        
        <div class="p-4 border-t border-gray-800">
            <a href="../auth/logout.php" class="flex items-center justify-center gap-2 w-full py-2.5 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white border border-red-500 rounded-lg transition-all text-sm font-bold">
                <i class="fa-solid fa-power-off"></i> Keluar Sistem
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 ml-64 flex flex-col min-w-0 bg-slate-50/50">
        
        <!-- TOP NAVBAR -->
        <header class="h-20 bg-white flex items-center justify-between px-8 shadow-sm border-b border-gray-200 z-10 sticky top-0">
            <h1 class="text-xl font-bold text-gray-800 tracking-tight">Terms of Service Content</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center bg-indigo-50 px-4 py-2 rounded-full border border-indigo-100">
                    <span class="text-sm font-bold text-indigo-900"><?= $nama_superadmin; ?></span>
                </div>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            <?= $pesan; ?>

            <div class="mb-8 flex justify-between items-end">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Edit Terms of Service</h2>
                    <p class="text-gray-500 mt-1">Sesuaikan syarat dan ketentuan untuk platform spp digital yang akan dilihat oleh semua admin sekolah.</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6">
                    <form method="POST" action="">
                        <div class="mb-6">
                            <label for="terms_content" class="block text-sm font-semibold text-gray-700 mb-2">Isi Terms of Service</label>
                            <textarea id="terms_content" name="terms_content" rows="15" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><?= htmlspecialchars($current_terms); ?></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" name="simpan" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-colors flex items-center">
                                <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts for Editor -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#terms_content').trumbowyg({
                btns: [
                    ['viewHTML'],
                    ['undo', 'redo'],
                    ['formatting'],
                    ['strong', 'em', 'del'],
                    ['superscript', 'subscript'],
                    ['link'],
                    ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
                    ['unorderedList', 'orderedList'],
                    ['horizontalRule'],
                    ['removeformat'],
                    ['fullscreen']
                ],
                autogrow: true
            });
        });
    </script>
</body>
</html>
