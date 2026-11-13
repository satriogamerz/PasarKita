-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2026 at 03:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pasarkita_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id_cart` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id_cart`, `id_user`, `id_produk`, `jumlah`, `updated_at`) VALUES
(3, 2, 5, 2, '2026-11-13 02:36:20');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id_kategori` int(10) UNSIGNED NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id_kategori`, `nama_kategori`, `deskripsi`, `created_at`) VALUES
(1, 'Kerajinan Tradisional', 'Produk kerajinan tangan tradisional', '2026-11-05 10:50:14'),
(2, 'Makanan Tradisional', 'Aneka makanan khas daerah', '2026-11-05 10:50:14'),
(3, 'Kain dan Busana', 'Kain, batik, dan busana tradisional', '2026-11-05 10:50:14'),
(4, 'Aksesori Lokal', 'Aksesori hasil karya pengrajin lokal', '2026-11-05 10:50:14');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id_pesanan` bigint(20) UNSIGNED NOT NULL,
  `kode_pesanan` varchar(30) NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `tanggal_pesanan` datetime NOT NULL DEFAULT current_timestamp(),
  `nama_penerima` varchar(100) NOT NULL,
  `no_hp_penerima` varchar(20) NOT NULL,
  `alamat_pengiriman` text NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `total_harga` decimal(14,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `status` enum('menunggu','diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id_pesanan`, `kode_pesanan`, `id_user`, `tanggal_pesanan`, `nama_penerima`, `no_hp_penerima`, `alamat_pengiriman`, `latitude`, `longitude`, `catatan`, `total_harga`, `status`, `created_at`) VALUES
(1, 'PK2026110795203589', 2, '2026-11-07 07:54:24', 'Muhamad Satrio', '085367808403', 'jln merpati 13, rawa makmur, kota bengkulu', NULL, NULL, 'kost putih hijau', 130000.00, 'diproses', '2026-11-07 00:54:24'),
(2, 'PK2026110870865885', 3, '2026-11-08 23:23:32', 'Arsa', '083825591282', 'jl bajak', NULL, NULL, 'rumah warna hijau', 55000.00, 'dibatalkan', '2026-11-08 16:23:32'),
(3, 'PK202611086848515B', 3, '2026-11-08 23:32:43', 'Arsa', '083825591282', 'jl bajak, dekat lapangan', NULL, NULL, 'rumah warna hijau', 55000.00, 'dibatalkan', '2026-11-08 16:32:43'),
(4, 'PK202611087633E7BF', 3, '2026-11-08 23:52:22', 'Arsa', '083825591282', 'jl bajak', NULL, NULL, 'rumah warna hijau', 55000.00, 'dibatalkan', '2026-11-08 16:52:22'),
(5, 'PK202611088D8476A8', 3, '2026-11-08 23:55:49', 'Arsa', '083825591282', 'jl bajak', NULL, NULL, 'rumah warna hijau', 55000.00, 'dibatalkan', '2026-11-08 16:55:49'),
(6, 'PK2026110932CE7175', 3, '2026-11-09 08:26:00', 'Arsa', '083825591282', 'jl bajak', NULL, NULL, 'rumah hijau', 55000.00, 'dibatalkan', '2026-11-09 01:26:00'),
(7, 'PK2026110963E524B0', 3, '2026-11-09 21:49:27', 'Arsa', '083825591282', 'jl bajak', NULL, NULL, 'rumah warna hijau', 1000.00, 'dibatalkan', '2026-11-09 14:49:27'),
(8, 'PK202611105B92C673', 3, '2026-11-10 07:48:08', 'Arsa', '083825591282', 'jl bajak', NULL, NULL, 'ruma warna hijau', 1000.00, 'dibatalkan', '2026-11-10 00:48:08'),
(9, 'PK20261110BF009AAA', 3, '2026-11-10 22:44:27', 'Arsa', '083825591282', 'jl bajak', -3.7846590, 102.2604237, 'rumah warna hijau masuk gang', 1000.00, 'dibatalkan', '2026-11-10 15:44:27'),
(10, 'PK20261110BE4AF9BB', 3, '2026-11-10 22:47:10', 'Arsa', '083825591282', 'jl bajak', -3.7847490, 102.2604203, 'rumah warna hijau', 1000.00, 'menunggu', '2026-11-10 15:47:10'),
(11, 'PK20261111EB915DBE', 3, '2026-11-11 12:49:27', 'Arsa', '083825591282', 'jl bali', -3.7864407, 102.2630397, '', 1000.00, 'menunggu', '2026-11-11 05:49:27'),
(12, 'PK20261111C3BA39A3', 3, '2026-11-11 12:50:28', 'Arsa', '083825591282', 'jln bali', -3.7864369, 102.2630586, 'kampus umb', 1000.00, 'menunggu', '2026-11-11 05:50:28'),
(13, 'PK2026111324C02B4B', 3, '2026-11-13 09:33:52', 'Arsa', '083825591282', 'jln rawa makmur', -3.7864222, 102.2630804, 'warna putih', 132000.00, 'diproses', '2026-11-13 02:33:52');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id_detail` bigint(20) UNSIGNED NOT NULL,
  `id_pesanan` bigint(20) UNSIGNED NOT NULL,
  `id_produk` int(10) UNSIGNED NOT NULL,
  `nama_produk_snapshot` varchar(150) NOT NULL,
  `jumlah` int(10) UNSIGNED NOT NULL,
  `harga` decimal(12,2) UNSIGNED NOT NULL,
  `subtotal` decimal(14,2) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id_detail`, `id_pesanan`, `id_produk`, `nama_produk_snapshot`, `jumlah`, `harga`, `subtotal`) VALUES
