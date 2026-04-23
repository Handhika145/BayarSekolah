<?php
require 'config/koneksi.php';
$res = mysqli_query($koneksi, "SELECT DISTINCT kelas FROM siswa");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['kelas'] . "\n";
}
?>
