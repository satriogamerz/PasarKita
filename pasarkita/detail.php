<?php
require_once "config/database.php";
require_once "includes/cart_sync.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit("ID produk tidak valid. <a href='index.php'>Kembali</a>");
}

$stmt = $conn->prepare("SELECT p.*, c.nama_kategori FROM products p
                        JOIN categories c ON c.id_kategori=p.id_kategori
                        WHERE p.id_produk=? AND p.status='aktif' LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    http_response_code(404);
    exit("Produk tidak ditemukan. <a href='index.php'>Kembali</a>");
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = filter_input(INPUT_POST, 'jumlah', FILTER_VALIDATE_INT);
    if (!$qty || $qty < 1) {
        $error = "Jumlah produk minimal 1.";
    } elseif ($qty > (int)$product['stok']) {
        $error = "Jumlah melebihi stok yang tersedia.";
    } else {
        // Pembeli wajib login sebelum menambahkan produk atau membeli langsung.
        if (empty($_SESSION['user']['id_user']) || (($_SESSION['user']['role'] ?? '') === 'admin')) {
            $kembali = 'detail.php?id=' . (int)$id;
            header('Location: login.php?perlu_login=1&kembali=' . urlencode($kembali));
            exit;
        }
        $_SESSION['keranjang'] = $_SESSION['keranjang'] ?? [];
        $current = (int)($_SESSION['keranjang'][$id] ?? 0);
        $aksi = $_POST['aksi'] ?? 'keranjang';
        if ($aksi === 'beli_sekarang' && $qty <= (int)$product['stok']) { $_SESSION['keranjang_beli_sekarang'] = [$id => $qty]; $_SESSION['mode_beli_sekarang'] = true; header('Location: checkout.php'); exit; }
        if ($current + $qty > (int)$product['stok']) {
            $error = "Jumlah di keranjang melebihi stok yang tersedia.";
        } else {
            $_SESSION['keranjang'][$id] = $current + $qty;
            if (!empty($_SESSION['user']['id_user'])) { simpanKeranjangAkun($conn, (int)$_SESSION['user']['id_user'], [$id => $_SESSION['keranjang'][$id]]); }
            header("Location: keranjang.php?added=1");
            exit;
        }
    }
}
$pageTitle = $product['nama_produk'];
require "includes/header.php";
?>
<div class="container py-5">
    <a href="index.php" class="text-decoration-none text-success"><i class="bi bi-arrow-left"></i> Kembali ke produk</a>
    <div class="row g-4 g-lg-5 mt-1 align-items-start">
        <div class="col-lg-6">
            <img class="detail-img shadow-sm" src="<?= htmlspecialchars($product['gambar'] ?: 'https://placehold.co/800x600/e8efe9/174c3c?text=PasarKita') ?>"
                 alt="<?= htmlspecialchars($product['nama_produk']) ?>"
                 onerror="this.src='https://placehold.co/800x600/e8efe9/174c3c?text=PasarKita'">
        </div>
        <div class="col-lg-6">
            <span class="badge text-bg-success mb-2"><?= htmlspecialchars($product['nama_kategori']) ?></span>
            <h1 class="fw-bold"><?= htmlspecialchars($product['nama_produk']) ?></h1>
            <div class="price fs-3 mb-3">Rp<?= number_format((float)$product['harga'], 0, ',', '.') ?></div>
            <p class="text-secondary"><?= nl2br(htmlspecialchars($product['deskripsi'] ?? 'Belum ada deskripsi produk.')) ?></p>
            <p><strong>Stok tersedia:</strong> <?= (int)$product['stok'] ?></p>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ((int)$product['stok'] > 0): ?>
            <form method="post" class="row g-2 align-items-end">
                <div class="col-4 col-md-3">
                    <label class="form-label" for="jumlah">Jumlah</label>
                    <input class="form-control" id="jumlah" type="number" name="jumlah" value="1" min="1" max="<?= (int)$product['stok'] ?>" required>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-success btn-lg" type="submit" name="aksi" value="keranjang"><i class="bi bi-cart-plus me-1"></i>Tambah ke Keranjang</button>
                        <button class="btn btn-pasar btn-lg" type="submit" name="aksi" value="beli_sekarang"><i class="bi bi-bag-check me-1"></i>Beli Sekarang</button>
                    </div>
                </div>
            </form>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>Stok Habis</button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require "includes/footer.php"; ?>
