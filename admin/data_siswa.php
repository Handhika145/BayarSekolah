<?php
session_start();
require '../config/koneksi.php';

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$nama_admin = $_SESSION['nama_lengkap'];
$id_sekolah = $_SESSION['id_sekolah'];
// --- NOTIFIKASI BANDING ---
$q_notif_banding = mysqli_query($koneksi, "SELECT COUNT(b.id_banding) as total_baru FROM banding b JOIN tagihan t ON b.id_tagihan = t.id_tagihan JOIN siswa s ON t.id_siswa = s.id_siswa WHERE s.id_sekolah = '$id_sekolah' AND b.status_banding = 'Menunggu'");
$notif_banding = mysqli_fetch_assoc($q_notif_banding)['total_baru'];
// KUNCI SAAS
$pesan = '';

// --- LOGIKA HAPUS DATA ---
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    // Hapus hanya jika siswa tersebut milik sekolah ini
    $delete = mysqli_query($koneksi, "DELETE FROM siswa WHERE id_siswa='$id_hapus' AND id_sekolah='$id_sekolah'");
    if ($delete) {
        $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm'>Data siswa berhasil dihapus!</div>";
    }
}

// --- LOGIKA TAMBAH DATA (SISWA + WALI MURID) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah'])) {
    $nisn = mysqli_real_escape_string($koneksi, $_POST['nisn']);
    $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama_siswa']);
    $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas']);

    // Data Akun Wali Murid Baru
    $nama_wali = mysqli_real_escape_string($koneksi, $_POST['nama_wali']);
    $username_wali = mysqli_real_escape_string($koneksi, $_POST['username_wali']);
    $password_wali = $_POST['password_wali'];

    // 1. Cek Duplikat NISN di dalam sekolah yang sama
    $cek_nisn = mysqli_query($koneksi, "SELECT * FROM siswa WHERE nisn='$nisn' AND id_sekolah='$id_sekolah'");
    if (mysqli_num_rows($cek_nisn) > 0) {
        $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm'>Gagal! NISN $nisn sudah terdaftar di sekolah ini.</div>";
    } else {
        // Mulai Transaksi Database
        mysqli_begin_transaction($koneksi);
        try {
            $id_wali_baru = 'NULL';

            // 2. Jika form akun wali murid diisi, buat akunnya dulu
            if (!empty($nama_wali) && !empty($username_wali) && !empty($password_wali)) {
                // Cek apakah username wali sudah dipakai (username harus unik secara global di sistem)
                $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username_wali'");
                if (mysqli_num_rows($cek_user) > 0) {
                    throw new Exception("Username wali murid '$username_wali' sudah dipakai. Silakan gunakan yang lain.");
                }

                $pass_hash = password_hash($password_wali, PASSWORD_DEFAULT);
                $q_wali = "INSERT INTO users (id_sekolah, username, password, password_plain, nama_lengkap, role) VALUES ('$id_sekolah', '$username_wali', '$pass_hash', '$password_wali', '$nama_wali', 'walimurid')";
                mysqli_query($koneksi, $q_wali);

                // Ambil ID User yang baru dibuat untuk ditautkan ke siswa
                $id_wali_baru = "'" . mysqli_insert_id($koneksi) . "'";
            }

            // 3. Simpan Data Siswa
            $q_siswa = "INSERT INTO siswa (id_sekolah, nisn, nama_siswa, kelas, id_walimurid) VALUES ('$id_sekolah', '$nisn', '$nama_siswa', '$kelas', $id_wali_baru)";
            mysqli_query($koneksi, $q_siswa);

            mysqli_commit($koneksi);
            $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm font-bold'>Berhasil! Data siswa (dan akun wali murid) telah ditambahkan.</div>";

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm'>" . $e->getMessage() . "</div>";
        }
    }
}

// --- LOGIKA EDIT DATA SISWA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    $id_siswa = $_POST['id_siswa'];
    $nisn = mysqli_real_escape_string($koneksi, $_POST['nisn']);
    $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama_siswa']);
    $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $id_walimurid = empty($_POST['id_walimurid']) ? 'NULL' : "'" . $_POST['id_walimurid'] . "'";

    $query = "UPDATE siswa SET nisn='$nisn', nama_siswa='$nama_siswa', kelas='$kelas', id_walimurid=$id_walimurid WHERE id_siswa='$id_siswa' AND id_sekolah='$id_sekolah'";
    if (mysqli_query($koneksi, $query)) {
        $pesan = "<div class='bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded shadow-sm'>Data siswa berhasil diperbarui!</div>";
    }
}

