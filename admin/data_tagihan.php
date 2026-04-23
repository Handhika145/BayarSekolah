<?php
session_start();
require '../config/koneksi.php';
$id_sekolah = $_SESSION['id_sekolah'];
// --- NOTIFIKASI BANDING ---
$q_notif_banding = mysqli_query($koneksi, "SELECT COUNT(b.id_banding) as total_baru FROM banding b JOIN tagihan t ON b.id_tagihan = t.id_tagihan JOIN siswa s ON t.id_siswa = s.id_siswa WHERE s.id_sekolah = '$id_sekolah' AND b.status_banding = 'Menunggu'");
$notif_banding = mysqli_fetch_assoc($q_notif_banding)['total_baru'];


// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$nama_admin = $_SESSION['nama_lengkap'];
$pesan = '';

// --- LOGIKA HAPUS TAGIHAN ---
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $delete = mysqli_query($koneksi, "DELETE FROM tagihan WHERE id_tagihan='$id_hapus'");
    if ($delete) {
        $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm'>Data tagihan berhasil dihapus!</div>";
    }
}

// --- LOGIKA TAMBAH & EDIT & MASSAL TAGIHAN ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['tambah'])) {
        $id_siswa = $_POST['id_siswa'];
        $jenis_tagihan = $_POST['jenis_tagihan'];
        $bulan = $_POST['bulan'];
        $tahun = $_POST['tahun'];
        $nominal = $_POST['nominal'];
        
        $no_bulan_list = ['UTS Semester Ganjil', 'UAS Semester Ganjil', 'UTS Semester Genap', 'UAS Semester Genap', 'PPDB', 'Daftar Ulang', 'LKS'];
        if (in_array($jenis_tagihan, $no_bulan_list)) {
            $bulan = '-';
        }

        $query = "INSERT INTO tagihan (id_siswa, jenis_tagihan, bulan, tahun, nominal, status) 
                  VALUES ('$id_siswa', '$jenis_tagihan', '$bulan', '$tahun', '$nominal', 'Belum Lunas')";
        if (mysqli_query($koneksi, $query)) {
            $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm font-bold'><i class='fa-solid fa-check'></i> Tagihan baru berhasil dibuat!</div>";
        } else {
            $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm'>Gagal membuat tagihan: " . mysqli_error($koneksi) . "</div>";
        }
    } elseif (isset($_POST['edit'])) {
        $id_siswa = $_POST['id_siswa'];
        $jenis_tagihan = $_POST['jenis_tagihan'];
        $bulan = $_POST['bulan'];
        $tahun = $_POST['tahun'];
        $nominal = $_POST['nominal'];
        $id_tagihan = $_POST['id_tagihan'];
        $status = $_POST['status']; 
        
        $no_bulan_list = ['UTS Semester Ganjil', 'UAS Semester Ganjil', 'UTS Semester Genap', 'UAS Semester Genap', 'PPDB', 'Daftar Ulang', 'LKS'];
        if (in_array($jenis_tagihan, $no_bulan_list)) {
            $bulan = '-';
        }

        $query = "UPDATE tagihan SET id_siswa='$id_siswa', jenis_tagihan='$jenis_tagihan', bulan='$bulan', tahun='$tahun', nominal='$nominal', status='$status' WHERE id_tagihan='$id_tagihan'";
        if (mysqli_query($koneksi, $query)) {
            $pesan = "<div class='bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded shadow-sm font-bold'><i class='fa-solid fa-check'></i> Data tagihan berhasil diperbarui!</div>";
        }
    } elseif (isset($_POST['tambah_massal'])) {
        $id_kelas = $_POST['id_kelas'];
        $sub_kelas_target = $_POST['sub_kelas_massal'] ?? 'Semua';
        $jenis_tagihan = $_POST['jenis_tagihan_massal'];
        $nominal = $_POST['nominal_massal'];
        $tahun = $_POST['tahun_massal'];
        $bulan_array = isset($_POST['bulan_massal']) ? $_POST['bulan_massal'] : [];

        $no_bulan_list = ['UTS Semester Ganjil', 'UAS Semester Ganjil', 'UTS Semester Genap', 'UAS Semester Genap', 'PPDB', 'Daftar Ulang', 'LKS'];
        if (in_array($jenis_tagihan, $no_bulan_list)) {
            $bulan_array = ['-'];
        }

        if(!empty($bulan_array)) {
            $berhasil = 0;
            $q_sasaran = "";
            if($id_kelas == 'Semua') {
                $q_sasaran = mysqli_query($koneksi, "SELECT id_siswa FROM siswa WHERE id_sekolah = '$id_sekolah'");
            } else {
                $query_filter = "SELECT id_siswa FROM siswa WHERE id_sekolah = '$id_sekolah' AND kelas = '$id_kelas'";
                if ($sub_kelas_target !== 'Semua') {
                    $query_filter .= " AND sub_kelas = '$sub_kelas_target'";
                }
                $q_sasaran = mysqli_query($koneksi, $query_filter);
            }
            
            while($s = mysqli_fetch_assoc($q_sasaran)) {
                $siswa_id = $s['id_siswa'];
                foreach($bulan_array as $bln) {
                    $q_insert = "INSERT INTO tagihan (id_siswa, jenis_tagihan, bulan, tahun, nominal, status) 
                                 VALUES ('$siswa_id', '$jenis_tagihan', '$bln', '$tahun', '$nominal', 'Belum Lunas')";
                    if (mysqli_query($koneksi, $q_insert)) {
                        $berhasil++;
                    }
                }
            }
            if ($berhasil > 0) {
                $pesan = "<div class='bg-indigo-100 border-l-4 border-indigo-500 text-indigo-800 p-4 mb-6 rounded shadow-sm font-bold'><i class='fa-solid fa-layer-group'></i> $berhasil tagihan massal berhasil di-generate!</div>";
            } else {
                $pesan = "<div class='bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 mb-6 rounded shadow-sm'>Tidak ada tagihan yang dibuat. Kelas tersebut mungkin belum memiliki data siswa.</div>";
            }
        } else {
            $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm font-bold'>Gagal: Anda belum mencentang pilihan bulan satupun!</div>";
        }
    }
}

