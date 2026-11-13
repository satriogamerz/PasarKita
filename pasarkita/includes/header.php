<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Badge keranjang menghitung jumlah jenis produk, bukan total kuantitas.
$jumlahKeranjang = (!empty($_SESSION['keranjang']) && is_array($_SESSION['keranjang']))
    ? count(array_filter($_SESSION['keranjang'], fn($qty) => (int)$qty > 0)) : 0;

if (!empty($_SESSION['user']) && empty($_SESSION['csrf_akun'])) {
    $_SESSION['csrf_akun'] = bin2hex(random_bytes(32));
}

$user = $_SESSION['user'] ?? null;
$pageTitle = $pageTitle ?? "PasarKita";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | PasarKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-pasar sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-shop-window me-2"></i>PasarKita
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarPasar" aria-controls="navbarPasar"
            aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarPasar">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Beranda</a>
                </li>
                <?php if (!$user || ($user['role'] ?? '') !== 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="keranjang.php">
                        <i class="bi bi-cart3"></i> Keranjang
                        <span class="badge rounded-pill bg-warning text-dark"><?= $jumlahKeranjang ?></span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($user): ?>
                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin/index.php"><i class="bi bi-speedometer2"></i> Dashboard Admin</a></li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="pesanan_saya.php">
                            <i class="bi bi-receipt"></i> Pesanan Saya
                        </a>
                    </li>
                    <?php endif; ?>

<li class="nav-item d-flex align-items-center gap-2">

    <!-- Foto profil: klik untuk mengganti foto -->
    <form method="post" action="akun.php"
          enctype="multipart/form-data" class="m-0">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_akun']) ?>">

        <input type="hidden" name="aksi" value="foto">

        <input type="file" name="foto_profil"
               id="fotoProfilNavbar"
               accept="image/jpeg,image/png,image/webp"
               hidden onchange="this.form.submit()">

        <label for="fotoProfilNavbar"
               style="cursor:pointer"
               title="Klik untuk mengganti foto profil">

            <?php if (!empty($user['foto_profil'])): ?>

                <img src="<?= htmlspecialchars($user['foto_profil']) ?>?v=<?= time() ?>"
                     alt="Foto profil"
                     class="rounded-circle"
                     style="width:34px;height:34px;object-fit:cover;border:2px solid white">

            <?php else: ?>

                <span class="rounded-circle bg-light text-success d-inline-flex align-items-center justify-content-center"
                      style="width:34px;height:34px">
                    <i class="bi bi-person-fill"></i>
                </span>

            <?php endif; ?>

        </label>
    </form>

    <!-- Nama akun: klik untuk membuka halaman akun -->
    <a href="akun.php"
       class="text-white text-decoration-none">
        Halo, <?= htmlspecialchars($user['nama'] ?? 'Akun') ?>
    </a>

</li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item">
                        <a class="btn btn-warning btn-sm text-dark" href="register.php">
                            <i class="bi bi-person-plus"></i> Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main>