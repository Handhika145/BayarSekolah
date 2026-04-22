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


$periode = $_GET['periode'] ?? 'semua';
$where_periode = "1=1";
$teks_periode = "Semua Waktu";
$teks_tgl_cetak = date('d F Y H:i:s');

if ($periode == '1_bulan') {
    $where_periode = "p.tgl_bayar >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
    $teks_periode = "1 Bulan Terakhir";
} else if ($periode == '3_bulan') {
    $where_periode = "p.tgl_bayar >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
    $teks_periode = "3 Bulan Terakhir";
} else if ($periode == '6_bulan') {
    $where_periode = "p.tgl_bayar >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
    $teks_periode = "6 Bulan Terakhir";
} else if ($periode == '1_tahun') {
    $where_periode = "p.tgl_bayar >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    $teks_periode = "1 Tahun Terakhir";
}

$q_data = mysqli_query($koneksi, "
    SELECT p.id_pembayaran, p.tgl_bayar, s.nama_siswa, s.kelas, t.jenis_tagihan, t.bulan, t.tahun, t.nominal, u.nama_lengkap AS nama_petugas
    FROM pembayaran p
    JOIN tagihan t ON p.id_tagihan = t.id_tagihan
    JOIN siswa s ON t.id_siswa = s.id_siswa
    JOIN users u ON p.id_admin = u.id_user
    WHERE s.id_sekolah = '$id_sekolah' AND $where_periode
    ORDER BY p.tgl_bayar DESC
");

// Simpan data di array untuk digunakan di tabel UI dan tabel PDF bayangan
$data_laporan = [];
$total_penerimaan = 0;
if (mysqli_num_rows($q_data) > 0) {
    while ($row = mysqli_fetch_assoc($q_data)) {
        $data_laporan[] = $row;
        $total_penerimaan += $row['nominal'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Sistem Pembayaran Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        #pdfTemplate { display: none; }
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
        <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto custom-scrollbar">
            <a href="dashboard.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-border-all w-7 text-[13px]"></i> Dashboard</a>
            <a href="data_siswa.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-regular fa-user w-7 text-[13px]"></i> Data Siswa & Wali</a>
            <a href="data_tagihan.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-regular fa-credit-card w-7 text-[13px]"></i> Tagihan</a>
            <a href="pembayaran.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-money-bill-transfer w-7 text-[13px]"></i> Pembayaran</a>
            <a href="m_rincian.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-list-check w-7 text-[13px]"></i> Master Rincian Biaya</a>
            <a href="laporan.php" class="flex items-center px-3 py-2.5 bg-[#10B981] text-white shadow-sm rounded-lg text-[13px] font-medium "><i class="fa-solid fa-file-invoice-dollar w-7 text-[13px]"></i> Laporan Keuangan</a>
            <a href="banding.php" class="flex items-center justify-between px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all ">
                <div class="flex items-center"><i class="fa-solid fa-scale-balanced w-7 text-[13px]"></i> Data Banding</div>
                <?php if (isset($notif_banding) && $notif_banding > 0): ?><span class="bg-red-500/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_banding; ?></span><?php endif; ?>
            </a>
            <a href="terms.php" class="flex items-center px-3 py-2.5 hover:bg-white/5 hover:text-gray-200 rounded-lg text-[13px] font-medium transition-all "><i class="fa-solid fa-file-contract w-7 text-[13px]"></i> Terms of Service</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 ml-[260px] flex flex-col min-w-0">
        <header class="h-14 bg-white flex items-center justify-between px-8 border-b border-gray-100 sticky top-0 z-10">
            <h1 class="text-sm font-semibold text-gray-800">Laporan Keuangan</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center bg-[#f0fdf4] text-[#166534] px-4 py-1.5 rounded-full text-xs font-semibold mr-2 border border-green-100"> Admin: <?= $nama_admin; ?> </div>
                <a href="../auth/logout.php" class="flex items-center text-xs text-gray-400 hover:text-gray-600 transition-colors"><i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout</a>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-y-auto">

            <div class="bg-white rounded-xl border border-gray-100 p-6 mb-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 mb-1">Filter & Ekspor Data</h3>
                        <p class="text-xs text-gray-400">Pilih rentang waktu untuk mengisolasi data transaksi.</p>
                    </div>
                    <form action="" method="GET" class="flex gap-2 w-full md:w-auto">
                        <select name="periode" class="px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-300 outline-none w-full md:w-44 bg-white text-xs font-medium">
                            <option value="semua" <?= $periode == 'semua' ? 'selected' : '' ?>>Semua Waktu</option>
                            <option value="1_bulan" <?= $periode == '1_bulan' ? 'selected' : '' ?>>1 Bulan Terakhir</option>
                            <option value="3_bulan" <?= $periode == '3_bulan' ? 'selected' : '' ?>>3 Bulan Terakhir</option>
                            <option value="6_bulan" <?= $periode == '6_bulan' ? 'selected' : '' ?>>6 Bulan Terakhir</option>
                            <option value="1_tahun" <?= $periode == '1_tahun' ? 'selected' : '' ?>>1 Tahun Terakhir</option>
                        </select>
                        <button type="submit" class="bg-[#10B981] hover:bg-[#059669] text-white px-4 py-2 rounded-lg text-xs font-semibold transition whitespace-nowrap">
                            <i class="fa-solid fa-filter mr-1"></i> Filter
                        </button>
                    </form>
                </div>

                <hr class="my-5 border-gray-50">

                <div class="flex flex-wrap gap-2">
                    <a href="export_excel.php?format=csv&periode=<?= $periode ?>" class="bg-[#10B981] hover:bg-[#059669] text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center shrink-0">
                        <i class="fa-solid fa-file-csv mr-1.5"></i> Download CSV
                    </a>
                    <a href="export_excel.php?format=xls&periode=<?= $periode ?>" class="bg-[#10B981] hover:bg-[#059669] text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center shrink-0">
                        <i class="fa-solid fa-file-excel mr-1.5"></i> Download .xls
                    </a>
                    <button id="btnDownloadPDF" class="bg-[#10B981] hover:bg-[#059669] text-white px-4 py-2 rounded-lg text-xs font-semibold transition flex items-center shrink-0">
                        <i class="fa-solid fa-file-pdf mr-1.5"></i> Download PDF
                    </button>
                </div>
            </div>

            <!-- TABEL PREVIEW -->
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-50 flex justify-between items-center">
                    <h3 class="text-xs font-semibold text-gray-800">Preview Data (<?= $teks_periode ?>)</h3>
                    <div class="bg-gray-50 text-gray-800 font-bold px-3.5 py-1.5 rounded-lg border border-gray-100 text-xs">
                        Total: Rp <?= number_format($total_penerimaan, 0, ',', '.'); ?>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar max-h-[600px]">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead class="sticky top-0 bg-white z-10">
                            <tr class="text-[11px] text-gray-400 uppercase tracking-wider font-medium border-b border-gray-100">
                                <th class="py-3 px-5 w-12">No</th>
                                <th class="py-3 px-5">Tgl Bayar</th>
                                <th class="py-3 px-5">Siswa & Kelas</th>
                                <th class="py-3 px-5">Rincian Tagihan</th>
                                <th class="py-3 px-5 text-right">Nominal</th>
                                <th class="py-3 px-5 text-center">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-600 divide-y divide-gray-50">
                            <?php
                            if (count($data_laporan) > 0):
                                $no = 1;
                                foreach ($data_laporan as $row):
                                    ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-5"><?= $no++; ?></td>
                                        <td class="py-3 px-5 font-medium"><?= date('d/m/Y', strtotime($row['tgl_bayar'])); ?></td>
                                        <td class="py-3 px-5">
                                            <p class="font-semibold text-gray-800"><?= $row['nama_siswa']; ?></p>
                                            <p class="text-[11px] text-gray-400"><?= $row['kelas']; ?></p>
                                        </td>
                                        <td class="py-3 px-5"><span class="bg-gray-50 text-gray-600 font-medium px-2 py-0.5 rounded text-xs mr-1"><?= $row['jenis_tagihan']; ?></span> <?= $row['bulan']; ?> <?= $row['tahun']; ?></td>
                                        <td class="py-3 px-5 text-right font-semibold text-gray-800">Rp <?= number_format($row['nominal'], 0, ',', '.'); ?></td>
                                        <td class="py-3 px-5 text-xs text-center text-gray-400"><?= $row['nama_petugas']; ?></td>
                                    </tr>
                                <?php
                                endforeach;
                            else:
                                ?>
                                <tr><td colspan="6" class="text-center py-10 text-gray-400">Tidak ada transaksi pada periode ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- BAYANGAN PDF (HIDDEN) -->
    <div id="pdfTemplate">
        <div style="padding: 40px; font-family: Helvetica, Arial, sans-serif; color: #333;">
            <div style="text-align: center; border-bottom: 2px solid #222; padding-bottom: 20px; margin-bottom: 30px;">
                <h1 style="margin: 0 0 10px 0; font-size: 24px; color: #1e293b; text-transform: uppercase;">LAPORAN KEUANGAN TRANSAKSI</h1>
                <h2 style="margin: 0; font-size: 18px; font-weight: normal;"><?= $_SESSION['nama_sekolah']; ?></h2>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">Periode: <strong><?= $teks_periode ?></strong></p>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">Dicetak Tanggal: <?= $teks_tgl_cetak ?> oleh <?= $nama_admin ?></p>
            </div>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 12px;">
                <thead>
                    <tr style="background-color: #f1f5f9; text-align: left;">
                        <th style="padding: 10px; border: 1px solid #cbd5e1; width: 5%;">No</th>
                        <th style="padding: 10px; border: 1px solid #cbd5e1; width: 12%;">Tgl Bayar</th>
                        <th style="padding: 10px; border: 1px solid #cbd5e1; width: 25%;">Nama Siswa</th>
                        <th style="padding: 10px; border: 1px solid #cbd5e1; width: 12%;">Kelas</th>
                        <th style="padding: 10px; border: 1px solid #cbd5e1; width: 25%;">Rincian Tagihan</th>
                        <th style="padding: 10px; border: 1px solid #cbd5e1; width: 21%; text-align: right;">Nominal (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($data_laporan) > 0):
                        $no = 1;
                        foreach ($data_laporan as $row):
                            ?>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #e2e8f0; text-align: center;"><?= $no++; ?></td>
                                <td style="padding: 8px; border: 1px solid #e2e8f0;"><?= date('d/m/Y', strtotime($row['tgl_bayar'])); ?></td>
                                <td style="padding: 8px; border: 1px solid #e2e8f0; font-weight: bold;"><?= $row['nama_siswa']; ?></td>
                                <td style="padding: 8px; border: 1px solid #e2e8f0;"><?= $row['kelas']; ?></td>
                                <td style="padding: 8px; border: 1px solid #e2e8f0;"><?= $row['jenis_tagihan'] . ' - ' . $row['bulan'] . ' ' . $row['tahun']; ?></td>
                                <td style="padding: 8px; border: 1px solid #e2e8f0; text-align: right;"><?= number_format($row['nominal'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php
                        endforeach;
                    else:
                        ?>
                        <tr><td colspan="6" style="padding: 20px; border: 1px solid #e2e8f0; text-align: center;">Tidak ada data</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="padding: 10px; border: 1px solid #cbd5e1; text-align: right; font-weight: bold; font-size: 14px;">TOTAL PENERIMAAN</td>
                        <td style="padding: 10px; border: 1px solid #cbd5e1; text-align: right; font-weight: bold; font-size: 14px; background-color: #f1f5f9;">Rp <?= number_format($total_penerimaan, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
            <div style="text-align: right; margin-top: 50px;">
                <p style="margin: 0 0 60px 0;">Mengetahui,</p>
                <p style="font-weight: bold; margin: 0; text-decoration: underline;"><?= $nama_admin ?></p>
                <p style="margin: 5px 0 0 0; color: #666;">Admin Sekolah</p>
            </div>
        </div>
    </div>

    <!-- SCRIPT HTML2PDF -->
    <script>
        document.getElementById('btnDownloadPDF').addEventListener('click', function () {
            const element = document.getElementById('pdfTemplate');
            element.style.display = 'block';
            const opt = {
                margin: 0,
                filename: 'Laporan_Keuangan_SPP_<?= $periode ?>_<?= date('Ymd_His') ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            const btn = document.getElementById('btnDownloadPDF');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Generating PDF...';
            btn.disabled = true;
            html2pdf().set(opt).from(element).save().then(() => {
                element.style.display = 'none';
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    </script>
</body>

</html>