// Ambil data Wali Murid (Hanya dari sekolah ini) untuk dropdown Edit
$q_wali = mysqli_query($koneksi, "SELECT id_user, nama_lengkap, username FROM users WHERE role='walimurid' AND id_sekolah='$id_sekolah'");
$data_wali = [];
while ($w = mysqli_fetch_assoc($q_wali)) {
    $data_wali[] = $w;
}

// --- LOGIKA FILTER ---
$filter_where = "WHERE s.id_sekolah = '$id_sekolah'";
$f_kelas = isset($_GET['f_kelas']) ? mysqli_real_escape_string($koneksi, $_GET['f_kelas']) : '';

if (!empty($f_kelas)) {
    $filter_where .= " AND s.kelas = '$f_kelas'";
}

// Ambil data Siswa beserta nama walinya (Hanya dari sekolah ini)
$q_siswa = mysqli_query($koneksi, "
    SELECT s.*, u.nama_lengkap AS nama_wali, u.username AS username_wali 
    FROM siswa s 
    LEFT JOIN users u ON s.id_walimurid = u.id_user 
    $filter_where
    ORDER BY s.kelas ASC, s.nama_siswa ASC
");

// Ambil List Kelas Unik untuk Filter
$q_list_kelas = mysqli_query($koneksi, "SELECT DISTINCT kelas FROM siswa WHERE id_sekolah = '$id_sekolah' ORDER BY kelas ASC");
$list_kelas = [];
while($lk = mysqli_fetch_assoc($q_list_kelas)) {
    $list_kelas[] = $lk['kelas'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - Sistem Pembayaran Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    </style>
</head>

<body class="bg-[#f8fafc] flex font-sans min-h-screen text-gray-700">

    <!-- SIDEBAR -->
    <aside class="w-[260px] bg-[#1C2434] text-gray-400 flex flex-col fixed h-full z-20">
        <div class="h-16 flex items-center px-5 border-b border-white/[0.06]">
            <div class="bg-white/10 p-2 rounded-lg mr-3">
                <i class="fa-solid fa-graduation-cap text-white text-base"></i>
            </div>
            <div class="overflow-hidden">
                <h2 class="text-[13px] font-semibold text-white leading-tight truncate w-40" title="<?= $_SESSION['nama_sekolah']; ?>">
                    <?= $_SESSION['nama_sekolah']; ?>
                </h2>
                <p class="text-[10px] text-[#10B981] mt-0.5 font-bold">SaaS Panel</p>
            </div>
        </div>
        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-solid fa-border-all w-7 text-[13px]"></i> Dashboard
            </a>
            <a href="data_siswa.php" class="flex items-center px-3 py-2.5 bg-[#10B981] text-white shadow-sm rounded-lg text-[13px] font-medium ">
                <i class="fa-regular fa-user w-7 text-[13px]"></i> Data Siswa & Wali
            </a>
            <a href="data_tagihan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-regular fa-credit-card w-7 text-[13px]"></i> Tagihan
            </a>
            <a href="pembayaran.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-solid fa-money-bill-transfer w-7 text-[13px]"></i> Pembayaran
            </a>
            <a href="m_rincian.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-solid fa-list-check w-7 text-[13px]"></i> Master Rincian Biaya
            </a>
            <a href="laporan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-solid fa-file-invoice-dollar w-7 text-[13px]"></i> Laporan Keuangan
            </a>
            <a href="banding.php" class="flex items-center justify-between px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <div class="flex items-center">
                    <i class="fa-solid fa-scale-balanced w-7 text-[13px]"></i> Data Banding
                </div>
                <?php if (isset($notif_banding) && $notif_banding > 0): ?>
                    <span class="bg-red-500/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_banding; ?></span>
                <?php endif; ?>
            </a>
            <a href="terms.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <i class="fa-solid fa-file-contract w-7 text-[13px]"></i> Terms of Service
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 ml-[260px] flex flex-col min-w-0">

        <header class="h-14 bg-white flex items-center justify-between px-8 border-b border-gray-100 sticky top-0 z-10">
            <h1 class="text-sm font-semibold text-gray-800">Data Siswa & Wali</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center bg-[#f0fdf4] text-[#166534] px-4 py-1.5 rounded-full text-xs font-semibold mr-2 border border-green-100"> Admin: <?= $nama_admin; ?> </div>
                <a href="../auth/logout.php" class="flex items-center text-xs text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout
                </a>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">

            <?= $pesan; ?>

            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Daftar Siswa & Wali Murid</h3>
                        <p class="text-xs text-gray-400 mt-1">Kelola data siswa dan buatkan akun portal untuk orang tua.</p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="cetak_akun_wali.php" target="_blank"
                            class="bg-[#10B981] hover:bg-[#059669] text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center">
                            <i class="fa-solid fa-print mr-1.5"></i> Cetak Akun Wali
                        </a>
                        <button onclick="openModal('modalTambah')"
                            class="bg-[#10B981] hover:bg-[#059669] text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center">
                            <i class="fa-solid fa-plus mr-1.5"></i> Tambah Data
                        </button>
                    </div>
                </div>

                <!-- FILTER FORM -->
                <form method="GET" class="mb-6 p-4 bg-emerald-50/30 rounded-xl border border-emerald-100/50 flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-bold text-emerald-700 uppercase tracking-wider mb-1.5 ml-1">Cari Berdasarkan Kelas</label>
                        <select name="f_kelas" class="w-full bg-white border border-emerald-100 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                            <option value="">-- Semua Kelas --</option>
                            <?php foreach($list_kelas as $kls): ?>
                                <option value="<?= $kls ?>" <?= $f_kelas == $kls ? 'selected' : '' ?>>Kelas <?= $kls ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-[#10B981] hover:bg-[#059669] text-white px-5 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center shadow-sm">
                            <i class="fa-solid fa-filter mr-2"></i> Filter Data
                        </button>
                        <?php if(!empty($f_kelas)): ?>
                            <a href="data_siswa.php" class="bg-rose-50 text-rose-600 hover:bg-rose-100 px-4 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center border border-rose-100">
                                <i class="fa-solid fa-rotate-left mr-2"></i> Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="text-[11px] text-gray-400 uppercase tracking-wider font-medium border-b border-gray-100">
                                <th class="py-3 px-4 w-12">No</th>
                                <th class="py-3 px-4">Nama Siswa & NISN</th>
                                <th class="py-3 px-4">Kelas</th>
                                <th class="py-3 px-4">Akun Wali Murid</th>
                                <th class="py-3 px-4 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-600">
                            <?php
                            $no = 1;
                            if (mysqli_num_rows($q_siswa) > 0):
                                while ($row = mysqli_fetch_assoc($q_siswa)):
                                    ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3.5 px-4 align-top"><?= $no++; ?></td>
                                        <td class="py-3.5 px-4 align-top">
                                            <p class="font-semibold text-gray-800 text-sm"><?= $row['nama_siswa']; ?></p>
                                            <p class="text-[11px] text-gray-400 mt-0.5 font-mono">NISN: <?= $row['nisn']; ?></p>
                                        </td>
                                        <td class="py-3.5 px-4 align-top">
                                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 py-1 px-2.5 rounded-md text-xs font-medium">
                                                <?= $row['kelas']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <?php if ($row['nama_wali']): ?>
                                                <div class="bg-indigo-50 border border-indigo-100 p-2 rounded-lg inline-block">
                                                    <p class="text-gray-700 font-semibold text-xs mb-0.5"><i class="fa-solid fa-user-shield mr-1 text-gray-400"></i> <?= $row['nama_wali']; ?></p>
                                                    <p class="text-gray-400 text-[11px] font-mono">User: <?= $row['username_wali']; ?></p>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-rose-500 italic text-xs bg-rose-50 py-1 px-2 rounded-md border border-rose-100 font-medium"><i class="fa-solid fa-link-slash mr-1"></i> Belum Ditautkan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4 flex justify-center space-x-1.5 align-top">
                                            <button
                                                onclick="openEditModal('<?= $row['id_siswa'] ?>', '<?= $row['nisn'] ?>', '<?= $row['nama_siswa'] ?>', '<?= $row['kelas'] ?>', '<?= $row['id_walimurid'] ?>')"
                                                class="bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white p-2 rounded-lg transition"
                                                title="Edit Identitas Siswa">
                                                <i class="fa-solid fa-pen text-xs"></i>
                                            </button>
                                            <a href="?hapus=<?= $row['id_siswa'] ?>"
                                                onclick="return confirm('Yakin ingin menghapus data siswa ini? Semua tagihannya akan ikut terhapus!');"
                                                class="bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white p-2 rounded-lg transition"
                                                title="Hapus">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-gray-400"><i class="fa-regular fa-folder-open text-3xl block mb-3 text-emerald-300 drop-shadow-sm"></i> <span class="text-emerald-600 font-medium tracking-wide">Belum ada data siswa terdaftar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ================= MODAL TAMBAH DATA (SISWA + WALI) ================= -->
    <div id="modalTambah"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[90vh] flex flex-col"
            id="modalTambahContent">
            <div class="bg-[#10B981] p-5 flex justify-between items-center text-white shrink-0">
                <h3 class="font-semibold text-sm"><i class="fa-solid fa-user-plus mr-2"></i> Tambah Data Baru</h3>
                <button onclick="closeModal('modalTambah')" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <div class="overflow-y-auto p-6 custom-scrollbar">
                <form action="" method="POST">

                    <!-- Bagian Data Siswa -->
                    <div class="mb-6">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 border-b border-gray-100 pb-2">1. Identitas Siswa</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">NISN <span class="text-red-400">*</span></label>
                                <input type="text" name="nisn" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm" placeholder="Nomor Induk Nasional">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Kelas <span class="text-red-400">*</span></label>
                                <input type="text" name="kelas" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm" placeholder="Cth: X MIPA 1">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Nama Lengkap Siswa <span class="text-red-400">*</span></label>
                                <input type="text" name="nama_siswa" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm" placeholder="Sesuai Akta Kelahiran">
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Buat Akun Wali Murid -->
                    <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100">
                        <div class="flex items-center mb-3">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">2. Buat Akun Wali Murid</h4>
                            <span class="ml-2 text-[10px] bg-gray-200 text-gray-600 py-0.5 px-2 rounded-full font-bold">OPSIONAL</span>
                        </div>
                        <p class="text-xs text-gray-400 mb-4">Isi form di bawah ini jika ingin langsung membuatkan username & password untuk orang tua siswa ini.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Nama Orang Tua / Wali</label>
                                <input type="text" name="nama_wali" class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm" placeholder="Nama lengkap orang tua">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Buat Username Login</label>
                                <input type="text" name="username_wali" class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm" placeholder="Tanpa spasi (cth: wali_budi)">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Buat Password</label>
                                <input type="text" name="password_wali" class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm" placeholder="Minimal 6 karakter">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-2">
                        <button type="button" onclick="closeModal('modalTambah')" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 font-medium transition text-xs">Batal</button>
                        <button type="submit" name="tambah" class="px-4 py-2 bg-[#10B981] hover:bg-[#059669] text-white rounded-lg font-semibold transition text-xs">Simpan Data Baru</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL EDIT DATA (Hanya Siswa & Tautan) ================= -->
    <div id="modalEdit"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col"
            id="modalEditContent">
            <div class="bg-[#10B981] p-5 flex justify-between items-center text-white shrink-0">
                <h3 class="font-semibold text-sm"><i class="fa-solid fa-pen-to-square mr-2"></i> Edit Data Siswa</h3>
                <button onclick="closeModal('modalEdit')" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <div class="overflow-y-auto p-6 custom-scrollbar">
                <form action="" method="POST">
                    <input type="hidden" name="id_siswa" id="edit_id">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">NISN</label>
                            <input type="text" name="nisn" id="edit_nisn" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nama Lengkap Siswa</label>
                            <input type="text" name="nama_siswa" id="edit_nama" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Kelas</label>
                            <input type="text" name="kelas" id="edit_kelas" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm">
                        </div>
                        <div class="pt-2 border-t border-gray-100">
                            <label class="block text-xs font-medium text-gray-500 mb-2">Ubah Tautan Wali Murid</label>
                            <select name="id_walimurid" id="edit_wali" class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm bg-gray-50">
                                <option value="">-- Tidak Ditautkan --</option>
                                <?php foreach ($data_wali as $w): ?>
                                    <option value="<?= $w['id_user'] ?>"><?= $w['nama_lengkap'] ?> (<?= $w['username'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-2">
                        <button type="button" onclick="closeModal('modalEdit')" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 font-medium transition text-xs">Batal</button>
                        <button type="submit" name="edit" class="px-4 py-2 bg-[#10B981] hover:bg-[#059669] text-white rounded-lg font-semibold transition text-xs">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT -->
    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            const content = document.getElementById(modalId + 'Content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            const content = document.getElementById(modalId + 'Content');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        function openEditModal(id, nisn, nama, kelas, id_wali) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nisn').value = nisn;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kelas').value = kelas;
            document.getElementById('edit_wali').value = id_wali;
            openModal('modalEdit');
        }
    </script>
</body>

</html>
