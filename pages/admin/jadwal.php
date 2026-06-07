<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('admin');

$success = $error = "";

if (isset($_POST['tambah'])) {
    $kelas_id    = intval($_POST['kelas_id']);
    $hari        = $_POST['hari'];
    $jam_mulai   = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $ruangan     = mysqli_real_escape_string($conn, trim($_POST['ruangan']));

    $sql = "INSERT INTO jadwal (kelas_id, hari, jam_mulai, jam_selesai, ruangan)
            VALUES ($kelas_id,'$hari','$jam_mulai','$jam_selesai','$ruangan')";
    if (mysqli_query($conn, $sql)) $success = "Jadwal berhasil ditambahkan!";
    else $error = "Gagal: " . mysqli_error($conn);
}

if (isset($_GET['hapus'])) {
    mysqli_query($conn, "DELETE FROM jadwal WHERE id=" . intval($_GET['hapus']));
    $success = "Jadwal dihapus.";
}

// EDIT JADWAL — ambil data
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id   = intval($_GET['edit']);
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM jadwal WHERE id=$edit_id"));
}

// EDIT JADWAL — simpan
if (isset($_POST['simpan_edit'])) {
    $id          = intval($_POST['edit_id']);
    $kelas_id    = intval($_POST['kelas_id']);
    $hari        = $_POST['hari'];
    $jam_mulai   = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $ruangan     = mysqli_real_escape_string($conn, trim($_POST['ruangan']));

    mysqli_query($conn, "
        UPDATE jadwal
        SET kelas_id=$kelas_id, hari='$hari',
            jam_mulai='$jam_mulai', jam_selesai='$jam_selesai',
            ruangan='$ruangan'
        WHERE id=$id
    ");
    $success   = "Jadwal berhasil diperbarui!";
    $edit_data = null;
}

$hari_list = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

$kelas_list = mysqli_query($conn, "
    SELECT k.id, k.nama_kelas, k.kode_matkul, k.semester, k.kelas, u.nama AS nama_dosen
    FROM kelas k
    JOIN users u ON k.dosen_id = u.id
    ORDER BY k.semester, k.kelas, k.nama_kelas
");

$jadwal_list = mysqli_query($conn, "
    SELECT j.*, k.nama_kelas, k.kode_matkul, k.semester, k.kelas, u.nama AS nama_dosen
    FROM jadwal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN users u ON k.dosen_id = u.id
    ORDER BY k.semester, k.kelas,
             FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'),
             j.jam_mulai
");

// Kelompokkan jadwal per semester + kelas
$jadwal_group = [];
while ($j = mysqli_fetch_assoc($jadwal_list)) {
    $key = 'Semester ' . $j['semester'] . ' - Kelas ' . $j['kelas'];
    $jadwal_group[$key][$j['hari']][] = $j;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Kuliah</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; }
        .row-2 { display:grid; grid-template-columns:360px 1fr; gap:24px; align-items:start; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; margin-bottom:20px; }
        .card-header { padding:16px 20px; background:#145a32; }
        .card-header h3 { color:white; font-size:15px; }
        .card-body { padding:20px; }
        .form-group { margin-bottom:14px; }
        label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:5px; }
        input, select { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:14px; outline:none; transition:0.2s; font-family:inherit; }
        input:focus, select:focus { border-color:#145a32; }
        .btn-primary { background:#145a32; color:white; border:none; border-radius:8px; padding:11px; width:100%; font-size:14px; font-weight:600; cursor:pointer; }
        .btn-primary:hover { background:#0e3d22; }
        .btn-danger { background:#e74c3c; color:white; font-size:12px; padding:5px 10px; border:none; border-radius:6px; cursor:pointer; text-decoration:none; }
        .alert { padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }

        .group-label {
            font-size:14px; font-weight:700; color:#145a32;
            background:#e8f5e9; padding:10px 16px;
            border-radius:8px; margin-bottom:12px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .hari-label {
            font-size:12px; font-weight:700; color:#555;
            text-transform:uppercase; letter-spacing:1px;
            padding:6px 12px; background:#f5f5f5;
            border-radius:6px; margin:10px 0 8px;
            display:inline-block;
        }
        .jadwal-item {
            background:#fafafa; border-radius:10px;
            border-left:4px solid #145a32;
            padding:12px 16px; margin-bottom:8px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .jadwal-item .info h4 { font-size:13px; color:#1e3a5f; font-weight:600; }
        .jadwal-item .info p  { font-size:12px; color:#888; margin-top:2px; }
        .jadwal-item .kanan   { text-align:right; }
        .jadwal-item .waktu   { font-size:13px; font-weight:700; color:#145a32; }
        .jadwal-item .ruang   { font-size:11px; color:#aaa; margin-top:2px; }
        .empty-jadwal { text-align:center; padding:40px; color:#aaa; font-size:13px; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>🗓️ Jadwal Kuliah</h1></div>

    <div class="page-body">
        <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <div class="row-2">
            <!-- Form tambah ATAU edit jadwal -->
            <div class="card">
                <?php if ($edit_data): ?>
                <div class="card-header"><h3>✏️ Edit Jadwal</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
                        <div class="form-group">
                            <label>Mata Kuliah & Kelas</label>
                            <select name="kelas_id" required>
                                <option value="">-- Pilih Matkul --</option>
                                <?php
                                $kelas_edit = mysqli_query($conn, "
                                    SELECT k.id, k.nama_kelas, k.kode_matkul, k.semester, k.kelas
                                    FROM kelas k JOIN users u ON k.dosen_id=u.id
                                    ORDER BY k.semester, k.kelas, k.nama_kelas
                                ");
                                $prev_sem = 0;
                                while ($k = mysqli_fetch_assoc($kelas_edit)):
                                    if ($k['semester'] != $prev_sem) {
                                        if ($prev_sem != 0) echo '</optgroup>';
                                        echo '<optgroup label="Semester ' . $k['semester'] . '">';
                                        $prev_sem = $k['semester'];
                                    }
                                ?>
                                <option value="<?= $k['id'] ?>" <?= $edit_data['kelas_id']==$k['id']?'selected':'' ?>>
                                    Kelas <?= $k['kelas'] ?> — <?= htmlspecialchars($k['nama_kelas']) ?>
                                    (<?= $k['kode_matkul'] ?>)
                                </option>
                                <?php endwhile; ?>
                                <?php if ($prev_sem != 0) echo '</optgroup>'; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Hari</label>
                            <select name="hari" required>
                                <?php foreach ($hari_list as $h): ?>
                                <option value="<?= $h ?>" <?= $edit_data['hari']==$h?'selected':'' ?>><?= $h ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jam Mulai</label>
                            <input type="time" name="jam_mulai" value="<?= substr($edit_data['jam_mulai'],0,5) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Jam Selesai</label>
                            <input type="time" name="jam_selesai" value="<?= substr($edit_data['jam_selesai'],0,5) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Ruangan</label>
                            <input type="text" name="ruangan" value="<?= htmlspecialchars($edit_data['ruangan']) ?>" placeholder="Contoh: Lab Komputer A">
                        </div>
                        <div style="display:flex;gap:8px">
                            <button type="submit" name="simpan_edit" class="btn-primary" style="flex:1">💾 Simpan</button>
                            <a href="kelola_jadwal.php" style="flex:1;padding:11px;background:#f0f0f0;color:#666;border-radius:8px;text-align:center;text-decoration:none;font-size:14px;font-weight:600">Batal</a>
                        </div>
                    </form>
                </div>

                <?php else: ?>
                <div class="card-header"><h3>➕ Tambah Jadwal</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Mata Kuliah & Kelas</label>
                            <select name="kelas_id" required>
                                <option value="">-- Pilih Matkul --</option>
                                <?php
                                $prev_sem = 0;
                                while ($k = mysqli_fetch_assoc($kelas_list)):
                                    if ($k['semester'] != $prev_sem) {
                                        if ($prev_sem != 0) echo '</optgroup>';
                                        echo '<optgroup label="Semester ' . $k['semester'] . '">';
                                        $prev_sem = $k['semester'];
                                    }
                                ?>
                                <option value="<?= $k['id'] ?>">
                                    Kelas <?= $k['kelas'] ?> — <?= htmlspecialchars($k['nama_kelas']) ?>
                                    (<?= $k['kode_matkul'] ?>)
                                </option>
                                <?php endwhile; ?>
                                <?php if ($prev_sem != 0) echo '</optgroup>'; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Hari</label>
                            <select name="hari" required>
                                <option value="">-- Pilih Hari --</option>
                                <?php foreach ($hari_list as $h): ?>
                                <option value="<?= $h ?>"><?= $h ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jam Mulai</label>
                            <input type="time" name="jam_mulai" required>
                        </div>
                        <div class="form-group">
                            <label>Jam Selesai</label>
                            <input type="time" name="jam_selesai" required>
                        </div>
                        <div class="form-group">
                            <label>Ruangan</label>
                            <input type="text" name="ruangan" placeholder="Contoh: Lab Komputer A">
                        </div>
                        <button type="submit" name="tambah" class="btn-primary">Tambah Jadwal</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tampilan jadwal per semester + kelas -->
            <div>
                <?php if (empty($jadwal_group)): ?>
                    <div class="card">
                        <div class="empty-jadwal">
                            📭 Belum ada jadwal. Tambahkan di form kiri.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($jadwal_group as $group_key => $hari_data): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="group-label">
                                <span>📅 <?= $group_key ?></span>
                            </div>
                            <?php foreach ($hari_list as $hari): ?>
                                <?php if (!isset($hari_data[$hari])) continue; ?>
                                <div class="hari-label">📌 <?= $hari ?></div>
                                <?php foreach ($hari_data[$hari] as $j): ?>
                                <div class="jadwal-item">
                                    <div class="info">
                                        <h4><?= htmlspecialchars($j['nama_kelas']) ?>
                                            <small style="color:#aaa;font-weight:400">(<?= $j['kode_matkul'] ?>)</small>
                                        </h4>
                                        <p>👨‍🏫 <?= htmlspecialchars($j['nama_dosen']) ?></p>
                                    </div>
                                    <div class="kanan">
                                        <div class="waktu">
                                            <?= substr($j['jam_mulai'],0,5) ?> – <?= substr($j['jam_selesai'],0,5) ?>
                                        </div>
                                        <div class="ruang">📍 <?= htmlspecialchars($j['ruangan'] ?: 'Ruangan belum diset') ?></div>
                                        <div style="margin-top:6px;display:flex;gap:6px;justify-content:flex-end">
                                            <a href="?edit=<?= $j['id'] ?>"
                                               style="font-size:12px;padding:5px 10px;background:#e8f5e9;color:#145a32;border-radius:6px;text-decoration:none;font-weight:600">
                                                ✏️
                                            </a>
                                            <a href="?hapus=<?= $j['id'] ?>"
                                               onclick="return confirm('Hapus jadwal ini?')"
                                               class="btn-danger">🗑️
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>