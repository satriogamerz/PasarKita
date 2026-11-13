<?php
require_once "config/database.php";
require_once "includes/cart_sync.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Arahkan pengguna sesuai role jika sudah login
if (!empty($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = "";
$email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === "" || $password === "") {

        $error = "Email dan password wajib diisi.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id_user, nama, email, password, no_hp, role, foto_profil
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {

            // Mencegah session fixation
            session_regenerate_id(true);

            // Simpan informasi pengguna ke session
            $_SESSION['user'] = [
                'id_user' => $user['id_user'],
                'nama' => $user['nama'],
                'email' => $user['email'],
                'no_hp' => $user['no_hp'],
                'role' => $user['role'],
                'foto_profil' => $user['foto_profil'] ?? null
            ];
            $_SESSION['keranjang'] = gabungKeranjangAkun($conn, (int)$user['id_user'], $_SESSION['keranjang'] ?? []);

            // Kembali ke halaman detail jika login diminta saat akan berbelanja.
            $kembali = $_GET['kembali'] ?? '';
            if ($user['role'] !== 'admin' && preg_match('/^detail\.php\?id=([0-9]+)$/', $kembali)) {
                header('Location: ' . $kembali);
            } elseif ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }

            exit;

        } else {

            $error = "Email atau password salah.";

        }
    }
}

$pageTitle = "Login";
require "includes/header.php";
?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-5">

            <div class="card border-0 shadow rounded-4">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <i class="bi bi-person-circle display-4 text-success"></i>

                        <h2 class="fw-bold mt-2">Login PasarKita</h2>

                        <p class="text-secondary">
                            Silakan masuk ke akun Anda
                        </p>

                    </div>

                    <?php if (!empty($_GET['perlu_login'])): ?>
                        <div class="alert alert-info">Silakan login terlebih dahulu sebelum menambahkan produk ke keranjang atau membeli.</div>
                    <?php endif; ?>

                    <?php if (!empty($_GET['registered'])): ?>

                        <div class="alert alert-success">
                            Registrasi berhasil! Silakan login menggunakan akun Anda.
                        </div>

                    <?php endif; ?>

                    <?php if ($error): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">Email</label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="Masukkan email"
                                value="<?= htmlspecialchars($email) ?>"
                                required
                            >

                        </div>

                        <div class="mb-4">

                            <label class="form-label">Password</label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Masukkan password"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-pasar w-100 py-2">

                            <i class="bi bi-box-arrow-in-right me-1"></i>
                            Login

                        </button>

                    </form>

                    <div class="text-center mt-4">

                        <span class="text-secondary">
                            Belum punya akun?
                        </span>

                        <a href="register.php" class="text-success fw-semibold">
                            Daftar sekarang
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require "includes/footer.php"; ?>