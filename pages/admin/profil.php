<?php
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('admin');

$admin_id = $_SESSION['user_id'];
$success  = $error = "";
$user     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$admin_id"));

if (isset($_POST['update'])) {
    $nama  = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    $cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id!=$admin_id");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Email sudah dipakai!";
    } else {
        $foto_baru = $user['foto'];
        if (isset($_FILES['foto']) && $_FILES['foto']['error']===0) {
            $file = $_FILES['foto'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png']) && $file['size'] <= 2*1024*1024) {
                $foto_baru = 'foto_' . $admin_id . '_' . time() . '.' . $ext;
                // ✅ FIX: pakai BASE_PATH
                move_uploaded_file($file['tmp_name'], BASE_PATH . '/uploads/foto_profil/' . $foto_baru);
            } else { $error = "Format JPG/PNG maks 2MB!"; }
        }
        if (!$error) {
            $pass_sql = "";
            if (!empty($_POST['password_baru'])) {
                if (strlen($_POST['password_baru']) < 6) { $error = "Password min 6 karakter!"; }
                else { $pass_sql = ", password='".MD5($_POST['password_baru'])."'"; }
            }
            if (!$error) {
                mysqli_query($conn, "UPDATE users SET nama='$nama', email='$email', foto='$foto_baru' $pass_sql WHERE id=$admin_id");
                $_SESSION['user_nama'] = $nama;
                $_SESSION['user_foto'] = $foto_baru;
                $success = "Profil berhasil diperbarui!";
                $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$admin_id"));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Admin</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;}
        .topbar{background:white;padding:16px 30px;border-bottom:1px solid #e0e0e0;}
        .topbar h1{font-size:20px;color:#1e3a5f;}
        .page-body{padding:28px;max-width:500px;}
        .card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);overflow:hidden;}
        .card-header{padding:16px 20px;background:#4a1a6b;}
        .card-header h3{color:white;font-size:15px;}
        .card-body{padding:24px;}
        .form-group{margin-bottom:15px;}
        label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:5px;}
        input[type="text"],input[type="email"],input[type="password"]{width:100%;padding:11px 13px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;outline:none;transition:0.2s;}
        input:focus{border-color:#4a1a6b;}
        .btn-primary{background:#4a1a6b;color:white;border:none;border-radius:8px;padding:12px;width:100%;font-size:14px;font-weight:600;cursor:pointer;}
        .btn-primary:hover{background:#3a1455;}
        .alert{padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:13px;}
        .alert-success{background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;}
        .alert-error{background:#ffebee;color:#c62828;border:1px solid #ffcdd2;}
        .foto-area{text-align:center;margin-bottom:22px;}
        .foto-area img,.f-avatar{width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #4a1a6b;}
        .f-avatar{background:#4a1a6b;color:white;display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto;}
        .divider{border:none;border-top:1px solid #f0f0f0;margin:18px 0;}
    </style>
</head>
<body>
<?php require_once '../../config/sidebar.php'; ?>
<div style="margin-left:220px;">
    <div class="topbar"><h1>👑 Profil Admin</h1></div>
    <div class="page-body">
        <?php if($success):?><div class="alert alert-success">✅ <?=$success?></div><?php endif;?>
        <?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>
        <div class="card">
            <div class="card-header"><h3>Edit Profil Admin</h3></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="foto-area">
                        <?php
                        // ✅ FIX: hapus file_exists, langsung cek nama foto
                        if ($user['foto']):?>
                            <img src="<?= BASE_URL ?>/uploads/foto_profil/<?= htmlspecialchars($user['foto']) ?>" id="pv">
                        <?php else:?>
                            <div class="f-avatar" id="pv">👑</div>
                        <?php endif;?>
                        <br>
                        <label for="fi" style="display:inline-block;margin-top:8px;padding:5px 14px;background:#f3e8ff;color:#4a1a6b;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer">📷 Ganti Foto</label>
                        <input type="file" id="fi" name="foto" accept=".jpg,.jpeg,.png" onchange="pFoto(this)" style="display:none">
                    </div>
                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" name="nama" value="<?=htmlspecialchars($user['nama'])?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required>
                    </div>
                    <hr class="divider">
                    <div class="form-group">
                        <label>Password Baru <small style="font-weight:400;color:#aaa">(kosongkan jika tidak diganti)</small></label>
                        <input type="password" name="password_baru" placeholder="Min 6 karakter">
                    </div>
                    <button type="submit" name="update" class="btn-primary">💾 Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function pFoto(i){
    if(i.files&&i.files[0]){
        const r=new FileReader();
        r.onload=e=>{
            let p=document.getElementById('pv');
            if(p.tagName==='DIV'){const img=document.createElement('img');img.id='pv';img.style.cssText='width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #4a1a6b';p.parentNode.replaceChild(img,p);p=img;}
            p.src=e.target.result;
        };
        r.readAsDataURL(i.files[0]);
    }
}
</script>
</body>
</html>