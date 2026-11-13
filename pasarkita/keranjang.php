<?php
require_once "config/database.php";
require_once "includes/cart_sync.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['keranjang'] = $_SESSION['keranjang'] ?? [];

// Proses update dan hapus produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id_produk', FILTER_VALIDATE_INT);

    if ($id) {

        if ($action === 'hapus') {
            unset($_SESSION['keranjang'][$id]);
            if (!empty($_SESSION['user']['id_user'])) { simpanKeranjangAkun($conn, (int)$_SESSION['user']['id_user'], [$id => 0]); }

        } elseif ($action === 'update') {

            $qty = filter_input(INPUT_POST, 'jumlah', FILTER_VALIDATE_INT);

            $check = $conn->prepare(
                "SELECT stok FROM products WHERE id_produk=? AND status='aktif'"
            );

            $check->bind_param("i", $id);
            $check->execute();

            $row = $check->get_result()->fetch_assoc();
            $check->close();

            if ($row && $qty && $qty > 0 && $qty <= (int)$row['stok']) {
                $_SESSION['keranjang'][$id] = $qty;
                if (!empty($_SESSION['user']['id_user'])) { simpanKeranjangAkun($conn, (int)$_SESSION['user']['id_user'], [$id => $qty]); }
            } else {
                $_SESSION['flash_error'] = "Jumlah produk tidak valid atau melebihi stok.";
            }
        }
    }

    header("Location: keranjang.php");
    exit;
}

// Mengambil produk dari keranjang
$items = [];
$total = 0;

if (!empty($_SESSION['keranjang'])) {

    $ids = array_map('intval', array_keys($_SESSION['keranjang']));

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare(
        "SELECT id_produk, nama_produk, harga, stok, gambar
         FROM products
         WHERE status='aktif' AND id_produk IN ($placeholders)"
    );

    $stmt->bind_param($types, ...$ids);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($p = $result->fetch_assoc()) {

        $qty = (int)$_SESSION['keranjang'][$p['id_produk']];

        if ($qty > (int)$p['stok']) {
            $qty = (int)$p['stok'];
        }

        $p['jumlah'] = $qty;
        $p['subtotal'] = $qty * (float)$p['harga'];

        $total += $p['subtotal'];
        $items[] = $p;
    }

    $stmt->close();
}

$pageTitle = "Keranjang Belanja";
require "includes/header.php";
?>

<div class="container py-5">

    <h1 class="section-title mb-4">Keranjang Belanja</h1>

    <?php if (!empty($_GET['added'])): ?>
        <div class="alert alert-success">
            Produk berhasil ditambahkan ke keranjang.
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if ($items): ?>

        <div class="card border-0 shadow-sm rounded-4">

            <div class="table-responsive">

                <table class="table align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th style="width:150px">Jumlah</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($items as $item): ?>

                        <tr>

                            <td>
                                <div class="d-flex align-items-center gap-3">

                                    <img
                                        src="<?= htmlspecialchars($item['gambar'] ?: 'https://placehold.co/100x100?text=Produk') ?>"
                                        alt=""
                                        width="72"
                                        height="64"
                                        style="object-fit:cover;border-radius:10px"
                                    >

                                    <span class="fw-semibold">
                                        <?= htmlspecialchars($item['nama_produk']) ?>
                                    </span>

                                </div>
                            </td>

                            <td>
                                Rp<?= number_format((float)$item['harga'], 0, ',', '.') ?>
                            </td>

                            <td>

                                <form method="post" class="d-flex gap-1">

                                    <input type="hidden" name="action" value="update">

                                    <input
                                        type="hidden"
                                        name="id_produk"
                                        value="<?= (int)$item['id_produk'] ?>"
                                    >

                                    <input
                                        class="form-control form-control-sm"
                                        type="number"
                                        name="jumlah"
                                        min="1"
                                        max="<?= (int)$item['stok'] ?>"
                                        value="<?= (int)$item['jumlah'] ?>"
                                    >

                                    <button class="btn btn-outline-success btn-sm" title="Perbarui">
                                        <i class="bi bi-check"></i>
                                    </button>

                                </form>

                            </td>

                            <td class="fw-semibold">
                                Rp<?= number_format((float)$item['subtotal'], 0, ',', '.') ?>
                            </td>

                            <td>

                                <form method="post">

                                    <input type="hidden" name="action" value="hapus">

                                    <input
                                        type="hidden"
                                        name="id_produk"
                                        value="<?= (int)$item['id_produk'] ?>"
                                    >

                                    <button class="btn btn-outline-danger btn-sm" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">

                <a href="index.php" class="btn btn-outline-success">
                    Lanjut Belanja
                </a>

                <div class="text-end">

                    <div class="text-secondary">Total sementara</div>

                    <div class="price fs-4">
                        Rp<?= number_format($total, 0, ',', '.') ?>
                    </div>

                    <a href="checkout.php" class="btn btn-pasar mt-2">
                        Lanjut Checkout
                    </a>

                </div>

            </div>

        </div>

    <?php else: ?>

        <div class="empty-state text-center shadow-sm">

            <i class="bi bi-cart-x display-3 text-success"></i>

            <h4 class="mt-3">Keranjang masih kosong</h4>

            <p class="text-secondary">
                Yuk, temukan produk tradisional favoritmu.
            </p>

            <a href="index.php" class="btn btn-pasar">
                Mulai Belanja
            </a>

        </div>

    <?php endif; ?>

</div>

<?php require "includes/footer.php"; ?>