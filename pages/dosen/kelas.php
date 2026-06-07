<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('dosen');

$dosen_id = $_SESSION['user_id'];

// Kelas yang diampu beserta jadwal dan mahasiswa
$kelas_list = mysqli_query($conn, "
    SELECT k.*,
        (SELECT COUNT(*) FROM mahasiswa_kelas mk WHERE mk.kelas_id=k.id) AS jml_mhs,
        (SELECT COUNT(*) FROM tugas t WHERE t.kelas_id=k.id) AS jml_tugas
    FROM kelas k
    WHERE k.dosen_id = $dosen_id
    ORDER BY k.semester, k.kelas
");

$hari_list = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelas Saya</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; }
        .kelas-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:20px; }
        .kelas-card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .kelas-card-top { padding:18px 20px; background:linear-gradient(135deg,#1a5276,#2980b9); color:white; }
        .kelas-card-top h3 { font-size:16px; }
        .kelas-card-top p  { font-size:12px; opacity:0.8; margin-top:4px; }
        .kelas-stats { display:flex; gap:20px; margin-top:12px; }
        .kelas-stats .stat { text-align:center; }
        .kelas-stats .stat h4 { font-size:20px; font-weight:700; }
        .kelas-stats .stat p  { font-size:11px; opacity:0.8; }
        .jadwal-section { padding:16px 20px; }
        .jadwal-section h4 { font-size:13px; font-weight:600; color:#555; margin-bottom:10px; }
        .jadwal-row {
            display:flex; align-items:center; gap:10px;
            padding:8px 12px; background:#f8f9fa;
            border-radius:8px; margin-bottom:6px; font-size:13px;
        }
        .jadwal-row .hari { font-weight:700; color:#1a5276; min-width:60px; }
        .jadwal-row .jam  { color:#333; }
        .jadwal-row .ruang{ color:#aaa; font-size:11px; margin-left:auto; }
        .mhs-section { padding:0 20px 16px; }
        .mhs-section h4 { font-size:13px; font-weight:600; color:#555; margin-bottom:10px; }
        .mhs-list { max-height:140px; overflow-y:auto; }
        .mhs-item { display:flex; align-items:center; gap:8px; padding:6px 0; border-bottom:1px solid #f5f5f5; font-size:13px; }
        .mhs-item:last-child { border-bottom:none; }
        .mhs-avatar { width:28px; height:28px; border-radius:50%; background:#e8f0fe; display:flex; align-items:center; justify-content:center; font-size:12px; }
        .badge { padding:3px 8px; border-radius:20px; font-size:11px; font-weight:600; }
        .badge-sem { background:#e8f0fe; color:#1a5276; }
        .badge-kelas { background:#e8f5e9; color:#2e7d32; font-size:13px; font-weight:800; }
        .empty { text-align:center; color:#aaa; font-size:12px; padding:16px; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>📚 Kelas yang Saya Ampu</h1></div>

    <div class="page-body">
        <div class="kelas-grid">
        <?php
        $ada = false;
        while ($k = mysqli_fetch_assoc($kelas_list)):
            $ada = true;

            // Ambil jadwal kelas ini
            $jadwal = mysqli_query($conn, "
                SELECT * FROM jadwal WHERE kelas_id={$k['id']}
                ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jam_mulai
            ");

            // Ambil mahasiswa di kelas ini
            $mahasiswa = mysqli_query($conn, "
                SELECT u.nama, u.nim_nidn FROM users u
                JOIN mahasiswa_kelas mk ON mk.mahasiswa_id = u.id
                WHERE mk.kelas_id = {$k['id']}
                ORDER BY u.nama
            ");
        ?>
        <div class="kelas-card">
            <div class="kelas-card-top">
                <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h3><?= htmlspecialchars($k['nama_kelas']) ?></h3>
                        <p>🔖 <?= $k['kode_matkul'] ?> &nbsp;|&nbsp; <?= $k['sks'] ?> SKS &nbsp;|&nbsp; <?= $k['tahun_ajaran'] ?></p>
                    </div>
                    <span class="badge badge-kelas" style="background:rgba(255,255,255,0.2);color:white;font-size:18px">
                        <?= $k['kelas'] ?>
                    </span>
                </div>
                <div class="kelas-stats">
                    <div class="stat">
                        <h4><?= $k['jml_mhs'] ?></h4>
                        <p>Mahasiswa</p>
                    </div>
                    <div class="stat">
                        <h4><?= $k['jml_tugas'] ?></h4>
                        <p>Tugas</p>
                    </div>
                    <div class="stat">
                        <span class="badge badge-sem" style="background:rgba(255,255,255,0.2);color:white">
                            Semester <?= $k['semester'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Jadwal kelas ini -->
            <div class="jadwal-section">
                <h4>🗓️ Jadwal</h4>
                <?php
                $ada_jadwal = false;
                while ($j = mysqli_fetch_assoc($jadwal)):
                    $ada_jadwal = true;
                ?>
                <div class="jadwal-row">
                    <span class="hari"><?= $j['hari'] ?></span>
                    <span class="jam"><?= substr($j['jam_mulai'],0,5) ?> – <?= substr($j['jam_selesai'],0,5) ?></span>
                    <span class="ruang">📍 <?= htmlspecialchars($j['ruangan'] ?: '-') ?></span>
                </div>
                <?php endwhile; ?>
                <?php if (!$ada_jadwal): ?>
                    <p class="empty">Belum ada jadwal</p>
                <?php endif; ?>
            </div>

            <!-- Daftar mahasiswa -->
            <div class="mhs-section">
                <h4>👥 Mahasiswa (<?= $k['jml_mhs'] ?>)</h4>
                <div class="mhs-list">
                <?php
                $ada_mhs = false;
                while ($m = mysqli_fetch_assoc($mahasiswa)):
                    $ada_mhs = true;
                ?>
                <div class="mhs-item">
                    <div class="mhs-avatar">👤</div>
                    <div>
                        <div style="font-weight:600"><?= htmlspecialchars($m['nama']) ?></div>
                        <div style="font-size:11px;color:#aaa"><?= htmlspecialchars($m['nim_nidn']) ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if (!$ada_mhs): ?>
                    <p class="empty">Belum ada mahasiswa terdaftar</p>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

        <?php if (!$ada): ?>
            <div style="text-align:center;color:#aaa;padding:60px;grid-column:1/-1">
                <div style="font-size:48px;margin-bottom:12px">📭</div>
                <p>Belum ada kelas yang diampu.</p>
                <small>Hubungi admin untuk assign kelas.</small>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>