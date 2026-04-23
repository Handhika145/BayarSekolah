<?php
require 'config/koneksi.php';
$res = mysqli_query($koneksi, "SELECT DISTINCT kelas FROM master_biaya");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['kelas'] . "\n";
}
?>
