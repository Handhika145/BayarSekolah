<?php
$host = "localhost";
$user = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password (kosong)
$database = "db_spp_saas";

// Membuat koneksi
$koneksi = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Fungsi Helper Jatuh Tempo (Berlaku Tanggal 10 di bulan tagihan)
function isJatuhTempo($bulan_tagihan, $tahun_tagihan)
{
    if (!$bulan_tagihan || !$tahun_tagihan || $bulan_tagihan == '-')
        return true;
    $bulan_index = [
        'Januari' => 1,
        'Februari' => 2,
        'Maret' => 3,
        'April' => 4,
        'Mei' => 5,
        'Juni' => 6,
        'Juli' => 7,
        'Agustus' => 8,
        'September' => 9,
        'Oktober' => 10,
        'November' => 11,
        'Desember' => 12
    ];
    $b_idx = isset($bulan_index[$bulan_tagihan]) ? $bulan_index[$bulan_tagihan] : 1;

    // Set 00:00:00 pada tanggal 10 di bulan & tahun tagihan
    $tgl_jatuh_tempo = mktime(0, 0, 0, $b_idx, 10, $tahun_tagihan);
    $hari_ini = time();

    return $hari_ini >= $tgl_jatuh_tempo;
}
// Automatisasi Generate Tagihan Bulanan (Real-time trigger)
// Dijalankan jika tanggal sekarang lebih dari tanggal 10.
$hari_ini = (int) date('j');
if ($hari_ini > 10) {
    $bulan_tahun_kunci = date('m_Y');

    // Pastikan session aktif dan cek apakah sudah digenerate di sesi ini
    if (session_status() !== PHP_SESSION_NONE && !isset($_SESSION['auto_spp_' . $bulan_tahun_kunci])) {
        $_SESSION['auto_spp_' . $bulan_tahun_kunci] = true; // Tandai agar tidak diloop terus

        $list_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $bulan_ini_angka = (int) date('n');
        $bulan_sekarang = $list_bulan[$bulan_ini_angka];
        $tahun_sekarang = date('Y');

        // Semester: Juli - Desember = Ganjil, Januari - Juni = Genap
        $semester = ($bulan_ini_angka >= 7) ? 'Ganjil' : 'Genap';
        $keyword_cari = "SPP Semester " . $semester;

        // Ambil data sekolah
        $q_sekolah = mysqli_query($koneksi, "SELECT id_sekolah FROM sekolah");
        if ($q_sekolah) {
            while ($sek = mysqli_fetch_assoc($q_sekolah)) {
                $id_sek = $sek['id_sekolah'];

                // Cari tarif SPP untuk sekolah ini
                $q_master = mysqli_query($koneksi, "SELECT kelas, nominal, nama_biaya FROM master_biaya WHERE id_sekolah='$id_sek' AND (nama_biaya LIKE '%SPP%')");
                $tarif_kelas = [];
                $tarif_semua = 0;

                while ($m = mysqli_fetch_assoc($q_master)) {
                    if ($m['nama_biaya'] == $keyword_cari || $m['nama_biaya'] == 'SPP' || strpos(strtolower($m['nama_biaya']), 'spp') !== false) {
                        if ($m['kelas'] == 'Semua') {
                            $tarif_semua = $m['nominal'];
                        } else {
                            $tarif_kelas[$m['kelas']] = $m['nominal'];
                        }
                    }
                }

                if ($tarif_semua > 0 || count($tarif_kelas) > 0) {
                    // Cek siswa dan generate tagihan jika belum ada
                    $q_siswa = mysqli_query($koneksi, "SELECT id_siswa, kelas FROM siswa WHERE id_sekolah='$id_sek'");
                    if ($q_siswa) {
                        while ($s = mysqli_fetch_assoc($q_siswa)) {
                            $id_sis = $s['id_siswa'];
                            $kelas = $s['kelas'];
                            $nominal = isset($tarif_kelas[$kelas]) ? $tarif_kelas[$kelas] : $tarif_semua;

                            if ($nominal > 0) {
                                // Pengecekan apakah tagihan SPP bulanan sudah dibuat
                                $q_cek = mysqli_query($koneksi, "SELECT id_tagihan FROM tagihan WHERE id_siswa='$id_sis' AND jenis_tagihan='SPP' AND bulan='$bulan_sekarang' AND tahun='$tahun_sekarang'");
                                if (mysqli_num_rows($q_cek) == 0) {
                                    mysqli_query($koneksi, "INSERT INTO tagihan (id_siswa, jenis_tagihan, bulan, tahun, nominal, status) VALUES ('$id_sis', 'SPP', '$bulan_sekarang', '$tahun_sekarang', '$nominal', 'Belum Lunas')");
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>