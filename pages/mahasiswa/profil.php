<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('mahasiswa');

$mhs_id  = $_SESSION['user_id'];
$success = $error = "";

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$mhs_id"));

if (isset($_POST['update'])) {
    $nama  = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $nim   = mysqli_real_escape_string($conn, trim($_POST['nim']));

    $cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id != $mhs_id");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Email sudah dipakai user lain!";
    } else {
        $foto_baru = $user['foto'];
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $file    = $_FILES['foto'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $allowed)) {
                $error = "Format foto harus JPG, PNG, atau GIF!";
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = "Ukuran foto maksimal 2MB!";
            } else {
                $foto_baru = 'foto_' . $mhs_id . '_' . time() . '.' . $ext;
                // ✅ FIX: pakai BASE_PATH untuk upload
                move_uploaded_file($file['tmp_name'], BASE_PATH . '/uploads/foto_profil/' . $foto_baru);
            }
        }

        if (!$error) {
            $pass_sql = "";
            if (!empty($_POST['password_baru'])) {
                if (strlen($_POST['password_baru']) < 6) {
                    $error = "Password baru minimal 6 karakter!";
                } else {
                    $pass_baru = MD5($_POST['password_baru']);
                    $pass_sql  = ", password='$pass_baru'";
                }
            }

            if (!$error) {
                mysqli_query($conn, "UPDATE users SET nama='$nama', email='$email', nim_nidn='$nim', foto='$foto_baru' $pass_sql WHERE id=$mhs_id");
                $_SESSION['user_nama'] = $nama;
                $_SESSION['user_foto'] = $foto_baru;
                $success = "Profil berhasil diperbarui!";
                $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$mhs_id"));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; max-width:580px; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .card-header { padding:16px 20px; background:#784212; }
        .card-header h3 { color:white; font-size:15px; }
        .card-body { padding:28px; }
        .foto-area { text-align:center; margin-bottom:24px; }
        .foto-area img, .foto-avatar {
            width:100px; height:100px; border-radius:50%;
            object-fit:cover; border:3px solid #784212;
        }
        .foto-avatar {
            background:#784212; color:white;
            display:flex; align-items:center; justify-content:center;
            font-size:40px; margin:0 auto;
        }
        .foto-area label {
            display:inline-block; margin-top:10px; padding:6px 16px;
            background:#f5f0eb; color:#784212; border-radius:20px;
            font-size:12px; font-weight:600; cursor:pointer; border:1px solid #e0d0c0;
        }
        .foto-area input { display:none; }
        .form-group { margin-bottom:16px; }
        label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:5px; }
        input[type="text"], input[type="email"], input[type="password"] {
            width:100%; padding:11px 13px; border:2px solid #e0e0e0;
            border-radius:8px; font-size:14px; outline:none; transition:0.2s;
        }
        input:focus { border-color:#784212; }
        .divider { border:none; border-top:1px solid #f0f0f0; margin:20px 0; }
        .btn-primary { background:#784212; color:white; border:none; border-radius:8px; padding:12px; width:100%; font-size:14px; font-weight:600; cursor:pointer; }
        .btn-primary:hover { background:#5d3510; }
        .alert { padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }
        small { font-size:11px; color:#aaa; display:block; margin-top:4px; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>👤 Profil Saya</h1></div>

    <div class="page-body">
        <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header"><h3>Edit Profil</h3></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="foto-area">
                        <?php
                        // ✅ FIX: tidak pakai file_exists, langsung tampilkan jika ada nama foto
                        if ($user['foto']):
                        ?>
                            <img src="<?= BASE_URL ?>/uploads/foto_profil/<?= htmlspecialchars($user['foto']) ?>"
                                 id="preview_foto" alt="Foto Profil">
                        <?php else: ?>
                            <div class="foto-avatar" id="preview_avatar">👤</div>
                        <?php endif; ?>
                        <br>
                        <label for="foto_input">📷 Ganti Foto Profil</label>
                        <input type="file" id="foto_input" name="foto"
                               accept=".jpg,.jpeg,.png,.gif"
                               onchange="previewFoto(this)">
                        <small>JPG, PNG, GIF — Maks 2MB</small>
                    </div>

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>NIM</label>
                        <input type="text" name="nim" value="<?= htmlspecialchars($user['nim_nidn']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <hr class="divider">
                    <p style="font-size:13px;color:#888;margin-bottom:14px">
                        🔒 Ganti Password <small style="display:inline">(kosongkan jika tidak ingin diganti)</small>
                    </p>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="password_baru" placeholder="Minimal 6 karakter">
                    </div>

                    <button type="submit" name="update" class="btn-primary">💾 Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewFoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.getElementById('preview_foto');
            if (!preview) {
                const avatar = document.getElementById('preview_avatar');
                const img = document.createElement('img');
                img.id = 'preview_foto';
                img.style.cssText = 'width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #784212';
                avatar.parentNode.replaceChild(img, avatar);
                preview = img;
            }
            preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>