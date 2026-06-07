<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/db.php';

$error = $success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama     = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $nim      = mysqli_real_escape_string($conn, trim($_POST['nim']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $konfirm  = $_POST['konfirm_password'];
    $semester = intval($_POST['semester']);
    $kelas    = $_POST['kelas'];

    $kelas_valid    = ['A','B','C','D','E'];
    $semester_valid = range(1, 8);

    if (!in_array($kelas, $kelas_valid)) {
        $error = "Kelas tidak valid!";
    } elseif (!in_array($semester, $semester_valid)) {
        $error = "Semester tidak valid!";
    } elseif ($password !== $konfirm) {
        $error = "Password dan konfirmasi tidak sama!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Cek email duplikat
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            $pass_md5 = MD5($password);

            // Simpan user
            $sql = "INSERT INTO users (nama, email, password, role, nim_nidn, semester, kelas)
                    VALUES ('$nama','$email','$pass_md5','mahasiswa','$nim',$semester,'$kelas')";

            if (mysqli_query($conn, $sql)) {
                $mhs_id = mysqli_insert_id($conn);

                // Cari semua kelas yang cocok: semester & kelas sama
                $kelas_cocok = mysqli_query($conn, "
                    SELECT id FROM kelas
                    WHERE semester=$semester AND kelas='$kelas'
                ");

                // Daftarkan otomatis ke semua matkul di semester & kelas itu
                while ($k = mysqli_fetch_assoc($kelas_cocok)) {
                    mysqli_query($conn, "
                        INSERT INTO mahasiswa_kelas (mahasiswa_id, kelas_id)
                        VALUES ($mhs_id, {$k['id']})
                    ");
                }

                $success = "Registrasi berhasil!";
            } else {
                $error = "Gagal mendaftar: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Mahasiswa</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',sans-serif;
            background:linear-gradient(135deg,#1e3a5f,#2d6a9f);
            min-height:100vh;
            display:flex; align-items:center; justify-content:center;
            padding:30px 20px;
        }
        .box {
            background:white; padding:36px;
            border-radius:16px; width:100%; max-width:480px;
            box-shadow:0 20px 60px rgba(0,0,0,0.3);
        }
        h2 { text-align:center; color:#1e3a5f; margin-bottom:4px; font-size:22px; }
        .sub { text-align:center; color:#888; font-size:13px; margin-bottom:26px; }

        .form-group { margin-bottom:15px; }
        label { display:block; font-size:13px; font-weight:600; color:#444; margin-bottom:5px; }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width:100%; padding:11px 13px;
            border:2px solid #e0e0e0; border-radius:8px;
            font-size:14px; outline:none; transition:0.2s;
            font-family:inherit;
        }
        input:focus, select:focus { border-color:#2d6a9f; }

        .row-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

        /* Pilihan kelas A-E */
        .kelas-row {
            display:flex; gap:8px;
        }
        .kelas-btn {
            flex:1; padding:11px 0;
            border:2px solid #e0e0e0; border-radius:8px;
            text-align:center; font-size:15px; font-weight:700;
            color:#888; cursor:pointer; transition:0.2s;
            background:white;
        }
        .kelas-btn:hover { border-color:#2d6a9f; color:#2d6a9f; }
        .kelas-btn.aktif {
            border-color:#2d6a9f;
            background:#2d6a9f;
            color:white;
        }
        .kelas-hidden { display:none; }

        .btn {
            width:100%; padding:13px;
            background:#2d6a9f; color:white;
            border:none; border-radius:8px;
            font-size:15px; font-weight:600;
            cursor:pointer; margin-top:8px; transition:0.2s;
        }
        .btn:hover { background:#1e3a5f; }

        .alert { padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:14px; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }

        .link { text-align:center; margin-top:18px; font-size:13px; color:#666; }
        .link a { color:#2d6a9f; font-weight:600; text-decoration:none; }

        .divider { border:none; border-top:1px solid #f0f0f0; margin:20px 0; }

        .info-box {
            background:#e8f0fe; border-radius:8px;
            padding:10px 14px; font-size:12px; color:#1a5276;
            margin-top:10px; display:none;
        }
        .info-box.show { display:block; }
    </style>
</head>
<body>
<div class="box">
    <h2>🎓 Daftar Mahasiswa</h2>
    <p class="sub">Isi data diri kamu untuk membuat akun</p>

    <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ Registrasi berhasil! Kamu sudah terdaftar ke semua matkul di kelasmu.<br>
            <a href="index.php" style="color:#2e7d32;font-weight:700">Login sekarang →</a>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="">

        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" placeholder="Contoh: Andi Pratama"
                   value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>NIM</label>
            <input type="text" name="nim" placeholder="Nomor Induk Mahasiswa"
                   value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>" required>
        </div>

        <div class="row-2">
            <div class="form-group">
                <label>Semester</label>
                <select name="semester" required onchange="updateInfoKelas()">
                    <option value="">-- Pilih --</option>
                    <?php for ($i=1; $i<=8; $i++): ?>
                    <option value="<?= $i ?>" <?= (($_POST['semester'] ?? '')==$i)?'selected':'' ?>>
                        Semester <?= $i ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Kelas</label>
                <div class="kelas-row">
                    <?php
                    $kelas_list = ['A','B','C','D','E'];
                    $kelas_post = $_POST['kelas'] ?? '';
                    foreach ($kelas_list as $k):
                    ?>
                    <div class="kelas-btn <?= $kelas_post==$k?'aktif':'' ?>"
                         onclick="pilihKelas('<?= $k ?>')">
                        <?= $k ?>
                    </div>
                    <?php endforeach; ?>
                    <input type="hidden" name="kelas" id="input_kelas"
                           value="<?= htmlspecialchars($kelas_post) ?>" required>
                </div>
            </div>
        </div>

        <!-- Info matkul yang akan didapat -->
        <div class="info-box" id="info_kelas">
            ⏳ Pilih semester dan kelas untuk melihat matkul yang tersedia...
        </div>

        <div class="form-group" style="margin-top:15px">
            <label>Email</label>
            <input type="email" name="email" placeholder="Email aktif kamu"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Minimal 6 karakter" required>
        </div>

        <div class="form-group">
            <label>Konfirmasi Password</label>
            <input type="password" name="konfirm_password" placeholder="Ulangi password" required>
        </div>

        <button type="submit" class="btn">🎓 Daftar Sekarang</button>
    </form>
    <?php endif; ?>

    <div class="link">
        Sudah punya akun? <a href="index.php">Login di sini</a>
    </div>
</div>

<script>
function pilihKelas(k) {
    // Hapus semua aktif
    document.querySelectorAll('.kelas-btn').forEach(b => b.classList.remove('aktif'));
    // Aktifkan yang dipilih
    event.target.classList.add('aktif');
    document.getElementById('input_kelas').value = k;
    updateInfoKelas();
}

function updateInfoKelas() {
    const semester = document.querySelector('select[name="semester"]').value;
    const kelas    = document.getElementById('input_kelas').value;
    const box      = document.getElementById('info_kelas');

    if (!semester || !kelas) {
        box.classList.remove('show');
        return;
    }

    box.classList.add('show');
    box.innerHTML = '⏳ Mengecek matkul tersedia...';

    // Fetch ke server untuk cek kelas tersedia
    fetch('ajax_cek_kelas.php?semester=' + semester + '&kelas=' + kelas)
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) {
                box.innerHTML = '⚠️ Belum ada matkul untuk Semester ' + semester + ' Kelas ' + kelas + '. Hubungi admin.';
                box.style.background = '#fff8e1';
                box.style.color = '#e65100';
            } else {
                let html = '✅ <strong>' + data.length + ' matkul</strong> akan otomatis terdaftar:<br><br>';
                data.forEach(function(m) {
                    html += '📚 <strong>' + m.nama_kelas + '</strong> (' + m.kode_matkul + ') — 👨‍🏫 ' + m.nama_dosen + '<br>';
                });
                box.innerHTML = html;
                box.style.background = '#e8f0fe';
                box.style.color = '#1a5276';
            }
        });
}
</script>
</body>
</html>