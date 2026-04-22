<?php
session_start();
require '../config/koneksi.php';

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$id_sekolah = $_SESSION['id_sekolah'];
$nama_admin = $_SESSION['nama_lengkap'];

// --- PROSES CRUD ---
$pesan = '';

// Tambah Data
if (isset($_POST['tambah'])) {
    $kelas = $_POST['kelas'] ?? 'Semua';
    $nama_biaya = $_POST['nama_biaya'] ?? '';
    // Jika user memilih dropdown 'Lainnya', ambil input custom
    if ($nama_biaya === 'Lainnya') {
        $nama_biaya = $_POST['nama_biaya_custom'] ?? '';
    }

    $nominal = $_POST['nominal'] ?? 0;

    if (!empty($nama_biaya)) {
        $query = "INSERT INTO master_biaya (id_sekolah, kelas, nama_biaya, nominal) VALUES ('$id_sekolah', '$kelas', '$nama_biaya', '$nominal')";
        if (mysqli_query($koneksi, $query)) {
            $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm font-bold'>Data Master Biaya berhasil ditambahkan!</div>";
        } else {
            $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm font-bold'>Gagal menambahkan data " . mysqli_error($koneksi) . "</div>";
        }
    }
}

// Edit Data
if (isset($_POST['edit'])) {
    $id_biaya = $_POST['id_biaya'];
    $kelas = $_POST['kelas'] ?? 'Semua';
    $nama_biaya = $_POST['nama_biaya'] ?? '';
    if ($nama_biaya === 'Lainnya') {
        $nama_biaya = $_POST['nama_biaya_custom'] ?? '';
    }
    $nominal = $_POST['nominal'] ?? 0;

    if (!empty($nama_biaya)) {
        $query = "UPDATE master_biaya SET kelas='$kelas', nama_biaya='$nama_biaya', nominal='$nominal' WHERE id_biaya='$id_biaya' AND id_sekolah='$id_sekolah'";
        if (mysqli_query($koneksi, $query)) {
            $pesan = "<div class='bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded shadow-sm font-bold'>Data berhasil diperbarui!</div>";
        } else {
            $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm font-bold'>Gagal memperbarui data.</div>";
        }
    }
}

// Hapus Data
if (isset($_GET['hapus'])) {
    $id_biaya = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM master_biaya WHERE id_biaya='$id_biaya' AND id_sekolah='$id_sekolah'");
    header("Location: m_rincian.php");
    exit;
}

// Ambil Data
$q_rincian = mysqli_query($koneksi, "SELECT * FROM master_biaya WHERE id_sekolah='$id_sekolah' ORDER BY id_biaya DESC");
$data_rincian = [];
while ($row = mysqli_fetch_assoc($q_rincian)) {
    $data_rincian[] = $row;
}