(1, 1, 3, 'Tas Anyaman Tradisional', 1, 95000.00, 95000.00),
(2, 1, 4, 'Camilan Tradisional', 1, 35000.00, 35000.00),
(3, 2, 5, 'Batik Tradisional Bengkulu', 1, 55000.00, 55000.00),
(4, 3, 5, 'Batik Tradisional Bengkulu', 1, 55000.00, 55000.00),
(5, 4, 5, 'Batik Tradisional Bengkulu', 1, 55000.00, 55000.00),
(6, 5, 5, 'Batik Tradisional Bengkulu', 1, 55000.00, 55000.00),
(7, 6, 5, 'Batik Tradisional Bengkulu', 1, 55000.00, 55000.00),
(8, 7, 5, 'Batik Tradisional Bengkulu', 1, 1000.00, 1000.00),
(9, 8, 5, 'Batik Tradisional Bengkulu', 1, 1000.00, 1000.00),
(10, 9, 5, 'Batik Tradisional Bengkulu', 1, 1000.00, 1000.00),
(11, 10, 5, 'Batik Tradisional Bengkulu', 1, 1000.00, 1000.00),
(12, 11, 5, 'Batik Tradisional Bengkulu', 1, 1000.00, 1000.00),
(13, 12, 5, 'Batik Tradisional Bengkulu', 1, 1000.00, 1000.00),
(14, 13, 3, 'Tas Anyaman Tradisional', 1, 95000.00, 95000.00),
(15, 13, 4, 'Camilan Tradisional', 1, 35000.00, 35000.00),
(16, 13, 5, 'Batik Tradisional Bengkulu', 2, 1000.00, 2000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id_pembayaran` bigint(20) UNSIGNED NOT NULL,
  `id_pesanan` bigint(20) UNSIGNED NOT NULL,
  `metode` enum('transfer_bank','cod','midtrans') NOT NULL,
  `jumlah_bayar` decimal(14,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `status` enum('belum_bayar','menunggu_verifikasi','lunas','ditolak') NOT NULL DEFAULT 'belum_bayar',
  `tanggal_bayar` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `snap_token` varchar(255) DEFAULT NULL,
  `redirect_url` text DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id_pembayaran`, `id_pesanan`, `metode`, `jumlah_bayar`, `status`, `tanggal_bayar`, `created_at`, `snap_token`, `redirect_url`, `transaction_id`) VALUES
