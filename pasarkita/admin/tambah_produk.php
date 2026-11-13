<?php
session_start();

require_once "../config/database.php";

// Pastikan hanya admin yang bisa membuka halaman
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = "";
$error = "";

// Ambil daftar kategori
$queryKategori = $conn->query(
    "SELECT id_kategori, nama_kategori FROM categories ORDER BY nama_kategori ASC"
);

// Proses ketika tombol simpan ditekan
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama = trim($_POST['nama_produk'] ?? '');
    $idKategori = (int) ($_POST['id_kategori'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = (float) ($_POST['harga'] ?? 0);
    $stok = (int) ($_POST['stok'] ?? 0);

    $idUser = (int) $_SESSION['user']['id_user'];

    if ($nama === "" || $idKategori <= 0 || $harga <= 0 || $stok < 0) {
        $error = "Mohon isi data produk dengan benar.";
    } elseif (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        $error = "Silakan pilih gambar produk.";
    } else {

        // Pastikan kategori tersedia
        $cekKategori = $conn->prepare(
            "SELECT id_kategori FROM categories WHERE id_kategori = ?"
        );
        $cekKategori->bind_param("i", $idKategori);
        $cekKategori->execute();
        $hasilKategori = $cekKategori->get_result();

        if ($hasilKategori->num_rows === 0) {
            $error = "Kategori tidak ditemukan.";
        } else {

            // Validasi gambar
            $fileTmp = $_FILES['gambar']['tmp_name'];
            $ukuran = $_FILES['gambar']['size'];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($fileTmp);

            $formatGambar = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($formatGambar[$mime])) {
                $error = "Format gambar harus JPG, PNG, atau WEBP.";
            } elseif ($ukuran > 2 * 1024 * 1024) {
                $error = "Ukuran gambar maksimal 2 MB.";
            } else {

                $folder = "../uploads/produk/";

                if (!is_dir($folder)) {
                    mkdir($folder, 0775, true);
                }

                $namaFile = uniqid("produk_", true) . "." . $formatGambar[$mime];
                $lokasiGambar = $folder . $namaFile;

                if (move_uploaded_file($fileTmp, $lokasiGambar)) {

                    $pathDatabase = "uploads/produk/" . $namaFile;

                    $stmt = $conn->prepare(
                        "INSERT INTO products
                        (id_user, id_kategori, nama_produk, deskripsi, harga, stok, gambar)
                        VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                        "iissdis",
                        $idUser,
                        $idKategori,
                        $nama,
                        $deskripsi,
                        $harga,
                        $stok,
                        $pathDatabase
                    );

                    if ($stmt->execute()) {
                        header("Location: produk.php?pesan=tambah");
                        exit;
                    } else {
                        if (file_exists($lokasiGambar)) {
                            unlink($lokasiGambar);
                        }

                        $error = "Produk gagal disimpan: " . $stmt->error;
                    }

                } else {
                    $error = "Gambar gagal diunggah. Periksa folder uploads/produk.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Produk - PasarKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">

    <div class="card shadow-sm mx-auto" style="max-width: 750px;">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Tambah Produk</h4>
        </div>

        <div class="card-body">

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="nama_produk" class="form-control"
                           maxlength="150" required
                           value="<?= htmlspecialchars($_POST['nama_produk'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="id_kategori" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>

                        <?php while ($kategori = $queryKategori->fetch_assoc()): ?>
                            <option value="<?= $kategori['id_kategori'] ?>"
                                <?= (($_POST['id_kategori'] ?? '') == $kategori['id_kategori']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kategori['nama_kategori']) ?>
                            </option>
                        <?php endwhile; ?>

                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control"
                               min="1" step="1" required
                               value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" class="form-control"
                               min="0" required
                               value="<?= htmlspecialchars($_POST['stok'] ?? '0') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Gambar Produk</label>
                    <input type="file" name="gambar" class="form-control"
                           accept=".jpg,.jpeg,.png,.webp" required>
                    <small class="text-muted">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Produk
                    </button>

                    <a href="produk.php" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>

</body>
</html>