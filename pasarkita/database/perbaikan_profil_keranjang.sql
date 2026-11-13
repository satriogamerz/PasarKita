-- Jalankan satu kali di phpMyAdmin pada database pasarkita_db.
ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) NULL AFTER alamat;

CREATE TABLE IF NOT EXISTS cart_items (
    id_cart INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_produk INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_produk (id_user, id_produk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
