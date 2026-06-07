<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('admin');

$success = $error = "";

if (isset($_POST['tambah'])) {
    $nama_kelas  = mysqli_real_escape_string($conn, trim($_POST['nama_kelas']));
    $kode_matkul = mysqli_real_escape_string($conn, trim($_POST['kode_matkul']));
    $semester    = intval($_POST['semester']);
    $kelas       = $_POST['kelas'];
    $sks         = intval($_POST['sks']);
    $tahun_ajaran= mysqli_real_escape_string($conn, trim($_POST['tahun_ajaran']));
    $dosen_id    = intval($_POST['dosen_id']);

    $kelas_valid = ['A','B','C','D','E'];
    if (!in_array($kelas, $kelas_valid)) {
        $error = "Kelas tidak valid!";
    } else {
        $sql = "INSERT INTO kelas (nama_kelas, kode_matkul, semester, kelas, sks, tahun_ajaran, dosen_id)
                VALUES ('$nama_kelas','$kode_matkul',$semester,'$kelas',$sks,'$tahun_ajaran',$dosen_id)";

        if (mysqli_query($conn, $sql)) {
            $kelas_id = mysqli_insert_id($conn);

            $mhs_cocok = mysqli_query($conn, "
                SELECT id FROM users
                WHERE role='mahasiswa' AND semester=$semester AND kelas='$kelas'
            ");
            while ($m = mysqli_fetch_assoc($mhs_cocok)) {
                mysqli_query($conn, "
                    INSERT IGNORE INTO mahasiswa_kelas (mahasiswa_id, kelas_id)
                    VALUES ({$m['id']}, $kelas_id)
                ");
            }

            $success = "Kelas berhasil dibuat dan mahasiswa terkait otomatis terdaftar!";
        } else {
            $error = "Gagal: " . mysqli_error($conn);
        }
    }
}

if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    mysqli_query($conn, "DELETE FROM mahasiswa_kelas WHERE kelas_id=$id");
    mysqli_query($conn, "DELETE FROM jadwal WHERE kelas_id=$id");
    mysqli_query($conn, "DELETE FROM kelas WHERE id=$id");
    $success = "Kelas dihapus.";
}

// EDIT KELAS — ambil data
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id   = intval($_GET['edit']);
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM kelas WHERE id=$edit_id"));
}

