<?php
session_start();
require '../config/koneksi.php';

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$nama_admin = $_SESSION['nama_lengkap'];
$id_admin_login = $_SESSION['id_user'];
$id_sekolah = $_SESSION['id_sekolah'];
// --- NOTIFIKASI BANDING ---
$q_notif_banding = mysqli_query($koneksi, "SELECT COUNT(b.id_banding) as total_baru FROM banding b JOIN tagihan t ON b.id_tagihan = t.id_tagihan JOIN siswa s ON t.id_siswa = s.id_siswa WHERE s.id_sekolah = '$id_sekolah' AND b.status_banding = 'Menunggu'");
$notif_banding = mysqli_fetch_assoc($q_notif_banding)['total_baru'];
// KUNCI KEAMANAN SAAS
$pesan = '';

// --- LOGIKA PROSES BANDING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['proses_banding'])) {
    $id_banding = $_POST['id_banding'];
    $id_tagihan = $_POST['id_tagihan'];
    $status_baru = $_POST['status_banding'];

    // Mulai transaksi database
    mysqli_begin_transaction($koneksi);

    try {
        // 1. Update status banding
        mysqli_query($koneksi, "UPDATE banding SET status_banding='$status_baru' WHERE id_banding='$id_banding'");

        // 2. Jika status diset 'Selesai', otomatis lunasi tagihan
        if ($status_baru == 'Selesai') {
            // Cek dulu apakah tagihan belum lunas (mencegah double input)
            $cek_tagihan = mysqli_query($koneksi, "SELECT status FROM tagihan WHERE id_tagihan='$id_tagihan'");
            $dt_tagihan = mysqli_fetch_assoc($cek_tagihan);

            if ($dt_tagihan['status'] != 'Lunas') {
                $tgl_bayar = date('Y-m-d');
                // Catat ke tabel pembayaran
                mysqli_query($koneksi, "INSERT INTO pembayaran (id_tagihan, tgl_bayar, id_admin) VALUES ('$id_tagihan', '$tgl_bayar', '$id_admin_login')");
                // Ubah status tagihan jadi lunas
                mysqli_query($koneksi, "UPDATE tagihan SET status='Lunas' WHERE id_tagihan='$id_tagihan'");
            }
            $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm'><i class='fa-solid fa-circle-check mr-2'></i> Banding Selesai! Tagihan otomatis dilunasi.</div>";
        } else {
            $pesan = "<div class='bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded shadow-sm'><i class='fa-solid fa-info-circle mr-2'></i> Status pengajuan banding berhasil diperbarui.</div>";
        }

        mysqli_commit($koneksi);
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm'><i class='fa-solid fa-triangle-exclamation mr-2'></i> Terjadi kesalahan sistem: " . $e->getMessage() . "</div>";
    }
}

// --- AMBIL DATA BANDING (DENGAN ISOLASI SAAS) ---
$q_banding = mysqli_query($koneksi, "
    SELECT b.*, u.nama_lengkap AS nama_wali, t.jenis_tagihan, t.bulan, t.tahun, t.nominal, s.nama_siswa, s.kelas 
    FROM banding b
    JOIN users u ON b.id_walimurid = u.id_user
    JOIN tagihan t ON b.id_tagihan = t.id_tagihan
    JOIN siswa s ON t.id_siswa = s.id_siswa
    WHERE s.id_sekolah = '$id_sekolah' 
    ORDER BY b.tgl_pengajuan DESC
");
// Penambahan WHERE s.id_sekolah = '$id_sekolah' di atas sangat krusial!
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Banding - Sistem Pembayaran Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }
    </style>
</head>

