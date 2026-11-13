<?php
require_once "../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Periksa login
if (empty($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Periksa role admin
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Membuat token keamanan
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pesan = "";
$error = "";

// Proses perubahan status pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';
    $id_pesanan = (int) ($_POST['id_pesanan'] ?? 0);
    $status_baru = $_POST['status'] ?? '';

    $status_valid = [
        'menunggu',
        'diproses',
        'dikirim',
        'selesai',
        'dibatalkan'
    ];

    if (!hash_equals($_SESSION['csrf_token'], $token)) {

        $error = "Permintaan tidak valid.";

    } elseif ($id_pesanan <= 0 || !in_array($status_baru, $status_valid, true)) {

        $error = "Data perubahan status tidak valid.";

    } else {

        $stmt = $conn->prepare("
            UPDATE orders
            SET status = ?
            WHERE id_pesanan = ?
        ");

        $stmt->bind_param("si", $status_baru, $id_pesanan);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $pesan = "Status pesanan berhasil diperbarui.";
        } else {
            $error = "Gagal memperbarui status pesanan.";
        }

        $stmt->close();
    }
}

// Ambil semua pesanan
$query = $conn->query("
    SELECT
        o.id_pesanan,
        o.kode_pesanan,
        o.tanggal_pesanan,
        o.nama_penerima,
        o.total_harga,
        o.status,
        u.nama AS nama_pembeli,
        u.email,
        p.metode,
        p.status AS status_pembayaran
    FROM orders o
    LEFT JOIN users u ON o.id_user = u.id_user
    LEFT JOIN payments p ON o.id_pesanan = p.id_pesanan
    ORDER BY o.tanggal_pesanan DESC
");

if (!$query) {
    die("Gagal mengambil data pesanan: " . htmlspecialchars($conn->error));
}

function warnaStatusAdmin($status)
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

    <title>Kelola Pesanan - PasarKita Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-success shadow-sm">
    <div class="container">

        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-shop"></i> PasarKita Admin
        </a>

        <div class="d-flex align-items-center gap-3">

            <span class="text-white">
                Halo, <?= htmlspecialchars($_SESSION['user']['nama']) ?>
            </span>

            <a href="../logout.php" class="btn btn-outline-light btn-sm">
                Logout
            </a>

        </div>
    </div>
</nav>

<div class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">Kelola Pesanan</h2>
            <p class="text-secondary mb-0">
                Daftar seluruh pesanan yang masuk ke PasarKita.
            </p>
        </div>

        <a href="index.php" class="btn btn-outline-success mt-3 mt-md-0">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>

    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th class="p-3">Kode Pesanan</th>
                            <th>Pembeli</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Detail</th>
<th>Ubah Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($query->num_rows > 0): ?>

                        <?php while ($item = $query->fetch_assoc()): ?>

                            <tr>

                                <td class="p-3">
                                    <strong>
                                        <?= htmlspecialchars($item['kode_pesanan']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($item['nama_pembeli'] ?? '-') ?>
                                    <br>
                                    <small class="text-secondary">
                                        <?= htmlspecialchars($item['email'] ?? '-') ?>
                                    </small>
                                </td>

                                <td>
                                    <?= date('d-m-Y H:i', strtotime($item['tanggal_pesanan'])) ?>
                                </td>

                                <td class="fw-semibold">
                                    Rp<?= number_format((float)$item['total_harga'], 0, ',', '.') ?>
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

                                    <br>

                                    <small class="text-secondary">
                                        <?= htmlspecialchars(
                                            ucwords(str_replace('_', ' ', $item['status_pembayaran'] ?? '-'))
                                        ) ?>
                                    </small>
                                </td>

                                <td>
                                    <span class="badge bg-<?= warnaStatusAdmin($item['status']) ?> text-dark">
                                        <?= htmlspecialchars(ucfirst($item['status'])) ?>
                                    </span>
                                </td>

                                <td>
    <a href="detail_pesanan.php?id=<?= (int)$item['id_pesanan'] ?>"
       class="btn btn-outline-primary btn-sm">
        <i class="bi bi-eye"></i> Detail
    </a>
</td>

                                <td>

                                    <form method="POST" class="d-flex gap-2">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="id_pesanan"
                                            value="<?= (int)$item['id_pesanan'] ?>"
                                        >

                                        <select name="status" class="form-select form-select-sm">

                                            <option value="menunggu" <?= $item['status'] === 'menunggu' ? 'selected' : '' ?>>
                                                Menunggu
                                            </option>

                                            <option value="diproses" <?= $item['status'] === 'diproses' ? 'selected' : '' ?>>
                                                Diproses
                                            </option>

                                            <option value="dikirim" <?= $item['status'] === 'dikirim' ? 'selected' : '' ?>>
                                                Dikirim
                                            </option>

                                            <option value="selesai" <?= $item['status'] === 'selesai' ? 'selected' : '' ?>>
                                                Selesai
                                            </option>

                                            <option value="dibatalkan" <?= $item['status'] === 'dibatalkan' ? 'selected' : '' ?>>
                                                Dibatalkan
                                            </option>

                                        </select>

                                        <button type="submit" class="btn btn-success btn-sm">
                                            Simpan
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-receipt display-4 text-secondary"></i>
                                <p class="text-secondary mt-2 mb-0">
                                    Belum ada pesanan masuk.
                                </p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>