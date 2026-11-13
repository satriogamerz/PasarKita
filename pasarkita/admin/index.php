<?php
require_once "../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan pengguna sudah login
if (empty($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// Pastikan hanya admin yang bisa masuk
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Fungsi menghitung jumlah data
function hitungData($conn, $tabel)
{
    $query = $conn->query("SELECT COUNT(*) AS total FROM `$tabel`");

    if (!$query) {
        return 0;
    }

    return (int) $query->fetch_assoc()['total'];
}

$totalProduk = hitungData($conn, 'products');
$totalPesanan = hitungData($conn, 'orders');
$totalPengguna = hitungData($conn, 'users');
$totalKategori = hitungData($conn, 'categories');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PasarKita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-success shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-shop"></i> PasarKita Admin
        </a>

        <div class="d-flex align-items-center gap-2">
            <a href="../index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-eye"></i> Lihat Website</a>
            <a href="../akun.php" class="btn btn-outline-light btn-sm"><i class="bi bi-person"></i> Akun Admin</a>
            <a href="../akun.php" class="text-white text-decoration-none d-flex align-items-center gap-2">
                <?php if (!empty($_SESSION['user']['foto_profil'])): ?>
                    <img src="../<?= htmlspecialchars($_SESSION['user']['foto_profil']) ?>?v=<?= time() ?>" alt="Foto profil" class="rounded-circle" style="width:32px;height:32px;object-fit:cover">
                <?php else: ?>
                    <i class="bi bi-person-circle fs-4"></i>
                <?php endif; ?>
                Halo, <?= htmlspecialchars($_SESSION['user']['nama']) ?>
            </a>

            <a href="../logout.php" class="btn btn-outline-light btn-sm">
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">

    <h2 class="fw-bold mb-2">Dashboard Admin</h2>
    <p class="text-secondary mb-4">
        Selamat datang di halaman pengelolaan PasarKita.
    </p>

    <div class="row g-4">

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="text-primary fs-1 mb-3">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <p class="text-secondary mb-1">Total Produk</p>
                    <h2 class="fw-bold"><?= $totalProduk ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="text-success fs-1 mb-3">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <p class="text-secondary mb-1">Total Pesanan</p>
                    <h2 class="fw-bold"><?= $totalPesanan ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="text-warning fs-1 mb-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <p class="text-secondary mb-1">Total Pengguna</p>
                    <h2 class="fw-bold"><?= $totalPengguna ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="text-danger fs-1 mb-3">
                        <i class="bi bi-tags"></i>
                    </div>
                    <p class="text-secondary mb-1">Total Kategori</p>
                    <h2 class="fw-bold"><?= $totalKategori ?></h2>
                </div>
            </div>
        </div>

    </div>

    <div class="card border-0 shadow-sm rounded-4 mt-5">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Menu Pengelolaan</h5>

<div class="d-flex flex-wrap gap-2">

    <a href="pesanan.php" class="btn btn-success">
        <i class="bi bi-receipt"></i>
        Kelola Pesanan
    </a>

    <a href="produk.php" class="btn btn-primary">
        <i class="bi bi-box-seam"></i>
        Kelola Produk
    </a>

</div>
            <p class="text-secondary mb-0">
            
            </p>
        </div>
    </div>


</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>