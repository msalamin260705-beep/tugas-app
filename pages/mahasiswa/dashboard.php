<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('mahasiswa');

$mhs_id = $_SESSION['user_id'];

// Refresh semester & kelas dari DB
$mhs_db = mysqli_fetch_assoc(mysqli_query($conn, "SELECT semester, kelas FROM users WHERE id=$mhs_id"));
$_SESSION['user_semester'] = $mhs_db['semester'];
$_SESSION['user_kelas']    = $mhs_db['kelas'];
$semester_aktif = $mhs_db['semester'];

$total_tugas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT t.id) as total FROM tugas t
    JOIN kelas k ON t.kelas_id = k.id
    JOIN mahasiswa_kelas mk ON mk.kelas_id = k.id
    WHERE mk.mahasiswa_id = $mhs_id"))['total'];

$sudah_submit = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM submissions WHERE mahasiswa_id=$mhs_id AND semester=$semester_aktif"))['total'];

$sudah_dinilai = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM submissions WHERE mahasiswa_id=$mhs_id AND semester=$semester_aktif AND nilai IS NOT NULL"))['total'];

$rata_nilai = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT AVG(nilai) as rata FROM submissions WHERE mahasiswa_id=$mhs_id AND nilai IS NOT NULL AND semester=$semester_aktif"))['rata'];

