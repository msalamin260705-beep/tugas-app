<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('dosen');

$dosen_id = $_SESSION['user_id'];
$success = $error = "";

// DAFTARKAN MAHASISWA MENGULANG — hanya ke kelas milik dosen ini
if (isset($_POST['daftarkan'])) {
    $mhs_id   = intval($_POST['mhs_id']);
    $kelas_id = intval($_POST['kelas_id']);

    // Pastikan kelas_id milik dosen ini
    $cek_kelas = mysqli_query($conn, "SELECT id FROM kelas WHERE id=$kelas_id AND dosen_id=$dosen_id");
    if (mysqli_num_rows($cek_kelas) == 0) {
        $error = "Kelas tidak valid atau bukan milik Anda!";
    } else {
        $cek = mysqli_query($conn, "SELECT id FROM mahasiswa_kelas WHERE mahasiswa_id=$mhs_id AND kelas_id=$kelas_id");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Mahasiswa sudah terdaftar di kelas ini!";
        } else {
            mysqli_query($conn, "INSERT INTO mahasiswa_kelas (mahasiswa_id, kelas_id) VALUES ($mhs_id, $kelas_id)");
            $success = "Mahasiswa berhasil didaftarkan untuk mengulang!";
        }
    }
}

// HAPUS pendaftaran mengulang — hanya yang kelasnya milik dosen ini
if (isset($_GET['hapus_mk'])) {
    $id = intval($_GET['hapus_mk']);
    // Validasi kepemilikan sebelum hapus
    $cek_own = mysqli_query($conn, "
        SELECT mk.id FROM mahasiswa_kelas mk
        JOIN kelas k ON mk.kelas_id = k.id
        WHERE mk.id=$id AND k.dosen_id=$dosen_id
    ");
    if (mysqli_num_rows($cek_own) > 0) {
        mysqli_query($conn, "DELETE FROM mahasiswa_kelas WHERE id=$id");
        $success = "Pendaftaran mengulang dihapus.";
    } else {
        $error = "Tidak diizinkan menghapus data ini.";
    }
}

// EDIT — ambil data
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    // Hanya boleh edit yang kelasnya milik dosen ini
    $q = mysqli_query($conn, "
        SELECT mk.* FROM mahasiswa_kelas mk
        JOIN kelas k ON mk.kelas_id = k.id
        WHERE mk.id=$edit_id AND k.dosen_id=$dosen_id
    ");
    $edit_data = mysqli_fetch_assoc($q);
    if (!$edit_data) $error = "Data tidak ditemukan atau bukan milik Anda.";
}

// EDIT — simpan
if (isset($_POST['simpan_edit'])) {
    $mk_id    = intval($_POST['mk_id']);
    $mhs_id   = intval($_POST['mhs_id']);
    $kelas_id = intval($_POST['kelas_id']);

    // Validasi kelas milik dosen ini
    $cek_kelas = mysqli_query($conn, "SELECT id FROM kelas WHERE id=$kelas_id AND dosen_id=$dosen_id");
    if (mysqli_num_rows($cek_kelas) == 0) {
        $error = "Kelas tidak valid atau bukan milik Anda!";
    } else {
        // Cek duplikat (exclude record ini sendiri)
        $cek_dup = mysqli_query($conn, "
            SELECT id FROM mahasiswa_kelas
            WHERE mahasiswa_id=$mhs_id AND kelas_id=$kelas_id AND id!=$mk_id
        ");
        if (mysqli_num_rows($cek_dup) > 0) {
            $error = "Mahasiswa sudah terdaftar di kelas ini!";
        } else {
            mysqli_query($conn, "
                UPDATE mahasiswa_kelas SET mahasiswa_id=$mhs_id, kelas_id=$kelas_id
                WHERE id=$mk_id
            ");
            $success   = "Data mengulang berhasil diperbarui!";
            $edit_data = null;
        }
    }
}

// Semua mahasiswa
$mhs_list = mysqli_query($conn, "SELECT id,nama,nim_nidn,semester,kelas FROM users WHERE role='mahasiswa' ORDER BY nama");

// Hanya kelas milik dosen ini
$kelas_list = mysqli_query($conn, "
    SELECT k.* FROM kelas k
    WHERE k.dosen_id=$dosen_id
    ORDER BY k.semester, k.kelas, k.nama_kelas
");

// Mahasiswa mengulang di kelas milik dosen ini
$mengulang = mysqli_query($conn, "
    SELECT mk.id AS mk_id, u.nama AS nama_mhs, u.nim_nidn, u.semester AS sem_aktif, u.kelas AS kelas_aktif,
           k.nama_kelas, k.kode_matkul, k.semester AS sem_kelas, k.kelas AS kelas_huruf, k.id AS kelas_id,
           mk.mahasiswa_id
    FROM mahasiswa_kelas mk
    JOIN users u ON mk.mahasiswa_id = u.id
    JOIN kelas k ON mk.kelas_id = k.id
    WHERE k.dosen_id=$dosen_id AND u.semester != k.semester
    ORDER BY u.nama
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Mahasiswa Mengulang</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;}
        .topbar{background:white;padding:16px 30px;border-bottom:1px solid #e0e0e0;display:flex;justify-content:space-between;align-items:center;}
        .topbar h1{font-size:20px;color:#1e3a5f;}
        .page-body{padding:28px;}
        .row-2{display:grid;grid-template-columns:380px 1fr;gap:24px;align-items:start;}
        .card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);overflow:hidden;margin-bottom:16px;}
        .card-header{padding:16px 20px;}
        .card-header h3{color:white;font-size:15px;}
        .card-body{padding:20px;}
        .form-group{margin-bottom:14px;}
        label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:5px;}
        select{width:100%;padding:10px 12px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;outline:none;transition:0.2s;font-family:inherit;}
        select:focus{border-color:#c0392b;}
        .btn-primary{background:#c0392b;color:white;border:none;border-radius:8px;padding:11px;width:100%;font-size:14px;font-weight:600;cursor:pointer;}
        .btn-primary:hover{background:#a93226;}
        .btn-danger{background:#e74c3c;color:white;font-size:12px;padding:5px 10px;border:none;border-radius:6px;cursor:pointer;text-decoration:none;}
        .alert{padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:13px;}
        .alert-success{background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;}
        .alert-error{background:#ffebee;color:#c62828;border:1px solid #ffcdd2;}
        table{width:100%;border-collapse:collapse;}
        th{background:#f8f9fa;padding:9px 14px;text-align:left;font-size:11px;color:#888;font-weight:700;text-transform:uppercase;}
        td{padding:11px 14px;font-size:13px;color:#444;border-bottom:1px solid #f5f5f5;}
        tr:last-child td{border-bottom:none;}
        .badge{padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;}
        .badge-red{background:#ffebee;color:#c62828;}
        .badge-blue{background:#e8f0fe;color:#1a5276;}
        .info-box{background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px 14px;font-size:13px;color:#e65100;margin-bottom:16px;}
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>🔄 Mahasiswa Mengulang Matkul</h1></div>

    <div class="page-body">
        <?php if($success):?><div class="alert alert-success">✅ <?=$success?></div><?php endif;?>
        <?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>

        <div class="info-box">
            ℹ️ Fitur ini untuk mendaftarkan mahasiswa ke mata kuliah Anda di semester lain (mengulang/remedial).
            Jadwal matkul yang diulang akan otomatis muncul di halaman jadwal mahasiswa tersebut.
        </div>

        <div class="row-2">
            <!-- Form tambah ATAU edit -->
            <div class="card">
                <?php if ($edit_data): ?>
                <div class="card-header" style="background:#c0392b"><h3>✏️ Edit Pendaftaran Mengulang</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="mk_id" value="<?=$edit_data['id']?>">
                        <div class="form-group">
                            <label>Pilih Mahasiswa</label>
                            <select name="mhs_id" required>
                                <option value="">-- Pilih Mahasiswa --</option>
                                <?php
                                $mhs_list2 = mysqli_query($conn,"SELECT id,nama,nim_nidn,semester,kelas FROM users WHERE role='mahasiswa' ORDER BY nama");
                                while($m=mysqli_fetch_assoc($mhs_list2)):?>
                                <option value="<?=$m['id']?>" <?=$edit_data['mahasiswa_id']==$m['id']?'selected':''?>>
                                    <?=htmlspecialchars($m['nama'])?> — <?=$m['nim_nidn']?>
                                    (Sem <?=$m['semester']?> Kelas <?=$m['kelas']?>)
                                </option>
                                <?php endwhile;?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mata Kuliah yang Diulang</label>
                            <select name="kelas_id" required>
                                <option value="">-- Pilih Matkul --</option>
                                <?php
                                $kelas_edit = mysqli_query($conn,"SELECT k.* FROM kelas k WHERE k.dosen_id=$dosen_id ORDER BY k.semester,k.kelas,k.nama_kelas");
                                $prev=0;
                                while($k=mysqli_fetch_assoc($kelas_edit)):
                                    if($k['semester']!=$prev){if($prev)echo'</optgroup>';echo'<optgroup label="Semester '.$k['semester'].'">';$prev=$k['semester'];}
                                ?>
                                <option value="<?=$k['id']?>" <?=$edit_data['kelas_id']==$k['id']?'selected':''?>>
                                    Kelas <?=$k['kelas']?> — <?=htmlspecialchars($k['nama_kelas'])?>
                                    (<?=$k['kode_matkul']?>)
                                </option>
                                <?php endwhile; if($prev)echo'</optgroup>';?>
                            </select>
                        </div>
                        <div style="display:flex;gap:8px">
                            <button type="submit" name="simpan_edit" class="btn-primary" style="flex:1">💾 Simpan</button>
                            <a href="mengulang_dosen.php" style="flex:1;padding:11px;background:#f0f0f0;color:#666;border-radius:8px;text-align:center;text-decoration:none;font-size:14px;font-weight:600">Batal</a>
                        </div>
                    </form>
                </div>

                <?php else: ?>
                <div class="card-header" style="background:#c0392b"><h3>➕ Daftarkan Mengulang</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Pilih Mahasiswa</label>
                            <select name="mhs_id" required>
                                <option value="">-- Pilih Mahasiswa --</option>
                                <?php while($m=mysqli_fetch_assoc($mhs_list)):?>
                                <option value="<?=$m['id']?>">
                                    <?=htmlspecialchars($m['nama'])?> — <?=$m['nim_nidn']?>
                                    (Sem <?=$m['semester']?> Kelas <?=$m['kelas']?>)
                                </option>
                                <?php endwhile;?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mata Kuliah yang Diulang</label>
                            <select name="kelas_id" required>
                                <option value="">-- Pilih Matkul --</option>
                                <?php
                                $prev=0;
                                while($k=mysqli_fetch_assoc($kelas_list)):
                                    if($k['semester']!=$prev){if($prev)echo'</optgroup>';echo'<optgroup label="Semester '.$k['semester'].'">';$prev=$k['semester'];}
                                ?>
                                <option value="<?=$k['id']?>">
                                    Kelas <?=$k['kelas']?> — <?=htmlspecialchars($k['nama_kelas'])?>
                                    (<?=$k['kode_matkul']?>)
                                </option>
                                <?php endwhile; if($prev)echo'</optgroup>';?>
                            </select>
                        </div>
                        <button type="submit" name="daftarkan" class="btn-primary">
                            🔄 Daftarkan Mengulang
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tabel mahasiswa mengulang -->
            <div class="card">
                <div class="card-header" style="background:#1a5276"><h3>📋 Mahasiswa Mengulang di Kelas Saya</h3></div>
                <table>
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Semester Aktif</th>
                            <th>Matkul Diulang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $ada = false;
                    while($r = mysqli_fetch_assoc($mengulang)):
                        $ada = true;
                    ?>
                    <tr>
                        <td>
                            <strong><?=htmlspecialchars($r['nama_mhs'])?></strong><br>
                            <small style="color:#aaa"><?=$r['nim_nidn']?></small>
                        </td>
                        <td>
                            <span class="badge badge-blue">Sem <?=$r['sem_aktif']?> Kelas <?=$r['kelas_aktif']?></span>
                        </td>
                        <td>
                            <strong><?=htmlspecialchars($r['nama_kelas'])?></strong><br>
                            <span class="badge badge-red">Sem <?=$r['sem_kelas']?> Kelas <?=$r['kelas_huruf']?></span>
                        </td>
                        <td>
                            <a href="?edit=<?=$r['mk_id']?>"
                               style="font-size:12px;padding:5px 10px;background:#fdecea;color:#c0392b;border-radius:6px;text-decoration:none;font-weight:600;margin-right:4px">
                                ✏️ Edit
                            </a>
                            <a href="?hapus_mk=<?=$r['mk_id']?>"
                               onclick="return confirm('Hapus pendaftaran mengulang ini?')"
                               class="btn-danger">🗑️ Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile;?>
                    <?php if(!$ada):?>
                    <tr><td colspan="4" style="text-align:center;color:#aaa;padding:30px">Belum ada mahasiswa mengulang di kelas Anda</td></tr>
                    <?php endif;?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>