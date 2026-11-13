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

// Ambil semua produk dan nama kategorinya
$query = $conn->query("
    SELECT p.*, c.nama_kategori
    FROM products p
    LEFT JOIN categories c ON p.id_kategori = c.id_kategori
    WHERE p.status = 'aktif'
    ORDER BY p.id_produk DESC
");

if (!$query) {
    die("Gagal mengambil data produk: " . htmlspecialchars($conn->error));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Produk - PasarKita Admin</title>

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

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">Kelola Produk</h2>

            <p class="text-secondary mb-0">
                Kelola seluruh produk yang tersedia di PasarKita.
            </p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">

            <a href="index.php" class="btn btn-outline-success">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>

            <a href="tambah_produk.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Tambah Produk
            </a>

        </div>

    </div>

    <?php if (!empty($_GET['sukses'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_GET['sukses']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>
                            <th class="p-3">No</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($query->num_rows > 0): ?>

                            <?php $no = 1; ?>

                            <?php while ($produk = $query->fetch_assoc()): ?>

                                <tr>

                                    <td class="p-3">
                                        <?= $no++ ?>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($produk['nama_produk']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($produk['nama_kategori'] ?? '-') ?>
                                    </td>

                                    <td class="fw-semibold">
                                        Rp<?= number_format((float)$produk['harga'], 0, ',', '.') ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-<?= (int)$produk['stok'] > 0 ? 'success' : 'danger' ?>">
                                            <?= (int)$produk['stok'] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="d-flex gap-2">

                                            <a
                                                href="edit_produk.php?id=<?= (int)$produk['id_produk'] ?>"
                                                class="btn btn-warning btn-sm"
                                            >
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>

                                            <a
                                                href="hapus_produk.php?id=<?= (int)$produk['id_produk'] ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Yakin ingin menghapus produk ini?')"
                                            >
                                                <i class="bi bi-trash"></i> Hapus
                                            </a>

                                        </div>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-box-seam display-4 text-secondary"></i>

                                    <p class="text-secondary mt-2 mb-0">
                                        Belum ada produk.
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