<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('dosen');

$dosen_id = $_SESSION['user_id'];
$success  = $error = "";

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$dosen_id"));

// Ambil kelas yang diampu
$kelas_diampu = mysqli_query($conn, "
    SELECT k.*, COUNT(mk.mahasiswa_id) AS jml_mhs
    FROM kelas k
    LEFT JOIN mahasiswa_kelas mk ON mk.kelas_id = k.id
    WHERE k.dosen_id = $dosen_id
    GROUP BY k.id
    ORDER BY k.semester, k.kelas
");

if (isset($_POST['update'])) {
    $nama  = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $nidn  = mysqli_real_escape_string($conn, trim($_POST['nidn']));

    $cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id!=$dosen_id");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Email sudah dipakai user lain!";
    } else {
        $foto_baru = $user['foto'];

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $file    = $_FILES['foto'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];
            if (!in_array($ext, $allowed)) {
                $error = "Format foto harus JPG/PNG/GIF!";
            } elseif ($file['size'] > 2*1024*1024) {
                $error = "Ukuran foto maks 2MB!";
            } else {
                $foto_baru = 'foto_' . $dosen_id . '_' . time() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], '../../uploads/foto_profil/' . $foto_baru);
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
                mysqli_query($conn, "UPDATE users SET nama='$nama', email='$email', nim_nidn='$nidn', foto='$foto_baru' $pass_sql WHERE id=$dosen_id");
                $_SESSION['user_nama'] = $nama;
                $_SESSION['user_foto'] = $foto_baru;
                $success = "Profil berhasil diperbarui!";
                $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$dosen_id"));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Dosen</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .card-header { padding:16px 20px; }
        .card-header h3 { font-size:15px; color:white; }
        .card-body { padding:24px; }
        .form-group { margin-bottom:15px; }
        label { display:block; font-size:13px; font-weight:600; color:#444; margin-bottom:5px; }
        input[type="text"], input[type="email"], input[type="password"] {
            width:100%; padding:11px 13px; border:2px solid #e0e0e0;
            border-radius:8px; font-size:14px; outline:none; transition:0.2s;
        }
        input:focus { border-color:#1a5276; }
        .btn-primary { background:#1a5276; color:white; border:none; border-radius:8px; padding:12px; width:100%; font-size:14px; font-weight:600; cursor:pointer; }
        .btn-primary:hover { background:#154360; }
        .alert { padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }
        .foto-area { text-align:center; margin-bottom:22px; }
        .foto-area img, .avatar {
            width:90px; height:90px; border-radius:50%;
            object-fit:cover; border:3px solid #1a5276;
        }
        .avatar {
            background:#1a5276; color:white;
            display:flex; align-items:center; justify-content:center;
            font-size:36px; margin:0 auto;
        }
        .foto-area label-upload {
            display:inline-block; margin-top:8px; padding:5px 14px;
            background:#e8f0fe; color:#1a5276; border-radius:20px;
            font-size:12px; font-weight:600; cursor:pointer;
        }
        .foto-area input { display:none; }
        .divider { border:none; border-top:1px solid #f0f0f0; margin:18px 0; }

        /* Kelas diampu */
        .kelas-item {
            padding:12px 16px; border-radius:10px;
            border:1px solid #e0e0e0; margin-bottom:10px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .kelas-item h4 { font-size:14px; color:#1e3a5f; }
        .kelas-item p  { font-size:12px; color:#888; margin-top:3px; }
        .badge { padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-blue  { background:#e8f0fe; color:#1a5276; }
        .badge-green { background:#e8f5e9; color:#2e7d32; }
        small { font-size:11px; color:#aaa; display:block; margin-top:3px; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>👤 Profil Saya</h1></div>

    <div class="page-body">
        <!-- Form edit profil -->
        <div>
            <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

            <div class="card">
                <div class="card-header" style="background:#1a5276"><h3>✏️ Edit Profil</h3></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <div class="foto-area">
                            <?php if ($user['foto'] && file_exists('../../uploads/foto_profil/'.$user['foto'])): ?>
                                <img src="/tugas-app/uploads/foto_profil/<?= $user['foto'] ?>" id="preview_foto">
                            <?php else: ?>
                                <div class="avatar" id="preview_avatar">👨‍🏫</div>
                            <?php endif; ?>
                            <br>
                            <label for="foto_input" style="display:inline-block;margin-top:8px;padding:5px 14px;background:#e8f0fe;color:#1a5276;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer">
                                📷 Ganti Foto
                            </label>
                            <input type="file" id="foto_input" name="foto"
                                   accept=".jpg,.jpeg,.png"
                                   onchange="previewFoto(this)">
                            <small style="text-align:center">JPG/PNG maks 2MB</small>
                        </div>

                        <div class="form-group">
                            <label>Nama Lengkap + Gelar</label>
                            <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>NIDN</label>
                            <input type="text" name="nidn" value="<?= htmlspecialchars($user['nim_nidn']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <hr class="divider">
                        <p style="font-size:13px;color:#888;margin-bottom:12px">🔒 Ganti Password <small style="display:inline">(kosongkan jika tidak ingin diganti)</small></p>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="password_baru" placeholder="Minimal 6 karakter">
                        </div>

                        <button type="submit" name="update" class="btn-primary">💾 Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kelas yang diampu -->
        <div class="card">
            <div class="card-header" style="background:#145a32"><h3>📚 Kelas yang Saya Ampu</h3></div>
            <div class="card-body">
                <?php
                $ada = false;
                while ($k = mysqli_fetch_assoc($kelas_diampu)):
                    $ada = true;
                ?>
                <div class="kelas-item">
                    <div>
                        <h4><?= htmlspecialchars($k['nama_kelas']) ?></h4>
                        <p>
                            <span class="badge badge-blue">Sem <?= $k['semester'] ?></span>
                            <span class="badge badge-blue">Kelas <?= $k['kelas'] ?></span>
                            <span class="badge badge-green"><?= $k['sks'] ?> SKS</span>
                        </p>
                        <p style="margin-top:4px">🔖 <?= $k['kode_matkul'] ?></p>
                    </div>
                    <div style="text-align:right">
                        <span style="font-size:20px;font-weight:700;color:#145a32"><?= $k['jml_mhs'] ?></span>
                        <p style="font-size:11px;color:#aaa">mahasiswa</p>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if (!$ada): ?>
                    <p style="text-align:center;color:#aaa;padding:30px 0">Belum ada kelas yang diampu.</p>
                <?php endif; ?>
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
                const img    = document.createElement('img');
                img.id       = 'preview_foto';
                img.style.cssText = 'width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #1a5276';
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