// Ambil Data Kelas untuk dropdown
$q_kelas = mysqli_query($koneksi, "SELECT DISTINCT kelas FROM siswa WHERE id_sekolah = '$id_sekolah' ORDER BY kelas ASC");
$data_kelas = [];
while ($k = mysqli_fetch_assoc($q_kelas)) {
    if (!empty($k['kelas']))
        $data_kelas[] = $k['kelas'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Rincian Biaya - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }</script>
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
            <div class="bg-white/10 p-2 rounded-lg mr-3 shrink-0"><i class="fa-solid fa-graduation-cap text-white text-base"></i></div>
            <div class="overflow-hidden">
                <h2 class="text-[13px] font-semibold text-white leading-tight truncate w-40"><?= $_SESSION['nama_sekolah']; ?></h2>
                <p class="text-[10px] text-[#10B981] mt-0.5 font-bold">SaaS Panel</p>
            </div>
        </div>
        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto custom-scrollbar">
            <a href="dashboard.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-border-all w-7 text-[13px]"></i> Dashboard</a>
            <a href="data_siswa.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-regular fa-user w-7 text-[13px]"></i> Data Siswa & Wali</a>
            <a href="data_tagihan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-regular fa-credit-card w-7 text-[13px]"></i> Tagihan</a>
            <a href="pembayaran.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-money-bill-transfer w-7 text-[13px]"></i> Pembayaran</a>
            <a href="m_rincian.php" class="flex items-center px-3 py-2.5 bg-[#10B981] text-white shadow-sm rounded-lg text-[13px] font-medium "><i class="fa-solid fa-list-check w-7 text-[13px]"></i> Master Rincian Biaya</a>
            <a href="laporan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-file-invoice-dollar w-7 text-[13px]"></i> Laporan Keuangan</a>
            <a href="banding.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-scale-balanced w-7 text-[13px]"></i> Data Banding</a>
            <a href="terms.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-file-contract w-7 text-[13px]"></i> Terms of Service</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 ml-[260px] flex flex-col min-w-0">
        <header class="h-14 bg-white flex items-center justify-between px-8 border-b border-gray-100 sticky top-0 z-10">
            <h1 class="text-sm font-semibold text-gray-800">Master Rincian Biaya</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center bg-[#f0fdf4] text-[#166534] px-4 py-1.5 rounded-full text-xs font-semibold mr-2 border border-green-100"> Admin: <?= $nama_admin; ?> </div>
                <a href="../auth/logout.php" class="flex items-center text-xs text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout</a>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">
            <?= $pesan ?>

            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="p-5 flex flex-col md:flex-row justify-between items-center gap-4 border-b border-gray-50">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Daftar Biaya Utama Sekolah</h2>
                        <p class="text-xs text-gray-400 mt-1">Data estimasi biaya di bawah ini akan otomatis muncul pada layar HP Walimurid.</p>
                    </div>
                    <button onclick="openModal('modalTambah')" class="bg-[#10B981] hover:bg-[#059669] text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center shrink-0">
                        <i class="fa-solid fa-plus mr-1.5"></i> Tambah Rincian Biaya
                    </button>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="text-[11px] text-gray-400 uppercase tracking-wider font-medium border-b border-gray-100">
                                <th class="py-3 px-5 w-16 text-center">No</th>
                                <th class="py-3 px-5">Nama / Jenis Biaya</th>
                                <th class="py-3 px-5">Kelas Sasaran</th>
                                <th class="py-3 px-5 text-right">Nominal Estimasi (Rp)</th>
                                <th class="py-3 px-5 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-600 divide-y divide-gray-50">
                            <?php if (count($data_rincian) > 0): ?>
                                <?php $no = 1;
                                foreach ($data_rincian as $row): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3.5 px-5 text-center"><?= $no++; ?></td>
                                        <td class="py-3.5 px-5 font-semibold text-gray-800">
                                            <div class="flex items-center">
                                                <i class="fa-solid fa-money-check-dollar text-gray-300 mr-2"></i>
                                                <?= $row['nama_biaya']; ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-5">
                                            <span class="bg-gray-50 text-gray-600 px-2.5 py-1 rounded-md border border-gray-100 text-xs font-medium"><?= htmlspecialchars($row['kelas']); ?></span>
                                        </td>
                                        <td class="py-3.5 px-5 text-right font-semibold text-gray-800">Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></td>
                                        <td class="py-3.5 px-5 text-center">
                                            <div class="flex justify-center space-x-1.5">
                                                <button onclick="openEditModal('<?= $row['id_biaya'] ?>', '<?= htmlspecialchars($row['nama_biaya']) ?>', '<?= $row['nominal'] ?>', '<?= htmlspecialchars($row['kelas']) ?>')"
                                                    class="bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white p-2 rounded-lg transition" title="Edit">
                                                    <i class="fa-solid fa-pen text-xs"></i>
                                                </button>
                                                <a href="m_rincian.php?hapus=<?= $row['id_biaya'] ?>"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus Rincian Biaya ini? Wali murid tidak akan bisa melihatnya lagi.')"
                                                    class="bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white p-2 rounded-lg transition" title="Hapus">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-gray-400">
                                        <i class="fa-solid fa-clipboard-list text-gray-300 text-3xl mb-3 block"></i>
                                        Belum ada Master Rincian Biaya yang ditambahkan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH -->
    <div id="modalTambah" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300" id="modalTambahContent">
            <div class="bg-[#10B981] p-5 flex justify-between items-center text-white">
                <h3 class="font-semibold text-sm"><i class="fa-solid fa-plus mr-2"></i> Tambah Master Biaya</h3>
                <button onclick="closeModal('modalTambah')" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form action="" method="POST" class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Kelas Sasaran <span class="text-red-400">*</span></label>
                        <select name="kelas" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="Semua">Semua Kelas</option>
                            <?php foreach ($data_kelas as $kls): ?>
                                <option value="<?= htmlspecialchars($kls) ?>">Kelas <?= htmlspecialchars($kls) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nama/Jenis Biaya <span class="text-red-400">*</span></label>
                        <select name="nama_biaya" id="tambah_jenis" onchange="toggleCustom('tambah_jenis', 'container_custom_tambah')" required
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="">-- Pilih Jenis Biaya --</option>
                            <option value="SPP Semester Ganjil">SPP Semester Ganjil</option>
                            <option value="SPP Semester Genap">SPP Semester Genap</option>
                            <option value="UTS Semester Ganjil">UTS Semester Ganjil</option>
                            <option value="UAS Semester Ganjil">UAS Semester Ganjil</option>
                            <option value="UTS Semester Genap">UTS Semester Genap</option>
                            <option value="UAS Semester Genap">UAS Semester Genap</option>
                            <option value="PPDB">PPDB</option>
                            <option value="Daftar Ulang">Daftar Ulang</option>
                            <option value="LKS">LKS</option>
                            <option value="Seragam">Seragam</option>
                            <option value="Lainnya">++ Ketik Rincian Tersendiri ++</option>
                        </select>
                    </div>
                    <div id="container_custom_tambah" style="display:none;">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nama Rincian (Baru) <span class="text-red-400">*</span></label>
                        <input type="text" name="nama_biaya_custom" id="input_custom_tambah"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm"
                            placeholder="Contoh: Ekstrakurikuler">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nominal (Rp) <span class="text-red-400">*</span></label>
                        <input type="number" name="nominal" required
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm"
                            placeholder="150000">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalTambah')" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 font-medium transition text-xs">Batal</button>
                    <button type="submit" name="tambah" class="px-4 py-2 bg-[#10B981] hover:bg-[#059669] text-white rounded-lg font-semibold transition text-xs">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div id="modalEdit" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300" id="modalEditContent">
            <div class="bg-[#10B981] p-5 flex justify-between items-center text-white">
                <h3 class="font-semibold text-sm"><i class="fa-solid fa-pen-to-square mr-2"></i> Edit Master Biaya</h3>
                <button onclick="closeModal('modalEdit')" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form action="" method="POST" class="p-6">
                <input type="hidden" name="id_biaya" id="edit_id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Kelas Sasaran <span class="text-red-400">*</span></label>
                        <select name="kelas" id="edit_kelas" required class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="Semua">Semua Kelas</option>
                            <?php foreach ($data_kelas as $kls): ?>
                                <option value="<?= htmlspecialchars($kls) ?>">Kelas <?= htmlspecialchars($kls) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nama/Jenis Biaya <span class="text-red-400">*</span></label>
                        <select name="nama_biaya" id="edit_jenis" onchange="toggleCustom('edit_jenis', 'container_custom_edit')" required
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none bg-white text-sm">
                            <option value="SPP Semester Ganjil">SPP Semester Ganjil</option>
                            <option value="SPP Semester Genap">SPP Semester Genap</option>
                            <option value="UTS Semester Ganjil">UTS Semester Ganjil</option>
                            <option value="UAS Semester Ganjil">UAS Semester Ganjil</option>
                            <option value="UTS Semester Genap">UTS Semester Genap</option>
                            <option value="UAS Semester Genap">UAS Semester Genap</option>
                            <option value="PPDB">PPDB</option>
                            <option value="Daftar Ulang">Daftar Ulang</option>
                            <option value="LKS">LKS</option>
                            <option value="Seragam">Seragam</option>
                            <option value="Lainnya">++ Ketik Rincian Tersendiri ++</option>
                        </select>
                    </div>
                    <div id="container_custom_edit" style="display:none;">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nama Rincian (Baru) <span class="text-red-400">*</span></label>
                        <input type="text" name="nama_biaya_custom" id="input_custom_edit"
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nominal (Rp) <span class="text-red-400">*</span></label>
                        <input type="number" name="nominal" id="edit_nominal" required
                            class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none text-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('modalEdit')" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 font-medium transition text-xs">Batal</button>
                    <button type="submit" name="edit" class="px-4 py-2 bg-[#10B981] hover:bg-[#059669] text-white rounded-lg font-semibold transition text-xs">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT -->
    <script>
        function toggleCustom(selectId, containerId) {
            let selectElement = document.getElementById(selectId);
            let container = document.getElementById(containerId);
            let inputField = container.querySelector('input');
            if (selectElement.value === 'Lainnya') {
                container.style.display = 'block';
                inputField.setAttribute('required', 'required');
            } else {
                container.style.display = 'none';
                inputField.removeAttribute('required');
                inputField.value = '';
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

            if (modalId === 'modalTambah') toggleCustom('tambah_jenis', 'container_custom_tambah');
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

        function openEditModal(id, nama, nominal, kelas) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nominal').value = nominal;
            document.getElementById('edit_kelas').value = kelas;

            let sel = document.getElementById('edit_jenis');
            let found = false;
            for (let i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === nama) {
                    found = true;
                    sel.selectedIndex = i;
                    break;
                }
            }
            if (!found) {
                sel.value = 'Lainnya';
                document.getElementById('input_custom_edit').value = nama;
            }
            toggleCustom('edit_jenis', 'container_custom_edit');

            openModal('modalEdit');
        }
    </script>
</body>

</html>
