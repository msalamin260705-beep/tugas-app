<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('mahasiswa');

$mhs_id = $_SESSION['user_id'];

$filter_kelas = isset($_GET['kelas_id']) ? intval($_GET['kelas_id']) : 0;
$filter_tipe  = isset($_GET['tipe']) ? $_GET['tipe'] : 'semua'; // semua | reguler | mengulang

// Ambil data mahasiswa untuk tahu semester aktif
$mhs      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT semester FROM users WHERE id=$mhs_id"));
$semester = $mhs['semester'];

// Kelas reguler + kelas mengulang mahasiswa ini
$kelas_saya = mysqli_query($conn, "
    SELECT k.*, u.nama AS nama_dosen,
        CASE WHEN k.semester = $semester THEN 'reguler' ELSE 'mengulang' END AS tipe
    FROM kelas k
    JOIN mahasiswa_kelas mk ON mk.kelas_id = k.id
    JOIN users u ON k.dosen_id = u.id
    WHERE mk.mahasiswa_id = $mhs_id
    ORDER BY tipe, k.nama_kelas
");

$where_kelas = $filter_kelas ? "AND k.id = $filter_kelas" : "";
$where_tipe  = "";
if ($filter_tipe === 'reguler')    $where_tipe = "AND k.semester = $semester";
if ($filter_tipe === 'mengulang')  $where_tipe = "AND k.semester != $semester";

$tugas_list = mysqli_query($conn, "
    SELECT t.*, k.nama_kelas, k.semester AS sem_kelas, u.nama AS nama_dosen,
        s.id AS submission_id, s.nilai, s.submitted_at,
        CASE WHEN k.semester = $semester THEN 'reguler' ELSE 'mengulang' END AS tipe
    FROM tugas t
    JOIN kelas k ON t.kelas_id = k.id
    JOIN users u ON k.dosen_id = u.id
    JOIN mahasiswa_kelas mk ON mk.kelas_id = k.id
    LEFT JOIN submissions s ON s.tugas_id = t.id AND s.mahasiswa_id = $mhs_id
    WHERE mk.mahasiswa_id = $mhs_id $where_kelas $where_tipe
    ORDER BY t.deadline ASC
");

$rows = [];
$ada_mengulang = false;
while ($t = mysqli_fetch_assoc($tugas_list)) {
    $rows[] = $t;
    if ($t['tipe'] === 'mengulang') $ada_mengulang = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Tugas</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; }

        /* Notif mengulang */
        .notif-mengulang { background:#fff8e1; border:1px solid #ffe082; border-radius:10px; padding:14px 18px; margin-bottom:20px; font-size:13px; color:#e65100; display:flex; gap:10px; align-items:center; }

        .filter-bar { background:white; padding:14px 20px; border-radius:12px; margin-bottom:20px; display:flex; gap:12px; align-items:center; box-shadow:0 2px 8px rgba(0,0,0,0.06); flex-wrap:wrap; }
        .filter-bar label { font-size:13px; font-weight:600; color:#555; }
        .filter-bar select { padding:8px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:13px; outline:none; }
        .filter-bar select:focus { border-color:#145a32; }
        .filter-bar button { padding:8px 18px; background:#145a32; color:white; border:none; border-radius:8px; font-size:13px; cursor:pointer; }

        /* Tab tipe */
        .tipe-tabs { display:flex; gap:8px; margin-bottom:20px; }
        .tipe-tab { padding:8px 18px; border-radius:20px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; border:2px solid #e0e0e0; color:#888; background:white; }
        .tipe-tab.aktif { background:#145a32; color:white; border-color:#145a32; }
        .tipe-tab.ulang.aktif { background:#c0392b; border-color:#c0392b; }

        .tugas-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:18px; }
        .tugas-card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; transition:transform 0.2s; }
        .tugas-card:hover { transform:translateY(-2px); }
        .tugas-card.mengulang { border-top:3px solid #c0392b; }
        .tugas-card-top { padding:18px 20px; border-bottom:1px solid #f0f0f0; }
        .tugas-card-top h4 { font-size:15px; color:#1e3a5f; margin-bottom:6px; }
        .tugas-card-top p  { font-size:12px; color:#888; line-height:1.5; }
        .tugas-card-bottom { padding:14px 20px; display:flex; justify-content:space-between; align-items:center; }
        .badge { padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-submit  { background:#e8f5e9; color:#2e7d32; }
        .badge-pending { background:#fff8e1; color:#e65100; }
        .badge-lewat   { background:#ffebee; color:#c62828; }
        .badge-dinilai { background:#e8f0fe; color:#1a5276; }
        .badge-ulang   { background:#ffebee; color:#c0392b; font-size:10px; }
        .btn-submit { padding:7px 16px; background:#145a32; color:white; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none; }
        .btn-submit:hover { background:#0e3d22; }
        .deadline-text { font-size:12px; color:#888; }
        .deadline-text.urgent { color:#e74c3c; font-weight:600; }
        .empty-state { text-align:center; padding:60px 20px; color:#aaa; }
        .empty-state .icon { font-size:48px; margin-bottom:12px; }

        .group-label { font-size:13px; font-weight:700; color:#555; margin:20px 0 10px; padding-left:4px; }
        .group-label.mengulang { color:#c0392b; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>📋 Daftar Tugas Saya</h1></div>

    <div class="page-body">

        <!-- Notif jika ada tugas mengulang -->
        <?php if ($ada_mengulang): ?>
        <div class="notif-mengulang">
            🔄 <strong>Kamu memiliki tugas dari mata kuliah yang sedang diulang.</strong>
            &nbsp;Pastikan semua tugas mengulang juga dikumpulkan tepat waktu.
        </div>
        <?php endif; ?>

        <!-- Filter -->
        <form method="GET" class="filter-bar">
            <label>Filter Kelas:</label>
            <select name="kelas_id">
                <option value="0">Semua Kelas</option>
                <?php
                $kelas_saya = mysqli_query($conn, "
                    SELECT k.*, CASE WHEN k.semester=$semester THEN 'reguler' ELSE 'mengulang' END AS tipe
                    FROM kelas k JOIN mahasiswa_kelas mk ON mk.kelas_id=k.id
                    WHERE mk.mahasiswa_id=$mhs_id ORDER BY tipe, k.nama_kelas
                ");
                $prev_tipe = '';
                while ($k = mysqli_fetch_assoc($kelas_saya)):
                    if ($k['tipe'] !== $prev_tipe) {
                        if ($prev_tipe) echo '</optgroup>';
                        echo '<optgroup label="' . ($k['tipe']==='mengulang' ? '🔄 Mengulang' : '📚 Reguler') . '">';
                        $prev_tipe = $k['tipe'];
                    }
                ?>
                <option value="<?= $k['id'] ?>" <?= $filter_kelas==$k['id']?'selected':'' ?>>
                    <?= htmlspecialchars($k['nama_kelas']) ?>
                </option>
                <?php endwhile; if ($prev_tipe) echo '</optgroup>'; ?>
            </select>
            <input type="hidden" name="tipe" value="<?= htmlspecialchars($filter_tipe) ?>">
            <button type="submit">Filter</button>
        </form>

        <!-- Tab tipe -->
        <div class="tipe-tabs">
            <a href="?kelas_id=<?=$filter_kelas?>&tipe=semua"
               class="tipe-tab <?= $filter_tipe==='semua'?'aktif':'' ?>">Semua</a>
            <a href="?kelas_id=<?=$filter_kelas?>&tipe=reguler"
               class="tipe-tab <?= $filter_tipe==='reguler'?'aktif':'' ?>">📚 Reguler</a>
            <?php if ($ada_mengulang || $filter_tipe==='mengulang'): ?>
            <a href="?kelas_id=<?=$filter_kelas?>&tipe=mengulang"
               class="tipe-tab ulang <?= $filter_tipe==='mengulang'?'aktif':'' ?>">🔄 Mengulang</a>
            <?php endif; ?>
        </div>

        <?php if (empty($rows)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>Belum ada tugas atau kamu belum terdaftar di kelas manapun.</p>
        </div>
        <?php else: ?>

        <?php
        // Pisahkan reguler dan mengulang untuk ditampilkan terpisah
        $rows_reguler   = array_filter($rows, fn($r) => $r['tipe']==='reguler');
        $rows_mengulang = array_filter($rows, fn($r) => $r['tipe']==='mengulang');

        $render = function($list, $label, $class='') {
            if (empty($list)) return;
            echo "<div class='group-label $class'>$label</div>";
            echo "<div class='tugas-grid'>";
            foreach ($list as $t):
                $lewat     = strtotime($t['deadline']) < time();
                $sisa      = strtotime($t['deadline']) - time();
                $hari_sisa = max(0, floor($sisa / 86400));
                $is_ulang  = $t['tipe'] === 'mengulang';
            ?>
            <div class="tugas-card <?= $is_ulang?'mengulang':'' ?>">
                <div class="tugas-card-top">
                    <h4>
                        <?= htmlspecialchars($t['judul']) ?>
                        <?php if ($is_ulang): ?><span class="badge badge-ulang">🔄 Mengulang</span><?php endif; ?>
                    </h4>
                    <p>📚 <?= htmlspecialchars($t['nama_kelas']) ?>
                        <?php if ($is_ulang): ?><span style="color:#c0392b"> (Sem <?= $t['sem_kelas'] ?>)</span><?php endif; ?>
                    </p>
                    <p>👨‍🏫 <?= htmlspecialchars($t['nama_dosen']) ?></p>
                    <?php if ($t['deskripsi']): ?>
                    <p style="margin-top:8px;color:#555"><?= htmlspecialchars(substr($t['deskripsi'],0,100)) ?>...</p>
                    <?php endif; ?>
                </div>
                <div class="tugas-card-bottom">
                    <div>
                        <?php if ($t['submission_id']): ?>
                            <?php if ($t['nilai'] !== null): ?>
                                <span class="badge badge-dinilai">✅ Nilai: <?= $t['nilai'] ?></span>
                            <?php else: ?>
                                <span class="badge badge-submit">📤 Sudah dikumpul</span>
                            <?php endif; ?>
                        <?php elseif ($lewat): ?>
                            <span class="badge badge-lewat">⛔ Terlambat</span>
                        <?php else: ?>
                            <span class="badge badge-pending">⏳ Belum dikumpul</span>
                        <?php endif; ?>
                        <div class="deadline-text <?= ($hari_sisa<=1&&!$lewat)?'urgent':'' ?>" style="margin-top:5px">
                            ⏰ <?= date('d M Y H:i', strtotime($t['deadline'])) ?>
                            <?php if (!$lewat && !$t['submission_id']): ?>(<?= $hari_sisa ?> hari lagi)<?php endif; ?>
                        </div>
                    </div>
                    <?php if (!$t['submission_id'] && !$lewat): ?>
                    <a href="submit.php?tugas_id=<?= $t['id'] ?>" class="btn-submit">Kumpulkan →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            endforeach;
            echo "</div>";
        };

        if ($filter_tipe === 'semua') {
            $render($rows_reguler,   '📚 Tugas Reguler');
            $render($rows_mengulang, '🔄 Tugas Mengulang', 'mengulang');
        } else {
            $render($rows, $filter_tipe==='mengulang' ? '🔄 Tugas Mengulang' : '📚 Tugas Reguler',
                    $filter_tipe==='mengulang' ? 'mengulang' : '');
        }
        ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>