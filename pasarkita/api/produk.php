<?php
/**
 * REST API Produk PasarKita
 * GET    /api/produk.php          daftar produk aktif
 * GET    /api/produk.php?id=1     detail produk
 * POST   /api/produk.php          tambah produk (admin login)
 * PUT    /api/produk.php?id=1     ubah produk (admin login)
 * DELETE /api/produk.php?id=1     nonaktifkan produk (admin login)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function kirimJson(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function bacaInput(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        kirimJson(400, ['success' => false, 'message' => 'Body harus berupa JSON yang valid.']);
    }
    return $data;
}
function wajibAdmin(): int {
    if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
        kirimJson(401, ['success' => false, 'message' => 'Login sebagai admin diperlukan untuk mengubah data produk.']);
    }
    return (int)$_SESSION['user']['id_user'];
}
function validasiKategori(mysqli $conn, int $idKategori): bool {
    $stmt = $conn->prepare("SELECT id_kategori FROM categories WHERE id_kategori = ?");
    $stmt->bind_param("i", $idKategori);
    $stmt->execute();
    $ada = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ada;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id = $id && $id > 0 ? (int)$id : 0;

if ($method === 'GET') {
    if ($id > 0) {
        $stmt = $conn->prepare(
            "SELECT p.id_produk, p.id_kategori, c.nama_kategori, p.nama_produk,
                    p.deskripsi, p.harga, p.stok, p.gambar, p.status
             FROM products p
             LEFT JOIN categories c ON c.id_kategori = p.id_kategori
             WHERE p.id_produk = ? AND p.status = 'aktif' LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $produk = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$produk) kirimJson(404, ['success' => false, 'message' => 'Produk tidak ditemukan.']);
        kirimJson(200, ['success' => true, 'data' => $produk]);
    }

    $result = $conn->query(
        "SELECT p.id_produk, p.id_kategori, c.nama_kategori, p.nama_produk,
                p.deskripsi, p.harga, p.stok, p.gambar, p.status
         FROM products p
         LEFT JOIN categories c ON c.id_kategori = p.id_kategori
         WHERE p.status = 'aktif'
         ORDER BY p.id_produk DESC"
    );
    if (!$result) kirimJson(500, ['success' => false, 'message' => 'Gagal mengambil produk.']);
    $items = [];
    while ($row = $result->fetch_assoc()) $items[] = $row;
    kirimJson(200, ['success' => true, 'total' => count($items), 'data' => $items]);
}

if ($method === 'POST') {
    $idUser = wajibAdmin();
    $data = bacaInput();
    $nama = trim((string)($data['nama_produk'] ?? ''));
    $idKategori = filter_var($data['id_kategori'] ?? null, FILTER_VALIDATE_INT);
    $deskripsi = trim((string)($data['deskripsi'] ?? ''));
    $harga = filter_var($data['harga'] ?? null, FILTER_VALIDATE_FLOAT);
    $stok = filter_var($data['stok'] ?? null, FILTER_VALIDATE_INT);
    $gambar = trim((string)($data['gambar'] ?? ''));

    if ($nama === '' || strlen($nama) > 150 || !$idKategori || !$harga || $harga <= 0 || $stok === false || $stok < 0) {
        kirimJson(422, ['success' => false, 'message' => 'nama_produk, id_kategori, harga (> 0), dan stok (>= 0) wajib diisi dengan benar.']);
    }
    if (!validasiKategori($conn, (int)$idKategori)) kirimJson(422, ['success' => false, 'message' => 'Kategori tidak ditemukan.']);

    $stmt = $conn->prepare(
        "INSERT INTO products (id_user, id_kategori, nama_produk, deskripsi, harga, stok, gambar)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iissdis", $idUser, $idKategori, $nama, $deskripsi, $harga, $stok, $gambar);
    if (!$stmt->execute()) kirimJson(500, ['success' => false, 'message' => 'Gagal menambahkan produk.']);
    $newId = $stmt->insert_id;
    $stmt->close();
    kirimJson(201, ['success' => true, 'message' => 'Produk berhasil ditambahkan.', 'id_produk' => $newId]);
}

if ($method === 'PUT') {
    wajibAdmin();
    if ($id <= 0) kirimJson(400, ['success' => false, 'message' => 'Parameter id wajib diisi.']);
    $data = bacaInput();
    $nama = trim((string)($data['nama_produk'] ?? ''));
    $idKategori = filter_var($data['id_kategori'] ?? null, FILTER_VALIDATE_INT);
    $deskripsi = trim((string)($data['deskripsi'] ?? ''));
    $harga = filter_var($data['harga'] ?? null, FILTER_VALIDATE_FLOAT);
    $stok = filter_var($data['stok'] ?? null, FILTER_VALIDATE_INT);
    $gambar = trim((string)($data['gambar'] ?? ''));

    if ($nama === '' || strlen($nama) > 150 || !$idKategori || !$harga || $harga <= 0 || $stok === false || $stok < 0) {
        kirimJson(422, ['success' => false, 'message' => 'nama_produk, id_kategori, harga (> 0), dan stok (>= 0) wajib diisi dengan benar.']);
    }
    if (!validasiKategori($conn, (int)$idKategori)) kirimJson(422, ['success' => false, 'message' => 'Kategori tidak ditemukan.']);

    $stmt = $conn->prepare(
        "UPDATE products SET id_kategori=?, nama_produk=?, deskripsi=?, harga=?, stok=?, gambar=?
         WHERE id_produk=? AND status='aktif'"
    );
    $stmt->bind_param("issdisi", $idKategori, $nama, $deskripsi, $harga, $stok, $gambar, $id);
    if (!$stmt->execute()) kirimJson(500, ['success' => false, 'message' => 'Gagal memperbarui produk.']);
    $affected = $stmt->affected_rows;
    $stmt->close();
    if ($affected === 0) {
        $cek = $conn->prepare("SELECT id_produk FROM products WHERE id_produk=? AND status='aktif'");
        $cek->bind_param("i", $id);
        $cek->execute();
        $ada = $cek->get_result()->num_rows > 0;
        $cek->close();
        if (!$ada) kirimJson(404, ['success' => false, 'message' => 'Produk tidak ditemukan atau sudah nonaktif.']);
    }
    kirimJson(200, ['success' => true, 'message' => 'Produk berhasil diperbarui.']);
}

if ($method === 'DELETE') {
    wajibAdmin();
    if ($id <= 0) kirimJson(400, ['success' => false, 'message' => 'Parameter id wajib diisi.']);
    $stmt = $conn->prepare("UPDATE products SET status='nonaktif' WHERE id_produk=? AND status='aktif'");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) kirimJson(500, ['success' => false, 'message' => 'Gagal menonaktifkan produk.']);
    $affected = $stmt->affected_rows;
    $stmt->close();
    if ($affected === 0) kirimJson(404, ['success' => false, 'message' => 'Produk tidak ditemukan atau sudah nonaktif.']);
    kirimJson(200, ['success' => true, 'message' => 'Produk berhasil dinonaktifkan.']);
}

header('Allow: GET, POST, PUT, DELETE');
kirimJson(405, ['success' => false, 'message' => 'Method tidak diizinkan.']);
