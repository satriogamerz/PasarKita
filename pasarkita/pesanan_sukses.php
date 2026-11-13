<?php
require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$kode = $_GET['kode'] ?? '';
$kodeTerakhir = $_SESSION['pesanan_terakhir'] ?? '';

if ($kode === '' || $kode !== $kodeTerakhir) {
    header("Location: index.php");
    exit;
}

$id_user = (int)$_SESSION['user']['id_user'];

$stmt = $conn->prepare(
    "SELECT kode_pesanan, tanggal_pesanan, nama_penerima,
            total_harga, status
     FROM orders
     WHERE kode_pesanan = ? AND id_user = ?
     LIMIT 1"
);

$stmt->bind_param("si", $kode, $id_user);
$stmt->execute();

$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    header("Location: index.php");
    exit;
}

$pageTitle = "Pesanan Berhasil";
require "includes/header.php";
?>

<div class="container py-5">

    <div class="card border-0 shadow-sm rounded-4 mx-auto text-center"
         style="max-width:650px">

        <div class="card-body p-5">

            <i class="bi bi-check-circle-fill text-success"
               style="font-size:75px"></i>

            <h2 class="mt-3">Pesanan Berhasil!</h2>

            <p class="text-secondary">
                Terima kasih sudah berbelanja di PasarKita.
                Pesanan kamu berhasil disimpan.
            </p>

            <hr>

            <div class="text-start">

                <p>
                    <strong>Kode Pesanan:</strong><br>
                    <?= htmlspecialchars($pesanan['kode_pesanan']) ?>
                </p>

                <p>
                    <strong>Nama Penerima:</strong><br>
                    <?= htmlspecialchars($pesanan['nama_penerima']) ?>
                </p>

                <p>
                    <strong>Tanggal Pesanan:</strong><br>
                    <?= htmlspecialchars($pesanan['tanggal_pesanan']) ?>
                </p>

                <p>
                    <strong>Total Pembayaran:</strong><br>
                    <span class="price fs-4">
                        Rp<?= number_format(
                            (float)$pesanan['total_harga'],
                            0,
                            ',',
                            '.'
                        ) ?>
                    </span>
                </p>

                <p>
                    <strong>Status Pesanan:</strong><br>
                    <span class="badge bg-warning text-dark">
                        <?= htmlspecialchars($pesanan['status']) ?>
                    </span>
                </p>

            </div>

            <hr>

            <a href="index.php" class="btn btn-pasar w-100">
                Kembali Belanja
            </a>

        </div>

    </div>

</div>

<?php require "includes/footer.php"; ?>