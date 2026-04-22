<?php
require 'config/koneksi.php';

// Add column foto_profil to siswa if it doesn't exist
$res = mysqli_query($koneksi, "SHOW COLUMNS FROM siswa LIKE 'foto_profil'");
if (mysqli_num_rows($res) == 0) {
    mysqli_query($koneksi, "ALTER TABLE siswa ADD COLUMN foto_profil VARCHAR(255) DEFAULT NULL");
    echo "Column added.";
} else {
    echo "Column exists.";
}
?>
