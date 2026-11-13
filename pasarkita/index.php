<?php
require_once "config/database.php";
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = "Beranda";
$keyword = trim($_GET['q'] ?? '');
$kategori = (int)($_GET['kategori'] ?? 0);

$sql = "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar,
               c.nama_kategori
        FROM products p
        JOIN categories c ON c.id_kategori = p.id_kategori
        WHERE p.status = 'aktif'";
$params = [];
$types = "";
if ($keyword !== '') {
    $sql .= " AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
    $like = "%".$keyword."%";
    $params[] = $like; $params[] = $like; $types .= "ss";
}
if ($kategori > 0) {
    $sql .= " AND p.id_kategori = ?";
    $params[] = $kategori; $types .= "i";
}
$sql .= " ORDER BY p.created_at DESC, p.id_produk DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();

$categories = $conn->query("SELECT id_kategori, nama_kategori FROM categories ORDER BY nama_kategori");
require "includes/header.php";
?>
<div class="container py-4 py-lg-5">
    <section class="hero-pasar shadow-sm mb-5">
        <div class="hero-inner">
            <span class="badge text-bg-warning mb-3">Bangga Produk Lokal</span>
            <h1 class="display-5 fw-bold">Temukan Karya & Cita Rasa Tradisional</h1>
            <p class="lead col-lg-8 mb-4">Belanja produk pilihan dari pengrajin dan pelaku usaha lokal Indonesia.</p>
            <a href="#daftar-produk" class="btn btn-light btn-lg fw-semibold">Jelajahi Produk</a>
        </div>
    </section>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3" id="daftar-produk">
        <div>
            <div class="text-success fw-semibold small text-uppercase">Katalog marketplace</div>
            <h2 class="section-title mb-0">Produk Pilihan</h2>
        </div>
        <form class="row g-2" method="get">
            <div class="col-auto">
                <input class="form-control" type="search" name="q" placeholder="Cari produk..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
            <div class="col-auto">
                <select class="form-select" name="kategori">
                    <option value="0">Semua kategori</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= (int)$cat['id_kategori'] ?>" <?= $kategori === (int)$cat['id_kategori'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nama_kategori']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-pasar">Cari</button></div>
        </form>
    </div>

    <?php if ($products->num_rows > 0): ?>
    <div class="row g-4">
        <?php while ($p = $products->fetch_assoc()): ?>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card product-card shadow-sm">
                <img class="product-img" src="<?= htmlspecialchars($p['gambar'] ?: 'https://placehold.co/600x420/e8efe9/174c3c?text=PasarKita') ?>"
                     alt="<?= htmlspecialchars($p['nama_produk']) ?>"
                     onerror="this.src='https://placehold.co/600x420/e8efe9/174c3c?text=PasarKita'">
                <div class="card-body d-flex flex-column">
                    <span class="small text-success fw-semibold"><?= htmlspecialchars($p['nama_kategori']) ?></span>
                    <h5 class="card-title mt-1"><?= htmlspecialchars($p['nama_produk']) ?></h5>
                    <div class="price mb-2">Rp<?= number_format((float)$p['harga'], 0, ',', '.') ?></div>
                    <div class="small text-secondary mb-3">Stok: <?= (int)$p['stok'] ?></div>
                    <?php if (($_SESSION['user']['role'] ?? '') !== 'admin'): ?>
                    <a href="detail.php?id=<?= (int)$p['id_produk'] ?>" class="btn btn-pasar mt-auto">Lihat Detail</a>
                    <?php else: ?>
                    <div class="mt-auto"><span class="badge text-bg-secondary">Mode lihat saja</span></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
        <div class="empty-state text-center shadow-sm">
            <i class="bi bi-search display-4 text-success"></i>
            <h4 class="mt-3">Produk belum ditemukan</h4>
            <p class="text-secondary mb-0">Coba kata kunci atau kategori lainnya.</p>
        </div>
    <?php endif; ?>
</div>
<?php
$stmt->close();
require "includes/footer.php";
?>
