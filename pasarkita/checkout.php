<?php
require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan pembeli sudah login
if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Pastikan keranjang tidak kosong
$modeBeliSekarang = !empty($_SESSION['mode_beli_sekarang']) && !empty($_SESSION['keranjang_beli_sekarang']);
$keranjang = $modeBeliSekarang ? $_SESSION['keranjang_beli_sekarang'] : ($_SESSION['keranjang'] ?? []);

if (empty($keranjang)) {
    header("Location: keranjang.php");
    exit;
}

$user = $_SESSION['user'];

$items = [];
$total = 0;

// Ambil data produk terbaru dari database
$ids = array_map('intval', array_keys($keranjang));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$stmt = $conn->prepare(
    "SELECT id_produk, nama_produk, harga, stok, gambar
     FROM products
     WHERE status = 'aktif'
     AND id_produk IN ($placeholders)"
);

$stmt->bind_param($types, ...$ids);
$stmt->execute();

$result = $stmt->get_result();

while ($produk = $result->fetch_assoc()) {
    $id = (int)$produk['id_produk'];
    $jumlah = (int)$keranjang[$id];

    if ($jumlah < 1 || $jumlah > (int)$produk['stok']) {
        $_SESSION['flash_error'] =
            "Jumlah produk " . $produk['nama_produk'] .
            " tidak valid atau stok tidak mencukupi.";

        header("Location: keranjang.php");
        exit;
    }

    $produk['jumlah'] = $jumlah;
    $produk['subtotal'] = $jumlah * (float)$produk['harga'];

    $total += $produk['subtotal'];
    $items[] = $produk;
}

$stmt->close();

// Pastikan semua produk masih tersedia
if (count($items) !== count($keranjang)) {
    $_SESSION['flash_error'] =
        "Ada produk yang sudah tidak tersedia. Periksa keranjang kamu.";

    header("Location: keranjang.php");
    exit;
}

$pageTitle = "Checkout";
require "includes/header.php";
?>

