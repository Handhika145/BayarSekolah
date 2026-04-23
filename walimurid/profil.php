<?php
session_start();
require_once '../config/koneksi.php';

// Cek login dan role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'walimurid') {
    header('Location: ../login.php');
    exit();
}

$id_walimurid = $_SESSION['id_user'];

// Proses Update Profil
$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $no_hp = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $tempat_lahir = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir']);
    $tanggal_lahir = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir']);

    // Handle upload foto profil
    $foto_profil_query = "";
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto_profil']['tmp_name'];
        $file_name = $_FILES['foto_profil']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = 'profil_' . $id_walimurid . '_' . time() . '.' . $file_ext;
            $upload_dir = '../uploads/profil/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $dest_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $dest_path)) {
                $foto_profil_query = ", foto_profil = '$new_file_name'";
            } else {
                $pesan_error = "Gagal mengunggah foto profil.";
            }
        } else {
            $pesan_error = "Format file tidak didukung. Harap unggah JPG, JPEG, atau PNG.";
        }
    }

    if (empty($pesan_error)) {
        $tanggal_lahir_query = empty($tanggal_lahir) ? "NULL" : "'$tanggal_lahir'";

        $q_update = "UPDATE users SET 
                        nama_lengkap = '$nama_lengkap',
                        email = '$email',
                        no_hp = '$no_hp',
                        tempat_lahir = '$tempat_lahir',
                        tanggal_lahir = $tanggal_lahir_query
                        $foto_profil_query
                     WHERE id_user = '$id_walimurid'";
                     
        if (mysqli_query($koneksi, $q_update)) {
            $pesan_sukses = "Profil berhasil diperbarui!";
            // Update session jika nama berubah
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            if(!empty($foto_profil_query)) {
                // Fetch the updated filename
                $q_foto = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id_user = '$id_walimurid'");
                if($q_foto && mysqli_num_rows($q_foto) > 0) {
                    $row = mysqli_fetch_assoc($q_foto);
                    $_SESSION['foto_profil'] = $row['foto_profil'];
                }
            }
        } else {
            $pesan_error = "Gagal memperbarui profil: " . mysqli_error($koneksi);
        }
    }
}

// Proses Update Foto Siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_foto_siswa'])) {
    $id_siswa_foto = mysqli_real_escape_string($koneksi, $_POST['id_siswa']);
    
    // Verifikasi bahwa siswa ini milik walimurid ini
    $cek_siswa = mysqli_query($koneksi, "SELECT id_siswa FROM siswa WHERE id_siswa = '$id_siswa_foto' AND id_walimurid = '$id_walimurid'");
    if(mysqli_num_rows($cek_siswa) > 0) {
        if (isset($_FILES['foto_siswa']) && $_FILES['foto_siswa']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['foto_siswa']['tmp_name'];
            $file_name = $_FILES['foto_siswa']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            if (in_array($file_ext, $allowed_ext)) {
                $new_file_name = 'siswa_' . $id_siswa_foto . '_' . time() . '.' . $file_ext;
                $upload_dir = '../uploads/profil/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $dest_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $q_update = "UPDATE siswa SET foto_profil = '$new_file_name' WHERE id_siswa = '$id_siswa_foto'";
                    if(mysqli_query($koneksi, $q_update)) {
                        $pesan_sukses = "Foto profil siswa berhasil diperbarui!";
                    } else {
                        $pesan_error = "Gagal memperbarui foto profil siswa.";
                    }
                } else {
                    $pesan_error = "Gagal mengunggah foto profil siswa.";
                }
            } else {
                $pesan_error = "Format file tidak didukung. Harap unggah JPG, JPEG, atau PNG.";
            }
        } else {
             $pesan_error = "Pilih foto yang valid untuk diunggah.";
        }
    } else {
        $pesan_error = "Akses ditolak atau data tidak valid.";
    }
}

// Ambil data wali murid terbaru
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_walimurid' AND role = 'walimurid'");
$currentUser = mysqli_fetch_assoc($query_user);

if (!$currentUser) {
    die("Data user tidak ditemukan. Silakan hubungi administrator.");
}

// Set default value variable
$foto_profil_url = (!empty($currentUser['foto_profil']) && file_exists('../uploads/profil/' . $currentUser['foto_profil'])) 
    ? '../uploads/profil/' . $currentUser['foto_profil'] 
    : 'default'; // special flag

