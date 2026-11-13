<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "pasarkita_db";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    die("Koneksi database gagal. Periksa config/database.php: " . htmlspecialchars($conn->connect_error));
}
$conn->set_charset("utf8mb4");