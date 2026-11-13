<?php
require_once "../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$id_pesanan = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_pesanan <= 0) {
    header("Location: pesanan.php");
    exit;
}

// Ambil informasi pesanan
$stmt = $conn->prepare("
    SELECT
        o.*,
        u.nama AS nama_pembeli,
        u.email,
        p.metode,
        p.status AS status_pembayaran,
        p.jumlah_bayar
    FROM orders o
    LEFT JOIN users u ON o.id_user = u.id_user
    LEFT JOIN payments p ON o.id_pesanan = p.id_pesanan
    WHERE o.id_pesanan = ?
    LIMIT 1
");

$stmt->bind_param("i", $id_pesanan);
$stmt->execute();

$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    header("Location: pesanan.php");
    exit;
}

// Ambil produk dalam pesanan
$stmt = $conn->prepare("
    SELECT nama_produk_snapshot, jumlah, harga, subtotal
    FROM order_items
    WHERE id_pesanan = ?
");

$stmt->bind_param("i", $id_pesanan);
$stmt->execute();

$result = $stmt->get_result();
$detail_produk = [];

while ($row = $result->fetch_assoc()) {
    $detail_produk[] = $row;
}

$stmt->close();

function warnaStatusDetailAdmin($status)
{
    switch ($status) {
        case 'menunggu':
            return 'warning';
        case 'diproses':
            return 'primary';
        case 'dikirim':
            return 'info';
        case 'selesai':
            return 'success';
        case 'dibatalkan':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Pesanan - PasarKita Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-success shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-shop"></i> PasarKita Admin
        </a>

        <a href="../logout.php" class="btn btn-outline-light btn-sm">
            Logout
        </a>
    </div>
</nav>

<div class="container py-5">

    <a href="pesanan.php" class="btn btn-outline-success mb-4">
        <i class="bi bi-arrow-left"></i> Kembali ke Kelola Pesanan
    </a>

    <h2 class="fw-bold mb-4">Detail Pesanan</h2>

    <!-- Informasi Pesanan -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">Informasi Pesanan</h5>

            <div class="row g-3">

                <div class="col-md-6">
                    <small class="text-secondary">Kode Pesanan</small>
                    <h5><?= htmlspecialchars($pesanan['kode_pesanan']) ?></h5>
                </div>

                <div class="col-md-6">
                    <small class="text-secondary">Tanggal Pesanan</small>
                    <h5>
                        <?= date('d-m-Y H:i', strtotime($pesanan['tanggal_pesanan'])) ?>
                    </h5>
                </div>

                <div class="col-md-6">
                    <small class="text-secondary">Status Pesanan</small>
                    <div>
                        <span class="badge bg-<?= warnaStatusDetailAdmin($pesanan['status']) ?> text-dark">
                            <?= htmlspecialchars(ucfirst($pesanan['status'])) ?>
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <small class="text-secondary">Metode Pembayaran</small>
                    <h5>
                        <?php
                        if ($pesanan['metode'] === 'transfer_bank') {
                            echo 'Transfer Bank';
                        } elseif ($pesanan['metode'] === 'cod') {
                            echo 'COD';
                        } else {
                            echo '-';
                        }
                        ?>
                    </h5>
                </div>

                <div class="col-md-6">
                    <small class="text-secondary">Status Pembayaran</small>
                    <h5>
                        <?= htmlspecialchars(
                            ucwords(str_replace('_', ' ', $pesanan['status_pembayaran'] ?? '-'))
                        ) ?>
                    </h5>
                </div>

            </div>
        </div>
    </div>

    <!-- Informasi Pembeli -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">Informasi Pembeli</h5>

            <p>
                <strong>Nama Akun:</strong><br>
                <?= htmlspecialchars($pesanan['nama_pembeli'] ?? '-') ?>
            </p>

            <p>
                <strong>Email:</strong><br>
                <?= htmlspecialchars($pesanan['email'] ?? '-') ?>
            </p>

            <p>
                <strong>Nama Penerima:</strong><br>
                <?= htmlspecialchars($pesanan['nama_penerima']) ?>
            </p>

            <p>
                <strong>Nomor HP Penerima:</strong><br>
                <?= htmlspecialchars($pesanan['no_hp_penerima']) ?>
            </p>

            <p>
                <strong>Alamat Pengiriman:</strong><br>
                <?= nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])) ?>
            </p>

            <p>
                <strong>Titik Lokasi Pengiriman:</strong><br>
                <?php if ($pesanan['latitude'] !== null && $pesanan['longitude'] !== null): ?>
                    <?= htmlspecialchars((string)$pesanan['latitude']) ?>, <?= htmlspecialchars((string)$pesanan['longitude']) ?><br>
                    <a class="btn btn-sm btn-outline-success mt-2" target="_blank" rel="noopener"
                       href="https://www.openstreetmap.org/?mlat=<?= urlencode((string)$pesanan['latitude']) ?>&mlon=<?= urlencode((string)$pesanan['longitude']) ?>#map=17/<?= urlencode((string)$pesanan['latitude']) ?>/<?= urlencode((string)$pesanan['longitude']) ?>">
                        <i class="bi bi-geo-alt"></i> Buka Titik di Peta
                    </a>
                <?php else: ?>
                    Belum ada titik lokasi yang dipilih.
                <?php endif; ?>
            </p>

            <p class="mb-0">
                <strong>Catatan:</strong><br>
                <?= !empty($pesanan['catatan'])
                    ? nl2br(htmlspecialchars($pesanan['catatan']))
                    : '-' ?>
            </p>

        </div>
    </div>

    <!-- Daftar Produk -->
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Produk yang Dipesan</h5>
        </div>

        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nama Produk</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($detail_produk as $item): ?>

                        <tr>
                            <td class="ps-4">
                                <?= htmlspecialchars($item['nama_produk_snapshot']) ?>
                            </td>

                            <td>
                                Rp<?= number_format((float)$item['harga'], 0, ',', '.') ?>
                            </td>

                            <td><?= (int)$item['jumlah'] ?></td>

                            <td class="fw-semibold">
                                Rp<?= number_format((float)$item['subtotal'], 0, ',', '.') ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total Pesanan</th>
                        <th class="text-success fs-5">
                            Rp<?= number_format((float)$pesanan['total_harga'], 0, ',', '.') ?>
                        </th>
                    </tr>
                </tfoot>

            </table>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>