// Ambil data Siswa untuk pilihan dropdown di form
$q_siswa = mysqli_query($koneksi, "SELECT id_siswa, nisn, nama_siswa, kelas, sub_kelas FROM siswa WHERE id_sekolah = '$id_sekolah' ORDER BY kelas ASC, sub_kelas ASC, nama_siswa ASC");
$data_siswa = [];
while ($s = mysqli_fetch_assoc($q_siswa)) {
    $data_siswa[] = $s;
}

// Ambil list unik kelas untuk pembuatan tagihan massal
$data_kelas = [];
$q_kelas = mysqli_query($koneksi, "SELECT DISTINCT kelas FROM siswa WHERE id_sekolah = '$id_sekolah' ORDER BY kelas ASC");
while($k = mysqli_fetch_assoc($q_kelas)){
    $data_kelas[] = $k['kelas'];
}

// Ambil data seluruh Tagihan beserta nama Siswa (JOIN) untuk sekolah admin ini saja
$q_tagihan = mysqli_query($koneksi, "
    SELECT t.*, s.nisn, s.nama_siswa, s.kelas, s.sub_kelas 
    FROM tagihan t 
    JOIN siswa s ON t.id_siswa = s.id_siswa 
    WHERE s.id_sekolah = '$id_sekolah'
    ORDER BY t.tahun DESC, t.id_tagihan DESC
");

// Daftar Bulan untuk dropdown
$list_bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tagihan - Sistem Pembayaran Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
    </style>
</head>

<body class="bg-[#f8fafc] flex font-sans min-h-screen text-gray-700">

    <!-- SIDEBAR -->
    <aside class="w-[260px] bg-[#1C2434] text-gray-400 flex flex-col fixed h-full z-20">
        <div class="h-16 flex items-center px-5 border-b border-white/[0.06]">
            <div class="bg-white/10 p-2 rounded-lg mr-3"><i class="fa-solid fa-graduation-cap text-white text-base"></i></div>
            <div class="overflow-hidden">
                <h2 class="text-[13px] font-semibold text-white leading-tight truncate w-40" title="<?= $_SESSION['nama_sekolah']; ?>"><?= $_SESSION['nama_sekolah']; ?></h2>
                <p class="text-[10px] text-[#10B981] mt-0.5 font-bold">SaaS Panel</p>
            </div>
        </div>
        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-border-all w-7 text-[13px]"></i> Dashboard</a>
            <a href="data_siswa.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-regular fa-user w-7 text-[13px]"></i> Data Siswa & Wali</a>
            <a href="data_tagihan.php" class="flex items-center px-3 py-2.5 bg-[#10B981] text-white shadow-sm rounded-lg text-[13px] font-medium "><i class="fa-regular fa-credit-card w-7 text-[13px]"></i> Tagihan</a>
            <a href="pembayaran.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-money-bill-transfer w-7 text-[13px]"></i> Pembayaran</a>
            <a href="m_rincian.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-list-check w-7 text-[13px]"></i> Master Rincian Biaya</a>
            <a href="laporan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-file-invoice-dollar w-7 text-[13px]"></i> Laporan Keuangan</a>
            <a href="banding.php" class="flex items-center justify-between px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <div class="flex items-center"><i class="fa-solid fa-scale-balanced w-7 text-[13px]"></i> Data Banding</div>
                <?php if(isset($notif_banding) && $notif_banding > 0): ?><span class="bg-red-500/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_banding; ?></span><?php endif; ?>
            </a>
            <a href="terms.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-file-contract w-7 text-[13px]"></i> Terms of Service</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 ml-[260px] flex flex-col min-w-0">
        <header class="h-14 bg-white flex items-center justify-between px-8 border-b border-gray-100 sticky top-0 z-10">
            <h1 class="text-sm font-semibold text-gray-800">Kelola Tagihan</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center bg-[#f0fdf4] text-[#166534] px-4 py-1.5 rounded-full text-xs font-semibold mr-2 border border-green-100"> Admin: <?= $nama_admin; ?> </div>
                <a href="../auth/logout.php" class="flex items-center text-xs text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout</a>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">
            <?= $pesan; ?>

            <div class="bg-white rounded-xl border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Daftar Seluruh Tagihan</h3>
                        <p class="text-xs text-gray-400 mt-1">Buat, pantau, dan kelola tagihan SPP serta Retribusi.</p>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="openModal('modalTambahMassal')" class="bg-[#10B981] hover:bg-[#059669] text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center">
                            <i class="fa-solid fa-layer-group mr-1.5"></i> Buat Massal
                        </button>
                        <button onclick="openModal('modalTambah')" class="bg-[#10B981] hover:bg-[#059669] text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center">
                            <i class="fa-solid fa-file-invoice-dollar mr-1.5"></i> Buat Manual
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="text-[11px] text-gray-400 uppercase tracking-wider font-medium border-b border-gray-100">
                                <th class="py-3 px-4 w-12">No</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-4">Kelas</th>
                                <th class="py-3 px-4">Jenis Tagihan</th>
                                <th class="py-3 px-4">Bulan & Tahun</th>
                                <th class="py-3 px-4">Nominal</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-600">
                            <?php
                            $no = 1;
                            if (mysqli_num_rows($q_tagihan) > 0):
                                while ($row = mysqli_fetch_assoc($q_tagihan)):
                                    ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3.5 px-4"><?= $no++; ?></td>
                                        <td class="py-3.5 px-4">
                                            <p class="font-semibold text-gray-800"><?= $row['nama_siswa']; ?></p>
                                            <p class="text-[11px] text-gray-400 font-mono">NISN: <?= $row['nisn']; ?></p>
                                        </td>
                                        <td class="py-3.5 px-4"><?= $row['kelas']; ?> <?= $row['sub_kelas']; ?></td>
                                        <td class="py-3.5 px-4 text-gray-500"><?= $row['jenis_tagihan']; ?></td>
                                        <td class="py-3.5 px-4"><?= $row['bulan']; ?> <?= $row['tahun']; ?></td>
                                        <td class="py-3.5 px-4 font-semibold text-gray-800">Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></td>
                                        <td class="py-3.5 px-4">
                                            <?php if ($row['status'] == 'Lunas'): ?>
                                                <span class="inline-flex items-center whitespace-nowrap bg-emerald-50 text-emerald-600 py-1 px-2.5 rounded-md text-xs font-medium"><i class="fa-solid fa-check mr-1"></i> Lunas</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center whitespace-nowrap bg-amber-50 text-amber-600 py-1 px-2.5 rounded-md text-xs font-medium"><i class="fa-regular fa-clock mr-1"></i> Belum Lunas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4 flex justify-center space-x-1.5">
                                            <button onclick="openEditModal('<?= $row['id_tagihan'] ?>', '<?= $row['id_siswa'] ?>', '<?= $row['jenis_tagihan'] ?>', '<?= $row['bulan'] ?>', '<?= $row['tahun'] ?>', '<?= $row['nominal'] ?>', '<?= $row['status'] ?>')"
                                                class="bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white p-2 rounded-lg transition" title="Edit"><i class="fa-solid fa-pen text-xs"></i></button>
                                            <a href="?hapus=<?= $row['id_tagihan'] ?>" onclick="return confirm('Hapus tagihan ini? Data pembayaran terkait juga akan hilang!');"
                                                class="bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white p-2 rounded-lg transition" title="Hapus"><i class="fa-solid fa-trash text-xs"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="8" class="text-center py-8 text-gray-400"><i class="fa-solid fa-receipt text-2xl block mb-2 text-emerald-300 drop-shadow-sm"></i> <span class="text-emerald-600 font-medium tracking-wide">Belum ada data tagihan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL BUAT TAGIHAN -->
    <div id="modalTambah" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl overflow-hidden transform scale-95 transition-transform duration-300" id="modalTambahContent">
            <div class="bg-[#10B981] p-5 flex justify-between items-center text-white">
                <h3 class="font-semibold text-sm"><i class="fa-solid fa-file-invoice-dollar mr-2"></i> Buat Tagihan Baru</h3>
                <button onclick="closeModal('modalTambah')" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form action="" method="POST" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Pilih Siswa <span class="text-red-400">*</span></label>
                        <select name="id_siswa" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($data_siswa as $s): ?>
                                <option value="<?= $s['id_siswa'] ?>"><?= $s['kelas'] ?> <?= $s['sub_kelas'] ?> - <?= $s['nama_siswa'] ?> (<?= $s['nisn'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Jenis Tagihan <span class="text-red-400">*</span></label>
                        <select name="jenis_tagihan" id="tambah_jenis" onchange="toggleBulanFields('tambah_jenis', 'tambah_bulan_container', 'tambah_bulan')" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="SPP">SPP Bulanan</option>
                            <option value="UTS Semester Ganjil">UTS Semester Ganjil</option>
                            <option value="UAS Semester Ganjil">UAS Semester Ganjil</option>
                            <option value="UTS Semester Genap">UTS Semester Genap</option>
                            <option value="UAS Semester Genap">UAS Semester Genap</option>
                            <option value="PPDB">PPDB</option>
                            <option value="Daftar Ulang">Daftar Ulang</option>
                            <option value="LKS">LKS</option>
                            <option value="Retribusi">Retribusi / Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nominal (Rp) <span class="text-red-400">*</span></label>
                        <input type="number" name="nominal" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm" placeholder="250000">
                    </div>
                    <div id="tambah_bulan_container">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Bulan <span class="text-red-400">*</span></label>
                        <select name="bulan" id="tambah_bulan" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="-">-</option>
                            <?php foreach ($list_bulan as $bln): ?><option value="<?= $bln ?>"><?= $bln ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tahun <span class="text-red-400">*</span></label>
                        <input type="number" name="tahun" value="<?= date('Y') ?>" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalTambah')" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 font-medium transition text-xs">Batal</button>
                    <button type="submit" name="tambah" class="px-4 py-2 bg-[#10B981] hover:bg-[#059669] text-white rounded-lg font-semibold transition text-xs">Buat Tagihan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT TAGIHAN -->
    <div id="modalEdit" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl overflow-hidden transform scale-95 transition-transform duration-300" id="modalEditContent">
            <div class="bg-[#10B981] p-5 flex justify-between items-center text-white">
                <h3 class="font-semibold text-sm"><i class="fa-solid fa-pen-to-square mr-2"></i> Edit Tagihan</h3>
                <button onclick="closeModal('modalEdit')" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form action="" method="POST" class="p-6">
                <input type="hidden" name="id_tagihan" id="edit_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Siswa</label>
                        <select name="id_siswa" id="edit_siswa" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg outline-none bg-emerald-50/50 text-emerald-800 font-medium text-sm pointer-events-none" readonly>
                            <?php foreach ($data_siswa as $s): ?><option value="<?= $s['id_siswa'] ?>"><?= $s['kelas'] ?> <?= $s['sub_kelas'] ?> - <?= $s['nama_siswa'] ?></option><?php endforeach; ?>
                        </select>
                        <p class="text-[11px] text-gray-400 mt-1">Siswa tidak dapat diubah.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Jenis Tagihan</label>
                        <select name="jenis_tagihan" id="edit_jenis" onchange="toggleBulanFields('edit_jenis', 'edit_bulan_container', 'edit_bulan')" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="SPP">SPP Bulanan</option>
                            <option value="UTS Semester Ganjil">UTS Semester Ganjil</option>
                            <option value="UAS Semester Ganjil">UAS Semester Ganjil</option>
                            <option value="UTS Semester Genap">UTS Semester Genap</option>
                            <option value="UAS Semester Genap">UAS Semester Genap</option>
                            <option value="PPDB">PPDB</option>
                            <option value="Daftar Ulang">Daftar Ulang</option>
                            <option value="LKS">LKS</option>
                            <option value="Retribusi">Retribusi / Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nominal (Rp)</label>
                        <input type="number" name="nominal" id="edit_nominal" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm">
                    </div>
                    <div id="edit_bulan_container">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Bulan</label>
                        <select name="bulan" id="edit_bulan" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="-">-</option>
                            <?php foreach ($list_bulan as $bln): ?><option value="<?= $bln ?>"><?= $bln ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tahun</label>
                        <input type="number" name="tahun" id="edit_tahun" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status Pembayaran</label>
                        <select name="status" id="edit_status" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="Belum Lunas">Belum Lunas</option>
                            <option value="Lunas">Lunas</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalEdit')" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 font-medium transition text-xs">Batal</button>
                    <button type="submit" name="edit" class="px-4 py-2 bg-[#10B981] hover:bg-[#059669] text-white rounded-lg font-semibold transition text-xs">Update Tagihan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL BUAT TAGIHAN MASSAL -->
    <div id="modalTambahMassal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl overflow-hidden transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto custom-scrollbar" id="modalTambahMassalContent">
            <div class="bg-[#10B981] p-5 flex justify-between items-center text-white sticky top-0 z-10">
                <h3 class="font-semibold text-sm"><i class="fa-solid fa-layer-group mr-2"></i> Generate Tagihan Massal</h3>
                <button type="button" onclick="closeModal('modalTambahMassal')" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form action="" method="POST" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Pilih Kelas / Sasaran <span class="text-red-400">*</span></label>
                        <select name="id_kelas" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="">-- Pilih Sasaran Siswa --</option>
                            <option value="Semua">Terapkan ke SEMUA SISWA & SEMUA KELAS</option>
                            <?php foreach ($data_kelas as $kls): ?><option value="<?= $kls ?>">Kelas <?= $kls ?></option><?php endforeach; ?>
                        </select>
                        <div class="mt-3">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Pilih Sub Kelas (Opsional)</label>
                            <select name="sub_kelas_massal" class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                                <option value="Semua">-- Semua Sub Kelas --</option>
                                <?php foreach(range('A', 'J') as $char): ?>
                                    <option value="<?= $char ?>"><?= $char ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Sistem akan otomatis membuat tagihan kepada setiap siswa di kelas ini.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Jenis Tagihan <span class="text-red-400">*</span></label>
                        <select name="jenis_tagihan_massal" id="tambah_massal_jenis" onchange="toggleBulanMassal()" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="SPP">SPP Bulanan</option>
                            <option value="UTS Semester Ganjil">UTS Semester Ganjil</option>
                            <option value="UAS Semester Ganjil">UAS Semester Ganjil</option>
                            <option value="UTS Semester Genap">UTS Semester Genap</option>
                            <option value="UAS Semester Genap">UAS Semester Genap</option>
                            <option value="PPDB">PPDB</option>
                            <option value="Daftar Ulang">Daftar Ulang</option>
                            <option value="LKS">LKS</option>
                            <option value="Retribusi">Retribusi / Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nominal per Bulan (Rp) <span class="text-red-400">*</span></label>
                        <input type="number" name="nominal_massal" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm" placeholder="250000">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tahun Tagihan <span class="text-red-400">*</span></label>
                        <input type="number" name="tahun_massal" value="<?= date('Y') ?>" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm">
                    </div>
                    <div class="md:col-span-2" id="tambah_massal_bulan_container">
                        <label class="block text-xs font-medium text-gray-500 mb-3">Pilih Bulan (Centang yang akan dibuatkan):</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 bg-gray-50 p-4 border border-gray-100 rounded-lg">
                            <?php foreach ($list_bulan as $bln): ?>
                                <label class="flex items-center space-x-2 cursor-pointer p-1.5 hover:bg-white rounded transition">
                                    <input type="checkbox" name="bulan_massal[]" value="<?= $bln ?>" class="w-3.5 h-3.5 text-gray-800 border-gray-300 rounded focus:ring-gray-400">
                                    <span class="text-xs font-medium text-gray-600"><?= $bln ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalTambahMassal')" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 font-medium transition text-xs">Batal</button>
                    <button type="submit" name="tambah_massal" class="px-4 py-2 bg-[#10B981] hover:bg-[#059669] text-white rounded-lg font-semibold transition text-xs">Generate Tagihan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT UNTUK MODAL (POP-UP) -->
    <script>
        const noBulanList = ['UTS Semester Ganjil', 'UAS Semester Ganjil', 'UTS Semester Genap', 'UAS Semester Genap', 'PPDB', 'Daftar Ulang', 'LKS'];

        function toggleBulanFields(selectId, containerId, bulanId) {
            let selectElement = document.getElementById(selectId);
            let container = document.getElementById(containerId);
            let bulanSelect = document.getElementById(bulanId);
            if(selectElement && container && bulanSelect) {
                if(noBulanList.includes(selectElement.value)) {
                    container.style.display = 'none';
                    bulanSelect.removeAttribute('required');
                    bulanSelect.value = '-';
                } else {
                    container.style.display = 'block';
                    bulanSelect.setAttribute('required', 'required');
                    if(bulanSelect.value === '-') bulanSelect.selectedIndex = 1;
                }
            }
        }

        function toggleBulanMassal() {
            let jenisTarget = document.getElementById('tambah_massal_jenis').value;
            let container = document.getElementById('tambah_massal_bulan_container');
            if(jenisTarget && container) {
                if(noBulanList.includes(jenisTarget)) {
                    container.style.display = 'none';
                } else {
                    container.style.display = 'block';
                }
            }
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            const content = document.getElementById(modalId + 'Content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
            
            if(modalId === 'modalTambah') toggleBulanFields('tambah_jenis', 'tambah_bulan_container', 'tambah_bulan');
            if(modalId === 'modalTambahMassal') toggleBulanMassal();
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

        function openEditModal(id, siswa, jenis, bulan, tahun, nominal, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_siswa').value = siswa;
            document.getElementById('edit_jenis').value = jenis;
            document.getElementById('edit_bulan').value = bulan;
            document.getElementById('edit_tahun').value = tahun;
            document.getElementById('edit_nominal').value = nominal;
            document.getElementById('edit_status').value = status;
            toggleBulanFields('edit_jenis', 'edit_bulan_container', 'edit_bulan');
            openModal('modalEdit');
        }
    </script>
</body>

</html>
