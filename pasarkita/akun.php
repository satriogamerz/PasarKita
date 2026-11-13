<?php
require_once __DIR__ . "/config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['user']['id_user'];
$error = "";
$success = "";

// Ambil data terbaru dari database
$stmt = $conn->prepare(
    "SELECT nama, email, no_hp, alamat, password, foto_profil
     FROM users
     WHERE id_user = ?
     LIMIT 1"
);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$akun = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$akun) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['csrf_akun'])) {
    $_SESSION['csrf_akun'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_akun'], $csrf)) {
        $error = "Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.";
    } else {
        $aksi = $_POST['aksi'] ?? '';

        if ($aksi === 'foto') {
            if (!isset($_FILES['foto_profil']) || $_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Pilih foto profil terlebih dahulu.';
            } elseif ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal 2 MB.';
            } else {
                $tmp = $_FILES['foto_profil']['tmp_name'];
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                $exts = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
                if (!isset($exts[$mime])) { $error = 'Format foto harus JPG, PNG, atau WEBP.'; }
                else {
                    $folder = __DIR__ . '/uploads/profil';
                    if (!is_dir($folder)) mkdir($folder, 0755, true);
                    $namaFile = 'profil_' . $id_user . '_' . bin2hex(random_bytes(8)) . '.' . $exts[$mime];
                    if (move_uploaded_file($tmp, $folder . '/' . $namaFile)) {
                        $pathFoto = 'uploads/profil/' . $namaFile;
                        $up = $conn->prepare('UPDATE users SET foto_profil=? WHERE id_user=?');
                        $up->bind_param('si', $pathFoto, $id_user);
                        if ($up->execute()) { if (!empty($akun['foto_profil']) && is_file(__DIR__ . '/' . $akun['foto_profil'])) @unlink(__DIR__ . '/' . $akun['foto_profil']); $akun['foto_profil']=$pathFoto; $_SESSION['user']['foto_profil']=$pathFoto; $success='Foto profil berhasil diperbarui.'; }
                        else { @unlink($folder . '/' . $namaFile); $error='Foto profil gagal disimpan.'; }
                        $up->close();
                    } else { $error='Foto gagal diunggah. Periksa izin folder uploads.'; }
                }
            }
        } elseif ($aksi === 'profil') {
            $nama = trim($_POST['nama'] ?? '');
            $no_hp = trim($_POST['no_hp'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');

            if ($nama === '' || strlen($nama) > 100) {
                $error = "Nama wajib diisi dan maksimal 100 karakter.";
            } elseif (strlen($no_hp) > 20) {
                $error = "Nomor HP maksimal 20 karakter.";
            } elseif (strlen($alamat) > 1000) {
                $error = "Alamat maksimal 1.000 karakter.";
            } else {
                $stmt = $conn->prepare(
                    "UPDATE users SET nama = ?, no_hp = ?, alamat = ?
                     WHERE id_user = ?"
                );
                $stmt->bind_param("sssi", $nama, $no_hp, $alamat, $id_user);

                if ($stmt->execute()) {
                    $_SESSION['user']['nama'] = $nama;
                    $_SESSION['user']['no_hp'] = $no_hp;
                    $success = "Profil berhasil diperbarui.";
                    $akun['nama'] = $nama;
                    $akun['no_hp'] = $no_hp;
                    $akun['alamat'] = $alamat;
                } else {
                    $error = "Profil gagal diperbarui. Silakan coba lagi.";
                }
                $stmt->close();
            }
        } elseif ($aksi === 'password') {
            $password_lama = $_POST['password_lama'] ?? '';
            $password_baru = $_POST['password_baru'] ?? '';
            $konfirmasi = $_POST['konfirmasi_password'] ?? '';

            if (!password_verify($password_lama, $akun['password'])) {
                $error = "Kata sandi saat ini tidak sesuai.";
            } elseif (strlen($password_baru) < 8) {
                $error = "Kata sandi baru minimal 8 karakter.";
            } elseif ($password_baru !== $konfirmasi) {
                $error = "Konfirmasi kata sandi baru tidak sama.";
            } else {
                $hash = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "UPDATE users SET password = ? WHERE id_user = ?"
                );
                $stmt->bind_param("si", $hash, $id_user);

                if ($stmt->execute()) {
                    $akun['password'] = $hash;
                    $success = "Kata sandi berhasil diganti.";
                } else {
                    $error = "Kata sandi gagal diganti. Silakan coba lagi.";
                }
                $stmt->close();
            }
        } else {
            $error = "Jenis perubahan tidak dikenali.";
        }
    }
}

