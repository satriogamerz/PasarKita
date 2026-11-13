<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM products WHERE id_produk = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();

if (!$produk) {
    die("Produk tidak ditemukan.");
}

$error = "";

$queryKategori = $conn->query(
    "SELECT id_kategori, nama_kategori FROM categories ORDER BY nama_kategori ASC"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST['nama_produk'] ?? '');
    $kategori = (int) ($_POST['id_kategori'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = (float) ($_POST['harga'] ?? 0);
    $stok = (int) ($_POST['stok'] ?? 0);

    $gambarLama = $produk['gambar'];
    $gambarBaru = $gambarLama;
    $lokasiGambarBaru = null;

    if ($nama === "" || $kategori <= 0 || $harga <= 0 || $stok < 0) {
        $error = "Mohon isi data produk dengan benar.";
    } else {
        $cek = $conn->prepare("SELECT id_kategori FROM categories WHERE id_kategori = ?");
        $cek->bind_param("i", $kategori);
        $cek->execute();

        if ($cek->get_result()->num_rows === 0) {
            $error = "Kategori tidak ditemukan.";
        } else {
            // Gambar baru bersifat opsional
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
                    $error = "Gambar gagal diunggah.";
                } else {
                    $tmp = $_FILES['gambar']['tmp_name'];
                    $ukuran = $_FILES['gambar']['size'];

                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($tmp);

                    $format = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp'
                    ];

                    if (!isset($format[$mime])) {
                        $error = "Format gambar harus JPG, PNG, atau WEBP.";
                    } elseif ($ukuran > 2 * 1024 * 1024) {
                        $error = "Ukuran gambar maksimal 2 MB.";
                    } else {
                        $folder = "../uploads/produk/";

                        if (!is_dir($folder)) {
                            mkdir($folder, 0775, true);
                        }

                        $namaFile = uniqid("produk_", true) . "." . $format[$mime];
                        $lokasiGambarBaru = $folder . $namaFile;

                        if (move_uploaded_file($tmp, $lokasiGambarBaru)) {
                            $gambarBaru = "uploads/produk/" . $namaFile;
                        } else {
                            $error = "Gambar gagal disimpan.";
                        }
                    }
                }
            }

            if ($error === "") {
                $update = $conn->prepare(
                    "UPDATE products
                     SET id_kategori = ?, nama_produk = ?, deskripsi = ?,
                         harga = ?, stok = ?, gambar = ?
                     WHERE id_produk = ?"
                );

                $update->bind_param(
                    "issdisi",
                    $kategori,
                    $nama,
                    $deskripsi,
                    $harga,
                    $stok,
                    $gambarBaru,
                    $id
                );

                if ($update->execute()) {
                    // Hapus gambar lama jika berhasil diganti
                    if ($lokasiGambarBaru !== null && !empty($gambarLama)) {
                        $pathLama = "../" . $gambarLama;

                        if (is_file($pathLama)) {
                            unlink($pathLama);
                        }
                    }

                    header("Location: produk.php?pesan=edit");
                    exit;
                } else {
                    $error = "Produk gagal diperbarui: " . $update->error;
                }
            }

            // Jika penyimpanan gagal, hapus file gambar baru
            if ($error !== "" && $lokasiGambarBaru !== null && is_file($lokasiGambarBaru)) {
                unlink($lokasiGambarBaru);
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
    <title>Edit Produk - PasarKita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 750px;">
        <div class="card-header bg-warning">
            <h4 class="mb-0">Edit Produk</h4>
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
                           value="<?= htmlspecialchars($_POST['nama_produk'] ?? $produk['nama_produk']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="id_kategori" class="form-select" required>
                        <?php while ($k = $queryKategori->fetch_assoc()): ?>
                            <?php $pilihan = $_POST['id_kategori'] ?? $produk['id_kategori']; ?>
                            <option value="<?= $k['id_kategori'] ?>"
                                <?= ($pilihan == $k['id_kategori']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($_POST['deskripsi'] ?? $produk['deskripsi']) ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control"
                               min="1" step="1" required
                               value="<?= htmlspecialchars($_POST['harga'] ?? $produk['harga']) ?>">
                        <small class="text-muted">Contoh Rp55.000, ketik 55000.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" class="form-control"
                               min="0" required
                               value="<?= htmlspecialchars($_POST['stok'] ?? $produk['stok']) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gambar Saat Ini</label><br>

                    <?php if (!empty($produk['gambar'])): ?>
                        <img src="../<?= htmlspecialchars($produk['gambar']) ?>"
                             alt="Gambar produk" style="width:120px; height:120px; object-fit:cover;">
                    <?php else: ?>
                        <p>Belum ada gambar.</p>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label">Ganti Gambar (Opsional)</label>
                    <input type="file" name="gambar" class="form-control"
                           accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar.</small>
                </div>

                <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                <a href="produk.php" class="btn btn-secondary">Kembali</a>

            </form>
        </div>
    </div>
</div>

</body>
</html>