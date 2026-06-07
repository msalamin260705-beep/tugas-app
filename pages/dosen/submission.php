<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('dosen');

$dosen_id = $_SESSION['user_id'];
$success = $error = "";

// BERI NILAI
if (isset($_POST['beri_nilai'])) {
    $sub_id = intval($_POST['submission_id']);
    $nilai  = intval($_POST['nilai']);
    if ($nilai < 0 || $nilai > 100) {
        $error = "Nilai harus antara 0 - 100!";
    } else {
        mysqli_query($conn, "UPDATE submissions SET nilai=$nilai WHERE id=$sub_id");
        $success = "Nilai berhasil disimpan!";
    }
}

// Filter by tugas
$filter_tugas = isset($_GET['tugas_id']) ? intval($_GET['tugas_id']) : 0;

// Semua tugas milik dosen ini (untuk filter)
$tugas_dosen = mysqli_query($conn, "
    SELECT t.id, t.judul, k.nama_kelas
    FROM tugas t JOIN kelas k ON t.kelas_id=k.id
    WHERE t.dosen_id=$dosen_id ORDER BY t.created_at DESC
");

// Ambil submissions
$where = $filter_tugas ? "AND t.id=$filter_tugas" : "";
$submissions = mysqli_query($conn, "
    SELECT s.*, u.nama AS mahasiswa, u.nim_nidn AS nim,
           t.judul AS judul_tugas, t.deadline, k.nama_kelas
    FROM submissions s
    JOIN users u ON s.mahasiswa_id = u.id
    JOIN tugas t ON s.tugas_id = t.id
    JOIN kelas k ON t.kelas_id = k.id
    WHERE t.dosen_id=$dosen_id $where
    ORDER BY s.submitted_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nilai Submission</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; }
        .filter-bar { background:white; padding:14px 20px; border-radius:12px; margin-bottom:20px; display:flex; gap:12px; align-items:center; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .filter-bar label { font-size:13px; font-weight:600; color:#555; }
        .filter-bar select { padding:8px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:13px; outline:none; }
        .filter-bar button { padding:8px 18px; background:#1a5276; color:white; border:none; border-radius:8px; font-size:13px; cursor:pointer; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .alert { padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }
        table { width:100%; border-collapse:collapse; }
        th { background:#f8f9fa; padding:10px 16px; text-align:left; font-size:11px; color:#888; font-weight:700; text-transform:uppercase; }
        td { padding:12px 16px; font-size:13px; color:#444; border-bottom:1px solid #f5f5f5; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        .badge { padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-orange { background:#fff8e1; color:#e65100; }
        .badge-green  { background:#e8f5e9; color:#2e7d32; }
        .nilai-form { display:flex; gap:6px; align-items:center; }
        .nilai-form input { width:70px; padding:6px 10px; border:2px solid #e0e0e0; border-radius:6px; font-size:13px; text-align:center; outline:none; }
        .nilai-form input:focus { border-color:#1a5276; }
        .nilai-form button { padding:6px 12px; background:#1a5276; color:white; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; }
        .nilai-form button:hover { background:#154360; }
        .nilai-besar { font-size:18px; font-weight:700; }
        .nilai-a { color:#27ae60; }
        .nilai-b { color:#2980b9; }
        .nilai-c { color:#f39c12; }
        .nilai-d { color:#e74c3c; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>📬 Submission Mahasiswa</h1></div>

    <div class="page-body">
        <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <form method="GET" class="filter-bar">
            <label>Filter Tugas:</label>
            <select name="tugas_id">
                <option value="0">Semua Tugas</option>
                <?php while ($t = mysqli_fetch_assoc($tugas_dosen)): ?>
                <option value="<?= $t['id'] ?>" <?= $filter_tugas==$t['id']?'selected':'' ?>>
                    <?= htmlspecialchars($t['judul']) ?> — <?= htmlspecialchars($t['nama_kelas']) ?>
                </option>
                <?php endwhile; ?>
            </select>
            <button type="submit">Filter</button>
        </form>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Tugas</th>
                        <th>Waktu Kumpul</th>
                        <th>File</th>
                        <th>Catatan</th>
                        <th>Nilai</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $ada = false;
                while ($s = mysqli_fetch_assoc($submissions)):
                    $ada = true;
                    if ($s['nilai'] >= 85)     $kelas_nilai = 'nilai-a';
                    elseif ($s['nilai'] >= 70) $kelas_nilai = 'nilai-b';
                    elseif ($s['nilai'] >= 55) $kelas_nilai = 'nilai-c';
                    else                       $kelas_nilai = 'nilai-d';
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($s['mahasiswa']) ?></strong><br>
                        <small style="color:#aaa"><?= htmlspecialchars($s['nim']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($s['judul_tugas']) ?><br>
                        <small style="color:#aaa"><?= htmlspecialchars($s['nama_kelas']) ?></small>
                    </td>
                    <td style="font-size:12px;color:#888">
                        <?= date('d M Y', strtotime($s['submitted_at'])) ?><br>
                        <?= date('H:i', strtotime($s['submitted_at'])) ?>
                    </td>
                    <td>
                        <?php if ($s['file_path']): ?>
                        <a href="/tugas-app/uploads/tugas/<?= $s['file_path'] ?>"
                           target="_blank"
                           style="color:#1a5276;font-weight:600;font-size:12px;text-decoration:none">
                            📄 Download
                        </a>
                        <?php else: ?>
                        <span style="color:#aaa;font-size:12px">Tidak ada file</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#777;max-width:150px">
                        <?= htmlspecialchars($s['catatan'] ?: '-') ?>
                    </td>
                    <td>
                        <?php if ($s['nilai'] !== null): ?>
                            <form method="POST" class="nilai-form">
                                <input type="hidden" name="submission_id" value="<?=$s['id']?>">
                                <input type="number" name="nilai" min="0" max="100"
                                    value="<?=$s['nilai']?>" required style="border-color:#27ae60">
                                <button type="submit" name="beri_nilai"
                                        style="background:#27ae60">Update</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="nilai-form">
                                <input type="hidden" name="submission_id" value="<?=$s['id']?>">
                                <input type="number" name="nilai" min="0" max="100" placeholder="0-100" required>
                                <button type="submit" name="beri_nilai">Simpan</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$ada): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#aaa;padding:40px">
                        Belum ada submission masuk
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>