<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('mahasiswa');

$mhs_id   = $_SESSION['user_id'];

$mhs      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$mhs_id"));
$semester = $mhs['semester'];
$kelas    = $mhs['kelas'];

// Jadwal reguler
$jadwal_reguler = mysqli_query($conn, "
    SELECT j.*, k.nama_kelas, k.kode_matkul, k.sks, u.nama AS nama_dosen,
           'reguler' AS tipe, k.semester AS sem_kelas, k.kelas AS kelas_huruf
    FROM jadwal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN users u ON k.dosen_id = u.id
    WHERE k.semester = $semester AND k.kelas = '$kelas'
    ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai
");

// Jadwal mengulang (kelas semester berbeda, terdaftar via mahasiswa_kelas oleh dosen)
$jadwal_mengulang = mysqli_query($conn, "
    SELECT j.*, k.nama_kelas, k.kode_matkul, k.sks, u.nama AS nama_dosen,
           'mengulang' AS tipe, k.semester AS sem_kelas, k.kelas AS kelas_huruf
    FROM jadwal j
    JOIN kelas k         ON j.kelas_id = k.id
    JOIN users u         ON k.dosen_id = u.id
    JOIN mahasiswa_kelas mk ON mk.kelas_id = k.id
    WHERE mk.mahasiswa_id = $mhs_id AND k.semester != $semester
    ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai
");

$jadwal_per_hari = [];
$total_sks       = 0;
$matkul_list     = [];
$matkul_mengulang = [];

while ($j = mysqli_fetch_assoc($jadwal_reguler)) {
    $jadwal_per_hari[$j['hari']][] = $j;
    if (!isset($matkul_list[$j['kelas_id']])) {
        $matkul_list[$j['kelas_id']] = $j;
        $total_sks += $j['sks'];
    }
}

while ($j = mysqli_fetch_assoc($jadwal_mengulang)) {
    $jadwal_per_hari[$j['hari']][] = $j;
    if (!isset($matkul_mengulang[$j['kelas_id']])) {
        $matkul_mengulang[$j['kelas_id']] = $j;
    }
}

// Urutkan tiap hari berdasarkan jam
foreach ($jadwal_per_hari as $h => $items) {
    usort($jadwal_per_hari[$h], fn($a,$b) => strcmp($a['jam_mulai'], $b['jam_mulai']));
}

$hari_list = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Kuliah Saya</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; }

        .info-banner { background:linear-gradient(135deg,#145a32,#1e8449); border-radius:12px; padding:20px 24px; display:flex; gap:30px; margin-bottom:24px; color:white; flex-wrap:wrap; }
        .info-banner .item { text-align:center; }
        .info-banner .item .big { font-size:26px; font-weight:700; }
        .info-banner .item p  { font-size:12px; opacity:0.8; margin-top:2px; }

        /* Banner notif mengulang */
        .notif-mengulang { background:#fff8e1; border:1px solid #ffe082; border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:flex-start; gap:12px; }
        .notif-mengulang .icon { font-size:22px; flex-shrink:0; }
        .notif-mengulang h4 { font-size:13px; font-weight:700; color:#e65100; margin-bottom:6px; }
        .notif-mengulang ul { padding-left:16px; }
        .notif-mengulang ul li { font-size:12px; color:#555; margin-bottom:3px; }

        .jadwal-week { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; margin-bottom:24px; }
        .jadwal-week-header { padding:14px 20px; background:#145a32; color:white; font-size:15px; font-weight:600; }

        .hari-block { border-bottom:1px solid #f0f0f0; }
        .hari-block:last-child { border-bottom:none; }
        .hari-title { padding:10px 20px; background:#f8f9fa; font-size:12px; font-weight:700; color:#145a32; text-transform:uppercase; letter-spacing:1px; display:flex; align-items:center; gap:8px; }

        .jadwal-item { padding:14px 20px; display:flex; align-items:center; gap:16px; border-bottom:1px solid #f5f5f5; }
        .jadwal-item:last-child { border-bottom:none; }
        .jadwal-item.mengulang { background:#fff8f8; }

        .jam-box { min-width:80px; text-align:center; background:#e8f5e9; border-radius:8px; padding:8px 6px; }
        .jadwal-item.mengulang .jam-box { background:#ffebee; }
        .jam-box .jam   { font-size:13px; font-weight:700; color:#145a32; }
        .jadwal-item.mengulang .jam-box .jam { color:#c0392b; }
        .jam-box .durasi { font-size:10px; color:#888; margin-top:2px; }

        .matkul-info { flex:1; }
        .matkul-info h4 { font-size:14px; color:#1e3a5f; font-weight:600; }
        .matkul-info .meta { font-size:12px; color:#888; margin-top:4px; }
        .matkul-info .meta span { margin-right:8px; }

        .dosen-box { text-align:right; }
        .dosen-box .nama  { font-size:13px; color:#333; font-weight:500; }
        .dosen-box .ruang { font-size:12px; color:#aaa; margin-top:3px; }

        .matkul-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; margin-bottom:24px; }
        .matkul-card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); padding:18px 20px; border-top:3px solid #145a32; }
        .matkul-card.mengulang { border-top-color:#c0392b; }
        .matkul-card h4 { font-size:14px; color:#1e3a5f; margin-bottom:6px; }
        .matkul-card p  { font-size:12px; color:#888; line-height:1.7; }

        .badge { padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-green { background:#e8f5e9; color:#2e7d32; }
        .badge-blue  { background:#e8f0fe; color:#1a5276; }
        .badge-ulang { background:#ffebee; color:#c0392b; }

        .empty-state { text-align:center; padding:60px 20px; color:#aaa; }
        .empty-state .icon { font-size:48px; margin-bottom:12px; }
        h2 { font-size:16px; color:#1e3a5f; margin-bottom:14px; }

        .section-label { display:flex; align-items:center; gap:8px; margin-bottom:14px; }
        .section-label h2 { margin:0; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar">
        <h1>🗓️ Jadwal Kuliah Saya</h1>
        <span style="font-size:13px;color:#888">Semester <?= $semester ?> — Kelas <?= $kelas ?></span>
    </div>

    <div class="page-body">

        <!-- Info banner -->
        <div class="info-banner">
            <div class="item"><div class="big"><?= $semester ?></div><p>Semester</p></div>
            <div class="item"><div class="big"><?= $kelas ?></div><p>Kelas</p></div>
            <div class="item"><div class="big"><?= count($matkul_list) ?></div><p>Mata Kuliah</p></div>
            <div class="item"><div class="big"><?= $total_sks ?></div><p>Total SKS</p></div>
            <div class="item"><div class="big"><?= count($jadwal_per_hari) ?></div><p>Hari Kuliah</p></div>
            <?php if (!empty($matkul_mengulang)): ?>
            <div class="item"><div class="big" style="color:#ffe082"><?= count($matkul_mengulang) ?></div><p>Matkul Mengulang</p></div>
            <?php endif; ?>
        </div>

        <!-- Notifikasi mengulang -->
        <?php if (!empty($matkul_mengulang)): ?>
        <div class="notif-mengulang">
            <div class="icon">🔄</div>
            <div>
                <h4>Kamu terdaftar untuk mengulang <?= count($matkul_mengulang) ?> mata kuliah</h4>
                <ul>
                    <?php foreach ($matkul_mengulang as $m): ?>
                    <li>
                        <strong><?= htmlspecialchars($m['nama_kelas']) ?></strong>
                        (<?= $m['kode_matkul'] ?>) — Semester <?= $m['sem_kelas'] ?> Kelas <?= $m['kelas_huruf'] ?>
                        &nbsp;· Dosen: <?= htmlspecialchars($m['nama_dosen']) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($jadwal_per_hari)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p style="font-size:15px;color:#555">Jadwal belum tersedia</p>
            <p>Admin belum mengatur jadwal untuk Semester <?= $semester ?> Kelas <?= $kelas ?>.</p>
        </div>

        <?php else: ?>

        <!-- Jadwal mingguan -->
        <h2>📅 Jadwal Mingguan</h2>
        <div class="jadwal-week">
            <div class="jadwal-week-header">
                Semester <?= $semester ?> — Kelas <?= $kelas ?>
                <?php if (!empty($matkul_mengulang)): ?>
                &nbsp;·&nbsp; <span style="font-size:12px;opacity:0.85">🔄 termasuk matkul mengulang</span>
                <?php endif; ?>
            </div>

            <?php foreach ($hari_list as $hari):
                if (!isset($jadwal_per_hari[$hari])) continue;
            ?>
            <div class="hari-block">
                <div class="hari-title">
                    📌 <?= $hari ?>
                    <span style="font-weight:400;color:#aaa;font-size:11px;text-transform:none">
                        (<?= count($jadwal_per_hari[$hari]) ?> sesi)
                    </span>
                </div>
                <?php foreach ($jadwal_per_hari[$hari] as $j):
                    $mulai   = strtotime($j['jam_mulai']);
                    $selesai = strtotime($j['jam_selesai']);
                    $durasi  = ($selesai - $mulai) / 60;
                    $is_ulang = $j['tipe'] === 'mengulang';
                ?>
                <div class="jadwal-item <?= $is_ulang ? 'mengulang' : '' ?>">
                    <div class="jam-box">
                        <div class="jam"><?= substr($j['jam_mulai'],0,5) ?></div>
                        <div class="jam"><?= substr($j['jam_selesai'],0,5) ?></div>
                        <div class="durasi"><?= $durasi ?> menit</div>
                    </div>
                    <div class="matkul-info">
                        <h4>
                            <?= htmlspecialchars($j['nama_kelas']) ?>
                            <?php if ($is_ulang): ?>
                            <span class="badge badge-ulang" style="margin-left:6px">🔄 Mengulang</span>
                            <?php endif; ?>
                        </h4>
                        <div class="meta">
                            <span class="badge badge-blue"><?= $j['kode_matkul'] ?></span>
                            <span class="badge badge-green"><?= $j['sks'] ?> SKS</span>
                            <?php if ($is_ulang): ?>
                            <span style="font-size:11px;color:#c0392b">Sem <?= $j['sem_kelas'] ?> Kelas <?= $j['kelas_huruf'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dosen-box">
                        <div class="nama">👨‍🏫 <?= htmlspecialchars($j['nama_dosen']) ?></div>
                        <div class="ruang">📍 <?= htmlspecialchars($j['ruangan'] ?: 'Ruangan belum diset') ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Daftar matkul reguler -->
        <h2>📚 Daftar Mata Kuliah</h2>
        <div class="matkul-grid">
            <?php foreach ($matkul_list as $m): ?>
            <div class="matkul-card">
                <h4><?= htmlspecialchars($m['nama_kelas']) ?></h4>
                <p>🔖 Kode: <?= $m['kode_matkul'] ?><br>📊 SKS: <?= $m['sks'] ?><br>👨‍🏫 <?= htmlspecialchars($m['nama_dosen']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Daftar matkul mengulang -->
        <?php if (!empty($matkul_mengulang)): ?>
        <h2>🔄 Mata Kuliah yang Diulang</h2>
        <div class="matkul-grid">
            <?php foreach ($matkul_mengulang as $m): ?>
            <div class="matkul-card mengulang">
                <h4><?= htmlspecialchars($m['nama_kelas']) ?></h4>
                <p>
                    🔖 Kode: <?= $m['kode_matkul'] ?><br>
                    📊 SKS: <?= $m['sks'] ?><br>
                    👨‍🏫 <?= htmlspecialchars($m['nama_dosen']) ?><br>
                    📅 Semester <?= $m['sem_kelas'] ?> Kelas <?= $m['kelas_huruf'] ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
</body>
</html>