<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('mahasiswa');

$mhs_id  = $_SESSION['user_id'];
$success = $error = "";

$tugas_id = isset($_GET['tugas_id']) ? intval($_GET['tugas_id']) : 0;

// ─── PROSES EDIT SUBMISSION ───────────────────────────────────────────────────
if (isset($_POST['edit_tugas'])) {
    $submission_id = intval($_POST['submission_id']);

    // Ambil data submission + deadline
    $cek = mysqli_query($conn, "
        SELECT s.*, t.deadline
        FROM submissions s
        JOIN tugas t ON s.tugas_id = t.id
        WHERE s.id = $submission_id AND s.mahasiswa_id = $mhs_id
    ");
    $sub = mysqli_fetch_assoc($cek);

    if (!$sub) {
        $error = "Submission tidak ditemukan.";
    } elseif (strtotime($sub['deadline']) < time()) {
        $error = "Tidak bisa mengedit — deadline sudah lewat!";
    } elseif ($sub['nilai'] !== null) {
        $error = "Tidak bisa mengedit — tugas sudah dinilai!";
    } else {
        $catatan   = mysqli_real_escape_string($conn, trim($_POST['catatan']));
        $nama_file = $sub['file_path']; // default pakai file lama

        // Jika ada file baru diupload
        if (isset($_FILES['file_tugas']) && $_FILES['file_tugas']['error'] === 0) {
            $file    = $_FILES['file_tugas'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'zip', 'rar', 'jpg', 'png', 'mp4', 'mov', 'avi', 'mkv', 'webm'];
            $max_size = 500 * 1024 * 1024; // 500MB untuk video

            if (!in_array($ext, $allowed)) {
                $error = "Format file tidak diizinkan! Gunakan: PDF, DOC, DOCX, ZIP, RAR, JPG, PNG, MP4, MOV, AVI, MKV, WEBM";
            } elseif ($file['size'] > $max_size) {
                $error = "Ukuran file maksimal 500MB!";
            } else {
                $nama_file_baru = 'tugas_' . $mhs_id . '_' . $sub['tugas_id'] . '_' . time() . '.' . $ext;
                $tujuan = '../../uploads/tugas/' . $nama_file_baru;
                if (move_uploaded_file($file['tmp_name'], $tujuan)) {
                    // Hapus file lama
                    $file_lama = '../../uploads/tugas/' . $sub['file_path'];
                    if (file_exists($file_lama)) unlink($file_lama);
                    $nama_file = $nama_file_baru;
                } else {
                    $error = "Gagal mengupload file baru.";
                }
            }
        }

        if (!$error) {
            $sql = "UPDATE submissions SET file_path='$nama_file', catatan='$catatan', submitted_at=NOW()
                    WHERE id=$submission_id AND mahasiswa_id=$mhs_id";
            if (mysqli_query($conn, $sql)) {
                $success = "Submission berhasil diperbarui! ✅";
            } else {
                $error = "Gagal memperbarui data.";
            }
        }
    }
}

// ─── PROSES UPLOAD BARU ───────────────────────────────────────────────────────
if (isset($_POST['submit_tugas'])) {
    $tugas_id  = intval($_POST['tugas_id']);
    $catatan   = mysqli_real_escape_string($conn, trim($_POST['catatan']));

    $cek = mysqli_query($conn, "SELECT id FROM submissions WHERE tugas_id=$tugas_id AND mahasiswa_id=$mhs_id");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Kamu sudah pernah mengumpulkan tugas ini!";
    } elseif (!isset($_FILES['file_tugas']) || $_FILES['file_tugas']['error'] !== 0) {
        $error = "Pilih file yang akan diupload!";
    } else {
        $file     = $_FILES['file_tugas'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['pdf', 'doc', 'docx', 'zip', 'rar', 'jpg', 'png', 'mp4', 'mov', 'avi', 'mkv', 'webm'];
        $max_size = 500 * 1024 * 1024; // 500MB untuk video

        if (!in_array($ext, $allowed)) {
            $error = "Format file tidak diizinkan! Gunakan: PDF, DOC, DOCX, ZIP, RAR, JPG, PNG, MP4, MOV, AVI, MKV, WEBM";
        } elseif ($file['size'] > $max_size) {
            $error = "Ukuran file maksimal 500MB!";
        } else {
            $nama_file = 'tugas_' . $mhs_id . '_' . $tugas_id . '_' . time() . '.' . $ext;
            $tujuan    = '../../uploads/tugas/' . $nama_file;
            if (move_uploaded_file($file['tmp_name'], $tujuan)) {
                $sql = "INSERT INTO submissions (tugas_id, mahasiswa_id, file_path, catatan)
                        VALUES ($tugas_id, $mhs_id, '$nama_file', '$catatan')";
                if (mysqli_query($conn, $sql)) {
                    $success = "Tugas berhasil dikumpulkan! ✅";
                    $tugas_id = 0;
                } else {
                    $error = "Gagal menyimpan ke database.";
                }
            } else {
                $error = "Gagal mengupload file. Pastikan folder uploads/tugas/ ada dan bisa ditulis.";
            }
        }
    }
}

// ─── QUERY DATA ───────────────────────────────────────────────────────────────
$tugas_tersedia = mysqli_query($conn, "
    SELECT t.*, k.nama_kelas, u.nama AS nama_dosen
    FROM tugas t
    JOIN kelas k ON t.kelas_id = k.id
    JOIN users u ON k.dosen_id = u.id
    JOIN mahasiswa_kelas mk ON mk.kelas_id = k.id
    WHERE mk.mahasiswa_id = $mhs_id
      AND t.deadline > NOW()
      AND t.id NOT IN (SELECT tugas_id FROM submissions WHERE mahasiswa_id=$mhs_id)
    ORDER BY t.deadline ASC
");

$riwayat = mysqli_query($conn, "
    SELECT s.*, t.judul, t.deadline, k.nama_kelas
    FROM submissions s
    JOIN tugas t ON s.tugas_id = t.id
    JOIN kelas k ON t.kelas_id = k.id
    WHERE s.mahasiswa_id = $mhs_id
    ORDER BY s.submitted_at DESC
");

$tugas_dipilih = null;
if ($tugas_id > 0) {
    $res = mysqli_query($conn, "SELECT t.*, k.nama_kelas FROM tugas t JOIN kelas k ON t.kelas_id=k.id WHERE t.id=$tugas_id");
    $tugas_dipilih = mysqli_fetch_assoc($res);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Submit Tugas</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; }
        .row-2 { display:grid; grid-template-columns:400px 1fr; gap:24px; align-items:start; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; margin-bottom:20px; }
        .card-header { padding:16px 20px; background:#145a32; }
        .card-header h3 { color:white; font-size:15px; }
        .card-header.riwayat { background:#1a5276; }
        .card-body { padding:20px; }
        .form-group { margin-bottom:14px; }
        label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:5px; }
        select, textarea { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:14px; outline:none; transition:0.2s; font-family:inherit; }
        select:focus, textarea:focus { border-color:#145a32; }
        textarea { resize:vertical; min-height:80px; }
        .file-drop {
            border:2px dashed #c0d9c0; border-radius:10px; padding:30px;
            text-align:center; cursor:pointer; transition:0.2s; background:#f9fff9;
        }
        .file-drop:hover { border-color:#145a32; background:#f0faf0; }
        .file-drop input { display:none; }
        .file-drop .icon { font-size:32px; margin-bottom:8px; }
        .file-drop p { font-size:13px; color:#888; }
        .file-drop .selected { font-size:13px; font-weight:600; color:#145a32; margin-top:8px; }
        .btn-primary { background:#145a32; color:white; border:none; border-radius:8px; padding:12px; width:100%; font-size:14px; font-weight:600; cursor:pointer; transition:0.2s; }
        .btn-primary:hover { background:#0e3d22; }
        .alert { padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }
        table { width:100%; border-collapse:collapse; }
        th { background:#f8f9fa; padding:9px 16px; text-align:left; font-size:11px; color:#888; font-weight:700; text-transform:uppercase; }
        td { padding:11px 16px; font-size:13px; color:#444; border-bottom:1px solid #f5f5f5; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        .badge { padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-green  { background:#e8f5e9; color:#2e7d32; }
        .badge-blue   { background:#e8f0fe; color:#1a5276; }
        .badge-orange { background:#fff8e1; color:#e65100; }
        .info-tugas { background:#f0faf0; border-radius:8px; padding:12px 14px; margin-bottom:16px; border-left:3px solid #145a32; font-size:13px; color:#333; }

        /* ── Tombol Edit ── */
        .btn-edit {
            display:inline-flex; align-items:center; gap:4px;
            background:#fff8e1; color:#e65100; border:1px solid #ffe0b2;
            border-radius:6px; padding:4px 10px; font-size:11px; font-weight:700;
            cursor:pointer; text-decoration:none; transition:0.2s; white-space:nowrap;
        }
        .btn-edit:hover { background:#ffe0b2; }
        .btn-edit.disabled {
            background:#f5f5f5; color:#bbb; border-color:#e0e0e0;
            cursor:not-allowed; pointer-events:none;
        }
        .deadline-passed { font-size:10px; color:#bbb; display:block; margin-top:3px; }

        /* ── Modal Edit ── */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.45); z-index:1000;
            align-items:center; justify-content:center;
        }
        .modal-overlay.active { display:flex; }
        .modal {
            background:white; border-radius:14px; width:100%; max-width:500px;
            box-shadow:0 8px 32px rgba(0,0,0,0.18); overflow:hidden;
            animation:modalIn 0.2s ease;
        }
        @keyframes modalIn {
            from { transform:translateY(20px); opacity:0; }
            to   { transform:translateY(0);    opacity:1; }
        }
        .modal-header {
            background:#145a32; padding:16px 20px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .modal-header h3 { color:white; font-size:15px; }
        .modal-close { background:none; border:none; color:white; font-size:20px; cursor:pointer; line-height:1; }
        .modal-body { padding:22px; }
        .modal-footer { padding:0 22px 22px; }

        /* file drop di modal — lebih compact */
        .file-drop-sm {
            border:2px dashed #c0d9c0; border-radius:8px; padding:16px;
            text-align:center; cursor:pointer; transition:0.2s; background:#f9fff9;
        }
        .file-drop-sm:hover { border-color:#145a32; background:#f0faf0; }
        .file-drop-sm input { display:none; }
        .file-drop-sm .selected { font-size:12px; font-weight:600; color:#145a32; margin-top:6px; }

        .file-current-sm {
            background:#f0f8ff; border:1px solid #bde0fc; border-radius:7px;
            padding:9px 12px; font-size:12px; margin-bottom:10px;
            display:flex; align-items:center; gap:8px;
        }
        .file-current-sm a { color:#145a32; font-weight:600; text-decoration:none; }
        .file-current-sm a:hover { text-decoration:underline; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>📤 Submit Tugas</h1></div>

    <div class="page-body">
        <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <div class="row-2">
            <!-- Form submit -->
            <div class="card">
                <div class="card-header"><h3>📤 Kumpulkan Tugas</h3></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Pilih Tugas</label>
                            <select name="tugas_id" id="tugas_select" required onchange="updateInfo(this)">
                                <option value="">-- Pilih Tugas --</option>
                                <?php while ($t = mysqli_fetch_assoc($tugas_tersedia)):
                                    $sel = ($tugas_id == $t['id']) ? 'selected' : '';
                                ?>
                                <option value="<?= $t['id'] ?>" <?= $sel ?>
                                    data-kelas="<?= htmlspecialchars($t['nama_kelas']) ?>"
                                    data-dosen="<?= htmlspecialchars($t['nama_dosen']) ?>"
                                    data-deadline="<?= date('d M Y H:i', strtotime($t['deadline'])) ?>"
                                    data-desk="<?= htmlspecialchars(substr($t['deskripsi'],0,200)) ?>">
                                    <?= htmlspecialchars($t['judul']) ?> — <?= htmlspecialchars($t['nama_kelas']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div id="info_tugas" style="display:none" class="info-tugas"></div>

                        <div class="form-group">
                            <label>File Tugas</label>
                            <div class="file-drop" onclick="document.getElementById('file_input').click()">
                                <input type="file" id="file_input" name="file_tugas"
                                       accept=".pdf,.doc,.docx,.zip,.rar,.jpg,.png,.mp4,.mov,.avi,.mkv,.webm"
                                       onchange="tampilkanFile(this,'nama_file')">
                                <div class="icon">📁</div>
                                <p>Klik untuk pilih file</p>
                                <p style="font-size:11px;color:#aaa;margin-top:4px">PDF, DOC, DOCX, ZIP, RAR, JPG, PNG, MP4, MOV, AVI, MKV, WEBM — Maks 500MB</p>
                                <div class="selected" id="nama_file">Belum ada file dipilih</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Catatan (opsional)</label>
                            <textarea name="catatan" placeholder="Tulis catatan untuk dosen..."></textarea>
                        </div>

                        <button type="submit" name="submit_tugas" class="btn-primary">
                            📤 Kumpulkan Tugas
                        </button>
                    </form>
                </div>
            </div>

            <!-- Riwayat -->
            <div class="card">
                <div class="card-header riwayat"><h3>📋 Riwayat Pengumpulan</h3></div>
                <table>
                    <thead>
                        <tr>
                            <th>Tugas</th>
                            <th>Dikumpul</th>
                            <th>Nilai</th>
                            <th>File</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $ada = false;
                    while ($r = mysqli_fetch_assoc($riwayat)):
                        $ada = true;
                        $bisa_edit     = strtotime($r['deadline']) > time() && $r['nilai'] === null;
                        $deadline_unix = strtotime($r['deadline']);
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($r['judul']) ?></strong><br>
                            <small style="color:#aaa"><?= htmlspecialchars($r['nama_kelas']) ?></small>
                        </td>
                        <td style="font-size:12px;color:#888">
                            <?= date('d M Y', strtotime($r['submitted_at'])) ?><br>
                            <?= date('H:i', strtotime($r['submitted_at'])) ?>
                        </td>
                        <td>
                            <?php if ($r['nilai'] !== null): ?>
                                <span class="badge badge-blue" style="font-size:14px"><?= $r['nilai'] ?></span>
                            <?php else: ?>
                                <span class="badge badge-orange">Menunggu</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['file_path']):
                                $ext_r = strtolower(pathinfo($r['file_path'], PATHINFO_EXTENSION));
                                $is_video = in_array($ext_r, ['mp4','mov','avi','mkv','webm']);
                            ?>
                            <?php if ($is_video): ?>
                                <a href="/tugas-app/uploads/tugas/<?= $r['file_path'] ?>"
                                   target="_blank"
                                   style="font-size:12px;color:#7b1fa2;text-decoration:none;font-weight:600">
                                    🎬 Lihat Video
                                </a>
                            <?php else: ?>
                                <a href="/tugas-app/uploads/tugas/<?= $r['file_path'] ?>"
                                   target="_blank"
                                   style="font-size:12px;color:#145a32;text-decoration:none;font-weight:600">
                                    📄 Lihat
                                </a>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($bisa_edit): ?>
                                <button class="btn-edit"
                                    onclick="bukaModalEdit(
                                        <?= $r['id'] ?>,
                                        '<?= htmlspecialchars(addslashes($r['judul'])) ?>',
                                        '<?= htmlspecialchars(addslashes($r['nama_kelas'])) ?>',
                                        '<?= date('d M Y H:i', $deadline_unix) ?>',
                                        '<?= htmlspecialchars(addslashes($r['file_path'])) ?>',
                                        '<?= htmlspecialchars(addslashes($r['catatan'] ?? '')) ?>'
                                    )">
                                    ✏️ Edit
                                </button>
                            <?php elseif ($r['nilai'] !== null): ?>
                                <span class="badge" style="background:#f5f5f5;color:#bbb">Sudah dinilai</span>
                            <?php else: ?>
                                <span class="btn-edit disabled">🔒 Terkunci</span>
                                <span class="deadline-passed">Deadline lewat</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$ada): ?>
                    <tr><td colspan="5" style="text-align:center;color:#aaa;padding:30px">Belum ada riwayat pengumpulan</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL EDIT                                                  -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Edit Submission</h3>
            <button class="modal-close" onclick="tutupModal()">×</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="submission_id" id="edit_submission_id">
            <div class="modal-body">

                <!-- Info tugas -->
                <div style="background:#f0faf0;border-radius:8px;padding:11px 13px;margin-bottom:16px;border-left:3px solid #145a32;font-size:13px;">
                    <strong id="edit_judul"></strong><br>
                    <span style="color:#777" id="edit_meta"></span>
                </div>

                <!-- File sekarang -->
                <div class="form-group">
                    <label>File Saat Ini</label>
                    <div class="file-current-sm">
                        <span id="edit_file_icon">📄</span> <div>
                            <span id="edit_file_nama" style="font-weight:600;color:#333"></span><br>
                            <a id="edit_file_link" href="#" target="_blank">🔗 Buka File</a>
                        </div>
                    </div>
                </div>

                <!-- Upload file baru -->
                <div class="form-group">
                    <label>Ganti File <span style="font-weight:400;color:#aaa">(opsional)</span></label>
                    <div class="file-drop-sm" onclick="document.getElementById('edit_file_input').click()">
                        <input type="file" id="edit_file_input" name="file_tugas"
                               accept=".pdf,.doc,.docx,.zip,.rar,.jpg,.png,.mp4,.mov,.avi,.mkv,.webm"
                               onchange="tampilkanFile(this,'edit_nama_file')">
                        <div style="font-size:22px">📁</div>
                        <p style="font-size:12px;color:#888;margin-top:4px">Klik untuk pilih file baru</p>
                        <p style="font-size:11px;color:#aaa">PDF, DOC, DOCX, ZIP, RAR, JPG, PNG, MP4, MOV, AVI, MKV, WEBM — Maks 500MB</p>
                        <div class="selected" id="edit_nama_file">Biarkan kosong jika tidak ingin mengganti</div>
                    </div>
                </div>

                <!-- Catatan -->
                <div class="form-group" style="margin-bottom:0">
                    <label>Catatan untuk Dosen</label>
                    <textarea name="catatan" id="edit_catatan" placeholder="Tulis catatan untuk dosen..." style="min-height:70px"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" name="edit_tugas" class="btn-primary">
                    ✏️ Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function tampilkanFile(input, targetId) {
    const nama = input.files[0] ? input.files[0].name : '';
    const el   = document.getElementById(targetId);
    const videoExts = ['mp4','mov','avi','mkv','webm'];
    const ext = nama.split('.').pop().toLowerCase();
    const icon = videoExts.includes(ext) ? '🎬' : '📄';
    if (targetId === 'nama_file') {
        el.textContent = nama ? icon + ' ' + nama : 'Belum ada file dipilih';
    } else {
        el.textContent = nama ? icon + ' ' + nama : 'Biarkan kosong jika tidak ingin mengganti';
    }
}

function updateInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('info_tugas');
    if (!opt.value) { box.style.display='none'; return; }
    box.style.display = 'block';
    box.innerHTML = `
        <strong>${opt.text.split(' — ')[0]}</strong><br>
        📚 ${opt.dataset.kelas} &nbsp;|&nbsp; 👨‍🏫 ${opt.dataset.dosen}<br>
        ⏰ Deadline: <strong>${opt.dataset.deadline}</strong>
        ${opt.dataset.desk ? '<br><span style="color:#777;margin-top:4px;display:block">' + opt.dataset.desk + '</span>' : ''}
    `;
}

function bukaModalEdit(id, judul, kelas, deadline, filePath, catatan) {
    const videoExts = ['mp4','mov','avi','mkv','webm'];
    const ext = filePath.split('.').pop().toLowerCase();
    const isVideo = videoExts.includes(ext);

    document.getElementById('edit_submission_id').value = id;
    document.getElementById('edit_judul').textContent   = judul;
    document.getElementById('edit_meta').textContent    = '📚 ' + kelas + '  ⏰ Deadline: ' + deadline;
    document.getElementById('edit_file_nama').textContent = filePath;
    document.getElementById('edit_file_icon').textContent = isVideo ? '🎬' : '📄';
    document.getElementById('edit_file_link').href = '/tugas-app/uploads/tugas/' + filePath;
    document.getElementById('edit_file_link').textContent = isVideo ? '▶️ Putar Video' : '🔗 Buka File';
    document.getElementById('edit_catatan').value   = catatan;
    document.getElementById('edit_nama_file').textContent = 'Biarkan kosong jika tidak ingin mengganti';
    // Reset file input
    document.getElementById('edit_file_input').value = '';
    document.getElementById('modalEdit').classList.add('active');
}

function tutupModal() {
    document.getElementById('modalEdit').classList.remove('active');
}

// Klik di luar modal → tutup
document.getElementById('modalEdit').addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});

// Auto-trigger jika ada tugas_id dari URL
window.onload = function() {
    const sel = document.getElementById('tugas_select');
    if (sel.value) updateInfo(sel);
}
</script>
</body>
</html>