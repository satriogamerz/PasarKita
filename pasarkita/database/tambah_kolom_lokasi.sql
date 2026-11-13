-- Jalankan satu kali pada database pasarkita_db.
ALTER TABLE orders
    ADD COLUMN latitude DECIMAL(10,7) NULL AFTER alamat_pengiriman,
    ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude;