<body class="bg-[#f8fafc] flex font-sans min-h-screen text-gray-700">

    <!-- SIDEBAR -->
    <aside class="w-[260px] bg-[#1C2434] text-gray-400 flex flex-col fixed h-full z-20">
        <div class="h-16 flex items-center px-5 border-b border-white/[0.06]">
            <div class="bg-white/10 p-2 rounded-lg mr-3 shrink-0"><i
                    class="fa-solid fa-graduation-cap text-white text-base"></i></div>
            <div class="overflow-hidden">
                <h2 class="text-[13px] font-semibold text-white leading-tight truncate w-40"
                    title="<?= $_SESSION['nama_sekolah']; ?>"><?= $_SESSION['nama_sekolah']; ?></h2>
                <p class="text-[10px] text-[#10B981] mt-0.5 font-bold">SaaS Panel</p>
            </div>
        </div>
        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto custom-scrollbar">
            <a href="dashboard.php"
                class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i
                    class="fa-solid fa-border-all w-7 text-[13px]"></i> Dashboard</a>
            <a href="data_siswa.php"
                class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i
                    class="fa-regular fa-user w-7 text-[13px]"></i> Data Siswa & Wali</a>
            <a href="data_tagihan.php"
                class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i
                    class="fa-regular fa-credit-card w-7 text-[13px]"></i> Tagihan</a>
            <a href="pembayaran.php"
                class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i
                    class="fa-solid fa-money-bill-transfer w-7 text-[13px]"></i> Pembayaran</a>
            <a href="m_rincian.php"
                class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i
                    class="fa-solid fa-list-check w-7 text-[13px]"></i> Master Rincian Biaya</a>
            <a href="laporan.php"
                class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i
                    class="fa-solid fa-file-invoice-dollar w-7 text-[13px]"></i> Laporan Keuangan</a>
            <a href="banding.php"
                class="flex items-center justify-between px-3 py-2.5 bg-[#10B981] text-white shadow-sm rounded-lg text-[13px] font-medium ">
                <div class="flex items-center"><i class="fa-solid fa-scale-balanced w-7 text-[13px]"></i> Data Banding
                </div>
                <?php if (isset($notif_banding) && $notif_banding > 0): ?><span
                        class="bg-red-500/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_banding; ?></span><?php endif; ?>
            </a>
            <a href="terms.php"
                class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i
                    class="fa-solid fa-file-contract w-7 text-[13px]"></i> Terms of Service</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 ml-[260px] flex flex-col min-w-0">
        <header class="h-14 bg-white flex items-center justify-between px-8 border-b border-gray-100 sticky top-0 z-10">
            <h1 class="text-sm font-semibold text-gray-800">Pengajuan Banding</h1>
            <div class="flex items-center space-x-4">
                <div
                    class="flex items-center bg-[#f0fdf4] text-[#166534] px-4 py-1.5 rounded-full text-xs font-semibold mr-2 border border-green-100">
                    Admin: <?= $nama_admin; ?> </div>
                <a href="../auth/logout.php"
                    class="flex items-center text-xs text-gray-400 hover:text-gray-600 transition-colors"><i
                        class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout</a>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">
            <?= $pesan; ?>

            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Daftar Komplain / Banding</h3>
                        <p class="text-xs text-gray-400 mt-1">Verifikasi bukti transfer dari wali murid.</p>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[1000px]">
                        <thead>
                            <tr
                                class="text-[11px] text-gray-400 uppercase tracking-wider font-medium border-b border-gray-100">
                                <th class="py-3 px-5">Waktu</th>
                                <th class="py-3 px-5">Pengirim (Wali)</th>
                                <th class="py-3 px-5">Rincian Tagihan</th>
                                <th class="py-3 px-5">Bukti & Pesan</th>
                                <th class="py-3 px-5">Status</th>
                                <th class="py-3 px-5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-600 divide-y divide-gray-50">
                            <?php
                            if (mysqli_num_rows($q_banding) > 0):
                                while ($row = mysqli_fetch_assoc($q_banding)):
                                    ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-4 px-5 text-gray-400 text-xs">
                                            <?= date('d M Y', strtotime($row['tgl_pengajuan'])); ?><br>
                                            <span class="text-gray-300"><?= date('H:i', strtotime($row['tgl_pengajuan'])); ?>
                                                WIB</span>
                                        </td>
                                        <td class="py-4 px-5">
                                            <p class="font-semibold text-gray-800"><?= $row['nama_wali']; ?></p>
                                        </td>
                                        <td class="py-4 px-5">
                                            <p class="font-semibold text-gray-800"><?= $row['nama_siswa']; ?>
                                                (<?= $row['kelas']; ?>)</p>
                                            <p class="text-xs text-gray-400"><?= $row['jenis_tagihan']; ?>         <?= $row['bulan']; ?>
                                                <?= $row['tahun']; ?></p>
                                            <p class="text-sm font-semibold text-gray-700 mt-0.5">Rp
                                                <?= number_format($row['nominal'], 0, ',', '.'); ?></p>
                                        </td>
                                        <td class="py-4 px-5 max-w-[200px]">
                                            <button onclick="lihatBukti('../uploads/<?= $row['bukti_transfer']; ?>')"
                                                class="bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white py-1.5 px-3 rounded-lg text-xs font-semibold transition mb-2 border border-gray-100">
                                                <i class="fa-solid fa-image mr-1"></i> Cek Bukti
                                            </button>
                                            <?php if (!empty($row['pesan_banding'])): ?>
                                                <div
                                                    class="bg-gray-50 border-l-2 border-gray-200 p-2 rounded text-xs text-gray-500 italic mt-1">
                                                    "<?= htmlspecialchars($row['pesan_banding']); ?>"
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-5">
                                            <?php
                                            $bg_badge = '';
                                            if ($row['status_banding'] == 'Menunggu')
                                                $bg_badge = 'bg-amber-50 text-amber-600';
                                            else if ($row['status_banding'] == 'Diproses')
                                                $bg_badge = 'bg-blue-50 text-blue-600';
                                            else if ($row['status_banding'] == 'Selesai')
                                                $bg_badge = 'bg-emerald-50 text-emerald-600';
                                            else
                                                $bg_badge = 'bg-red-50 text-red-500';
                                            ?>
                                            <span class="<?= $bg_badge ?> py-1 px-2.5 rounded-md text-xs font-medium">
                                                <?= $row['status_banding']; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-5 text-center">
                                            <?php if ($row['status_banding'] != 'Selesai' && $row['status_banding'] != 'Ditolak'): ?>
                                                <button
                                                    onclick="openProsesModal('<?= $row['id_banding'] ?>', '<?= $row['id_tagihan'] ?>', '<?= $row['status_banding'] ?>', '<?= addslashes($row['nama_siswa']) ?>')"
                                                    class="bg-[#10B981] hover:bg-[#059669] text-white py-1.5 px-4 rounded-lg text-xs font-semibold transition">
                                                    Proses
                                                </button>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-sm" title="Selesai"><i
                                                        class="fa-solid fa-check-double"></i></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center py-12 text-gray-400"><i
                                            class="fa-regular fa-envelope-open text-3xl block mb-3 text-emerald-300 drop-shadow-sm"></i>
                                        Belum ada pengajuan banding.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL LIHAT BUKTI -->
    <div id="modalBukti"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col"
            id="modalBuktiContent">
            <div class="bg-[#10B981] p-4 flex justify-between items-center text-white">
                <h3 class="font-semibold text-sm"><i class="fa-solid fa-image mr-2"></i> Bukti Transfer</h3>
                <button onclick="closeModal('modalBukti')" class="text-gray-400 hover:text-white transition"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="p-4 bg-gray-100 flex justify-center items-center min-h-[400px]">
                <img id="imgBukti" src="" alt="Bukti Transfer" class="max-h-[80vh] max-w-full object-contain rounded">
            </div>
        </div>
    </div>

    <!-- MODAL PROSES BANDING -->
    <div id="modalProses"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300"
            id="modalProsesContent">
            <div class="bg-[#10B981] p-5 flex justify-between items-center text-white">
                <h3 class="font-semibold text-sm"><i class="fa-solid fa-scale-balanced mr-2"></i> Proses Banding</h3>
                <button onclick="closeModal('modalProses')" class="text-gray-400 hover:text-white transition"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form action="" method="POST" class="p-6">
                <input type="hidden" name="id_banding" id="proses_id_banding">
                <input type="hidden" name="id_tagihan" id="proses_id_tagihan">

                <p class="text-xs text-gray-500 mb-4">Ubah status verifikasi untuk tagihan atas nama <strong
                        id="proses_nama" class="text-gray-800"></strong>.</p>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-2">Update Status</label>
                    <select name="status_banding" id="proses_status" required
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm font-medium">
                        <option value="Menunggu">Menunggu</option>
                        <option value="Diproses">Sedang Diproses (Cek Mutasi)</option>
                        <option value="Selesai">Selesai (Setujui & Lunasi)</option>
                        <option value="Ditolak">Ditolak (Bukti Tidak Valid)</option>
                    </select>
                </div>

                <div
                    class="mt-4 p-3 bg-amber-50 border-l-2 border-amber-300 text-xs text-amber-700 rounded-lg leading-relaxed">
                    <i class="fa-solid fa-circle-info mr-1"></i> Jika memilih <strong>"Selesai"</strong>, tagihan
                    otomatis dianggap Lunas.
                </div>

                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalProses')"
                        class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 font-medium transition text-xs">Batal</button>
                    <button type="submit" name="proses_banding"
                        class="px-4 py-2 bg-[#10B981] hover:bg-[#059669] text-white rounded-lg font-semibold transition text-xs flex items-center">
                        <i class="fa-solid fa-save mr-1.5"></i> Simpan Status
                    </button>
                </div>
            </form>
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

        // Penyesuaian Path Bukti Transfer (Sesuai dengan folder uploads/ yang baru kita buat)
        function lihatBukti(urlGambar) {
            const imgEl = document.getElementById('imgBukti');
            imgEl.src = urlGambar;

            // Fallback jika ekstensi ternyata PDF atau file hilang
            imgEl.onerror = function () {
                this.src = 'https://placehold.co/600x400/e2e8f0/475569?text=Gagal+Memuat+Gambar/Ini+Bukan+Gambar';
            };
            openModal('modalBukti');
        }

        function openProsesModal(id_banding, id_tagihan, status, nama) {
            document.getElementById('proses_id_banding').value = id_banding;
            document.getElementById('proses_id_tagihan').value = id_tagihan;
            document.getElementById('proses_status').value = status;
            document.getElementById('proses_nama').innerText = nama;
            openModal('modalProses');
        }
    </script>
</body>

</html>