<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'walimurid') {
    header('Location: ../login.php');
    exit();
}

$tipe = $_GET['tipe'] ?? '';
$id = $_GET['id'] ?? '';
$id_walimurid = $_SESSION['id_user'];

if ($tipe === 'tagihan' && !empty($id)) {
    mysqli_query($koneksi, "UPDATE tagihan SET is_read_walimurid = 1 WHERE id_tagihan = '$id'");
    header("Location: tagihan.php");
    exit();
} elseif ($tipe === 'banding' && !empty($id)) {
    mysqli_query($koneksi, "UPDATE banding SET is_read_walimurid = 1 WHERE id_banding = '$id' AND id_walimurid = '$id_walimurid'");
    header("Location: form_banding.php");
    exit();
}

header("Location: dashboard.php");
exit();
?>
