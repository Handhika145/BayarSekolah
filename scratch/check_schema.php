<?php
require 'config/koneksi.php';

function dump_table($koneksi, $table) {
    echo "Schema for table: $table\n";
    $res = mysqli_query($koneksi, "DESCRIBE $table");
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
    echo "\n";
}

dump_table($koneksi, 'siswa');
dump_table($koneksi, 'master_biaya');
?>
