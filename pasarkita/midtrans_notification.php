<?php

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/midtrans.php";

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed");
}

// Ambil notifikasi dari Midtrans
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!is_array($data)) {
    http_response_code(400);
    exit("Invalid notification");
}

// Ambil informasi transaksi
$order_id = $data['order_id'] ?? '';
$status_code = $data['status_code'] ?? '';
$gross_amount = $data['gross_amount'] ?? '';
$signature_key = $data['signature_key'] ?? '';

if (
    $order_id === '' ||
    $status_code === '' ||
    $gross_amount === '' ||
    $signature_key === ''
) {
    http_response_code(400);
    exit("Incomplete notification");
}

// =====================================
// 1. VALIDASI SIGNATURE MIDTRANS
// =====================================

$expected_signature = hash(
    'sha512',
    $order_id .
    $status_code .
    $gross_amount .
    MIDTRANS_SERVER_KEY
);

if (!hash_equals($expected_signature, $signature_key)) {
    http_response_code(403);
    exit("Invalid signature");
}

// =====================================
// 2. AMBIL STATUS TRANSAKSI
// =====================================

$transaction_status = $data['transaction_status'] ?? '';
$fraud_status = $data['fraud_status'] ?? '';
$transaction_id = $data['transaction_id'] ?? null;

$transaction_started = false;

try {

    // =====================================
    // 3. CARI PESANAN
    // =====================================

    $stmt = $conn->prepare(
        "SELECT
            p.id_pesanan,
            p.metode,
            p.status AS payment_status,
            o.total_harga
         FROM payments p
         INNER JOIN orders o
            ON p.id_pesanan = o.id_pesanan
         WHERE o.kode_pesanan = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $order_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();

    $stmt->close();

    // Notifikasi pengujian yang tidak mempunyai
    // pesanan asli tetap diakui jika signature valid.
    if (!$payment) {
        http_response_code(200);
        exit("Notification received");
    }

    // Pastikan metode pembayaran adalah Midtrans
    if ($payment['metode'] !== 'midtrans') {
        http_response_code(200);
        exit("Notification ignored");
    }

    // =====================================
    // 4. VALIDASI JUMLAH PEMBAYARAN
    // =====================================

    $expected_amount = number_format(
        (float) $payment['total_harga'],
        2,
        '.',
        ''
    );

    if ((float) $expected_amount !== (float) $gross_amount) {
        http_response_code(400);
        exit("Amount mismatch");
    }

    // =====================================
    // 5. TENTUKAN STATUS PEMBAYARAN
    // =====================================

    $payment_status = null;
    $order_status = null;

    if (
        $transaction_status === 'settlement' ||
        (
            $transaction_status === 'capture' &&
            $fraud_status === 'accept'
        )
    ) {
        $payment_status = 'lunas';
        $order_status = 'diproses';

    } elseif ($transaction_status === 'pending') {

        $payment_status = 'menunggu_verifikasi';

    } elseif (
        in_array(
            $transaction_status,
            ['deny', 'cancel', 'expire'],
            true
        )
    ) {
        $payment_status = 'ditolak';

    } else {
        http_response_code(200);
        exit("Notification received");
    }

    // =====================================
    // 6. MULAI TRANSAKSI DATABASE
    // =====================================

    $conn->begin_transaction();
    $transaction_started = true;

    $id_pesanan = (int) $payment['id_pesanan'];

    // Jangan menurunkan status pembayaran
    // jika pesanan sebelumnya sudah lunas.
    if ($payment['payment_status'] === 'lunas') {

        $conn->commit();
        $transaction_started = false;

        http_response_code(200);
        exit("Payment already settled");
    }

    // =====================================
    // 7. UPDATE STATUS PEMBAYARAN
    // =====================================

    $stmt = $conn->prepare(
        "UPDATE payments
         SET
            status = ?,
            transaction_id = ?,
            tanggal_bayar = CASE
                WHEN ? = 'lunas' THEN NOW()
                ELSE tanggal_bayar
            END
         WHERE id_pesanan = ?"
    );

    $stmt->bind_param(
        "sssi",
        $payment_status,
        $transaction_id,
        $payment_status,
        $id_pesanan
    );

    $stmt->execute();
    $stmt->close();

    // =====================================
    // 8. UPDATE STATUS PESANAN
    // =====================================

    if ($order_status === 'diproses') {

        $stmt = $conn->prepare(
            "UPDATE orders
             SET status = 'diproses'
             WHERE id_pesanan = ?"
        );

        $stmt->bind_param("i", $id_pesanan);
        $stmt->execute();
        $stmt->close();
    }

    // =====================================
    // 9. SIMPAN PERUBAHAN
    // =====================================

    $conn->commit();
    $transaction_started = false;

    http_response_code(200);
    echo "OK";

} catch (Throwable $e) {

    if ($transaction_started) {
        $conn->rollback();
    }

    error_log(
        "Midtrans notification error: " . $e->getMessage()
    );

    http_response_code(500);
    echo "Internal server error";
}