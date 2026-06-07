<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$role = $_SESSION['user_role'];
$nama = $_SESSION['user_nama'];
$foto = $_SESSION['user_foto'] ?? '';

// ✅ FIX: deteksi BASE_URL otomatis
$base = (isset($_SERVER['HTTP_HOST']) &&
        ($_SERVER['HTTP_HOST'] === 'localhost' || str_ends_with($_SERVER['HTTP_HOST'], '.test')))
        ? '/tugas-app' : '';

$menu_admin = [
    ['icon'=>'🏠', 'label'=>'Dashboard',         'url'=>"$base/pages/admin/dashboard.php"],
    ['icon'=>'🏛️', 'label'=>'Profil Prodi',      'url'=>"$base/pages/admin/profil.php"],
    ['icon'=>'👨‍🏫', 'label'=>'Kelola Dosen',     'url'=>"$base/pages/admin/kelola_dosen.php"],
    ['icon'=>'📚', 'label'=>'Kelola Kelas',       'url'=>"$base/pages/admin/kelola_kelas.php"],
    ['icon'=>'🗓️', 'label'=>'Jadwal',             'url'=>"$base/pages/admin/jadwal.php"],
    ['icon'=>'📢', 'label'=>'Informasi',          'url'=>"$base/pages/admin/informasi.php"],
    ['icon'=>'🎓', 'label'=>'Pengajuan Semester', 'url'=>"$base/pages/admin/pengajuan_semester.php"],
];

$menu_dosen = [
    ['icon'=>'🏠', 'label'=>'Dashboard',      'url'=>"$base/pages/dosen/dashboard.php"],
    ['icon'=>'👤', 'label'=>'Profil',         'url'=>"$base/pages/dosen/profil.php"],
    ['icon'=>'📚', 'label'=>'Kelas Saya',     'url'=>"$base/pages/dosen/kelas.php"],
    ['icon'=>'📝', 'label'=>'Kelola Tugas',   'url'=>"$base/pages/dosen/tugas.php"],
    ['icon'=>'📬', 'label'=>'Submission',     'url'=>"$base/pages/dosen/submission.php"],
    ['icon'=>'🔄', 'label'=>'Mhs Mengulang',  'url'=>"$base/pages/dosen/mahasiswa_mengulang.php"],
];

$menu_mahasiswa = [
    ['icon'=>'🏠', 'label'=>'Dashboard',         'url'=>"$base/pages/mahasiswa/dashboard.php"],
    ['icon'=>'👤', 'label'=>'Profil',            'url'=>"$base/pages/mahasiswa/profil.php"],
    ['icon'=>'🗓️', 'label'=>'Jadwal',            'url'=>"$base/pages/mahasiswa/jadwal.php"],
    ['icon'=>'📋', 'label'=>'Tugas',             'url'=>"$base/pages/mahasiswa/tugas.php"],
    ['icon'=>'📤', 'label'=>'Submit Tugas',      'url'=>"$base/pages/mahasiswa/submit.php"],
    ['icon'=>'🎓', 'label'=>'Naik Semester',     'url'=>"$base/pages/mahasiswa/pengajuan_semester.php"],
];

$menus = $role=='admin' ? $menu_admin : ($role=='dosen' ? $menu_dosen : $menu_mahasiswa);
$warna = $role=='admin' ? '#4a1a6b'  : ($role=='dosen' ? '#1a3a5c'   : '#145a32');
?>
<style>
    .sidebar {
        width:220px; min-height:100vh;
        background:<?= $warna ?>;
        position:fixed; top:0; left:0;
        display:flex; flex-direction:column;
        z-index:100;
    }
    .sidebar-user {
        padding:20px 16px;
        border-bottom:1px solid rgba(255,255,255,0.1);
        text-align:center;
    }
    .sidebar-user img, .s-avatar {
        width:56px; height:56px; border-radius:50%;
        object-fit:cover; border:2px solid rgba(255,255,255,0.4);
        margin-bottom:8px;
    }
    .s-avatar {
        background:rgba(255,255,255,0.15);
        display:flex; align-items:center; justify-content:center;
        font-size:24px; margin:0 auto 8px;
    }
    .sidebar-user h4 { color:white; font-size:13px; font-weight:600; }
    .sidebar-user span {
        color:rgba(255,255,255,0.5); font-size:10px;
        text-transform:uppercase; letter-spacing:1px;
    }
    .sidebar nav { flex:1; padding:8px 0; overflow-y:auto; }
    .sidebar nav a {
        display:flex; align-items:center; gap:10px;
        padding:10px 18px; color:rgba(255,255,255,0.7);
        text-decoration:none; font-size:13px; transition:0.15s;
    }
    .sidebar nav a:hover { background:rgba(255,255,255,0.1); color:white; padding-left:22px; }
    .sidebar nav a.active {
        background:rgba(255,255,255,0.18); color:white;
        border-right:3px solid rgba(255,255,255,0.8);
        font-weight:600;
    }
    .notif-badge {
        margin-left:auto;
        background:#e74c3c;
        color:white;
        font-size:10px;
        font-weight:700;
        padding:2px 7px;
        border-radius:20px;
        min-width:20px;
        text-align:center;
    }
    .sidebar-footer {
        padding:12px 0;
        border-top:1px solid rgba(255,255,255,0.1);
    }
    .sidebar-footer a {
        display:flex; align-items:center; gap:10px;
        padding:10px 18px; color:rgba(255,120,120,0.8);
        text-decoration:none; font-size:13px; transition:0.15s;
    }
    .sidebar-footer a:hover { background:rgba(255,0,0,0.1); color:#ff8080; }
    .main-content { margin-left:220px; min-height:100vh; background:#f5f7fa; }
</style>

<div class="sidebar">
    <div class="sidebar-user">
        <?php
        // ✅ FIX: pakai BASE_PATH untuk cek file foto
        $foto_path = $_SERVER['DOCUMENT_ROOT'] . $base . '/uploads/foto_profil/' . $foto;
        if ($foto && file_exists($foto_path)):
        ?>
            <img src="<?= $base ?>/uploads/foto_profil/<?= $foto ?>" alt="Foto">
        <?php else: ?>
            <div class="s-avatar">
                <?= $role=='admin' ? '👑' : ($role=='dosen' ? '👨‍🏫' : '🎓') ?>
            </div>
        <?php endif; ?>
        <h4><?= htmlspecialchars($nama) ?></h4>
        <span><?= ucfirst($role) ?></span>
    </div>

    <nav>
        <?php
        // Badge notif pengajuan menunggu (admin)
        $jml_pengajuan_menunggu = 0;
        if ($role === 'admin' && isset($conn)) {
            $res = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT COUNT(*) as n FROM pengajuan_semester WHERE status='menunggu'"));
            $jml_pengajuan_menunggu = $res['n'] ?? 0;
        }

        foreach ($menus as $menu):
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
            $docroot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
            $current = str_replace($docroot, '', $script);
            $is_active = ($current === $menu['url']);
        ?>
        <a href="<?= $menu['url'] ?>" class="<?= $is_active ? 'active' : '' ?>">
            <?= $menu['icon'] ?> <?= $menu['label'] ?>
            <?php if ($role==='admin' && $menu['label']==='Pengajuan Semester' && $jml_pengajuan_menunggu > 0): ?>
            <span class="notif-badge"><?= $jml_pengajuan_menunggu ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <!-- ✅ FIX: logout URL juga pakai $base -->
        <a href="<?= $base ?>/logout.php">🚪 Logout</a>
    </div>
</div>