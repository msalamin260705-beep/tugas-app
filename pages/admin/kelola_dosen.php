<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('admin');

$success = $error = "";

// TAMBAH DOSEN
if (isset($_POST['tambah'])) {
    $nama  = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $nidn  = trim($_POST['nidn']);
    $pass  = MD5($_POST['password']);

    $cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Email sudah digunakan!";
    } else {
        $sql = "INSERT INTO users (nama, email, password, role, nim_nidn)
                VALUES ('$nama','$email','$pass','dosen','$nidn')";
        if (mysqli_query($conn, $sql)) {
            $success = "Akun dosen berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan dosen.";
        }
    }
}

// HAPUS DOSEN
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    mysqli_query($conn, "DELETE FROM users WHERE id=$id AND role='dosen'");
    $success = "Dosen berhasil dihapus.";
}

// EDIT DOSEN — ambil data
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id   = intval($_GET['edit']);
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$edit_id AND role='dosen'"));
}

// EDIT DOSEN — simpan
if (isset($_POST['simpan_edit'])) {
    $id    = intval($_POST['edit_id']);
    $nama  = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $nidn  = mysqli_real_escape_string($conn, trim($_POST['nidn']));

    $cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id!=$id");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Email sudah dipakai dosen lain!";
    } else {
        $pass_sql = "";
        if (!empty($_POST['password_baru'])) {
            $pass_sql = ", password='".MD5($_POST['password_baru'])."'";
        }
        mysqli_query($conn, "UPDATE users SET nama='$nama',email='$email',nim_nidn='$nidn' $pass_sql WHERE id=$id AND role='dosen'");
        $success  = "Data dosen berhasil diperbarui!";
        $edit_data = null;
    }
}

// Ambil semua dosen
$dosen_list = mysqli_query($conn, "SELECT * FROM users WHERE role='dosen' ORDER BY nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Dosen</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }

        .topbar {
            background:white; padding:16px 30px;
            border-bottom:1px solid #e0e0e0;
            display:flex; justify-content:space-between; align-items:center;
        }

        .topbar h1 { font-size:20px; color:#1e3a5f; }

        .page-body { padding:28px; }

        .row-2 {
            display:grid;
            grid-template-columns: 380px 1fr;
            gap:24px;
            align-items:start;
        }

        .card {
            background:white;
            border-radius:12px;
            box-shadow:0 2px 8px rgba(0,0,0,0.06);
            overflow:hidden;
        }

        .card-header {
            padding:16px 20px;
            border-bottom:1px solid #f0f0f0;
            background:#6c3483;
        }

        .card-header h3 { color:white; font-size:15px; }

        .card-body { padding:20px; }

        .form-group { margin-bottom:14px; }

        label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:5px; }

        input[type="text"], input[type="email"], input[type="password"] {
            width:100%; padding:10px 12px;
            border:2px solid #e0e0e0; border-radius:8px;
            font-size:14px; outline:none; transition:border-color 0.2s;
        }

        input:focus { border-color:#6c3483; }

        .btn {
            padding:10px 20px; border:none; border-radius:8px;
            font-size:14px; font-weight:600; cursor:pointer; transition:0.2s;
        }

        .btn-primary { background:#6c3483; color:white; width:100%; padding:11px; }
        .btn-primary:hover { background:#5b2c6f; }
        .btn-danger  { background:#e74c3c; color:white; font-size:12px; padding:5px 12px; }
        .btn-danger:hover { background:#c0392b; }

        .alert { padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }

        table { width:100%; border-collapse:collapse; }
        th {
            background:#f8f9fa; padding:10px 16px;
            text-align:left; font-size:12px; color:#888;
            font-weight:600; text-transform:uppercase; letter-spacing:0.5px;
        }

        td { padding:12px 16px; font-size:13px; color:#444; border-bottom:1px solid #f5f5f5; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#fafafa; }

        .avatar-sm {
            width:36px; height:36px; border-radius:50%;
            background:#6c3483; color:white;
            display:inline-flex; align-items:center;
            justify-content:center; font-size:14px;
            margin-right:8px; vertical-align:middle;
        }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar">
        <h1>👨‍🏫 Kelola Dosen</h1>
        <span><?= mysqli_num_rows($dosen_list) ?> dosen terdaftar</span>
    </div>

    <div class="page-body">
        <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <div class="row-2">
            <!-- Form tambah ATAU edit dosen -->
            <div class="card">
                <?php if ($edit_data): ?>
                <div class="card-header"><h3>✏️ Edit Dosen</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" value="<?= htmlspecialchars($edit_data['nama']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>NIDN</label>
                            <input type="text" name="nidn" value="<?= htmlspecialchars($edit_data['nim_nidn']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($edit_data['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru <small style="font-weight:400;color:#aaa">(kosongkan jika tidak diganti)</small></label>
                            <input type="password" name="password_baru" placeholder="Min 6 karakter">
                        </div>
                        <div style="display:flex;gap:8px">
                            <button type="submit" name="simpan_edit" class="btn btn-primary" style="flex:1">💾 Simpan</button>
                            <a href="kelola_dosen.php" style="flex:1;padding:10px;background:#f0f0f0;color:#666;border-radius:8px;text-align:center;text-decoration:none;font-size:14px">Batal</a>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="card-header"><h3>➕ Tambah Akun Dosen</h3></div>
                <div class="card-body">
                    <p style="font-size:12px;color:#888;margin-bottom:16px">
                        Admin yang buat akun — dosen tinggal login saja.
                    </p>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nama Lengkap + Gelar</label>
                            <input type="text" name="nama" placeholder="Dr. Budi Santoso, M.Kom" required>
                        </div>
                        <div class="form-group">
                            <label>NIDN</label>
                            <input type="text" name="nidn" placeholder="Nomor Induk Dosen Nasional" required>
                        </div>
                        <div class="form-group">
                            <label>Email (untuk login)</label>
                            <input type="email" name="email" placeholder="email@kampus.com" required>
                        </div>
                        <div class="form-group">
                            <label>Password Awal</label>
                            <input type="password" name="password" placeholder="Beri password sementara" required>
                        </div>
                        <button type="submit" name="tambah" class="btn btn-primary">
                            Buat Akun Dosen
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tabel daftar dosen -->
            <div class="card">
                <div class="card-header"><h3>📋 Daftar Dosen</h3></div>
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NIDN</th>
                            <th>Email</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Re-query karena pointer sudah habis
                        $dosen_list = mysqli_query($conn, "SELECT * FROM users WHERE role='dosen' ORDER BY nama");
                        while ($row = mysqli_fetch_assoc($dosen_list)):
                        ?>
                        <tr>
                            <td>
                                <span class="avatar-sm">👨‍🏫</span>
                                <?= htmlspecialchars($row['nama']) ?>
                            </td>
                            <td><?= htmlspecialchars($row['nim_nidn']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <a href="?edit=<?= $row['id'] ?>"
                                   style="font-size:12px;padding:5px 10px;background:#e8f0fe;color:#1a5276;border-radius:6px;text-decoration:none;font-weight:600;margin-right:4px">
                                    ✏️
                                </a>
                                <a href="?hapus=<?= $row['id'] ?>"
                                   onclick="return confirm('Hapus dosen <?= addslashes($row['nama']) ?>?')"
                                   class="btn btn-danger">🗑️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows(mysqli_query($conn,"SELECT id FROM users WHERE role='dosen' LIMIT 1"))==0): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;color:#aaa;padding:30px">
                                Belum ada dosen. Tambahkan di form kiri.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>