<div class="container py-5">

    <h1 class="section-title mb-4">Checkout Pesanan</h1>

    <?php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<form action="proses_checkout.php" method="POST">
    <input type="hidden" name="csrf_token"
           value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="row g-4">

            <div class="col-lg-7">

                <div class="card border-0 shadow-sm rounded-4 mb-4">

                    <div class="card-body p-4">

                        <h5 class="mb-4">Informasi Penerima</h5>

                        <div class="mb-3">
                            <label class="form-label">Nama Penerima</label>

                            <input
                                type="text"
                                name="nama_penerima"
                                class="form-control"
                                maxlength="100"
                                value="<?= htmlspecialchars($user['nama']) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nomor HP</label>

                            <input
                                type="tel"
                                name="no_hp_penerima"
                                class="form-control"
                                maxlength="20"
                                value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Pengiriman</label>

                            <textarea
                                name="alamat_pengiriman"
                                id="alamat_pengiriman"
                                class="form-control"
                                rows="4"
                                required
                                placeholder="Masukkan alamat lengkap, kecamatan, kota, dan kode pos"
                            ></textarea>
                            <div class="form-text">Tulis alamat lengkap, lalu tentukan titik lokasi pada peta.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Titik Lokasi Pengiriman</label>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-outline-success btn-sm" id="btnLokasiSaya">
                                    <i class="bi bi-geo-alt"></i> Gunakan Lokasi Saya
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetPeta">
                                    Atur Ulang Peta
                                </button>
                            </div>
                            <div id="petaPengiriman" style="height:320px;border-radius:12px;overflow:hidden;border:1px solid #dee2e6"></div>
                            <div class="form-text mt-2" id="statusLokasi">Klik peta untuk memilih titik, atau gunakan lokasi perangkat.</div>
                            <input type="hidden" name="latitude" id="latitude" value="">
                            <input type="hidden" name="longitude" id="longitude" value="">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan (Opsional)</label>

                            <textarea
                                name="catatan"
                                class="form-control"
                                rows="2"
                                placeholder="Contoh: Rumah pagar warna putih"
                            ></textarea>
                        </div>

                    </div>

                </div>

                <div class="card border-0 shadow-sm rounded-4">

                    <div class="card-body p-4">

                        <h5 class="mb-3">Metode Pembayaran</h5>

                        <div class="form-check border rounded-3 p-3 mb-3">

                            <input
                                class="form-check-input"
                                type="radio"
                                name="metode"
                                id="transfer_bank"
                                value="transfer_bank"
                                checked
                                required
                            >

                            <label class="form-check-label ms-2" for="transfer_bank">
                                <i class="bi bi-bank me-2"></i>
                               Transfer Bank / QRIS (Midtrans)
                            </label>

                        </div>

                        <div class="form-check border rounded-3 p-3">

                            <input
                                class="form-check-input"
                                type="radio"
                                name="metode"
                                id="cod"
                                value="cod"
                                required
                            >

                            <label class="form-check-label ms-2" for="cod">
                                <i class="bi bi-cash-coin me-2"></i>
                                COD (Bayar di Tempat)
                            </label>

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-lg-5">

                <div class="card border-0 shadow-sm rounded-4">

                    <div class="card-body p-4">

                        <h5 class="mb-4">Ringkasan Pesanan</h5>

                        <?php foreach ($items as $item): ?>

                            <div class="d-flex gap-3 mb-3">

                                <img
                                    src="<?= htmlspecialchars($item['gambar'] ?: 'https://placehold.co/100x100?text=Produk') ?>"
                                    alt=""
                                    width="65"
                                    height="65"
                                    style="object-fit:cover;border-radius:10px"
                                >

                                <div class="flex-grow-1">

                                    <div class="fw-semibold">
                                        <?= htmlspecialchars($item['nama_produk']) ?>
                                    </div>

                                    <small class="text-secondary">
                                        <?= $item['jumlah'] ?> x
                                        Rp<?= number_format((float)$item['harga'], 0, ',', '.') ?>
                                    </small>

                                </div>

                                <div class="fw-semibold">
                                    Rp<?= number_format((float)$item['subtotal'], 0, ',', '.') ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">

                            <span class="text-secondary">Total Pembayaran</span>

                            <span class="price fs-4">
                                Rp<?= number_format($total, 0, ',', '.') ?>
                            </span>

                        </div>

                        <button type="submit" class="btn btn-pasar w-100">
                            Buat Pesanan
                        </button>

                        <a href="keranjang.php"
                           class="btn btn-outline-secondary w-100 mt-2">
                            Kembali ke Keranjang
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const center = [-2.5, 118];
    const map = L.map('petaPengiriman').setView(center, 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const lat = document.getElementById('latitude');
    const lng = document.getElementById('longitude');
    const status = document.getElementById('statusLokasi');
    let marker = null;

    function pilihTitik(a, b, zoom) {
        if (!marker) {
            marker = L.marker([a, b], {draggable:true}).addTo(map);
            marker.on('dragend', function () {
                const p = marker.getLatLng();
                lat.value = p.lat.toFixed(7);
                lng.value = p.lng.toFixed(7);
                status.textContent = 'Titik lokasi diperbarui.';
            });
        } else marker.setLatLng([a,b]);
        lat.value = Number(a).toFixed(7);
        lng.value = Number(b).toFixed(7);
        map.setView([a,b], zoom || 16);
        status.textContent = 'Titik lokasi dipilih. Geser penanda jika perlu.';
    }

    map.on('click', e => pilihTitik(e.latlng.lat, e.latlng.lng, 16));

    document.getElementById('btnLokasiSaya').addEventListener('click', function () {
        if (!navigator.geolocation) {
            status.textContent = 'Geolokasi tidak didukung. Pilih titik langsung pada peta.';
            return;
        }
        status.textContent = 'Meminta izin lokasi perangkat...';
        navigator.geolocation.getCurrentPosition(function (pos) {
            pilihTitik(pos.coords.latitude, pos.coords.longitude, 17);
        }, function (err) {
            status.textContent = err.code === 1
                ? 'Izin lokasi ditolak. Silakan pilih titik manual pada peta.'
                : 'Lokasi tidak berhasil dideteksi. Silakan pilih titik manual.';
        }, {enableHighAccuracy:true, timeout:15000, maximumAge:60000});
    });

    document.getElementById('btnResetPeta').addEventListener('click', function () {
        if (marker) map.removeLayer(marker);
        marker = null; lat.value = ''; lng.value = '';
        map.setView(center, 5);
        status.textContent = 'Pilih titik lokasi pada peta.';
    });
    setTimeout(() => map.invalidateSize(), 300);
});
</script>

<?php require "includes/footer.php"; ?>