// EDIT KELAS — simpan
if (isset($_POST['simpan_edit'])) {
    $id          = intval($_POST['edit_id']);
    $nama_kelas  = mysqli_real_escape_string($conn, trim($_POST['nama_kelas']));
    $kode_matkul = mysqli_real_escape_string($conn, trim($_POST['kode_matkul']));
    $semester    = intval($_POST['semester']);
    $kelas       = $_POST['kelas'];
    $sks         = intval($_POST['sks']);
    $tahun_ajaran= mysqli_real_escape_string($conn, trim($_POST['tahun_ajaran']));
    $dosen_id    = intval($_POST['dosen_id']);

    $kelas_valid = ['A','B','C','D','E'];
    if (!in_array($kelas, $kelas_valid)) {
        $error = "Kelas tidak valid!";
    } else {
        mysqli_query($conn, "
            UPDATE kelas
            SET nama_kelas='$nama_kelas', kode_matkul='$kode_matkul',
                semester=$semester, kelas='$kelas', sks=$sks,
                tahun_ajaran='$tahun_ajaran', dosen_id=$dosen_id
            WHERE id=$id
        ");
        $success   = "Data kelas berhasil diperbarui!";
        $edit_data = null;
    }
}

$dosen_list = mysqli_query($conn, "SELECT id, nama FROM users WHERE role='dosen' ORDER BY nama");
$kelas_list = mysqli_query($conn, "
    SELECT k.*, u.nama AS nama_dosen,
        (SELECT COUNT(*) FROM mahasiswa_kelas mk WHERE mk.kelas_id=k.id) AS jml_mahasiswa
    FROM kelas k
    JOIN users u ON k.dosen_id=u.id
    ORDER BY k.semester, k.kelas, k.nama_kelas
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kelas</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; }
        .row-2 { display:grid; grid-template-columns:400px 1fr; gap:24px; align-items:start; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .card-header { padding:16px 20px; background:#1a5276; }
        .card-header h3 { color:white; font-size:15px; }
        .card-body { padding:20px; }
        .form-group { margin-bottom:14px; }
        label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:5px; }
        input, select { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:14px; outline:none; transition:0.2s; font-family:inherit; }
        input:focus, select:focus { border-color:#1a5276; }
        .row-in { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

        .kelas-row { display:flex; gap:6px; }
        .kelas-btn { flex:1; padding:10px 0; border:2px solid #e0e0e0; border-radius:8px; text-align:center; font-size:14px; font-weight:700; color:#888; cursor:pointer; transition:0.2s; background:white; }
        .kelas-btn:hover  { border-color:#1a5276; color:#1a5276; }
        .kelas-btn.aktif  { border-color:#1a5276; background:#1a5276; color:white; }

        .btn-primary { background:#1a5276; color:white; border:none; border-radius:8px; padding:11px; width:100%; font-size:14px; font-weight:600; cursor:pointer; }
        .btn-primary:hover { background:#154360; }
        .btn-danger { background:#e74c3c; color:white; font-size:12px; padding:5px 10px; border:none; border-radius:6px; cursor:pointer; text-decoration:none; }
        .alert { padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }

        .semester-group { margin-bottom:24px; }
        .semester-label { font-size:13px; font-weight:700; color:#1a5276; background:#e8f0fe; padding:8px 14px; border-radius:8px; margin-bottom:10px; display:inline-block; }
        table { width:100%; border-collapse:collapse; }
        th { background:#f8f9fa; padding:9px 14px; text-align:left; font-size:11px; color:#888; font-weight:700; text-transform:uppercase; }
        td { padding:11px 14px; font-size:13px; color:#444; border-bottom:1px solid #f5f5f5; }
        tr:last-child td { border-bottom:none; }
        .badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-kelas { background:#e8f0fe; color:#1a5276; font-size:13px; font-weight:800; }
        .badge-mhs   { background:#e8f5e9; color:#2e7d32; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>📚 Kelola Kelas & Matkul</h1></div>

    <div class="page-body">
        <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <div class="row-2">
            <!-- Form tambah ATAU edit kelas -->
            <div class="card">
                <?php if ($edit_data): ?>
                <div class="card-header"><h3>✏️ Edit Kelas</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
                        <div class="form-group">
                            <label>Nama Mata Kuliah</label>
                            <input type="text" name="nama_kelas" value="<?= htmlspecialchars($edit_data['nama_kelas']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Kode Matkul</label>
                            <input type="text" name="kode_matkul" value="<?= htmlspecialchars($edit_data['kode_matkul']) ?>" required>
                        </div>
                        <div class="row-in">
                            <div class="form-group">
                                <label>Semester</label>
                                <select name="semester" required>
                                    <?php for ($i=1; $i<=8; $i++): ?>
                                    <option value="<?= $i ?>" <?= $edit_data['semester']==$i?'selected':'' ?>>Semester <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>SKS</label>
                                <select name="sks">
                                    <?php for ($i=1; $i<=6; $i++): ?>
                                    <option value="<?= $i ?>" <?= $edit_data['sks']==$i?'selected':'' ?>><?= $i ?> SKS</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Kelas</label>
                            <div class="kelas-row">
                                <?php foreach (['A','B','C','D','E'] as $k): ?>
                                <div class="kelas-btn <?= $edit_data['kelas']==$k?'aktif':'' ?>"
                                     onclick="pilihKelas(this,'<?= $k ?>','edit')">
                                    <?= $k ?>
                                </div>
                                <?php endforeach; ?>
                                <input type="hidden" name="kelas" id="input_kelas_edit" value="<?= htmlspecialchars($edit_data['kelas']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" value="<?= htmlspecialchars($edit_data['tahun_ajaran']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Dosen Pengampu</label>
                            <select name="dosen_id" required>
                                <option value="">-- Pilih Dosen --</option>
                                <?php
                                $dosen_list2 = mysqli_query($conn, "SELECT id, nama FROM users WHERE role='dosen' ORDER BY nama");
                                while ($d = mysqli_fetch_assoc($dosen_list2)): ?>
                                <option value="<?= $d['id'] ?>" <?= $edit_data['dosen_id']==$d['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($d['nama']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div style="display:flex;gap:8px">
                            <button type="submit" name="simpan_edit" class="btn-primary" style="flex:1">💾 Simpan</button>
                            <a href="kelola_kelas.php" style="flex:1;padding:11px;background:#f0f0f0;color:#666;border-radius:8px;text-align:center;text-decoration:none;font-size:14px;font-weight:600">Batal</a>
                        </div>
                    </form>
                </div>

                <?php else: ?>
                <div class="card-header"><h3>➕ Tambah Matkul / Kelas</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Nama Mata Kuliah</label>
                            <input type="text" name="nama_kelas" placeholder="Pemrograman Web Lanjut" required>
                        </div>
                        <div class="form-group">
                            <label>Kode Matkul</label>
                            <input type="text" name="kode_matkul" placeholder="PWL401" required>
                        </div>
                        <div class="row-in">
                            <div class="form-group">
                                <label>Semester</label>
                                <select name="semester" required>
                                    <option value="">-- Pilih --</option>
                                    <?php for ($i=1; $i<=8; $i++): ?>
                                    <option value="<?= $i ?>">Semester <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>SKS</label>
                                <select name="sks">
                                    <?php for ($i=1; $i<=6; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i==3?'selected':'' ?>><?= $i ?> SKS</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Kelas</label>
                            <div class="kelas-row">
                                <?php foreach (['A','B','C','D','E'] as $k): ?>
                                <div class="kelas-btn" onclick="pilihKelas(this,'<?= $k ?>','admin')">
                                    <?= $k ?>
                                </div>
                                <?php endforeach; ?>
                                <input type="hidden" name="kelas" id="input_kelas_admin" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" placeholder="2024/2025" required>
                        </div>
                        <div class="form-group">
                            <label>Dosen Pengampu</label>
                            <select name="dosen_id" required>
                                <option value="">-- Pilih Dosen --</option>
                                <?php while ($d = mysqli_fetch_assoc($dosen_list)): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nama']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" name="tambah" class="btn-primary">Tambah Kelas</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Daftar kelas dikelompokkan per semester -->
            <div>
                <?php
                $kelas_per_sem = [];
                while ($k = mysqli_fetch_assoc($kelas_list)) {
                    $kelas_per_sem[$k['semester']][] = $k;
                }

                if (empty($kelas_per_sem)) {
                    echo '<div class="card"><div class="card-body" style="text-align:center;color:#aaa;padding:40px">Belum ada kelas. Tambahkan di form kiri.</div></div>';
                }

                for ($sem = 1; $sem <= 8; $sem++):
                    if (!isset($kelas_per_sem[$sem])) continue;
                ?>
                <div class="semester-group">
                    <div class="semester-label">📅 Semester <?= $sem ?></div>
                    <div class="card">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Mata Kuliah</th>
                                    <th>Dosen</th>
                                    <th>SKS</th>
                                    <th>Mahasiswa</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($kelas_per_sem[$sem] as $k): ?>
                            <tr>
                                <td><span class="badge badge-kelas"><?= $k['kelas'] ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($k['nama_kelas']) ?></strong><br>
                                    <small style="color:#aaa"><?= $k['kode_matkul'] ?> — <?= $k['tahun_ajaran'] ?></small>
                                </td>
                                <td><?= htmlspecialchars($k['nama_dosen']) ?></td>
                                <td><?= $k['sks'] ?></td>
                                <td><span class="badge badge-mhs">👥 <?= $k['jml_mahasiswa'] ?></span></td>
                                <td style="white-space:nowrap;">
                                    <a href="?edit=<?= $k['id'] ?>"
                                    style="font-size:12px;padding:5px 10px;background:#e8f0fe;color:#1a5276;border-radius:6px;text-decoration:none;font-weight:600;margin-right:4px;display:inline-block;">
                                    ✏️
                                    </a>

                                    <a href="?hapus=<?= $k['id'] ?>"
                                    onclick="return confirm('Hapus kelas ini?')"
                                    class="btn-danger">
                                    🗑️
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<script>
function pilihKelas(el, k, mode) {
    const inputId = mode === 'edit' ? 'input_kelas_edit' : 'input_kelas_admin';
    el.closest('.kelas-row').querySelectorAll('.kelas-btn').forEach(b => b.classList.remove('aktif'));
    el.classList.add('aktif');
    document.getElementById(inputId).value = k;
}
</script>
</body>
</html>