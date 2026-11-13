<?php
require_once "config/database.php";
require_once "config/midtrans.php";
require_once "includes/cart_sync.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wajib login
if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}
// Periksa token keamanan
if (
    empty($_SESSION['csrf_token']) ||
    !isset($_POST['csrf_token']) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token']
    )
) {
    die("Permintaan tidak valid. Silakan kembali dan coba lagi.");
}

// Ambil data pembeli
$id_user = (int) $_SESSION['user']['id_user'];

$nama = trim($_POST['nama_penerima'] ?? '');
$no_hp = trim($_POST['no_hp_penerima'] ?? '');
$alamat = trim($_POST['alamat_pengiriman'] ?? '');
$catatan = trim($_POST['catatan'] ?? '');
$latitude_input = trim($_POST['latitude'] ?? '');
$longitude_input = trim($_POST['longitude'] ?? '');
$metode_input = $_POST['metode'] ?? '';

$modeBeliSekarang = !empty($_SESSION['mode_beli_sekarang']) && !empty($_SESSION['keranjang_beli_sekarang']);
$keranjang = $modeBeliSekarang ? $_SESSION['keranjang_beli_sekarang'] : ($_SESSION['keranjang'] ?? []);

// Validasi data
if (
    $nama === '' ||
    $no_hp === '' ||
    $alamat === '' ||
    !in_array($metode_input, ['transfer_bank', 'midtrans', 'cod'], true) ||
    empty($keranjang)
) {
    die("Data checkout tidak lengkap atau tidak valid.");
}

if (
    strlen($nama) > 100 ||
    strlen($no_hp) > 20 ||
    strlen($catatan) > 65535
) {
    die("Data yang dimasukkan terlalu panjang.");
}

// Validasi koordinat: boleh kosong jika pembeli tidak memilih titik peta.
$latitude = null;
$longitude = null;
if ($latitude_input !== '' || $longitude_input !== '') {
    if ($latitude_input === '' || $longitude_input === '' ||
        !is_numeric($latitude_input) || !is_numeric($longitude_input)) {
        die("Titik lokasi tidak valid. Silakan pilih ulang pada peta.");
    }
    $latitude = (float)$latitude_input;
    $longitude = (float)$longitude_input;
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        die("Koordinat lokasi di luar rentang yang valid.");
    }
}

// Transfer bank diproses melalui Midtrans
$metode = ($metode_input === 'cod') ? 'cod' : 'midtrans';

$items = [];
$total = 0;
$id_pesanan = 0;
$kode_pesanan = '';