$isAdmin = (($_SESSION['user']['role'] ?? '') === 'admin');
$pageTitle = $isAdmin ? "Profil Admin" : "Akun Saya";
require __DIR__ . "/includes/header.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="mb-4">
                <h1 class="section-title mb-1"><?= $isAdmin ? 'Profil Admin' : 'Akun Saya' ?></h1>
                <p class="text-secondary mb-0"><?= $isAdmin ? 'Kelola profil administrator PasarKita.' : 'Kelola informasi pribadi dan keamanan akun PasarKita.' ?></p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <form method="post" enctype="multipart/form-data" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_akun']) ?>">
                            <input type="hidden" name="aksi" value="foto">
                            <input type="file" name="foto_profil" id="fotoProfilAkun" accept="image/jpeg,image/png,image/webp" hidden onchange="this.form.submit()">
                            <label for="fotoProfilAkun" class="d-block position-relative" style="cursor:pointer" title="Klik foto untuk mengganti foto profil">
                                <?php if (!empty($akun['foto_profil'])): ?>
                                    <img src="<?= htmlspecialchars($akun['foto_profil']) ?>?v=<?= time() ?>" alt="Foto profil" class="rounded-circle" style="width:76px;height:76px;object-fit:cover">
                                <?php else: ?>
                                    <span class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:76px;height:76px;font-size:2rem"><i class="bi bi-camera"></i></span>
                                <?php endif; ?>
                                <span class="position-absolute bottom-0 end-0 badge rounded-pill text-bg-success"><i class="bi bi-pencil"></i></span>
                            </label>
                        </form>
                        <div>
                            <h4 class="mb-1"><?= htmlspecialchars($akun['nama']) ?></h4>
                            <div class="text-secondary"><?= htmlspecialchars($akun['email']) ?></div>
                            <small class="text-secondary">Klik foto untuk menggantinya (JPG/PNG/WEBP, maks. 2 MB).</small>
                        </div>
                    </div>
                    <h5 class="mb-3"><i class="bi bi-person-vcard me-2"></i>Informasi Profil</h5>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_akun']) ?>">
                        <input type="hidden" name="aksi" value="profil">

                        <div class="mb-3">
                            <label class="form-label">Nama lengkap</label>
                            <input class="form-control" type="text" name="nama" maxlength="100"
                                   value="<?= htmlspecialchars($akun['nama']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input class="form-control" type="email"
                                   value="<?= htmlspecialchars($akun['email']) ?>" disabled>
                            <div class="form-text">Email akun saat ini tidak diubah dari halaman ini.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nomor HP</label>
                            <input class="form-control" type="tel" name="no_hp" maxlength="20"
                                   value="<?= htmlspecialchars($akun['no_hp'] ?? '') ?>"
                                   placeholder="Masukkan nomor HP">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat lengkap</label>
                            <textarea class="form-control" name="alamat" rows="3" maxlength="1000"
                                      placeholder="Masukkan alamat rumah"><?= htmlspecialchars($akun['alamat'] ?? '') ?></textarea>
                        </div>

                        <button class="btn btn-pasar" type="submit">
                            <i class="bi bi-save me-1"></i> Simpan Profil
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="mb-3"><i class="bi bi-shield-lock me-2"></i>Ganti Kata Sandi</h5>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_akun']) ?>">
                        <input type="hidden" name="aksi" value="password">

                        <div class="mb-3">
                            <label class="form-label">Kata sandi saat ini</label>
                            <input class="form-control" type="password" name="password_lama" autocomplete="current-password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kata sandi baru</label>
                            <input class="form-control" type="password" name="password_baru"
                                   minlength="8" autocomplete="new-password" required>
                            <div class="form-text">Minimal 8 karakter.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi kata sandi baru</label>
                            <input class="form-control" type="password" name="konfirmasi_password"
                                   minlength="8" autocomplete="new-password" required>
                        </div>
                        <button class="btn btn-outline-success" type="submit">
                            <i class="bi bi-key me-1"></i> Ganti Kata Sandi
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <a href="admin/index.php" class="btn btn-link px-0 mt-3 text-success text-decoration-none"><i class="bi bi-speedometer2 me-1"></i> Kembali ke Dashboard Admin</a>
            <?php else: ?>
                <a href="pesanan_saya.php" class="btn btn-link px-0 mt-3 text-success text-decoration-none"><i class="bi bi-receipt me-1"></i> Lihat Pesanan Saya</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . "/includes/footer.php"; ?>
