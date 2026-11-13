<?php
require_once "config/database.php";
require_once "includes/cart_sync.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simpan keranjang milik akun yang sedang keluar ke database akun tersebut.
if (!empty($_SESSION['user']['id_user'])) {
    $idUser = (int) $_SESSION['user']['id_user'];
    $keranjangAkun = $_SESSION['keranjang'] ?? [];
    if (is_array($keranjangAkun)) {
        simpanKeranjangAkun($conn, $idUser, $keranjangAkun);
    }
}

// Penting: bersihkan keranjang dari session browser.
// Dengan begitu, akun berikutnya yang login tidak mewarisi keranjang akun sebelumnya.
unset(
    $_SESSION['user'],
    $_SESSION['keranjang'],
    $_SESSION['keranjang_beli_sekarang'],
    $_SESSION['mode_beli_sekarang']
);

header("Location: index.php");
exit;
