<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('admin');

$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='mahasiswa'"))['total'];
$total_dosen     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='dosen'"))['total'];
$total_tugas     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tugas"))['total'];
$total_submit    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM submissions"))['total'];

$aktivitas = mysqli_query($conn, "
    SELECT s.submitted_at, u.nama AS mahasiswa, t.judul AS tugas, s.nilai
    FROM submissions s
    JOIN users u ON s.mahasiswa_id = u.id
    JOIN tugas t ON s.tugas_id = t.id
    ORDER BY s.submitted_at DESC LIMIT 8
");

$info_terbaru = mysqli_query($conn, "
    SELECT i.judul, i.isi, i.created_at, i.poster, i.warna_bg, i.tipe, u.nama AS admin
    FROM informasi i
    JOIN users u ON i.admin_id = u.id
    ORDER BY i.created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .topbar span { font-size:13px; color:#888; }
        .page-body { padding:28px; }
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:28px; }
        .stat-card { background:white; padding:24px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; align-items:center; gap:16px; }
        .stat-icon { width:54px; height:54px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px; }
        .stat-card h3 { font-size:28px; color:#1e3a5f; }
        .stat-card p  { font-size:13px; color:#888; margin-top:2px; }
        .row-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .card-header { padding:16px 20px; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; }
        .card-header h3 { font-size:15px; color:#1e3a5f; }
        .card-header a { font-size:12px; color:#2d6a9f; text-decoration:none; }
        table { width:100%; border-collapse:collapse; }
        th { background:#f8f9fa; padding:10px 16px; text-align:left; font-size:12px; color:#888; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; }
        td { padding:11px 16px; font-size:13px; color:#444; border-bottom:1px solid #f5f5f5; }
        tr:last-child td { border-bottom:none; }
        .badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-success { background:#e8f5e9; color:#2e7d32; }
        .badge-warning { background:#fff8e1; color:#f57f17; }
        .shortcut-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:28px; }
        .shortcut-card { background:white; border-radius:12px; padding:20px; text-align:center; text-decoration:none; color:inherit; box-shadow:0 2px 8px rgba(0,0,0,0.06); transition:transform 0.2s; border-top:3px solid transparent; }
        .shortcut-card:hover { transform:translateY(-3px); }
        .shortcut-card .icon { font-size:28px; margin-bottom:8px; }
        .shortcut-card p { font-size:13px; font-weight:600; color:#333; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Dashboard Admin</h1>
        <span>📅 <?= date('l, d F Y') ?></span>
    </div>
    <div class="page-body">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon" style="background:#e8f0fe">👨‍🎓</div><div><h3><?= $total_mahasiswa ?></h3><p>Total Mahasiswa</p></div></div>
            <div class="stat-card"><div class="stat-icon" style="background:#fce8ff">👨‍🏫</div><div><h3><?= $total_dosen ?></h3><p>Total Dosen</p></div></div>
            <div class="stat-card"><div class="stat-icon" style="background:#fff8e1">📝</div><div><h3><?= $total_tugas ?></h3><p>Total Tugas</p></div></div>
            <div class="stat-card"><div class="stat-icon" style="background:#e8f5e9">📬</div><div><h3><?= $total_submit ?></h3><p>Total Submission</p></div></div>
        </div>
        <div class="shortcut-grid">
            <a href="kelola_dosen.php" class="shortcut-card" style="border-color:#6c3483"><div class="icon">👨‍🏫</div><p>Kelola Dosen</p></a>
            <a href="kelola_kelas.php" class="shortcut-card" style="border-color:#1a5276"><div class="icon">📚</div><p>Kelola Kelas</p></a>
            <a href="jadwal.php"       class="shortcut-card" style="border-color:#145a32"><div class="icon">🗓️</div><p>Jadwal Kuliah</p></a>
            <a href="informasi.php"    class="shortcut-card" style="border-color:#784212"><div class="icon">📢</div><p>Informasi</p></a>
        </div>
        <div class="row-2">
            <div class="card">
                <div class="card-header"><h3>📬 Aktivitas Submission Terbaru</h3></div>
                <table>
                    <thead><tr><th>Mahasiswa</th><th>Tugas</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($aktivitas)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['mahasiswa']) ?></td>
                        <td><?= htmlspecialchars($row['tugas']) ?></td>
                        <td>
                            <?php if ($row['nilai'] !== null): ?>
                                <span class="badge badge-success">Dinilai: <?= $row['nilai'] ?></span>
                            <?php else: ?>
                                <span class="badge badge-warning">Belum dinilai</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($total_submit == 0): ?>
                    <tr><td colspan="3" style="text-align:center;color:#aaa;padding:20px">Belum ada submission</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <div class="card-header"><h3>📢 Informasi Terbaru</h3><a href="informasi.php">+ Tambah</a></div>
                <div style="padding:16px 20px">
                <?php
                $ada_info = false;
                while ($row = mysqli_fetch_assoc($info_terbaru)):
                    $ada_info = true;
                    $tipe   = $row['tipe']    ?? 'teks';
                    $warna  = $row['warna_bg'] ?? '#1a3a5c';
                    $poster = $row['poster']   ?? '';
                ?>
                <div style="border-bottom:1px solid #f5f5f5;padding:12px 0">
                    <?php if ($tipe === 'poster'): ?>
                        <?php
                        // ✅ FIX: hapus file_exists, langsung cek nama poster
                        if ($poster):?>
                            <div style="position:relative;border-radius:8px;overflow:hidden;margin-bottom:6px">
                                <!-- ✅ FIX: pakai BASE_URL -->
                                <img src="<?= BASE_URL ?>/uploads/poster/<?= htmlspecialchars($poster) ?>"
                                     style="width:100%;max-height:180px;object-fit:cover;display:block">
                                <div style="position:absolute;bottom:0;left:0;right:0;padding:12px;background:linear-gradient(transparent,rgba(0,0,0,0.75));color:white">
                                    <strong style="font-size:13px"><?= htmlspecialchars($row['judul']) ?></strong>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="background:<?= htmlspecialchars($warna) ?>;border-radius:8px;padding:20px;text-align:center;color:white;margin-bottom:6px">
                                <div style="font-size:28px;margin-bottom:8px">📢</div>
                                <strong style="font-size:14px"><?= htmlspecialchars($row['judul']) ?></strong>
                                <?php if ($row['isi']): ?>
                                <p style="font-size:12px;opacity:0.85;margin-top:6px"><?= nl2br(htmlspecialchars(substr($row['isi'],0,120))) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <strong style="font-size:13px;color:#333">📣 <?= htmlspecialchars($row['judul']) ?></strong>
                        <?php if ($row['isi']): ?>
                        <p style="font-size:12px;color:#555;margin-top:4px;line-height:1.5"><?= htmlspecialchars(substr($row['isi'],0,120)) ?><?= strlen($row['isi'])>120?'...':'' ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span style="font-size:11px;color:#aaa">📅 <?= date('d M Y', strtotime($row['created_at'])) ?> · <?= htmlspecialchars($row['admin']) ?></span>
                </div>
                <?php endwhile; ?>
                <?php if (!$ada_info): ?>
                <p style="text-align:center;color:#aaa;padding:20px 0">Belum ada informasi</p>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>