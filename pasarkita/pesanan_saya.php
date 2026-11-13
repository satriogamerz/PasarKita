<?php
require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wajib login
if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int)$_SESSION['user']['id_user'];

// Ambil semua pesanan milik pembeli yang sedang login
$stmt = $conn->prepare(
    "SELECT
        o.id_pesanan,
        o.kode_pesanan,
        o.tanggal_pesanan,
        o.total_harga,
        o.status,
        p.metode,
        p.status AS status_pembayaran
     FROM orders o
     LEFT JOIN payments p
        ON o.id_pesanan = p.id_pesanan
     WHERE o.id_user = ?
     ORDER BY o.tanggal_pesanan DESC"
);

$stmt->bind_param("i", $id_user);
$stmt->execute();

$result = $stmt->get_result();

$pesanan = [];

while ($row = $result->fetch_assoc()) {
    $pesanan[] = $row;
}

$stmt->close();

// Fungsi warna status
function warnaStatus($status) {
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

$pageTitle = "Pesanan Saya";
require "includes/header.php";
?>

<div class="container py-5">

    <h1 class="section-title mb-2">Pesanan Saya</h1>

    <p class="text-secondary mb-4">
        Berikut adalah daftar pesanan yang pernah kamu buat di PasarKita.
    </p>

    <?php if (!empty($pesanan)): ?>

        <div class="card border-0 shadow-sm rounded-4">

            <div class="table-responsive">

                <table class="table align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Kode Pesanan</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Detail</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($pesanan as $item): ?>

                        <tr>

                            <td class="fw-semibold">
                                <?= htmlspecialchars($item['kode_pesanan']) ?>
                            </td>

                            <td>
                                <?= date(
                                    'd-m-Y H:i',
                                    strtotime($item['tanggal_pesanan'])
                                ) ?>
                            </td>

                            <td class="fw-semibold">
                                Rp<?= number_format(
                                    (float)$item['total_harga'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <?php
                                if ($item['metode'] === 'transfer_bank') {
                                    echo 'Transfer Bank';
                                } elseif ($item['metode'] === 'cod') {
                                    echo 'COD';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>

                            <td>
                                <span class="badge bg-<?= warnaStatus($item['status']) ?> text-dark">
                                    <?= htmlspecialchars(ucfirst($item['status'])) ?>
                                </span>
                            </td>

                            <td>
                                <a
                                    href="detail_pesanan.php?id=<?= (int)$item['id_pesanan'] ?>"
                                    class="btn btn-outline-success btn-sm"
                                >
                                    <i class="bi bi-eye"></i>
                                    Detail
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php else: ?>

        <div class="empty-state text-center shadow-sm">

            <i class="bi bi-receipt display-3 text-success"></i>

            <h4 class="mt-3">Belum Ada Pesanan</h4>

            <p class="text-secondary">
                Kamu belum pernah melakukan pemesanan di PasarKita.
            </p>

            <a href="index.php" class="btn btn-pasar">
                Mulai Belanja
            </a>

        </div>

    <?php endif; ?>

</div>

<?php require "includes/footer.php"; ?>