$tugas_aktif = mysqli_query($conn, "
    SELECT t.*, k.nama_kelas, u.nama AS nama_dosen
    FROM tugas t JOIN kelas k ON t.kelas_id=k.id JOIN users u ON k.dosen_id=u.id
    JOIN mahasiswa_kelas mk ON mk.kelas_id=k.id
    WHERE mk.mahasiswa_id=$mhs_id AND t.deadline>NOW()
      AND t.id NOT IN (SELECT tugas_id FROM submissions WHERE mahasiswa_id=$mhs_id)
    ORDER BY t.deadline ASC LIMIT 5");

// Nilai terbaru semester aktif
$nilai_terbaru = mysqli_query($conn, "
    SELECT s.nilai, s.submitted_at, t.judul FROM submissions s
    JOIN tugas t ON s.tugas_id=t.id
    WHERE s.mahasiswa_id=$mhs_id AND s.semester=$semester_aktif AND s.nilai IS NOT NULL
    ORDER BY s.submitted_at DESC LIMIT 5");

// Rata nilai per semester untuk rekap
$nilai_per_semester = mysqli_query($conn, "
    SELECT semester, AVG(nilai) as rata, COUNT(*) as total
    FROM submissions
    WHERE mahasiswa_id=$mhs_id AND nilai IS NOT NULL
    GROUP BY semester ORDER BY semester ASC");

$info = mysqli_query($conn, "
    SELECT judul,isi,created_at,poster,warna_bg,tipe FROM informasi
    ORDER BY created_at DESC LIMIT 5");

// Cek ada pengajuan naik semester yang pending
$cek_pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM pengajuan_semester WHERE mahasiswa_id=$mhs_id AND status='menunggu' LIMIT 1"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Mahasiswa</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;}
        .topbar{background:white;padding:16px 30px;border-bottom:1px solid #e0e0e0;display:flex;justify-content:space-between;align-items:center;}
        .topbar h1{font-size:20px;color:#1e3a5f;}
        .topbar span{font-size:13px;color:#888;}
        .page-body{padding:28px;}

        /* Stats */
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:26px;}
        .stat-card{background:white;padding:22px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);display:flex;align-items:center;gap:14px;}
        .stat-icon{width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;}
        .stat-card h3{font-size:26px;color:#1e3a5f;}
        .stat-card p{font-size:12px;color:#888;margin-top:2px;}
        .nilai-bar{background:#f0f0f0;border-radius:10px;height:8px;margin-top:6px;overflow:hidden;}
        .nilai-fill{height:100%;border-radius:10px;background:linear-gradient(90deg,#27ae60,#2ecc71);}

        /* Shortcut — 4 kolom */
        .shortcut-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:26px;}
        .shortcut-card{background:white;border-radius:12px;padding:18px;text-align:center;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(0,0,0,0.06);transition:transform 0.2s,box-shadow 0.2s;border-top:3px solid transparent;position:relative;}
        .shortcut-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,0.1);}
        .shortcut-card .icon{font-size:26px;margin-bottom:6px;}
        .shortcut-card p{font-size:13px;font-weight:600;color:#333;}
        .shortcut-card .pending-dot{position:absolute;top:10px;right:12px;width:8px;height:8px;border-radius:50%;background:#e65100;}

        /* Row 2 */
        .row-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
        .card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);overflow:hidden;}
        .card-header{padding:14px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;}
        .card-header h3{font-size:14px;color:#1e3a5f;}
        .card-header a{font-size:12px;color:#145a32;text-decoration:none;font-weight:600;}
        .card-body{padding:16px 20px;}

        .tugas-item{padding:12px 0;border-bottom:1px solid #f5f5f5;}
        .tugas-item:last-child{border-bottom:none;}
        .tugas-item h4{font-size:13px;color:#1e3a5f;font-weight:600;}
        .tugas-item p{font-size:12px;color:#888;margin-top:3px;}
        .countdown{font-size:11px;font-weight:700;margin-top:5px;}
        .countdown.urgent{color:#e74c3c;}
        .countdown.normal{color:#f39c12;}
        .countdown.ok{color:#27ae60;}

        /* Rekap semester */
        .sem-summary-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;}
        .sem-summary-card{border-radius:10px;padding:16px;border-left:4px solid #145a32;background:white;box-shadow:0 1px 4px rgba(0,0,0,0.06);text-align:center;}
        .sem-summary-card.aktif{border-left-color:#f5c518;background:#fffdf0;}
        .sem-summary-card .sem-num{font-size:11px;color:#888;margin-bottom:4px;}
        .sem-summary-card .sem-rata{font-size:28px;font-weight:800;}
        .sem-summary-card .sem-total{font-size:11px;color:#aaa;margin-top:4px;}

        /* Info */
        .info-item{border-bottom:1px solid #f5f5f5;padding:14px 0;}
        .info-item:first-child{padding-top:0;}
        .info-item:last-child{border-bottom:none;padding-bottom:0;}
        .info-item h4{font-size:13px;color:#333;font-weight:600;}
        .info-item .isi{font-size:13px;color:#555;margin-top:5px;line-height:1.6;}
        .info-item .tgl{font-size:11px;color:#aaa;margin-top:5px;}
        .poster-img-wrap{position:relative;border-radius:10px;overflow:hidden;margin-bottom:12px;}
        .poster-img-wrap img{width:100%;max-height:220px;object-fit:cover;display:block;}
        .poster-img-overlay{position:absolute;bottom:0;left:0;right:0;padding:16px;background:linear-gradient(transparent,rgba(0,0,0,0.75));color:white;}
        .poster-img-overlay h4{font-size:15px;font-weight:700;}
        .poster-img-overlay p{font-size:12px;opacity:0.85;margin-top:3px;}
        .poster-gen{border-radius:10px;padding:28px 20px;text-align:center;color:white;margin-bottom:12px;}
        .poster-gen .pg-icon{font-size:36px;margin-bottom:10px;}
        .poster-gen h4{font-size:17px;font-weight:800;margin-bottom:8px;line-height:1.3;}
        .poster-gen p{font-size:13px;opacity:0.88;line-height:1.6;}
        .poster-gen .pg-tgl{margin-top:12px;font-size:11px;opacity:0.6;border-top:1px solid rgba(255,255,255,0.2);padding-top:10px;}

        .semester-badge{display:inline-block;background:#145a32;color:white;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;margin-left:8px;vertical-align:middle;}
    </style>
</head>
<body>
<?php require_once '../../config/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <h1>Halo, <?= htmlspecialchars($_SESSION['user_nama']) ?>! 👋
            <span class="semester-badge">Semester <?= $semester_aktif ?></span>
        </h1>
        <span>📅 <?= date('d F Y') ?></span>
    </div>

    <div class="page-body">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f0fe">📋</div>
                <div><h3><?= $total_tugas ?></h3><p>Total Tugas</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f5e9">📤</div>
                <div><h3><?= $sudah_submit ?></h3><p>Dikumpul Sem <?= $semester_aktif ?></p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e1">⭐</div>
                <div><h3><?= $sudah_dinilai ?></h3><p>Dinilai Sem <?= $semester_aktif ?></p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fce8ff">🎯</div>
                <div>
                    <h3><?= $rata_nilai ? number_format($rata_nilai,1) : '-' ?></h3>
                    <p>Rata-rata Sem <?= $semester_aktif ?></p>
                    <?php if($rata_nilai): ?>
                    <div class="nilai-bar"><div class="nilai-fill" style="width:<?= min($rata_nilai,100) ?>%"></div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Shortcut 4 kolom termasuk Naik Semester -->
        <div class="shortcut-grid">
            <a href="tugas.php" class="shortcut-card" style="border-color:#145a32">
                <div class="icon">📋</div><p>Lihat Tugas</p>
            </a>
            <a href="submit.php" class="shortcut-card" style="border-color:#1a5276">
                <div class="icon">📤</div><p>Submit Tugas</p>
            </a>
            <a href="profil.php" class="shortcut-card" style="border-color:#784212">
                <div class="icon">👤</div><p>Profil Saya</p>
            </a>
            <a href="pengajuan_semester.php" class="shortcut-card" style="border-color:#6a1b9a">
                <?php if($cek_pending): ?>
                <span class="pending-dot" title="Ada pengajuan menunggu"></span>
                <?php endif; ?>
                <div class="icon">🎓</div>
                <p>Naik Semester</p>
                <?php if($cek_pending): ?>
                <small style="color:#e65100;font-size:11px;display:block;margin-top:4px">⏳ Menunggu</small>
                <?php else: ?>
                <small style="color:#888;font-size:11px;display:block;margin-top:4px">Sem <?= $semester_aktif ?> → <?= $semester_aktif+1 ?></small>
                <?php endif; ?>
            </a>
        </div>

        <!-- Rekap nilai semua semester -->
        <?php
        $sem_data = [];
        while($sd = mysqli_fetch_assoc($nilai_per_semester)) $sem_data[] = $sd;
        if(count($sem_data) > 0):
        ?>
        <div class="card" style="margin-bottom:20px">
            <div class="card-header">
                <h3>📊 Rekap Nilai Semua Semester</h3>
                <a href="submit.php">Lihat riwayat →</a>
            </div>
            <div class="card-body">
                <div class="sem-summary-grid">
                    <?php foreach($sem_data as $sd):
                        $warna = $sd['rata']>=80?'#27ae60':($sd['rata']>=60?'#f39c12':'#e74c3c');
                        $is_aktif = ($sd['semester'] == $semester_aktif);
                    ?>
                    <div class="sem-summary-card <?= $is_aktif?'aktif':'' ?>">
                        <div class="sem-num">Semester <?= $sd['semester'] ?><?= $is_aktif?' ⭐':'' ?></div>
                        <div class="sem-rata" style="color:<?= $warna ?>"><?= number_format($sd['rata'],1) ?></div>
                        <div class="sem-total"><?= $sd['total'] ?> tugas dinilai</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tugas aktif & nilai terbaru -->
        <div class="row-2">
            <div class="card">
                <div class="card-header"><h3>⏰ Tugas Aktif (Belum Dikumpul)</h3><a href="tugas.php">Lihat semua →</a></div>
                <div class="card-body">
                <?php $ada=false; while($t=mysqli_fetch_assoc($tugas_aktif)): $ada=true;
                    $sisa=strtotime($t['deadline'])-time();
                    $hari_sisa=floor($sisa/86400);
                    $kelas_c=$hari_sisa<=1?'urgent':($hari_sisa<=3?'normal':'ok'); ?>
                    <div class="tugas-item">
                        <h4><?= htmlspecialchars($t['judul']) ?></h4>
                        <p>📚 <?= htmlspecialchars($t['nama_kelas']) ?> &nbsp;|&nbsp; 👨‍🏫 <?= htmlspecialchars($t['nama_dosen']) ?></p>
                        <div class="countdown <?= $kelas_c ?>">⏰ Deadline: <?= date('d M Y H:i',strtotime($t['deadline'])) ?>
                            <?php if($hari_sisa<=0): ?> — <strong>Hari ini!</strong>
                            <?php elseif($hari_sisa==1): ?> — <strong>Besok!</strong>
                            <?php else: ?> — <?= $hari_sisa ?> hari lagi<?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php if(!$ada): ?><p style="text-align:center;color:#aaa;padding:20px 0">Semua tugas sudah dikumpulkan ✅</p><?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>⭐ Nilai Terbaru — Sem <?= $semester_aktif ?></h3>
                    <a href="submit.php">Lihat semua →</a>
                </div>
                <div class="card-body">
                <?php $ada_nilai=false; while($n=mysqli_fetch_assoc($nilai_terbaru)): $ada_nilai=true;
                    $wn=$n['nilai']>=80?'#27ae60':($n['nilai']>=60?'#f39c12':'#e74c3c'); ?>
                    <div class="tugas-item">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <div><h4><?= htmlspecialchars($n['judul']) ?></h4><p><?= date('d M Y',strtotime($n['submitted_at'])) ?></p></div>
                            <div style="font-size:22px;font-weight:700;color:<?= $wn ?>"><?= $n['nilai'] ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php if(!$ada_nilai): ?><p style="text-align:center;color:#aaa;padding:20px 0">Belum ada nilai semester ini</p><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Informasi & Pengumuman -->
        <div class="card">
            <div class="card-header"><h3>📢 Informasi & Pengumuman</h3></div>
            <div class="card-body">
            <?php $ada_info=false; while($i=mysqli_fetch_assoc($info)): $ada_info=true;
                $tipe=$i['tipe']??'teks'; $warna=$i['warna_bg']??'#1a3a5c'; $poster=$i['poster']??''; ?>
            <div class="info-item">
                <?php if($tipe==='poster'):
                    $poster_path=BASE_PATH.'/uploads/poster/'.$poster;
                    if($poster && file_exists($poster_path)): ?>
                        <div class="poster-img-wrap">
                            <img src="<?= BASE_URL ?>/uploads/poster/<?= htmlspecialchars($poster) ?>" alt="Poster">
                            <div class="poster-img-overlay">
                                <h4><?= htmlspecialchars($i['judul']) ?></h4>
                                <?php if($i['isi']): ?><p><?= htmlspecialchars(substr($i['isi'],0,100)) ?><?= strlen($i['isi'])>100?'...':'' ?></p><?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="poster-gen" style="background:<?= htmlspecialchars($warna) ?>">
                            <div class="pg-icon">📢</div>
                            <h4><?= htmlspecialchars($i['judul']) ?></h4>
                            <?php if($i['isi']): ?><p><?= nl2br(htmlspecialchars($i['isi'])) ?></p><?php endif; ?>
                            <div class="pg-tgl">📅 <?= date('d F Y',strtotime($i['created_at'])) ?></div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <h4>📣 <?= htmlspecialchars($i['judul']) ?></h4>
                    <p class="isi"><?= nl2br(htmlspecialchars(substr($i['isi'],0,200))) ?><?= strlen($i['isi'])>200?'...':'' ?></p>
                <?php endif; ?>
                <p class="tgl">📅 <?= date('d M Y',strtotime($i['created_at'])) ?></p>
            </div>
            <?php endwhile; ?>
            <?php if(!$ada_info): ?><p style="text-align:center;color:#aaa;padding:16px 0">Belum ada pengumuman</p><?php endif; ?>
            </div>
        </div>

    </div>
</div>
</body>
</html>