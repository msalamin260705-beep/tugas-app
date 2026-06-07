<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('dosen');

$dosen_id = $_SESSION['user_id'];
$success = $error = "";

// TAMBAH TUGAS
if (isset($_POST['tambah'])) {
    $judul     = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
    $deadline  = $_POST['deadline'];
    $kelas_id  = intval($_POST['kelas_id']);

    $sql = "INSERT INTO tugas (judul, deskripsi, deadline, kelas_id, dosen_id)
            VALUES ('$judul','$deskripsi','$deadline',$kelas_id,$dosen_id)";
    if (mysqli_query($conn, $sql)) $success = "Tugas berhasil dibuat!";
    else $error = "Gagal membuat tugas.";
}

// HAPUS TUGAS
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    mysqli_query($conn, "DELETE FROM submissions WHERE tugas_id=$id");
    mysqli_query($conn, "DELETE FROM tugas WHERE id=$id AND dosen_id=$dosen_id");
    $success = "Tugas dihapus.";
}

// EDIT — ambil data
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id   = intval($_GET['edit']);
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM tugas WHERE id=$edit_id AND dosen_id=$dosen_id"));
    if (!$edit_data) { $error = "Tugas tidak ditemukan."; }
}

// EDIT — simpan
if (isset($_POST['simpan_edit'])) {
    $id        = intval($_POST['edit_id']);
    $judul     = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
    $deadline  = $_POST['deadline'];
    $kelas_id  = intval($_POST['kelas_id']);

    mysqli_query($conn, "
        UPDATE tugas SET judul='$judul', deskripsi='$deskripsi',
            deadline='$deadline', kelas_id=$kelas_id
        WHERE id=$id AND dosen_id=$dosen_id
    ");
    $success   = "Tugas berhasil diperbarui!";
    $edit_data = null;
}

$tugas_list = mysqli_query($conn, "
    SELECT t.*, k.nama_kelas,
        (SELECT COUNT(*) FROM submissions s WHERE s.tugas_id=t.id) AS jml_submit,
        (SELECT COUNT(*) FROM submissions s WHERE s.tugas_id=t.id AND s.nilai IS NULL) AS belum_nilai
    FROM tugas t
    JOIN kelas k ON t.kelas_id=k.id
    WHERE t.dosen_id=$dosen_id
    ORDER BY t.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Tugas</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; }
        .row-2 { display:grid; grid-template-columns:380px 1fr; gap:24px; align-items:start; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; margin-bottom:16px; }
        .card-header { padding:16px 20px; background:#1a5276; }
        .card-header h3 { color:white; font-size:15px; }
        .card-body { padding:20px; }
        .form-group { margin-bottom:14px; }
        label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:5px; }
        input, select, textarea { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:14px; outline:none; transition:0.2s; font-family:inherit; }
        input:focus, select:focus, textarea:focus { border-color:#1a5276; }
        textarea { resize:vertical; min-height:90px; }
        .btn-primary { background:#1a5276; color:white; border:none; border-radius:8px; padding:11px; width:100%; font-size:14px; font-weight:600; cursor:pointer; }
        .btn-primary:hover { background:#154360; }
        .btn-danger { background:#e74c3c; color:white; font-size:12px; padding:5px 10px; border:none; border-radius:6px; cursor:pointer; text-decoration:none; }
        .btn-edit { font-size:12px; padding:5px 10px; background:#e8f0fe; color:#1a5276; border-radius:6px; text-decoration:none; font-weight:600; }
        .alert { padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }
        .tugas-item { background:white; border-radius:10px; border-left:4px solid #1a5276; padding:16px 18px; margin-bottom:12px; box-shadow:0 1px 4px rgba(0,0,0,0.06); }
        .tugas-item h4 { font-size:15px; color:#1e3a5f; margin-bottom:4px; }
        .tugas-item p  { font-size:12px; color:#777; margin-bottom:8px; line-height:1.5; }
        .tugas-footer { display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid #f0f0f0; }
        .badge { padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-blue    { background:#e8f0fe; color:#1a5276; }
        .badge-warning { background:#fff8e1; color:#e65100; }
        .badge-danger  { background:#ffebee; color:#c62828; }
        .meta-row { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:8px; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar"><h1>📝 Kelola Tugas</h1></div>

    <div class="page-body">
        <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <div class="row-2">
            <!-- Form tambah ATAU edit -->
            <div class="card">
                <?php if ($edit_data): ?>
                <div class="card-header"><h3>✏️ Edit Tugas</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
                        <div class="form-group">
                            <label>Judul Tugas</label>
                            <input type="text" name="judul" value="<?= htmlspecialchars($edit_data['judul']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Kelas</label>
                            <select name="kelas_id" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php
                                $kelas_edit = mysqli_query($conn, "SELECT * FROM kelas WHERE dosen_id=$dosen_id ORDER BY nama_kelas");
                                while ($k = mysqli_fetch_assoc($kelas_edit)):
                                ?>
                                <option value="<?= $k['id'] ?>" <?= $edit_data['kelas_id']==$k['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Deskripsi Tugas</label>
                            <textarea name="deskripsi"><?= htmlspecialchars($edit_data['deskripsi']) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Deadline</label>
                            <input type="datetime-local" name="deadline"
                                   value="<?= date('Y-m-d\TH:i', strtotime($edit_data['deadline'])) ?>" required>
                        </div>
                        <div style="display:flex;gap:8px">
                            <button type="submit" name="simpan_edit" class="btn-primary" style="flex:1">💾 Simpan</button>
                            <a href="tugas.php" style="flex:1;padding:11px;background:#f0f0f0;color:#666;border-radius:8px;text-align:center;text-decoration:none;font-size:14px;font-weight:600">Batal</a>
                        </div>
                    </form>
                </div>

                <?php else: ?>
                <div class="card-header"><h3>➕ Buat Tugas Baru</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Judul Tugas</label>
                            <input type="text" name="judul" placeholder="Contoh: UTS Pemrograman Web" required>
                        </div>
                        <div class="form-group">
                            <label>Kelas</label>
                            <select name="kelas_id" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php
                                $kelas_dosen = mysqli_query($conn, "SELECT * FROM kelas WHERE dosen_id=$dosen_id ORDER BY nama_kelas");
                                while ($k = mysqli_fetch_assoc($kelas_dosen)):
                                ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Deskripsi Tugas</label>
                            <textarea name="deskripsi" placeholder="Jelaskan instruksi tugas..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Deadline</label>
                            <input type="datetime-local" name="deadline" required>
                        </div>
                        <button type="submit" name="tambah" class="btn-primary">📝 Buat Tugas</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Daftar tugas -->
            <div>
                <?php if (mysqli_num_rows($tugas_list) == 0): ?>
                    <div class="card"><div class="card-body" style="text-align:center;color:#aaa;padding:40px">Belum ada tugas. Buat tugas pertama di form kiri!</div></div>
                <?php endif; ?>

                <?php while ($t = mysqli_fetch_assoc($tugas_list)):
                    $lewat = strtotime($t['deadline']) < time();
                ?>
                <div class="tugas-item">
                    <h4><?= htmlspecialchars($t['judul']) ?></h4>
                    <p><?= htmlspecialchars(substr($t['deskripsi'], 0, 120)) ?><?= strlen($t['deskripsi']) > 120 ? '...' : '' ?></p>
                    <div class="meta-row">
                        <span class="badge badge-blue">📚 <?= htmlspecialchars($t['nama_kelas']) ?></span>
                        <span class="badge <?= $lewat ? 'badge-danger' : 'badge-warning' ?>">
                            ⏰ <?= date('d M Y H:i', strtotime($t['deadline'])) ?>
                            <?= $lewat ? ' (Lewat)' : '' ?>
                        </span>
                    </div>
                    <div class="tugas-footer">
                        <span style="font-size:12px;color:#888">
                            📬 <?= $t['jml_submit'] ?> submission &nbsp;|&nbsp;
                            ⏳ <?= $t['belum_nilai'] ?> belum dinilai
                        </span>
                        <div style="display:flex;gap:8px">
                            <a href="?edit=<?= $t['id'] ?>" class="btn-edit">✏️</a>
                            <a href="?hapus=<?= $t['id'] ?>"
                               onclick="return confirm('Hapus tugas ini beserta semua submission-nya?')"
                               class="btn-danger">🗑️</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div,
        </div>
    </div>
</div>
</body>
</html>