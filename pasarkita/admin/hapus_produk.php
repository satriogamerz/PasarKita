<?php
session_start();
require_once "../config/database.php";

// Hanya admin yang boleh mengakses
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Buat token keamanan
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: produk.php");
    exit;
}

// Ambil data produk
$stmt = $conn->prepare(
    "SELECT id_produk, nama_produk, status FROM products WHERE id_produk = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();

if (!$produk) {
    die("Produk tidak ditemukan.");
}

// Proses ketika tombol konfirmasi ditekan
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Permintaan tidak valid. Silakan kembali dan coba lagi.");
    }

    $update = $conn->prepare(
        "UPDATE products SET status = 'nonaktif' WHERE id_produk = ?"
    );
    $update->bind_param("i", $id);

    if ($update->execute()) {
        header("Location: produk.php?pesan=hapus");
        exit;
    } else {
        $error = "Produk gagal dinonaktifkan: " . $update->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hapus Produk - PasarKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 550px;">

        <div class="card-header bg-danger text-white">
            <h4 class="mb-0">Konfirmasi Hapus Produk</h4>
        </div>

        <div class="card-body">

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <p>
                Apakah kamu yakin ingin menonaktifkan produk berikut?
            </p>

            <div class="alert alert-warning">
                <strong>
                    <?= htmlspecialchars($produk['nama_produk']) ?>
                </strong>
                <br>
                <small>
                    Produk tidak akan aktif dijual, tetapi datanya tetap tersimpan.
                </small>
            </div>

            <form method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <button type="submit" class="btn btn-danger">
                    Ya, Nonaktifkan Produk
                </button>

                <a href="produk.php" class="btn btn-secondary">
                    Batal
                </a>
            </form>

        </div>
    </div>
</div>

</body>
</html>