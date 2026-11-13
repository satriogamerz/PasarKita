<?php
require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, kembali ke beranda
if (!empty($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = "";
$nama = "";
$email = "";
$no_hp = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    // Validasi input
    if ($nama === "" || $email === "" || $password === "") {
        $error = "Nama, email, dan password wajib diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";

    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";

    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak sama.";

    } else {

        // Periksa apakah email sudah digunakan
        $check = $conn->prepare(
            "SELECT id_user FROM users WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = "Email sudah terdaftar.";

        } else {

            // Enkripsi password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Simpan akun baru sebagai pembeli
$stmt = $conn->prepare(
    "INSERT INTO users (nama, email, password, no_hp, role)
     VALUES (?, ?, ?, ?, 'pembeli')"
);

$stmt->bind_param(
    "ssss",
    $nama,
    $email,
    $passwordHash,
    $no_hp
);

            if ($stmt->execute()) {

                $stmt->close();
                $check->close();

                header("Location: login.php?registered=1");
exit;

            } else {

                $error = "Registrasi gagal. Silakan coba kembali.";

            }

            $stmt->close();
        }

        $check->close();
    }
}

$pageTitle = "Daftar Akun";
require "includes/header.php";
?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-5">

            <div class="card border-0 shadow rounded-4">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <i class="bi bi-person-plus display-4 text-success"></i>

                        <h2 class="fw-bold mt-2">Daftar Akun</h2>

                        <p class="text-secondary">
                            Bergabunglah dengan PasarKita
                        </p>

                    </div>

                    <?php if ($error): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">Nama Lengkap</label>

                            <input
                                type="text"
                                name="nama"
                                class="form-control"
                                placeholder="Masukkan nama lengkap"
                                value="<?= htmlspecialchars($nama) ?>"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">Email</label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="contoh@email.com"
                                value="<?= htmlspecialchars($email) ?>"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">Nomor HP</label>

                            <input
                                type="text"
                                name="no_hp"
                                class="form-control"
                                placeholder="Masukkan nomor HP"
                                value="<?= htmlspecialchars($no_hp) ?>"
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">Password</label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Minimal 8 karakter"
                                minlength="8"
                                required
                            >

                        </div>

                        <div class="mb-4">

                            <label class="form-label">Konfirmasi Password</label>

                            <input
                                type="password"
                                name="konfirmasi_password"
                                class="form-control"
                                placeholder="Ulangi password"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-pasar w-100 py-2">

                            <i class="bi bi-person-check me-1"></i>
                            Daftar Sekarang

                        </button>

                    </form>

                    <div class="text-center mt-4">

                        <span class="text-secondary">
                            Sudah punya akun?
                        </span>

                        <a href="login.php" class="text-success fw-semibold">
                            Login di sini
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require "includes/footer.php"; ?>