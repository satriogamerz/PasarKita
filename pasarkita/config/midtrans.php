<?php

// Konfigurasi Midtrans PasarKita
// Gunakan Sandbox untuk pengujian.

define('MIDTRANS_IS_PRODUCTION', false);

// Masukkan Client Key dari dashboard Midtrans.
define('MIDTRANS_CLIENT_KEY', 'Mid-client-xxxxxxxxx');

// Masukkan Server Key dari dashboard Midtrans.
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-xxxxxxxx');

// Masukkan Signature Secret Key dari dashboard Midtrans.
define('MIDTRANS_SIGNATURE_KEY', 'Mid-server-xxxxxxxxxxx');

// Alamat API Midtrans Sandbox.
define(
    'MIDTRANS_API_URL',
    'https://app.sandbox.midtrans.com/snap/v1/transactions'
);

// Alamat Snap.js Midtrans Sandbox.
define(
    'MIDTRANS_SNAP_URL',
    'https://app.sandbox.midtrans.com/snap/snap.js'
);