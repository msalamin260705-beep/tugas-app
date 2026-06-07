<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('dosen');

$dosen_id = $_SESSION['user_id'];

$total_kelas = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM kelas WHERE dosen_id=$dosen_id"))['total'];

$total_tugas = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM tugas WHERE dosen_id=$dosen_id"))['total'];

$total_submit = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM submissions s
    JOIN tugas t ON s.tugas_id = t.id
    WHERE t.dosen_id = $dosen_id"))['total'];

$belum_dinilai = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM submissions s
    JOIN tugas t ON s.tugas_id = t.id
    WHERE t.dosen_id=$dosen_id AND s.nilai IS NULL"))['total'];

$tugas_terbaru = mysqli_query($conn, "
    SELECT t.*, k.nama_kelas,
        (SELECT COUNT(*) FROM submissions s WHERE s.tugas_id=t.id) as jml_submit
    FROM tugas t
    JOIN kelas k ON t.kelas_id = k.id
    WHERE t.dosen_id = $dosen_id
    ORDER BY t.created_at DESC LIMIT 5
");

$perlu_dinilai = mysqli_query($conn, "
    SELECT s.*, u.nama AS mahasiswa, t.judul AS tugas
    FROM submissions s
    JOIN users u ON s.mahasiswa_id = u.id
    JOIN tugas t ON s.tugas_id = t.id
    WHERE t.dosen_id=$dosen_id AND s.nilai IS NULL
    ORDER BY s.submitted_at DESC LIMIT 6
");

// Informasi dari admin — ambil semua kolom
$info = mysqli_query($conn, "
    SELECT judul, isi, created_at, poster, warna_bg, tipe
    FROM informasi
    ORDER BY created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Dosen</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .topbar span { font-size:13px; color:#888; }
        .page-body { padding:28px; }
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-bottom:26px; }
        .stat-card { background:white; padding:22px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; align-items:center; gap:14px; }
        .stat-icon { width:50px; height:50px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:22px; }
        .stat-card h3 { font-size:26px; color:#1e3a5f; }
        .stat-card p  { font-size:12px; color:#888; margin-top:2px; }
        .row-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .card-header { padding:14px 20px; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; }
        .card-header h3 { font-size:14px; color:#1e3a5f; }
        .card-header a { font-size:12px; color:#1a5276; text-decoration:none; font-weight:600; }
        .card-body { padding:16px 20px; }
        table { width:100%; border-collapse:collapse; }
        th { background:#f8f9fa; padding:9px 16px; text-align:left; font-size:11px; color:#888; font-weight:700; text-transform:uppercase; }
        td { padding:11px 16px; font-size:13px; color:#444; border-bottom:1px solid #f5f5f5; }
        tr:last-child td { border-bottom:none; }
        .badge { padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-ok { background:#e8f5e9; color:#2e7d32; }
        .deadline-near { color:#e74c3c; font-weight:600; }
        .shortcut-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:26px; }
        .shortcut-card { background:white; border-radius:12px; padding:20px; text-align:center; text-decoration:none; color:inherit; box-shadow:0 2px 8px rgba(0,0,0,0.06); transition:transform 0.2s; border-top:3px solid transparent; }
        .shortcut-card:hover { transform:translateY(-3px); }
        .shortcut-card .icon { font-size:28px; margin-bottom:8px; }
        .shortcut-card p { font-size:13px; font-weight:600; color:#333; }

        /* Info teks */
        .info-item { border-bottom:1px solid #f5f5f5; padding:14px 0; }
        .info-item:first-child { padding-top:0; }
        .info-item:last-child { border-bottom:none; padding-bottom:0; }
        .info-item h4 { font-size:13px; color:#333; font-weight:600; }
        .info-item .isi { font-size:13px; color:#555; margin-top:5px; line-height:1.6; }
        .info-item .tgl { font-size:11px; color:#aaa; margin-top:5px; }

        /* Poster dengan gambar */
        .poster-img-wrap { position:relative; border-radius:10px; overflow:hidden; margin-bottom:8px; }
        .poster-img-wrap img { width:100%; max-height:220px; object-fit:cover; display:block; }
        .poster-img-overlay {
            position:absolute; bottom:0; left:0; right:0;
            padding:16px;
            background:linear-gradient(transparent, rgba(0,0,0,0.75));
            color:white;
        }
        .poster-img-overlay h4 { font-size:15px; font-weight:700; }
        .poster-img-overlay p  { font-size:12px; opacity:0.85; margin-top:3px; }

        /* Poster generated */
        .poster-gen {
            border-radius:10px; padding:28px 20px;
            text-align:center; color:white; margin-bottom:8px;
        }
        .poster-gen .pg-icon { font-size:36px; margin-bottom:10px; }
        .poster-gen h4 { font-size:17px; font-weight:800; margin-bottom:8px; line-height:1.3; }
        .poster-gen p  { font-size:13px; opacity:0.88; line-height:1.6; }
        .poster-gen .pg-tgl { margin-top:12px; font-size:11px; opacity:0.6; border-top:1px solid rgba(255,255,255,0.2); padding-top:10px; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <h1>Selamat datang, <?= htmlspecialchars($_SESSION['user_nama']) ?>! 👋</h1>
        <span>📅 <?= date('d F Y') ?></span>
    </div>

    <div class="page-body">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f0fe">📚</div>
                <div><h3><?= $total_kelas ?></h3><p>Kelas Diampu</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e1">📝</div>
                <div><h3><?= $total_tugas ?></h3><p>Tugas Dibuat</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f5e9">📬</div>
                <div><h3><?= $total_submit ?></h3><p>Total Submission</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#ffebee">⏳</div>
                <div><h3><?= $belum_dinilai ?></h3><p>Belum Dinilai</p></div>
            </div>
        </div>

        <div class="shortcut-grid">
            <a href="tugas.php" class="shortcut-card" style="border-color:#1a5276">
                <div class="icon">📝</div><p>Buat Tugas Baru</p>
            </a>
            <a href="submission.php" class="shortcut-card" style="border-color:#784212">
                <div class="icon">📬</div><p>Nilai Submission</p>
            </a>
            <a href="profil.php" class="shortcut-card" style="border-color:#145a32">
                <div class="icon">👤</div><p>Edit Profil</p>
            </a>
        </div>

        <div class="row-2">
            <div class="card">
                <div class="card-header">
                    <h3>📝 Tugas Terbaru</h3>
                    <a href="tugas.php">Lihat semua →</a>
                </div>
                <table>
                    <thead><tr><th>Judul</th><th>Deadline</th><th>Submit</th></tr></thead>
                    <tbody>
                    <?php while ($t = mysqli_fetch_assoc($tugas_terbaru)):
                        $lewat = strtotime($t['deadline']) < time(); ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($t['judul']) ?></strong><br>
                            <small style="color:#aaa"><?= htmlspecialchars($t['nama_kelas']) ?></small>
                        </td>
                        <td class="<?= $lewat?'deadline-near':'' ?>">
                            <?= date('d M Y', strtotime($t['deadline'])) ?>
                            <?= $lewat ? '<br><small>Lewat</small>' : '' ?>
                        </td>
                        <td><span class="badge badge-ok"><?= $t['jml_submit'] ?> file</span></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($total_tugas == 0): ?>
                    <tr><td colspan="3" style="text-align:center;color:#aaa;padding:24px">Belum ada tugas</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>⏳ Perlu Dinilai</h3>
                    <a href="submission.php">Nilai semua →</a>
                </div>
                <table>
                    <thead><tr><th>Mahasiswa</th><th>Tugas</th><th>Waktu</th></tr></thead>
                    <tbody>
                    <?php while ($s = mysqli_fetch_assoc($perlu_dinilai)): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['mahasiswa']) ?></td>
                        <td><?= htmlspecialchars($s['tugas']) ?></td>
                        <td style="font-size:11px;color:#aaa"><?= date('d M, H:i', strtotime($s['submitted_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($belum_dinilai == 0): ?>
                    <tr><td colspan="3" style="text-align:center;color:#aaa;padding:24px">Semua sudah dinilai ✅</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Informasi dari Admin -->
        <div class="card">
            <div class="card-header"><h3>📢 Informasi & Pengumuman dari Admin</h3></div>
            <div class="card-body">
                <?php
                $ada_info = false;
                while ($i = mysqli_fetch_assoc($info)):
                    $ada_info = true;
                    $tipe   = $i['tipe']    ?? 'teks';
                    $warna  = $i['warna_bg'] ?? '#1a3a5c';
                    $poster = $i['poster']   ?? '';
                ?>
                <div class="info-item">
                    <?php if ($tipe === 'poster'): ?>
                        <?php
                        $poster_path = '../../uploads/poster/' . $poster;
                        if ($poster && file_exists($poster_path)):
                        ?>
                            <div class="poster-img-wrap">
                                <img src="/tugas-app/uploads/poster/<?= htmlspecialchars($poster) ?>" alt="Poster">
                                <div class="poster-img-overlay">
                                    <h4><?= htmlspecialchars($i['judul']) ?></h4>
                                    <?php if ($i['isi']): ?>
                                    <p><?= htmlspecialchars(substr($i['isi'],0,100)) ?><?= strlen($i['isi'])>100?'...':'' ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="poster-gen" style="background:<?= htmlspecialchars($warna) ?>">
                                <div class="pg-icon">📢</div>
                                <h3 style="color:#ffffff;"><?= htmlspecialchars($i['judul']) ?></h3>
                                <?php if ($i['isi']): ?>
                                <p style="color:#ffffff;"><?= nl2br(htmlspecialchars($i['isi'])) ?></p>
                                <?php endif; ?>
                                <div class="pg-tgl">📅 <?= date('d F Y', strtotime($i['created_at'])) ?></div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <h4>📣 <?= htmlspecialchars($i['judul']) ?></h4>
                        <p class="isi"><?= nl2br(htmlspecialchars(substr($i['isi'],0,200))) ?><?= strlen($i['isi'])>200?'...':'' ?></p>
                    <?php endif; ?>
                    <p class="tgl">📅 <?= date('d M Y', strtotime($i['created_at'])) ?></p>
                </div>
                <?php endwhile; ?>
                <?php if (!$ada_info): ?>
                <p style="text-align:center;color:#aaa;padding:16px 0">Belum ada pengumuman</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
</body>
</html>