// Ambil data siswa yang terhubung dengan akun ini
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_walimurid = '$id_walimurid'");
$siswa_list = [];
while($s = mysqli_fetch_assoc($query_siswa)) {
    $siswa_list[] = $s;
}

function escape($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - SPP Sekolah</title>
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
        .transition-smooth { transition: all 0.2s ease; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #1e293b; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
    </style>
</head>

<body class="bg-[#f4f7f6] flex font-sans min-h-screen text-gray-800">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#1e293b] text-gray-300 flex flex-col fixed h-full z-20">
        <div class="h-20 flex items-center px-6 border-b border-gray-700">
            <div class="bg-white p-1.5 rounded-full mr-3">
                <i class="fa-solid fa-graduation-cap text-green-600 text-xl"></i>
            </div>
            <div class="overflow-hidden">
                <h2 class="text-sm font-bold text-white leading-tight truncate w-40" title="<?= escape($_SESSION['nama_sekolah']); ?>">
                    <?= escape($_SESSION['nama_sekolah']); ?>
                </h2>
                <h2 class="text-[10px] font-bold text-green-400 uppercase tracking-widest mt-0.5">Portal Wali Murid</h2>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto py-6 custom-scrollbar">
            <nav class="px-4 space-y-1">
                <a href="dashboard.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium shadow-md transition-smooth">
                    <i class="fa-solid fa-border-all w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="tagihan.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-smooth">
                    <i class="fa-regular fa-credit-card w-6"></i>
                    <span>Tagihan & Bayar</span>
                </a>
                <a href="rincian_biaya.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-smooth">
                    <i class="fa-solid fa-list-check w-6"></i>
                    <span>Rincian Biaya</span>
                </a>
                <a href="form_banding.php" class="flex items-center px-4 py-3 hover:bg-gray-800 text-white rounded-lg font-medium shadow-md transition-colors">
                    <i class="fa-solid fa-clock-rotate-left w-6"></i>
                    <span>Riwayat Pengajuan</span>
                </a>
                <!-- Link Profil Aktif -->
                <a href="profil.php" class="flex items-center px-4 py-3 bg-[#10b981] text-white rounded-lg font-medium shadow-md transition-smooth">
                    <i class="fa-solid fa-user-gear w-6"></i>
                    <span>Profil Saya</span>
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-gray-700">
            <div class="flex items-center gap-3 mb-4 px-2">
                <?php if($foto_profil_url == 'default'): ?>
                    <div class="h-10 w-10 rounded-full bg-gray-700 flex items-center justify-center border border-gray-600">
                        <i class="fa-solid fa-user text-gray-300"></i>
                    </div>
                <?php else: ?>
                    <img src="<?= $foto_profil_url ?>" alt="Profil" class="h-10 w-10 rounded-full object-cover border border-gray-600">
                <?php endif; ?>
                <div class="flex-1 overflow-hidden">
                    <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($currentUser['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-green-400">Wali Murid</p>
                </div>
            </div>
            <a href="../auth/logout.php" class="flex items-center justify-center gap-2 w-full py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-smooth text-sm font-bold shadow-md">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 ml-64 flex flex-col min-w-0">
        <header class="h-20 bg-white flex items-center justify-between px-8 shadow-sm z-10 sticky top-0">
            <h1 class="text-xl font-bold text-gray-800 uppercase tracking-wide">Profil Saya</h1>
            <div class="flex items-center space-x-6">
                <!-- User Profile -->
                <a href="profil.php" class="flex items-center bg-green-50 px-4 py-2 rounded-full border border-green-100 transition hover:bg-green-100">
                    <?php if($foto_profil_url == 'default'): ?>
                        <div class="h-8 w-8 rounded-full bg-green-200 flex items-center justify-center mr-2">
                            <i class="fa-regular fa-user text-green-700"></i>
                        </div>
                    <?php else: ?>
                        <img src="<?= $foto_profil_url ?>" alt="Profil" class="h-8 w-8 rounded-full object-cover mr-2 border border-green-300">
                    <?php endif; ?>
                    <span class="text-sm font-semibold text-gray-700"><?= escape($currentUser['nama_lengkap']) ?></span>
                </a>
            </div>
        </header>

        <div class="p-8 max-w-6xl mx-auto w-full">
            <?php if (!empty($pesan_sukses)): ?>
                <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
                    <i class="fa-solid fa-circle-check mr-2"></i> <?= $pesan_sukses ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($pesan_error)): ?>
                <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i> <?= $pesan_error ?>
                </div>
            <?php endif; ?>

            <!-- TABS NAVIGATION -->
            <div class="mb-8 border-b border-gray-200">
                <nav class="flex space-x-8" aria-label="Tabs">
                    <button id="tab-btn-wali" class="border-[#10b981] text-[#10b981] whitespace-nowrap py-4 px-1 border-b-2 font-bold text-base transition-colors" onclick="switchTab('wali')">
                        <i class="fa-solid fa-user-tie mr-2"></i> Profil Wali Murid
                    </button>
                    <button id="tab-btn-siswa" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-base transition-colors" onclick="switchTab('siswa')">
                        <i class="fa-solid fa-children mr-2"></i> Profil Siswa/i
                    </button>
                </nav>
            </div>

            <!-- TABS CONTENT: WALI MURID -->
            <div id="tab-content-wali" class="block transition-opacity duration-300">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Kiri: Foto Profil & Info Ringkas -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
                            <div class="relative w-32 h-32 mx-auto mb-4 group">
                                <?php if($foto_profil_url == 'default'): ?>
                                    <div class="w-full h-full rounded-full bg-gray-100 border-4 border-green-50 flex items-center justify-center">
                                        <i class="fa-solid fa-user text-6xl text-gray-300"></i>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= $foto_profil_url ?>" alt="Foto Profil" class="w-full h-full rounded-full object-cover border-4 border-green-50 shadow-sm">
                                <?php endif; ?>
                                
                                <div class="absolute bottom-1 right-1 bg-green-500 p-2 rounded-full text-white shadow-md border-2 border-white">
                                    <i class="fa-solid fa-camera text-sm"></i>
                                </div>
                            </div>
                            
                            <h2 class="text-xl font-bold text-gray-800"><?= escape($currentUser['nama_lengkap']) ?></h2>
                            <p class="text-sm text-green-600 font-medium mb-4">Wali Murid</p>
                            
                            <div class="border-t border-gray-100 py-4 flex flex-col gap-2 text-left">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fa-solid fa-envelope w-6 text-gray-400"></i>
                                    <span class="truncate"><?= escape($currentUser['email'] ?: 'Belum diisi') ?></span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fa-solid fa-phone w-6 text-gray-400"></i>
                                    <span><?= escape($currentUser['no_hp'] ?: 'Belum diisi') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kanan: Form Edit Profil -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="font-bold text-xl text-gray-800 mb-6 border-b pb-3">Informasi Personal Wali Murid</h3>
                            
                            <form action="profil.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Nama Lengkap -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                        <input type="text" name="nama_lengkap" value="<?= escape($currentUser['nama_lengkap']) ?>" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-400 focus:outline-none transition">
                                    </div>
                                    
                                    <!-- Email -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-xs text-gray-400 font-normal">(Opsional)</span></label>
                                        <input type="email" name="email" value="<?= escape($currentUser['email']) ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-400 focus:outline-none transition">
                                    </div>
                                    
                                    <!-- No. Handphone -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">No. Handphone</label>
                                        <input type="text" name="no_hp" value="<?= escape($currentUser['no_hp']) ?>" placeholder="Contoh: 08123456789" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-400 focus:outline-none transition">
                                    </div>
                                    
                                    <!-- Username -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-xs text-gray-400 font-normal">(Untuk Login)</span></label>
                                        <input type="text" value="<?= escape($currentUser['username']) ?>" disabled class="w-full px-4 py-2 bg-gray-100 border border-gray-200 text-gray-500 rounded-lg cursor-not-allowed">
                                    </div>
                                    
                                    <!-- Tempat Lahir -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tempat Lahir</label>
                                        <input type="text" name="tempat_lahir" value="<?= escape($currentUser['tempat_lahir']) ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-400 focus:outline-none transition">
                                    </div>
                                    
                                    <!-- Tanggal Lahir -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                                        <input type="date" name="tanggal_lahir" value="<?= escape($currentUser['tanggal_lahir']) ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-400 focus:outline-none transition">
                                    </div>
                                    
                                    <!-- Upload Foto -->
                                    <div class="md:col-span-2 mt-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Perbarui Foto Profil <span class="text-xs text-gray-400 font-normal">(Format JPG, PNG max 2MB)</span></label>
                                        <div class="flex items-center justify-center w-full">
                                            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-2"></i>
                                                    <p class="mb-1 text-sm text-gray-500"><span class="font-semibold" id="file-name">Klik untuk memilih file</span> atau seret file ke sini</p>
                                                </div>
                                                <input id="dropzone-file" type="file" name="foto_profil" class="hidden" accept=".jpg,.jpeg,.png" onchange="updateFileName(this)" />
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end pt-4 border-t border-gray-100">
                                    <button type="submit" name="update_profil" class="px-6 py-2.5 bg-[#10b981] hover:bg-green-600 text-white rounded-lg font-bold shadow-md transition-smooth flex items-center gap-2">
                                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT: SISWA/I -->
            <div id="tab-content-siswa" class="hidden transition-opacity duration-300">
                <?php if (count($siswa_list) > 0): ?>
                    <div class="space-y-8">
                        <?php foreach ($siswa_list as $siswa): ?>
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <!-- Kiri: Foto Siswa & Info Ringkas -->
                                <div class="lg:col-span-1 space-y-6">
                                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
                                        <?php
                                        $foto_siswa_url = (!empty($siswa['foto_profil']) && file_exists('../uploads/profil/' . $siswa['foto_profil'])) 
                                            ? '../uploads/profil/' . $siswa['foto_profil'] 
                                            : 'default'; 
                                        ?>
                                        <div class="relative w-32 h-32 mx-auto mb-4 group">
                                            <?php if($foto_siswa_url == 'default'): ?>
                                                <div class="w-full h-full rounded-full bg-blue-50 border-4 border-blue-50 flex items-center justify-center shadow-sm">
                                                    <i class="fa-solid fa-user-graduate text-6xl text-blue-300"></i>
                                                </div>
                                            <?php else: ?>
                                                <img src="<?= $foto_siswa_url ?>" alt="Foto Siswa" class="w-full h-full rounded-full object-cover border-4 border-blue-50 shadow-sm">
                                            <?php endif; ?>
                                            
                                            <div class="absolute bottom-1 right-1 bg-blue-500 p-2 rounded-full text-white shadow-md border-2 border-white">
                                                <i class="fa-solid fa-camera text-sm"></i>
                                            </div>
                                        </div>
                                        
                                        <h2 class="text-xl font-bold text-gray-800"><?= escape($siswa['nama_siswa']) ?></h2>
                                        <p class="text-sm text-blue-600 font-medium mb-4">Siswa Aktif</p>
                                        
                                        <div class="border-t border-gray-100 py-4 flex flex-col gap-2 text-left">
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fa-solid fa-id-card w-6 text-gray-400"></i>
                                                <span class="truncate"><?= escape($siswa['nisn']) ?></span>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fa-solid fa-school w-6 text-gray-400"></i>
                                                <span>Kelas <?= escape($siswa['kelas']) ?> <?= escape($siswa['sub_kelas']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kanan: Detail Informasi & Form Foto -->
                                <div class="lg:col-span-2">
                                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col h-full">
                                        <h3 class="font-bold text-xl text-gray-800 mb-6 border-b pb-3">Informasi Detail Siswa/i</h3>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                            <!-- Nama Lengkap -->
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Siswa</label>
                                                <input type="text" value="<?= escape($siswa['nama_siswa']) ?>" disabled class="w-full px-4 py-2 bg-gray-50 border border-gray-200 text-gray-600 rounded-lg cursor-not-allowed">
                                            </div>
                                            
                                            <!-- NISN -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">NISN (Nomor Induk Siswa Nasional)</label>
                                                <input type="text" value="<?= escape($siswa['nisn']) ?>" disabled class="w-full px-4 py-2 bg-gray-50 border border-gray-200 text-gray-600 rounded-lg cursor-not-allowed">
                                            </div>
                                            
                                            <!-- Kelas -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Tingkat Kelas</label>
                                                <input type="text" value="<?= escape($siswa['kelas']) ?> <?= escape($siswa['sub_kelas']) ?>" disabled class="w-full px-4 py-2 bg-gray-50 border border-gray-200 text-gray-600 rounded-lg cursor-not-allowed">
                                            </div>
                                            
                                            <!-- Status Tautan -->
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Status Tautan Sistem</label>
                                                <div class="flex items-center w-full px-4 py-2 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                                                    <i class="fa-solid fa-check-circle mr-2"></i> Terhubung dengan Wali Murid
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-auto pt-6 border-t border-gray-100">
                                            <form action="profil.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="id_siswa" value="<?= $siswa['id_siswa'] ?>">
                                                
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Perbarui Foto Profil Siswa <span class="text-xs text-gray-400 font-normal">(Format JPG, PNG max 2MB)</span></label>
                                                <div class="flex items-center gap-4">
                                                    <div class="flex-1">
                                                        <label for="dropzone-siswa-<?= $siswa['id_siswa'] ?>" class="flex flex-col items-center justify-center w-full h-16 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                                            <div class="flex flex-col items-center justify-center">
                                                                <p class="text-sm text-gray-500"><i class="fa-solid fa-cloud-arrow-up mr-1"></i> <span class="font-semibold" id="file-name-siswa-<?= $siswa['id_siswa'] ?>">Pilih foto</span> atau seret</p>
                                                            </div>
                                                            <input id="dropzone-siswa-<?= $siswa['id_siswa'] ?>" type="file" name="foto_siswa" class="hidden" accept=".jpg,.jpeg,.png" onchange="document.getElementById('file-name-siswa-<?= $siswa['id_siswa'] ?>').textContent = this.files[0].name; document.getElementById('file-name-siswa-<?= $siswa['id_siswa'] ?>').classList.add('text-blue-600');" required />
                                                        </label>
                                                    </div>
                                                    <button type="submit" name="update_foto_siswa" class="px-5 h-16 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-bold shadow-md transition-smooth flex items-center justify-center gap-2 shrink-0 w-40">
                                                        <i class="fa-solid fa-upload"></i> Unggah Foto
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="mt-6 pt-4 border-t border-gray-100">
                                            <p class="text-xs text-gray-500 flex items-start">
                                                <i class="fa-solid fa-circle-info mt-0.5 mr-2 text-blue-400"></i> 
                                                <span>Data text siswa bersifat read-only. Perubahan profil (selain foto) silakan ajukan melalui administrator.</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
                        <div class="text-center py-12 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <i class="fa-solid fa-user-xmark text-5xl mb-4 text-gray-300"></i>
                            <h3 class="text-lg font-bold text-gray-700 mb-2">Belum Ada Siswa Terhubung</h3>
                            <p class="text-gray-500 text-sm max-w-md mx-auto">Akun ini belum memiliki siswa yang ditautkan. Hubungi admin sekolah untuk mengaitkan data anak Anda dengan akun ini.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function switchTab(tabId) {
            const btnWali = document.getElementById('tab-btn-wali');
            const btnSiswa = document.getElementById('tab-btn-siswa');
            const contentWali = document.getElementById('tab-content-wali');
            const contentSiswa = document.getElementById('tab-content-siswa');
            
            if (tabId === 'wali') {
                // Activate Wali button
                btnWali.classList.replace('border-transparent', 'border-[#10b981]');
                btnWali.classList.replace('text-gray-500', 'text-[#10b981]');
                btnWali.classList.remove('hover:text-gray-700', 'hover:border-gray-300');
                
                // Deactivate Siswa button
                btnSiswa.classList.replace('border-blue-500', 'border-transparent');
                btnSiswa.classList.replace('text-blue-600', 'text-gray-500');
                btnSiswa.classList.add('hover:text-gray-700', 'hover:border-gray-300');
                
                // Content display
                contentWali.classList.remove('hidden');
                contentWali.classList.add('block');
                contentSiswa.classList.remove('block');
                contentSiswa.classList.add('hidden');
            } else {
                // Activate Siswa button
                btnSiswa.classList.replace('border-transparent', 'border-blue-500');
                btnSiswa.classList.replace('text-gray-500', 'text-blue-600');
                btnSiswa.classList.remove('hover:text-gray-700', 'hover:border-gray-300');
                
                // Deactivate Wali button
                btnWali.classList.replace('border-[#10b981]', 'border-transparent');
                btnWali.classList.replace('text-[#10b981]', 'text-gray-500');
                btnWali.classList.add('hover:text-gray-700', 'hover:border-gray-300');
                
                // Content display
                contentSiswa.classList.remove('hidden');
                contentSiswa.classList.add('block');
                contentWali.classList.remove('block');
                contentWali.classList.add('hidden');
            }
        }

        function updateFileName(input) {
            const fileName = document.getElementById('file-name');
            if (input.files.length > 0) {
                fileName.textContent = input.files[0].name;
                fileName.classList.add('text-green-600');
            } else {
                fileName.innerHTML = 'Klik untuk memilih file';
                fileName.classList.remove('text-green-600');
            }
        }
    </script>

    <!-- CHATBOT WIDGET -->
    <?php include 'chatbot.php'; ?>

</body>
</html>