try {
    $conn->begin_transaction();

    // Kunci data produk selama proses checkout
    $ids = array_map('intval', array_keys($keranjang));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare(
        "SELECT id_produk, nama_produk, harga, stok
         FROM products
         WHERE status = 'aktif'
         AND id_produk IN ($placeholders)
         FOR UPDATE"
    );

    $stmt->bind_param($types, ...$ids);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($produk = $result->fetch_assoc()) {
        $id = (int) $produk['id_produk'];
        $jumlah = (int) ($keranjang[$id] ?? 0);

        if ($jumlah < 1 || $jumlah > (int) $produk['stok']) {
            throw new Exception(
                "Stok produk " . $produk['nama_produk'] .
                " tidak mencukupi."
            );
        }

        $produk['jumlah'] = $jumlah;
        $produk['subtotal'] = $jumlah * (float) $produk['harga'];

        $total += $produk['subtotal'];
        $items[] = $produk;
    }

    $stmt->close();

    if (count($items) !== count($keranjang)) {
        throw new Exception("Ada produk yang sudah tidak tersedia.");
    }

    // Buat kode pesanan
    $kode_pesanan = 'PK' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));

    // Simpan pesanan utama
    $stmt = $conn->prepare(
        "INSERT INTO orders
        (kode_pesanan, id_user, nama_penerima, no_hp_penerima,
         alamat_pengiriman, latitude, longitude, catatan, total_harga)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "sisssddsd",
        $kode_pesanan,
        $id_user,
        $nama,
        $no_hp,
        $alamat,
        $latitude,
        $longitude,
        $catatan,
        $total
    );

    $stmt->execute();
    $id_pesanan = $conn->insert_id;
    $stmt->close();

    // Simpan rincian produk dan kurangi stok
    $stmt_item = $conn->prepare(
        "INSERT INTO order_items
        (id_pesanan, id_produk, nama_produk_snapshot,
         jumlah, harga, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt_stok = $conn->prepare(
        "UPDATE products
         SET stok = stok - ?
         WHERE id_produk = ? AND stok >= ?"
    );

    foreach ($items as $item) {
        $id_produk = (int) $item['id_produk'];
        $nama_produk = $item['nama_produk'];
        $jumlah = (int) $item['jumlah'];
        $harga = (float) $item['harga'];
        $subtotal = (float) $item['subtotal'];

        $stmt_item->bind_param(
            "iisidd",
            $id_pesanan,
            $id_produk,
            $nama_produk,
            $jumlah,
            $harga,
            $subtotal
        );

        $stmt_item->execute();

        $stmt_stok->bind_param(
            "iii",
            $jumlah,
            $id_produk,
            $jumlah
        );

        $stmt_stok->execute();

        if ($stmt_stok->affected_rows !== 1) {
            throw new Exception("Gagal memperbarui stok produk.");
        }
    }

    $stmt_item->close();
    $stmt_stok->close();

    // Simpan data pembayaran
    $stmt = $conn->prepare(
        "INSERT INTO payments
        (id_pesanan, metode, jumlah_bayar)
        VALUES (?, ?, ?)"
    );

    $stmt->bind_param(
        "isd",
        $id_pesanan,
        $metode,
        $total
    );

    $stmt->execute();
    $stmt->close();

    // Simpan semua data pesanan terlebih dahulu
    $conn->commit();

} catch (Throwable $e) {
    $conn->rollback();

    $_SESSION['flash_error'] = $e->getMessage();

    header("Location: keranjang.php");
    exit;
}

// Simpan kode pesanan untuk halaman berikutnya
$_SESSION['pesanan_terakhir'] = $kode_pesanan;

// Kosongkan keranjang setelah pesanan berhasil dibuat
if ($modeBeliSekarang) { unset($_SESSION['keranjang_beli_sekarang'], $_SESSION['mode_beli_sekarang']); }
else { unset($_SESSION['keranjang']); if (!empty($_SESSION['user']['id_user'])) { $hapusCart=$conn->prepare('DELETE FROM cart_items WHERE id_user=?'); $hapusCart->bind_param('i', $_SESSION['user']['id_user']); $hapusCart->execute(); $hapusCart->close(); } }
unset($_SESSION['csrf_token']);

// Jika COD, langsung ke halaman sukses
if ($metode === 'cod') {
    header(
        "Location: pesanan_sukses.php?kode=" .
        urlencode($kode_pesanan)
    );
    exit;
}

// ========================================
// PEMBAYARAN MIDTRANS SNAP
// ========================================

$gross_amount = (int) round($total);

$item_details = [];

foreach ($items as $item) {
    $item_details[] = [
        'id' => (string) $item['id_produk'],
        'price' => (int) round((float) $item['harga']),
        'quantity' => (int) $item['jumlah'],
        'name' => substr($item['nama_produk'], 0, 50)
    ];
}

$payload = [
    'transaction_details' => [
        'order_id' => $kode_pesanan,
        'gross_amount' => $gross_amount
    ],
    'customer_details' => [
        'first_name' => $nama,
        'email' => $_SESSION['user']['email'] ?? '',
        'phone' => $no_hp,
        'shipping_address' => [
            'first_name' => $nama,
            'phone' => $no_hp,
            'address' => $alamat
        ]
    ],
    'item_details' => $item_details
];

// Pastikan ekstensi cURL tersedia
if (!function_exists('curl_init')) {
    die(
        "Pesanan berhasil dibuat dengan kode " .
        htmlspecialchars($kode_pesanan) .
        ", tetapi pembayaran belum dapat dibuka. " .
        "Aktifkan ekstensi cURL PHP terlebih dahulu."
    );
}

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => MIDTRANS_API_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json'
    ],
    CURLOPT_USERPWD => MIDTRANS_SERVER_KEY . ':',
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

curl_close($ch);

// Periksa respons Midtrans
if ($response === false || $http_code < 200 || $http_code >= 300) {

    echo "<h3>Pembayaran Midtrans Gagal</h3>";

    echo "<p>Kode pesanan: " .
        htmlspecialchars($kode_pesanan) . "</p>";

    echo "<p>HTTP Status: " . (int)$http_code . "</p>";

    echo "<p>cURL Error: " .
        htmlspecialchars($curl_error) . "</p>";

    echo "<p>Respons Midtrans:</p>";

    echo "<pre>" .
        htmlspecialchars((string)$response) .
        "</pre>";

    exit;
}

$data = json_decode($response, true);

if (empty($data['token']) || empty($data['redirect_url'])) {
    error_log("Respons Midtrans tidak lengkap: " . $response);

    die(
        "Pesanan berhasil dibuat dengan kode " .
        htmlspecialchars($kode_pesanan) .
        ", tetapi respons pembayaran Midtrans tidak lengkap."
    );
}

// Simpan Snap Token dan URL pembayaran
$stmt = $conn->prepare(
    "UPDATE payments
     SET snap_token = ?, redirect_url = ?
     WHERE id_pesanan = ?"
);

$stmt->bind_param(
    "ssi",
    $data['token'],
    $data['redirect_url'],
    $id_pesanan
);

$stmt->execute();
$stmt->close();

// Arahkan pembeli ke halaman pembayaran Midtrans
header("Location: " . $data['redirect_url']);
exit;