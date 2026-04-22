<?php
require 'config/koneksi.php';

$sql = "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if(mysqli_query($koneksi, $sql)){
    echo "Table settings created.\n";
} else {
    echo "Error creating table: " . mysqli_error($koneksi) . "\n";
}

$sql_insert = "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('terms_of_service', '<p>Ini adalah Terms of Service default. Silakan ubah melalui akun Super Admin.</p>')";
if(mysqli_query($koneksi, $sql_insert)){
    echo "Default data inserted.\n";
} else {
    echo "Error inserting data: " . mysqli_error($koneksi) . "\n";
}
?>