(1, 1, 'transfer_bank', 130000.00, 'belum_bayar', NULL, '2026-11-07 00:54:24', NULL, NULL, NULL),
(2, 2, 'midtrans', 55000.00, 'belum_bayar', NULL, '2026-11-08 16:23:32', NULL, NULL, NULL),
(3, 3, 'midtrans', 55000.00, 'belum_bayar', NULL, '2026-11-08 16:32:43', NULL, NULL, NULL),
(4, 4, 'midtrans', 55000.00, 'belum_bayar', NULL, '2026-11-08 16:52:22', NULL, NULL, NULL),
(5, 5, 'midtrans', 55000.00, 'belum_bayar', NULL, '2026-11-08 16:55:49', '538716e1-3113-46d1-b8e2-822695db00ee', 'https://app.sandbox.midtrans.com/snap/v4/redirection/538716e1-3113-46d1-b8e2-822695db00ee', NULL),
(6, 6, 'midtrans', 55000.00, 'belum_bayar', NULL, '2026-11-09 01:26:00', '04602408-3cb4-4c8a-928a-acb9662e6c4b', 'https://app.sandbox.midtrans.com/snap/v4/redirection/04602408-3cb4-4c8a-928a-acb9662e6c4b', NULL),
(7, 7, 'midtrans', 1000.00, 'belum_bayar', NULL, '2026-11-09 14:49:27', '6c7d56ce-ef3c-44d2-8114-d4dd245f2a92', 'https://app.sandbox.midtrans.com/snap/v4/redirection/6c7d56ce-ef3c-44d2-8114-d4dd245f2a92', NULL),
(8, 8, 'midtrans', 1000.00, 'belum_bayar', NULL, '2026-11-10 00:48:08', '7a5aa2eb-7831-418e-a6cd-edf3330307c8', 'https://app.sandbox.midtrans.com/snap/v4/redirection/7a5aa2eb-7831-418e-a6cd-edf3330307c8', NULL),
(9, 9, 'midtrans', 1000.00, 'belum_bayar', NULL, '2026-11-10 15:44:27', '91ba0da9-243c-400c-8eeb-f4eb4c4c9aa8', 'https://app.sandbox.midtrans.com/snap/v4/redirection/91ba0da9-243c-400c-8eeb-f4eb4c4c9aa8', NULL),
(10, 10, 'cod', 1000.00, 'belum_bayar', NULL, '2026-11-10 15:47:10', NULL, NULL, NULL),
(11, 11, 'midtrans', 1000.00, 'belum_bayar', NULL, '2026-11-11 05:49:27', '5b3c1132-f324-45ff-843b-bb96f0bfab47', 'https://app.sandbox.midtrans.com/snap/v4/redirection/5b3c1132-f324-45ff-843b-bb96f0bfab47', NULL),
(12, 12, 'cod', 1000.00, 'belum_bayar', NULL, '2026-11-11 05:50:28', NULL, NULL, NULL),
(13, 13, 'cod', 132000.00, 'belum_bayar', NULL, '2026-11-13 02:33:52', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id_produk` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `id_kategori` int(10) UNSIGNED NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `stok` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id_produk`, `id_user`, `id_kategori`, `nama_produk`, `deskripsi`, `harga`, `stok`, `gambar`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Keranjang Anyaman Bambu', 'Keranjang anyaman bambu buatan pengrajin lokal. Cocok untuk dekorasi dan penyimpanan.', 75000.00, 20, 'https://images.unsplash.com/photo-1590736969955-71cc94901144?auto=format&fit=crop&w=900&q=80', 'aktif', '2026-11-05 10:50:57', NULL),
(2, 1, 3, 'Kain Batik Nusantara', 'Kain batik motif tradisional dengan warna menarik. Cocok untuk busana maupun koleksi.', 125000.00, 15, 'https://images.unsplash.com/photo-1584865288642-42078afe6942?auto=format&fit=crop&w=900&q=80', 'aktif', '2026-11-05 10:50:57', NULL),
(3, 1, 1, 'Tas Anyaman Tradisional', 'Tas anyaman bergaya etnik yang ringan dan dapat digunakan sehari-hari.', 95000.00, 10, 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=900&q=80', 'aktif', '2026-11-05 10:50:57', '2026-11-13 02:33:52'),
(4, 1, 2, 'Camilan Tradisional', 'Camilan khas daerah untuk teman bersantai bersama keluarga.', 35000.00, 28, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=900&q=80', 'aktif', '2026-11-05 10:50:57', '2026-11-13 02:33:52'),
(5, 2, 3, 'Batik Tradisional Bengkulu', 'Batik Bersurek Tradisional Bengkulu', 1000.00, 2, 'uploads/produk/produk_6aeec70eedb4f4.58691419.jpg', 'aktif', '2026-11-07 06:37:02', '2026-11-13 02:33:52'),
(6, 2, 3, 'Batik Tradisional Bengkulu', 'Batik Bersurek Bengkulu', 55.00, 15, 'uploads/produk/produk_6aef5d18614b51.17726081.jpg', 'nonaktif', '2026-11-07 17:17:12', '2026-11-07 17:21:51'),
(7, 2, 4, 'Batik Tradisional Bengkulu', 'xx', 78000.00, 11, 'uploads/produk/produk_6aef5eee08a342.10414326.jpg', 'nonaktif', '2026-11-07 17:25:02', '2026-11-07 17:25:08'),
(8, 2, 1, 'Uji API PasarKita Updated', 'Produk percobaan yang sudah diperbarui melalui REST API.', 30000.00, 15, 'https://placehold.co/600x400', 'nonaktif', '2026-11-12 05:55:05', '2026-11-12 14:20:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(10) UNSIGNED NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `role` enum('admin','penjual','pembeli') NOT NULL DEFAULT 'pembeli',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `no_hp`, `alamat`, `foto_profil`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Penjual Demo', 'demo@pasarkita.local', '$2y$10$8KZgxkXLdn7tzjn.Lx7DMulFmOkrCfnUyaGgh.WQ7Y1nZw835fo0S', NULL, NULL, NULL, 'penjual', '2026-11-05 10:50:57', NULL),
(2, 'Muhamad Satrio', 'msatrio890@gmail.com', '$2y$10$nxsIqePEkifxzNClFPNUNOwfWvXUkePluBHrWC6LZqJxo43tIpkQS', '085367808403', NULL, 'uploads/profil/profil_2_fa920493bb66f292.jpg', 'admin', '2026-11-06 06:18:30', '2026-11-11 06:16:25'),
(3, 'Arsa', 'Arsa123@id.com', '$2y$10$erLL0f6Uum0V8GbwMEp8M.KgMrexaIdUNhZ1xNbpnQVmZ29qx0U2S', '083825591282', 'jl. bajak', 'uploads/profil/profil_3_0bbc5af9bb829a2f.jpg', 'pembeli', '2026-11-07 17:31:52', '2026-11-11 10:59:53'),
(4, 'Satrio', 'satrio890@gmail.com', '$2y$10$Ul1v.Ku.6ypKCq8gtw./buXiCh.fmlyKIVyhlhqG9BLfgqOAcjcSG', '082278822152', NULL, NULL, 'pembeli', '2026-11-11 11:04:10', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id_cart`),
  ADD UNIQUE KEY `uq_user_produk` (`id_user`,`id_produk`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  ADD KEY `idx_orders_user` (`id_user`),
  ADD KEY `idx_orders_date` (`tanggal_pesanan`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_order_items_order` (`id_pesanan`),
  ADD KEY `idx_order_items_product` (`id_produk`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id_pembayaran`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id_produk`),
  ADD KEY `fk_products_user` (`id_user`),
  ADD KEY `idx_products_category` (`id_kategori`),
  ADD KEY `idx_products_status` (`status`),
  ADD KEY `idx_products_name` (`nama_produk`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id_cart` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_kategori` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id_pesanan` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id_detail` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id_pembayaran` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id_produk` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`id_pesanan`) REFERENCES `orders` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`id_produk`) REFERENCES `products` (`id_produk`) ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`id_kategori`) REFERENCES `categories` (`id_kategori`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
