<?php
session_start();
require '../config/koneksi.php';

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses tertolak.");
}

$id_sekolah = $_SESSION['id_sekolah'];
$format = $_GET['format'] ?? 'csv';
$periode = $_GET['periode'] ?? 'semua';

// Tentukan filter waktu
$where_periode = "1=1";
$teks_periode = "Semua Waktu";
if($periode == '1_bulan') {
    $where_periode = "p.tgl_bayar >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
    $teks_periode = "1 Bulan Terakhir";
} else if($periode == '3_bulan') {
    $where_periode = "p.tgl_bayar >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
    $teks_periode = "3 Bulan Terakhir";
} else if($periode == '6_bulan') {
    $where_periode = "p.tgl_bayar >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
    $teks_periode = "6 Bulan Terakhir";
} else if($periode == '1_tahun') {
    $where_periode = "p.tgl_bayar >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    $teks_periode = "1 Tahun Terakhir";
}

$q_data = mysqli_query($koneksi, "
    SELECT p.tgl_bayar, s.nama_siswa, s.kelas, s.sub_kelas, t.jenis_tagihan, t.bulan, t.tahun, t.nominal, u.nama_lengkap AS nama_petugas
    FROM pembayaran p
    JOIN tagihan t ON p.id_tagihan = t.id_tagihan
    JOIN siswa s ON t.id_siswa = s.id_siswa
    JOIN users u ON p.id_admin = u.id_user
    WHERE s.id_sekolah = '$id_sekolah' AND $where_periode
    ORDER BY p.tgl_bayar DESC
");

$filename = "Laporan_Keuangan_" . date('Ymd_His');

if ($format == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Laporan Keuangan - ' . $teks_periode]);
    fputcsv($output, ['Tanggal Bayar', 'Nama Siswa', 'Kelas', 'Jenis Tagihan', 'Periode', 'Nominal (Rp)', 'Petugas']);

    $total = 0;
    while ($row = mysqli_fetch_assoc($q_data)) {
        fputcsv($output, [
            $row['tgl_bayar'],
            $row['nama_siswa'],
            $row['kelas'] . ' ' . $row['sub_kelas'],
            $row['jenis_tagihan'],
            $row['bulan'] . ' ' . $row['tahun'],
            $row['nominal'],
            $row['nama_petugas']
        ]);
        $total += $row['nominal'];
    }
    fputcsv($output, ['', '', '', '', 'TOTAL PENERIMAAN', $total, '']);
    fclose($output);
    exit;

} else if ($format == 'xls') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename.xls\"");
    header("Cache-Control: max-age=0");
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid black; padding: 5px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
        </style>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
    <body>
        <h2>Laporan Keuangan Sekolah (<?= $teks_periode ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Bayar</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Jenis Tagihan</th>
                    <th>Periode Tagihan</th>
                    <th>Nominal (Rp)</th>
                    <th>Petugas Penerima</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1; 
                $total = 0;
                while($row = mysqli_fetch_assoc($q_data)): 
                    $total += $row['nominal'];
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d-m-Y', strtotime($row['tgl_bayar'])) ?></td>
                    <td><?= $row['nama_siswa'] ?></td>
                    <td><?= $row['kelas'] ?> <?= $row['sub_kelas'] ?></td>
                    <td><?= $row['jenis_tagihan'] ?></td>
                    <td><?= $row['bulan'] ?> <?= $row['tahun'] ?></td>
                    <td><?= $row['nominal'] ?></td>
                    <td><?= $row['nama_petugas'] ?></td>
                </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL PENERIMAAN:</td>
                    <td colspan="2" style="font-weight: bold;"><?= $total ?></td>
                </tr>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}
?>
