<?php
require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['user']['id_user'];
$id_pesanan = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_pesanan <= 0) {
    header("Location: pesanan_saya.php");
    exit;
}

// Ambil data pesanan milik pengguna yang sedang login
$stmt = $conn->prepare("
    SELECT o.*, p.metode, p.status AS status_pembayaran
    FROM orders o
    LEFT JOIN payments p ON o.id_pesanan = p.id_pesanan
    WHERE o.id_pesanan = ? AND o.id_user = ?
    LIMIT 1
");
$stmt->bind_param("ii", $id_pesanan, $id_user);
$stmt->execute();

$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    header("Location: pesanan_saya.php");
    exit;
}

// Ambil daftar produk dalam pesanan
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

function warnaStatusDetail($status)
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

$pageTitle = "Detail Pesanan";
require "includes/header.php";
?>

<div class="container py-5">

    <a href="pesanan_saya.php" class="btn btn-outline-secondary mb-4">
        <i class="bi bi-arrow-left"></i> Kembali ke Pesanan Saya
    </a>

    <h2 class="section-title mb-4">Detail Pesanan</h2>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
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
                        <span class="badge bg-<?= warnaStatusDetail($pesanan['status']) ?> text-dark">
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
                            echo 'COD (Bayar di Tempat)';
                        } else {
                            echo '-';
                        }
                        ?>
                    </h5>
                </div>

                <div class="col-md-6">
                    <small class="text-secondary">Status Pembayaran</small>
                    <h5>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $pesanan['status_pembayaran'] ?? '-'))) ?>
                    </h5>
                </div>

            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white p-3">
            <h5 class="mb-0">Informasi Pengiriman</h5>
        </div>

        <div class="card-body p-4">
            <p>
                <strong>Nama Penerima:</strong><br>
                <?= htmlspecialchars($pesanan['nama_penerima']) ?>
            </p>

            <p>
                <strong>Nomor HP:</strong><br>
                <?= htmlspecialchars($pesanan['no_hp_penerima']) ?>
            </p>

            <p>
                <strong>Alamat Pengiriman:</strong><br>
                <?= nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])) ?>
            </p>

            <p class="mb-0">
                <strong>Catatan:</strong><br>
                <?= !empty($pesanan['catatan'])
                    ? nl2br(htmlspecialchars($pesanan['catatan']))
                    : '-' ?>
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white p-3">
            <h5 class="mb-0">Produk yang Dipesan</h5>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($detail_produk as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nama_produk_snapshot']) ?></td>
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

<?php require "includes/footer.php"; ?>